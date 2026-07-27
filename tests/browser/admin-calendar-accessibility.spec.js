import { expect, test } from 'playwright/test';

/**
 * Admin calendar accessibility coverage.
 *
 * Validates: Requirements 3.9, 6.5–6.11, 9.1, 10.1–10.7, 11.5
 */

const baseUrl = process.env.CALENDAR_BROWSER_BASE_URL ?? process.env.TEST_BASE_URL;
const adminPhone = process.env.TEST_ADMIN_PHONE;
const adminPassword = process.env.TEST_ADMIN_PASSWORD;
const hasLiveRuntime = Boolean(baseUrl && adminPhone && adminPassword);

const CALENDAR_ROOT = '[data-calendar-root]';
const EVENT_CARD = '.calendar-timegrid-event-content';
const DRAWER_PANEL = '[data-calendar-drawer-panel]';
const FILTER_KEYS = ['teacher_id', 'student_id', 'room', 'instrument_id'];
const STATUS_PATTERN = 'scheduled|completed|cancelled|missed';

const calendarUrl = () => new URL('/admin/calendar', baseUrl).toString();
const loginUrl = () => new URL('/login', baseUrl).toString();
const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

async function loginAsAdmin(page) {
    await page.goto(loginUrl(), { waitUntil: 'domcontentloaded' });
    await page.locator('input[name="phone"], #phone').first().fill(adminPhone);
    await page.locator('input[name="password"], #password').first().fill(adminPassword);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.endsWith('/login')),
        page.locator('button[form="login-form"], button[type="submit"]').first().click(),
    ]);
    await page.waitForLoadState('domcontentloaded');
}

async function openCalendar(page) {
    await loginAsAdmin(page);

    if (!page.url().includes('/admin/calendar')) {
        await page.goto(calendarUrl(), { waitUntil: 'domcontentloaded' });
    }

    await expect(page.locator(CALENDAR_ROOT)).toBeVisible();
    await expect(page.locator('[data-calendar-mount] .fc-timegrid')).toBeVisible();
    await expect(page.locator('[data-calendar-loading]')).toBeHidden();
}

async function firstEventCard(page) {
    const card = page.locator(EVENT_CARD).first();
    const total = await page.locator(EVENT_CARD).count();
    test.skip(total === 0, 'The authenticated calendar needs at least one session on the current day for event-card coverage.');
    await expect(card).toBeVisible();
    return card;
}

/**
 * Read the token-driven focus presentation of the currently focused element.
 * Focus must be moved by keyboard so `:focus-visible` applies.
 */
function readFocusRing(page) {
    return page.evaluate(() => {
        const element = document.activeElement;
        const style = getComputedStyle(element);
        const root = element.closest('[data-calendar-root]') ?? document.documentElement;
        const rootStyle = getComputedStyle(root);
        const probe = document.createElement('span');
        document.body.append(probe);
        const resolve = (property, value) => {
            probe.setAttribute('style', '');
            probe.style.setProperty(property, value);
            return getComputedStyle(probe).getPropertyValue(property).trim();
        };
        const focusColor = resolve('color', rootStyle.getPropertyValue('--admin-color-focus').trim());
        const focusShadowToken = rootStyle.getPropertyValue('--shadow-input-focus').trim();
        const focusShadowColorToken = focusShadowToken.match(/rgba?\([^)]*\)/)?.[0] ?? '';
        const focusShadowColor = resolve('background-color', focusShadowColorToken);
        probe.remove();

        return {
            focusVisible: element.matches(':focus-visible'),
            outlineStyle: style.outlineStyle,
            outlineWidth: Number.parseFloat(style.outlineWidth),
            outlineColor: style.outlineColor,
            boxShadow: style.boxShadow,
            focusColor,
            focusShadowColor,
        };
    });
}

async function expectTokenFocusRing(page, description) {
    let ring = await readFocusRing(page);

    // The focus shadow is transitioned, so wait for the token value to settle before comparing.
    await expect
        .poll(async () => {
            ring = await readFocusRing(page);
            return ring.focusShadowColor !== '' && ring.boxShadow !== '' && ring.boxShadow !== 'none';
        }, { message: `${description} should settle on the --shadow-input-focus token` })
        .toBe(true);

    expect(ring.focusVisible, `${description} should expose a focus-visible ring`).toBe(true);
    expect(ring.outlineStyle, description).toBe('solid');
    expect(ring.outlineWidth, description).toBeGreaterThanOrEqual(2);
    expect(ring.outlineColor, description).toBe(ring.focusColor);
}

test.describe('admin calendar accessibility', () => {
    test.skip(!hasLiveRuntime, 'Set CALENDAR_BROWSER_BASE_URL (or TEST_BASE_URL), TEST_ADMIN_PHONE, and TEST_ADMIN_PASSWORD to run authenticated calendar coverage.');
    test.describe.configure({ mode: 'serial', timeout: 90_000 });
    test.use({ viewport: { width: 1366, height: 900 } });

    test('moves Tab focus from the sidebar through the filters into the calendar grid', async ({ page }) => {
        await openCalendar(page);

        const selectedDay = page.locator('[data-calendar-week-day][data-selected="true"]');
        const clearFilters = page.locator('[data-calendar-filters-clear]');
        const grid = page.locator('[data-calendar-mount]');

        await expect(page.locator('[data-calendar-week-day]')).toHaveCount(7);
        await expect(selectedDay).toHaveCount(1);

        await selectedDay.focus();
        await page.keyboard.press('Tab');
        await expect(clearFilters).toBeFocused();

        for (const key of FILTER_KEYS) {
            await page.keyboard.press('Tab');
            await expect(page.locator(`[data-calendar-filter="${key}"]`)).toBeFocused();
        }

        await page.keyboard.press('Tab');
        await expect(grid).toBeFocused();

        await clearFilters.focus();
        await page.keyboard.press('Shift+Tab');
        await expect(selectedDay).toBeFocused();
    });

    test('exposes labelled selects, today semantics, and token focus rings', async ({ page }) => {
        await openCalendar(page);

        await expect(page.locator('[data-calendar-week-day][aria-current="date"]')).toHaveCount(1);

        for (const key of FILTER_KEYS) {
            const select = page.locator(`[data-calendar-filter="${key}"]`);
            const label = await select.evaluate((control) => ({
                count: control.labels.length,
                forAttribute: control.labels[0]?.getAttribute('for') ?? '',
                id: control.id,
                text: control.labels[0]?.textContent?.trim() ?? '',
            }));

            expect(label.count, `${key} needs an associated label`).toBeGreaterThan(0);
            expect(label.forAttribute).toBe(label.id);
            expect(label.text).not.toBe('');
        }

        await page.locator('[data-calendar-week-day][data-selected="true"]').focus();
        await page.keyboard.press('Shift+Tab');
        await expectTokenFocusRing(page, 'header navigation control');

        await page.locator('[data-calendar-week-day][data-selected="true"]').focus();
        await page.keyboard.press('Tab');
        await expectTokenFocusRing(page, 'clear filters control');

        await page.keyboard.press('Tab');
        await expectTokenFocusRing(page, 'teacher filter select');
    });

    test('activates navigation, day, and filter controls with Enter and Space', async ({ page }) => {
        await openCalendar(page);

        const currentDate = page.locator('[data-calendar-current-date]');
        const weekDays = page.locator('[data-calendar-week-days]');
        const selectedDate = await weekDays.getAttribute('data-calendar-selected-date');
        const initialLabel = await currentDate.innerText();

        await page.locator('[data-calendar-next-day]').focus();
        await page.keyboard.press('Enter');
        await expect(currentDate).not.toHaveText(initialLabel);

        await page.locator('[data-calendar-prev-day]').focus();
        await page.keyboard.press('Space');
        await expect(currentDate).toHaveText(initialLabel);

        const focusedDay = page.locator('[data-calendar-week-day][data-selected="true"]');
        await focusedDay.focus();
        await page.keyboard.press('ArrowRight');
        await expect(weekDays).not.toHaveAttribute('data-calendar-selected-date', selectedDate);
        await expectTokenFocusRing(page, 'sidebar day button');

        await page.keyboard.press('Enter');
        await expect(page.locator('[data-calendar-week-day][data-selected="true"]')).toBeFocused();

        await page.locator('[data-calendar-today]').focus();
        await page.keyboard.press('Enter');
        await expect(weekDays).toHaveAttribute('data-calendar-selected-date', selectedDate);

        const teacher = page.locator('[data-calendar-filter="teacher_id"]');
        const hasTeacherOption = await teacher.evaluate((control) => [...control.options].some((option) => option.value !== ''));
        test.skip(!hasTeacherOption, 'The authenticated calendar needs at least one teacher option for filter keyboard coverage.');

        await teacher.selectOption({ index: 1 });
        await expect(page.locator('[data-calendar-filters]')).toHaveAttribute('data-calendar-active-filter-count', '1');
        await page.locator('[data-calendar-filters-clear]').focus();
        await page.keyboard.press('Enter');
        await expect(page.locator('[data-calendar-filters]')).toHaveAttribute('data-calendar-active-filter-count', '0');
        await expect(page.locator('[data-calendar-filters-count]')).toBeHidden();
    });

    test('gives event cards button semantics with a student and status accessible name', async ({ page }) => {
        await openCalendar(page);
        const card = await firstEventCard(page);
        const studentName = (await card.locator('.calendar-timegrid-event-content__primary').innerText()).trim();

        await expect(card).toHaveAttribute('role', 'button');
        await expect(card).toHaveAttribute('tabindex', '0');
        await expect(card).toHaveAttribute(
            'aria-label',
            new RegExp(`^${escapeRegExp(studentName)} – (?:${STATUS_PATTERN})$`),
        );

        await card.focus();
        await page.keyboard.press('Enter');
        await expect(page.locator(DRAWER_PANEL)).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(page.locator(DRAWER_PANEL)).toBeHidden();
        await expect(card).toBeFocused();

        await card.focus();
        await page.keyboard.press('Space');
        await expect(page.locator(DRAWER_PANEL)).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(page.locator(DRAWER_PANEL)).toBeHidden();
        await expect(card).toBeFocused();
    });

    test('uses dialog semantics, cycles trapped focus, and restores the triggering card', async ({ page }) => {
        await openCalendar(page);
        const card = await firstEventCard(page);
        const panel = page.locator(DRAWER_PANEL);
        const closeButton = page.locator('[data-calendar-drawer-close]');

        await card.focus();
        await page.keyboard.press('Enter');
        await expect(panel).toBeVisible();
        await expect(panel).toHaveAttribute('role', 'dialog');
        await expect(panel).toHaveAttribute('aria-modal', 'true');
        await expect(panel).toHaveAttribute('aria-labelledby', 'calendar-event-drawer-title');
        await expect(panel).toHaveAttribute('aria-hidden', 'false');
        await expect(panel).toHaveAttribute('x-trap.noscroll', 'open');
        await expect(page.locator('#calendar-event-drawer-title')).not.toHaveText('');
        await expect(closeButton).toHaveAccessibleName(/\S/);

        await expect.poll(() => panel.evaluate((element) => element.contains(document.activeElement))).toBe(true);
        await expectTokenFocusRing(page, 'drawer close button');

        for (let step = 0; step < 6; step += 1) {
            await page.keyboard.press('Tab');
            expect(
                await panel.evaluate((element) => element.contains(document.activeElement)),
                'focus must stay inside the trapped drawer',
            ).toBe(true);
        }

        await page.keyboard.press('Shift+Tab');
        expect(await panel.evaluate((element) => element.contains(document.activeElement))).toBe(true);

        await page.keyboard.press('Escape');
        await expect(panel).toBeHidden();
        await expect(card).toBeFocused();
    });

    test('keeps RTL, reduced motion, and token contrast on the dark calendar surfaces', async ({ page }) => {
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await openCalendar(page);
        const card = await firstEventCard(page);

        await expect(page.locator(CALENDAR_ROOT)).toHaveAttribute('dir', 'rtl');

        await card.focus();
        const assessment = await card.evaluate((element) => {
            const parseColor = (value) => {
                const channels = value.match(/[\d.]+/g)?.map(Number) ?? [];
                if (channels.length < 3) {
                    return null;
                }

                return {
                    red: channels[0],
                    green: channels[1],
                    blue: channels[2],
                    alpha: channels.length > 3 ? channels[3] : 1,
                };
            };
            const resolveToken = (token) => {
                const probe = document.createElement('span');
                probe.style.color = getComputedStyle(element.closest('[data-calendar-root]')).getPropertyValue(token).trim();
                document.body.append(probe);
                const resolved = getComputedStyle(probe).color;
                probe.remove();
                return parseColor(resolved);
            };
            const flatten = (foreground, background) => ({
                red: foreground.red * foreground.alpha + background.red * (1 - foreground.alpha),
                green: foreground.green * foreground.alpha + background.green * (1 - foreground.alpha),
                blue: foreground.blue * foreground.alpha + background.blue * (1 - foreground.alpha),
                alpha: 1,
            });
            const luminance = ({ red, green, blue }) => {
                const [r, g, b] = [red, green, blue].map((channel) => {
                    const normalized = channel / 255;
                    return normalized <= 0.03928 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4;
                });
                return r * 0.2126 + g * 0.7152 + b * 0.0722;
            };
            const contrast = (foreground, background) => {
                const [light, dark] = [luminance(foreground), luminance(background)].sort((first, second) => second - first);
                return (light + 0.05) / (dark + 0.05);
            };

            const style = getComputedStyle(element);
            const panel = document.querySelector('[data-calendar-drawer-panel]');
            const pageBackground = resolveToken('--admin-color-page');
            const surface = flatten(parseColor(style.backgroundColor), pageBackground);
            const statusContrasts = [...document.querySelectorAll('.calendar-timegrid-event-content')].map((eventCard) => {
                const cardStyle = getComputedStyle(eventCard);
                return contrast(
                    flatten(parseColor(cardStyle.borderInlineStartColor), surface),
                    surface,
                );
            });

            return {
                reducedMotion: matchMedia('(prefers-reduced-motion: reduce)').matches,
                cardTransition: style.transitionDuration,
                drawerTransition: panel ? getComputedStyle(panel).transitionDuration : '',
                focusContrast: contrast(flatten(parseColor(style.outlineColor), surface), surface),
                statusContrasts,
            };
        });

        const allDurationsAreReduced = (durationValue) => durationValue.trim() === ''
            || durationValue.split(',').every((duration) => Number.parseFloat(duration) <= 0.01);

        expect(assessment.reducedMotion).toBe(true);
        expect(allDurationsAreReduced(assessment.cardTransition)).toBe(true);
        expect(allDurationsAreReduced(assessment.drawerTransition)).toBe(true);
        expect(assessment.focusContrast).toBeGreaterThanOrEqual(3);
        expect(assessment.statusContrasts.length).toBeGreaterThan(0);
        assessment.statusContrasts.forEach((ratio) => expect(ratio).toBeGreaterThanOrEqual(3));
    });

    test.describe('touch presentation', () => {
        test.use({ viewport: { width: 390, height: 844 }, hasTouch: true });

        test('keeps coarse-pointer targets and mobile drawer semantics accessible', async ({ page }) => {
            await openCalendar(page);

            const toggle = page.locator('[data-calendar-filters-toggle]');
            await expect(toggle).toBeVisible();
            await expect(page.locator('[data-calendar-filter-fields]')).toHaveAttribute('aria-hidden', 'true');
            await toggle.focus();
            await page.keyboard.press('Enter');
            await expect(toggle).toHaveAttribute('aria-expanded', 'true');
            await expect(page.locator('[data-calendar-filter-fields]')).toHaveAttribute('aria-hidden', 'false');

            const card = await firstEventCard(page);
            const coarsePointer = await page.evaluate(() => matchMedia('(pointer: coarse)').matches);
            if (coarsePointer) {
                const box = await card.boundingBox();
                expect(box.height, 'coarse-pointer event cards need a 44px touch target').toBeGreaterThanOrEqual(44);
            }

            await card.focus();
            await page.keyboard.press('Enter');
            await expect(page.locator('[data-calendar-drawer]')).toHaveAttribute('data-calendar-drawer-presentation', 'bottom-sheet');
            await expect(page.locator(DRAWER_PANEL)).toBeVisible();
            await expect(page.locator(DRAWER_PANEL)).toHaveAttribute('role', 'dialog');
            await page.keyboard.press('Escape');
            await expect(page.locator(DRAWER_PANEL)).toBeHidden();
            await expect(card).toBeFocused();
        });
    });
});
