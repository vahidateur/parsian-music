import { expect, test } from 'playwright/test';

/**
 * Admin calendar browser integration coverage.
 *
 * The session feed is stubbed so day navigation, filtering, loading, error,
 * empty, drawer, and responsive behaviour are deterministic without depending
 * on database fixtures. Filter option values are read from the real page.
 *
 * Validates: Requirements 1.1–1.11, 3.4–3.8, 6.1–6.10, 7.2–7.6, 11.1–11.5
 */

const baseUrl = process.env.CALENDAR_BROWSER_BASE_URL ?? process.env.TEST_BASE_URL ?? 'http://127.0.0.1:8000';
const adminPhone = process.env.TEST_ADMIN_PHONE;
const adminPassword = process.env.TEST_ADMIN_PASSWORD;

const calendarUrl = `${baseUrl}/admin/calendar`;
const loginUrl = `${baseUrl}/login`;
const eventsPathname = '/admin/calendar/events';
const eventsPattern = '**/admin/calendar/events*';

const filterKeys = ['teacher_id', 'student_id', 'room', 'instrument_id'];
const card = '.calendar-timegrid-event-content';
const drawerPanel = '[data-calendar-drawer-panel]';

const sessions = [
    { id: 9001, student: 'Session Alpha Student', teacher: 'Session Alpha Teacher', instrument: 'ساز آلفا', room: 'CAL-A', status: 'scheduled', time: '09:00', duration: 30, notes: null },
    { id: 9002, student: 'Session Beta Student', teacher: 'Session Beta Teacher', instrument: 'ساز بتا', room: 'CAL-B', status: 'completed', time: '10:00', duration: 45, notes: 'یادداشت جلسه بتا' },
    { id: 9003, student: 'Session Gamma Student', teacher: 'Session Gamma Teacher', instrument: 'ساز گاما', room: 'CAL-C', status: 'cancelled', time: '11:30', duration: 60, notes: null },
    { id: 9004, student: 'Session Delta Student', teacher: 'Session Delta Teacher', instrument: 'ساز دلتا', room: 'CAL-D', status: 'missed', time: '13:00', duration: 30, notes: null },
];

const pause = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

const addMinutes = (time, minutes) => {
    const [hours, mins] = time.split(':').map(Number);
    const total = hours * 60 + mins + minutes;
    return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
};

const shiftIsoDate = (isoDate, days) => {
    const date = new Date(`${isoDate}T12:00:00`);
    date.setDate(date.getDate() + days);
    return date.toISOString().slice(0, 10);
};

const toEvent = (session, isoDate) => ({
    id: session.id,
    title: `${session.student} — ${session.instrument}`,
    start: `${isoDate}T${session.time}:00`,
    end: `${isoDate}T${addMinutes(session.time, session.duration)}:00`,
    status: session.status,
    studentName: session.student,
    teacherName: session.teacher,
    instrumentName: session.instrument,
    room: session.room,
    extendedProps: {
        enrollment_id: session.id + 100,
        session_fee: 5000000,
        duration_minutes: session.duration,
        notes: session.notes,
        session_date: isoDate,
    },
});

async function stubEventFeed(page) {
    const feed = { requests: [], mode: 'ok', delayMs: 0 };

    await page.route(eventsPattern, async (route) => {
        const url = new URL(route.request().url());
        feed.requests.push(url);

        if (feed.delayMs > 0) {
            await pause(feed.delayMs);
        }

        if (feed.mode === 'error') {
            await route.fulfill({ status: 500, contentType: 'application/json', body: JSON.stringify({ message: 'server error' }) });
            return;
        }

        const isoDate = url.searchParams.get('start') ?? new Date().toISOString().slice(0, 10);
        const filtered = feed.mode === 'empty'
            ? []
            : (filterKeys.some((key) => url.searchParams.has(key)) ? sessions.slice(0, 1) : sessions);

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(filtered.map((session) => toEvent(session, isoDate))),
        });
    });

    return feed;
}

async function signIn(page) {
    await page.goto(loginUrl, { waitUntil: 'domcontentloaded' });
    await page.locator('#phone').fill(adminPhone);
    await page.locator('#password').fill(adminPassword);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.endsWith('/login')),
        page.locator('button[form="login-form"]').click(),
    ]);
}

async function openCalendar(page, { settle = true } = {}) {
    const feed = await stubEventFeed(page);
    await signIn(page);
    await page.goto(calendarUrl, { waitUntil: 'domcontentloaded' });

    if (settle) {
        await settleCalendar(page);
    }

    return feed;
}

async function settleCalendar(page) {
    await expect(page.locator('[data-calendar-root]')).toBeVisible();
    await expect(page.locator('#calendar-mount.fc')).toBeVisible({ timeout: 20000 });
    await expect(page.locator('[data-calendar-loading]')).toBeHidden({ timeout: 20000 });
    await expect(page.locator(card).first()).toBeVisible({ timeout: 20000 });
}

const selectedDate = (page) => page.locator('[data-calendar-root]').getAttribute('data-calendar-selected-date');

async function requestAfter(page, feed, action) {
    const before = feed.requests.length;
    await action();
    await expect.poll(() => feed.requests.length, { timeout: 10000 }).toBeGreaterThan(before);
    return feed.requests.slice(before);
}

test.describe('admin calendar integration', () => {
    test.skip(!adminPhone || !adminPassword, 'Set TEST_ADMIN_PHONE and TEST_ADMIN_PASSWORD (and optionally TEST_BASE_URL) to run the authenticated calendar coverage.');
    test.use({ viewport: { width: 1366, height: 900 } });

    test('renders the RTL day grid with localized toolbar controls and status-coded cards', async ({ page }) => {
        await openCalendar(page);

        const root = page.locator('[data-calendar-root]');
        await expect(root).toHaveAttribute('dir', 'rtl');
        await expect(page.locator('#calendar-mount .fc-timegrid')).toBeVisible();
        await expect(page.locator('#calendar-mount .fc-daygrid-body')).toHaveCount(0);
        await expect(page.locator('#calendar-mount .fc-timegrid-slot-lane')).toHaveCount(28);
        await expect(page.locator('.fc-prev-button')).toBeVisible();
        await expect(page.locator('.fc-next-button')).toBeVisible();
        await expect(page.locator('.fc-today-button')).toBeVisible();
        await expect(page.locator('[data-calendar-current-date]')).not.toHaveText('');
        await expect(page.locator('[data-calendar-week-day]')).toHaveCount(7);
        await expect(page.locator('[data-calendar-week-day][data-selected="true"]')).toHaveCount(1);
        await expect(page.locator('[data-calendar-week-day][aria-current="date"]')).toHaveCount(1);

        const alpha = page.locator(card).filter({ hasText: 'Session Alpha Student' }).first();
        await expect(alpha).toBeVisible();
        await expect(alpha.locator('.calendar-timegrid-event-content__primary')).toHaveText('Session Alpha Student');
        await expect(alpha.locator('.calendar-timegrid-event-content__secondary')).toHaveText('Session Alpha Teacher');
        await expect(alpha.locator('.calendar-timegrid-event-content__time')).toHaveText('09:00–09:30');
        await expect(alpha.locator('.calendar-timegrid-event-content__room')).toHaveText('CAL-A');
        await expect(alpha.locator('.calendar-timegrid-event-content__instrument')).toHaveText('ساز آلفا');
        await expect(alpha).toHaveAttribute('role', 'button');
        await expect(alpha).toHaveAttribute('aria-label', 'Session Alpha Student – scheduled');

        for (const status of ['scheduled', 'completed', 'cancelled', 'missed']) {
            await expect(page.locator(`${card}[data-status="${status}"]`)).toHaveCount(1);
        }

        expect(await page.locator('#calendar-mount.fc').evaluate((element) => getComputedStyle(element).direction)).toBe('rtl');
    });

    test('navigates previous, next, today, and rolls the sidebar week over', async ({ page }) => {
        const feed = await openCalendar(page);
        const today = await selectedDate(page);

        await requestAfter(page, feed, () => page.locator('[data-calendar-prev-day]').click());
        await expect(page.locator('[data-calendar-root]')).toHaveAttribute('data-calendar-selected-date', shiftIsoDate(today, -1));
        await expect(page.locator('[data-calendar-week-day][data-selected="true"]')).toHaveCount(1);

        await requestAfter(page, feed, () => page.locator('[data-calendar-next-day]').click());
        await expect(page.locator('[data-calendar-root]')).toHaveAttribute('data-calendar-selected-date', today);

        const weekStart = await page.locator('[data-calendar-week-day]').first().getAttribute('data-calendar-date');
        for (let index = 0; index < 7; index += 1) {
            await requestAfter(page, feed, () => page.locator('[data-calendar-next-day]').click());
        }
        await expect(page.locator('[data-calendar-root]')).toHaveAttribute('data-calendar-selected-date', shiftIsoDate(today, 7));
        await expect(page.locator('[data-calendar-week-day]').first()).not.toHaveAttribute('data-calendar-date', weekStart);

        await requestAfter(page, feed, () => page.locator('[data-calendar-today]').click());
        await expect(page.locator('[data-calendar-root]')).toHaveAttribute('data-calendar-selected-date', today);
        await expect(page.locator('[data-calendar-week-day]').first()).toHaveAttribute('data-calendar-date', weekStart);
    });

    test('updates the timeline within 300ms of a sidebar day selection', async ({ page }) => {
        const feed = await openCalendar(page);
        const today = await selectedDate(page);
        const target = page.locator('[data-calendar-week-day]').nth(3);
        const targetDate = await target.getAttribute('data-calendar-date');
        test.skip(targetDate === today, 'The fourth week day is already selected; pick a different sidebar target.');

        const startedAt = Date.now();
        await requestAfter(page, feed, () => target.click());
        expect(Date.now() - startedAt).toBeLessThan(300);

        await expect(page.locator('[data-calendar-root]')).toHaveAttribute('data-calendar-selected-date', targetDate);
        await expect(target).toHaveAttribute('data-selected', 'true');
        expect(feed.requests.at(-1).searchParams.get('start')).toBe(targetDate);
    });

    test('shows the loading skeleton, preserves prior sessions on failure, retries, and renders the empty state', async ({ page }) => {
        const feed = await stubEventFeed(page);
        feed.delayMs = 600;
        await signIn(page);
        await page.goto(calendarUrl, { waitUntil: 'domcontentloaded' });

        await expect(page.locator('[data-calendar-loading]')).toBeVisible();
        feed.delayMs = 0;
        await settleCalendar(page);
        await expect(page.locator('[data-calendar-loading]')).toBeHidden();
        await expect(page.locator('[data-calendar-empty]')).toBeHidden();

        feed.mode = 'error';
        await requestAfter(page, feed, () => page.locator('[data-calendar-next-day]').click());
        await expect(page.locator('[data-calendar-error]')).toBeVisible({ timeout: 15000 });
        await expect(page.locator('[data-calendar-error]')).toContainText('خطا در بارگذاری جلسات');
        await expect(page.locator(card).filter({ hasText: 'Session Alpha Student' }).first()).toBeVisible();
        await expect(page.locator('#calendar-page-title')).toBeVisible();
        await expect(page.locator('a[href$="/admin/dashboard"]').first()).toBeVisible();
        await expect(page.locator('[data-calendar-drawer]')).toBeVisible();
        await expect(page.locator('[data-calendar-retry]')).toBeVisible();

        feed.mode = 'ok';
        await page.locator('[data-calendar-retry]').click();
        await expect(page.locator('[data-calendar-error]')).toBeHidden({ timeout: 15000 });

        feed.mode = 'empty';
        await requestAfter(page, feed, () => page.locator('[data-calendar-next-day]').click());
        await expect(page.locator('[data-calendar-empty]')).toBeVisible();
        await expect(page.locator('[data-calendar-empty]')).toContainText('جلسه‌ای برای نمایش وجود ندارد');
        await expect(page.locator(card)).toHaveCount(0);
    });

    test('debounces each filter into one request, keeps selections across days, and clears all filters', async ({ page }) => {
        const feed = await openCalendar(page);

        const available = [];
        for (const key of filterKeys) {
            const value = await page.locator(`[data-calendar-filter="${key}"]`)
                .evaluate((select) => [...select.options].find((option) => option.value)?.value ?? '');
            if (value) {
                available.push([key, value]);
            }
        }
        test.skip(available.length === 0, 'The authenticated calendar needs at least one populated filter option.');

        for (const [key, value] of available) {
            const before = feed.requests.length;
            await page.locator(`[data-calendar-filter="${key}"]`).evaluate((select, nextValue) => {
                ['', nextValue, '', nextValue].forEach((candidate) => {
                    select.value = candidate;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }, value);

            await pause(200);
            expect(feed.requests).toHaveLength(before);
            await expect.poll(() => feed.requests.length, { timeout: 5000 }).toBe(before + 1);
            await pause(200);
            expect(feed.requests).toHaveLength(before + 1);
            expect(feed.requests.at(-1).searchParams.get(key)).toBe(value);

            await expect(page.locator('[data-calendar-filters-count]')).toBeVisible();
            await expect(page.locator('[data-calendar-filters-count]')).toHaveText('1');
            await expect(page.locator(card)).toHaveCount(1);
            await expect(page.locator(card).filter({ hasText: 'Session Alpha Student' })).toHaveCount(1);

            await requestAfter(page, feed, () => page.locator('[data-calendar-filters-clear]').click());
            await expect(page.locator('[data-calendar-filters-count]')).toBeHidden();
            await expect(page.locator(card)).toHaveCount(sessions.length);
        }

        const [firstKey, firstValue] = available[0];
        const control = page.locator(`[data-calendar-filter="${firstKey}"]`);
        await requestAfter(page, feed, () => control.selectOption(firstValue));
        await requestAfter(page, feed, () => page.locator('[data-calendar-next-day]').click());
        await expect(control).toHaveValue(firstValue);
        expect(feed.requests.at(-1).searchParams.get(firstKey)).toBe(firstValue);

        await requestAfter(page, feed, () => page.locator('[data-calendar-filters-clear]').click());
        expect(filterKeys.some((key) => feed.requests.at(-1).searchParams.has(key))).toBe(false);
        await expect(page.locator(card)).toHaveCount(sessions.length);
    });

    test('opens the localized drawer from a card and closes by Escape, overlay, and close button', async ({ page }) => {
        await openCalendar(page);

        const trigger = page.locator(card).filter({ hasText: 'Session Beta Student' }).first();
        const panel = page.locator(drawerPanel);

        await trigger.focus();
        await trigger.press('Enter');
        await expect(panel).toBeVisible();
        await expect(panel).toHaveAttribute('role', 'dialog');
        await expect(panel).toHaveAttribute('aria-modal', 'true');
        await expect(panel).toHaveAttribute('aria-labelledby', 'calendar-event-drawer-title');
        await expect(panel).toContainText('Session Beta Student');
        await expect(panel).toContainText('Session Beta Teacher');
        await expect(panel).toContainText('ساز بتا');
        await expect(panel).toContainText('CAL-B');
        await expect(panel).toContainText('10:00');
        await expect(panel).toContainText('45');
        await expect(panel).toContainText('یادداشت جلسه بتا');
        await expect(panel).toContainText('برگزارشده');
        await expect(page.locator('[data-calendar-drawer-close]')).toHaveAttribute('aria-label', 'بستن جزئیات جلسه');

        await page.keyboard.press('Escape');
        await expect(panel).toBeHidden();
        await expect(trigger).toBeFocused();

        await trigger.click();
        await expect(panel).toBeVisible();
        await page.locator('[data-calendar-drawer-overlay]').dispatchEvent('click');
        await expect(panel).toBeHidden();
        await expect(trigger).toBeFocused();

        await trigger.click();
        await expect(panel).toBeVisible();
        await page.locator('[data-calendar-drawer-close]').click();
        await expect(panel).toBeHidden();
        await expect(trigger).toBeFocused();

        const withoutNotes = page.locator(card).filter({ hasText: 'Session Alpha Student' }).first();
        await withoutNotes.click();
        await expect(panel).toContainText('بدون یادداشت');
    });

    test('preserves the admin shell and calendar fallback when JavaScript is disabled', async ({ page }) => {
        await signIn(page);
        const cookies = await page.context().cookies();
        const browser = page.context().browser();
        const noScriptContext = await browser.newContext({
            javaScriptEnabled: false,
            viewport: { width: 1366, height: 900 },
        });

        try {
            await noScriptContext.addCookies(cookies);
            const noScriptPage = await noScriptContext.newPage();
            await noScriptPage.goto(calendarUrl, { waitUntil: 'domcontentloaded' });

            await expect(noScriptPage.locator('#calendar-page-title')).toBeVisible();
            await expect(noScriptPage.locator('a[href$="/admin/dashboard"]').first()).toBeVisible();
            await expect(noScriptPage.locator('[data-calendar-root]')).toBeVisible();
            await expect(noScriptPage.locator('[data-calendar-loading]')).toBeVisible();
            await expect(noScriptPage.locator('[data-calendar-mount]')).toBeVisible();
        } finally {
            await noScriptContext.close();
        }
    });

    test('adapts sidebar, filters, and drawer across the required breakpoints without horizontal overflow', async ({ page }) => {
        await openCalendar(page);
        const root = page.locator('[data-calendar-root]');

        for (const width of [390, 430, 768, 1024, 1366, 1600, 1920]) {
            await page.setViewportSize({ width, height: 900 });
            await expect
                .poll(() => root.evaluate((element) => element.scrollWidth - element.clientWidth), { timeout: 5000 })
                .toBeLessThanOrEqual(1);
        }

        await page.setViewportSize({ width: 390, height: 844 });
        await expect(page.locator('[data-calendar-filters-toggle]')).toBeVisible();
        await expect(page.locator('[data-calendar-filter-fields]')).toHaveAttribute('aria-hidden', 'true');
        await page.locator('[data-calendar-filters-toggle]').click();
        await expect(page.locator('[data-calendar-filter-fields]')).toHaveAttribute('aria-hidden', 'false');
        await expect(page.locator('.calendar-week-sidebar__days')).toHaveCSS('overflow-x', 'auto');
        await page.locator('[data-calendar-filters-toggle]').click();
        await expect(page.locator('[data-calendar-filter-fields]')).toHaveAttribute('aria-hidden', 'true');
        await expect(page.locator('[data-calendar-drawer]')).toHaveAttribute('data-calendar-drawer-presentation', 'bottom-sheet');

        await page.locator(card).first().click();
        await expect(page.locator(drawerPanel)).toBeVisible();
        expect(await page.locator(drawerPanel).evaluate((element) => element.getBoundingClientRect().height))
            .toBeLessThanOrEqual(844 * 0.8 + 1);
        await page.locator('[data-calendar-drawer-close]').click();

        await page.setViewportSize({ width: 768, height: 900 });
        await expect(page.locator('[data-calendar-filters-toggle]')).toBeHidden();
        await expect(page.locator('[data-calendar-filter-fields]')).toHaveAttribute('aria-hidden', 'false');
        await expect(page.locator('[data-calendar-drawer]')).toHaveAttribute('data-calendar-drawer-presentation', 'side');
        await expect(page.locator('.calendar-week-sidebar__days')).toHaveCSS('overflow-x', 'auto');

        await page.setViewportSize({ width: 1024, height: 900 });
        await expect
            .poll(() => page.locator('.calendar-week-sidebar__days').evaluate((element) => getComputedStyle(element).display))
            .toBe('grid');
    });
});
