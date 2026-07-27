import { expect, test } from 'playwright/test';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const workspaceRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const manifestPath = path.join(workspaceRoot, 'public/build/manifest.json');
const baseUrl = process.env.CALENDAR_BROWSER_BASE_URL ?? process.env.TEST_BASE_URL;
const adminPhone = process.env.TEST_ADMIN_PHONE;
const adminPassword = process.env.TEST_ADMIN_PASSWORD;
const hasLiveRuntime = Boolean(baseUrl && adminPhone && adminPassword);

const eventsPathname = '/admin/calendar/events';
const timeline = '#calendar-day-timeline';
const skeleton = '[data-calendar-loading]';
const renderedCalendar = '[data-calendar-mount].fc .fc-view-harness';

/** Debounce window from Requirement 12.4, with a small tolerance for timer jitter. */
const DEBOUNCE_MS = 300;
const DEBOUNCE_TOLERANCE_MS = 25;
/** Held response time that keeps the loading state observable without racing it. */
const FEED_DELAY_MS = 1200;
/**
 * Requirement 12.5 asks for no measurable shift while events load. Sub-pixel
 * rounding inside the time grid can still register tiny entries, so the budget
 * is kept at half of the 0.1 "good" CLS threshold rather than an exact zero.
 */
const CLS_BUDGET = 0.05;
const HEIGHT_TOLERANCE_PX = 2;

const pause = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));
const calendarUrl = () => new URL('/admin/calendar', baseUrl).toString();
const loginUrl = () => new URL('/login', baseUrl).toString();

async function loginAsAdmin(page) {
    await page.goto(loginUrl(), { waitUntil: 'domcontentloaded' });
    await page.locator('input[name="phone"]').fill(adminPhone);
    await page.locator('input[name="password"]').fill(adminPassword);
    await Promise.all([
        page.waitForURL((url) => url.pathname !== '/login', { waitUntil: 'domcontentloaded' }),
        page.locator('button[type="submit"]').first().click(),
    ]);
}

function trackEventRequests(page) {
    const requests = [];

    page.on('request', (request) => {
        if (new URL(request.url()).pathname === eventsPathname) {
            requests.push({ url: request.url(), requestedAt: Date.now() });
        }
    });

    return requests;
}

async function observeLayoutShifts(page) {
    await page.addInitScript(() => {
        window.__calendarLayoutShifts = [];

        new PerformanceObserver((entries) => {
            entries.getEntries().forEach((entry) => {
                if (entry.hadRecentInput) {
                    return;
                }

                const insideCalendar = (entry.sources ?? []).some((source) => {
                    const node = source.node;
                    const element = node?.nodeType === 1 ? node : node?.parentElement;

                    return Boolean(element?.closest?.('[data-calendar-root]'));
                });

                window.__calendarLayoutShifts.push({ value: entry.value, insideCalendar });
            });
        }).observe({ type: 'layout-shift', buffered: true });
    });
}

async function readLayoutShiftScores(page) {
    const entries = await page.evaluate(() => window.__calendarLayoutShifts ?? []);
    const sum = (values) => values.reduce((total, entry) => total + entry.value, 0);

    return {
        calendar: sum(entries.filter((entry) => entry.insideCalendar)),
        page: sum(entries),
    };
}

test('keeps FullCalendar out of the eager bundle as dynamic Vite chunks', async () => {
    const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
    const calendarEntry = manifest['resources/js/calendar/calendar-app.js'];
    const expectedChunks = [
        'node_modules/@fullcalendar/core/index.js',
        'node_modules/@fullcalendar/timegrid/index.js',
        'node_modules/@fullcalendar/interaction/index.js',
        'node_modules/@fullcalendar/core/locales/fa.js',
    ];

    expect(calendarEntry.isEntry).toBe(true);
    expect(calendarEntry.dynamicImports).toEqual(expect.arrayContaining(expectedChunks));
    expectedChunks.forEach((chunk) => {
        expect(manifest[chunk].isDynamicEntry).toBe(true);
        expect(calendarEntry.imports ?? []).not.toContain(chunk);
    });
});

test.describe('admin calendar live performance', () => {
    // Signing in and booting the admin shell is slower than the default budget.
    test.describe.configure({ timeout: 90_000 });
    test.skip(!hasLiveRuntime, 'Set CALENDAR_BROWSER_BASE_URL (or TEST_BASE_URL), TEST_ADMIN_PHONE, and TEST_ADMIN_PASSWORD to run live calendar coverage.');

    test('holds a stable skeleton and adds no measurable layout shift while events load', async ({ page }) => {
        await observeLayoutShifts(page);
        const eventRequests = trackEventRequests(page);

        await loginAsAdmin(page);
        await page.route(`**${eventsPathname}?**`, async (route) => {
            await pause(FEED_DELAY_MS);
            await route.continue();
        });
        await page.goto(calendarUrl(), { waitUntil: 'domcontentloaded' });

        const timelineRegion = page.locator(timeline);
        const loadingSkeleton = page.locator(skeleton);

        await expect(loadingSkeleton).toBeVisible();
        expect(await timelineRegion.evaluate((element) => Number.parseFloat(getComputedStyle(element).minHeight)))
            .toBeGreaterThan(0);

        const loadingBox = await timelineRegion.boundingBox();
        expect(loadingBox).not.toBeNull();
        expect(loadingBox.height).toBeGreaterThan(0);

        await expect(loadingSkeleton).toBeHidden({ timeout: FEED_DELAY_MS + 10_000 });
        await expect(page.locator(renderedCalendar)).toBeVisible();

        const settledBox = await timelineRegion.boundingBox();
        expect(settledBox).not.toBeNull();
        expect(Math.abs(settledBox.height - loadingBox.height)).toBeLessThanOrEqual(HEIGHT_TOLERANCE_PX);
        expect(Math.abs(settledBox.y - loadingBox.y)).toBeLessThanOrEqual(HEIGHT_TOLERANCE_PX);

        const shifts = await readLayoutShiftScores(page);
        expect(shifts.calendar).toBeLessThan(CLS_BUDGET);
        expect(shifts.page).toBeLessThan(0.1);
        expect(eventRequests).toHaveLength(1);
    });

    test('coalesces a rapid filter burst into one request after the debounce window', async ({ page }) => {
        const eventRequests = trackEventRequests(page);

        await loginAsAdmin(page);
        await page.goto(calendarUrl(), { waitUntil: 'domcontentloaded' });
        await expect(page.locator(renderedCalendar)).toBeVisible();
        await expect(page.locator(skeleton)).toBeHidden();

        const burst = await page.locator('[data-calendar-filter]').evaluateAll((controls) => {
            const control = controls.find((candidate) => candidate.options.length > 1);
            const value = control ? [...control.options].find((option) => option.value)?.value : null;

            return value ? { key: control.getAttribute('data-calendar-filter'), value } : null;
        });
        expect(burst, 'The live calendar needs at least one populated filter to exercise the debounce.').not.toBeNull();

        const requestsBeforeBurst = eventRequests.length;
        const burstStartedAt = Date.now();
        await page.locator(`[data-calendar-filter="${burst.key}"]`).evaluate((control, value) => {
            ['', value, '', value, '', value].forEach((nextValue) => {
                control.value = nextValue;
                control.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }, burst.value);

        await pause(DEBOUNCE_MS - 2 * DEBOUNCE_TOLERANCE_MS);
        expect(eventRequests).toHaveLength(requestsBeforeBurst);

        await expect.poll(() => eventRequests.length, { timeout: 5_000 }).toBe(requestsBeforeBurst + 1);
        const debouncedRequest = eventRequests.at(-1);
        expect(debouncedRequest.requestedAt - burstStartedAt).toBeGreaterThanOrEqual(DEBOUNCE_MS - DEBOUNCE_TOLERANCE_MS);
        expect(new URL(debouncedRequest.url).searchParams.get(burst.key)).toBe(burst.value);

        await pause(DEBOUNCE_MS * 2);
        expect(eventRequests).toHaveLength(requestsBeforeBurst + 1);
    });
});
