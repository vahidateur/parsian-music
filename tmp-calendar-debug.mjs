import { chromium } from 'playwright';

const baseUrl = process.env.TEST_BASE_URL ?? 'http://127.0.0.1:8000';
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1366, height: 900 } });

page.on('console', (message) => console.log('[console]', message.type(), message.text()));
page.on('pageerror', (error) => console.log('[pageerror]', error.message, error.stack));
page.on('requestfailed', (request) => console.log('[requestfailed]', request.url(), request.failure()?.errorText));
page.on('response', (response) => {
    if (response.status() >= 400) {
        console.log('[response]', response.status(), response.url());
    }
});

if (process.env.STUB === '1') {
    await page.route('**/admin/calendar/events*', async (route) => {
        const url = new URL(route.request().url());
        console.log('[stub]', url.pathname + url.search);
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify([{
                id: 9001,
                title: 'Session Alpha Student — Alpha',
                start: `${url.searchParams.get('start')}T09:00:00`,
                end: `${url.searchParams.get('start')}T09:30:00`,
                status: 'scheduled',
                studentName: 'Session Alpha Student',
                teacherName: 'Session Alpha Teacher',
                instrumentName: 'Alpha',
                room: 'CAL-A',
                extendedProps: { enrollment_id: 1, session_fee: 1, duration_minutes: 30, notes: null, session_date: url.searchParams.get('start') },
            }]),
        });
    });
}

await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
await page.locator('#phone').fill(process.env.TEST_ADMIN_PHONE);
await page.locator('#password').fill(process.env.TEST_ADMIN_PASSWORD);
await Promise.all([
    page.waitForURL((url) => !url.pathname.endsWith('/login')),
    page.locator('button[form="login-form"]').click(),
]);
await page.goto(`${baseUrl}/admin/calendar`, { waitUntil: 'networkidle' });
await page.waitForTimeout(3000);

const snapshot = await page.evaluate(() => {
    const root = document.querySelector('[data-calendar-root]');
    const mount = document.querySelector('#calendar-mount');
    return {
        appState: root?.dataset.calendarAppState,
        booted: root?.dataset.calendarAppBooted,
        selected: root?.dataset.calendarSelectedDate,
        eventsUrl: root?.dataset.eventsUrl,
        mountHtml: mount?.innerHTML.slice(0, 200),
        errorHidden: document.querySelector('[data-calendar-error]')?.hidden,
        errorText: document.querySelector('[data-calendar-error]')?.textContent?.trim(),
        loadingHidden: document.querySelector('[data-calendar-loading]')?.hidden,
        scripts: [...document.querySelectorAll('script[src]')].map((s) => s.src),
    };
});
console.log(JSON.stringify(snapshot, null, 2));

await browser.close();
