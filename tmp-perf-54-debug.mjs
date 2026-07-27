import { chromium } from 'playwright';

const baseUrl = process.env.TEST_BASE_URL;
const browser = await chromium.launch();
const page = await browser.newPage();

await page.addInitScript(() => {
    window.__shifts = [];
    new PerformanceObserver((entries) => {
        entries.getEntries().forEach((entry) => {
            if (entry.hadRecentInput) {
                return;
            }

            window.__shifts.push({
                value: entry.value,
                time: entry.startTime,
                sources: (entry.sources || []).map((source) => {
                    const node = source.node;
                    if (!node) {
                        return 'unknown';
                    }
                    const element = node.nodeType === 1 ? node : node.parentElement;
                    const inCalendar = Boolean(element?.closest?.('[data-calendar-root]'));
                    return `${element?.tagName}.${element?.className}`.slice(0, 80) + (inCalendar ? ' [CAL]' : '');
                }),
            });
        });
    }).observe({ type: 'layout-shift', buffered: true });
});

const requests = [];
page.on('request', (request) => {
    if (new URL(request.url()).pathname === '/admin/calendar/events') {
        requests.push({ url: request.url(), at: Date.now() });
    }
});

await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
await page.locator('input[name="phone"]').fill(process.env.TEST_ADMIN_PHONE);
await page.locator('input[name="password"]').fill(process.env.TEST_ADMIN_PASSWORD);
await Promise.all([
    page.waitForURL((url) => !url.pathname.endsWith('/login')),
    page.locator('button[type="submit"]').first().click(),
]);

await page.goto(`${baseUrl}/admin/calendar`, { waitUntil: 'networkidle' });
await page.locator('[data-calendar-mount].fc').waitFor();
await page.waitForTimeout(800);
console.log('initial requests:', requests.length);
console.log('shifts:', JSON.stringify(await page.evaluate(() => window.__shifts), null, 1));

const before = requests.length;
const startedAt = Date.now();
await page.locator('[data-calendar-filter="room"]').evaluate((control) => {
    const value = [...control.options].find((option) => option.value)?.value;
    ['', value, '', value, '', value].forEach((next) => {
        control.value = next;
        control.dispatchEvent(new Event('change', { bubbles: true }));
    });
});
await page.waitForTimeout(250);
console.log('after 250ms:', requests.length - before);
await page.waitForTimeout(400);
console.log('after 650ms:', requests.length - before, requests.at(-1) ? requests.at(-1).at - startedAt : null);
await page.waitForTimeout(500);
console.log('after 1150ms:', requests.length - before, requests.at(-1)?.url);

await browser.close();
