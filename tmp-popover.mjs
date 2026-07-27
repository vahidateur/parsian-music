import { chromium } from 'playwright';

const baseUrl = 'http://127.0.0.1:8000';
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1366, height: 768 } });
const page = await context.newPage();
await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
await page.locator('#phone').fill('09999999999');
await page.locator('#password').fill('testpass123');
await Promise.all([
    page.waitForURL((u) => !u.pathname.endsWith('/login')),
    page.locator('button[form="login-form"]').click(),
]);

async function check(theme, width, height) {
    await context.addCookies([{ name: 'pm_admin_theme', value: theme, url: baseUrl }]);
    await page.setViewportSize({ width, height });
    await page.goto(`${baseUrl}/admin/calendar`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);
    const links = page.locator('#calendar-mount .fc-timegrid-more-link');
    const count = await links.count();
    const results = [];
    for (let i = 0; i < count; i += 1) {
        await links.nth(i).scrollIntoViewIfNeeded();
        await links.nth(i).click();
        await page.waitForTimeout(250);
        results.push(await page.evaluate(() => {
            const pop = document.querySelector('.fc-popover');
            if (!pop) return 'no-popover';
            const p = pop.getBoundingClientRect();
            const host = document.querySelector('.calendar-day-timeline').getBoundingClientRect();
            const midY = Math.round(p.top + p.height / 2);
            const hit = document.elementFromPoint(Math.round(p.left + 3), midY);
            return {
                w: Math.round(p.width),
                inside: Math.round(p.left - host.left),
                leadingEdgeVisible: Boolean(hit && pop.contains(hit)),
                events: pop.querySelectorAll('.calendar-timegrid-event-content').length,
                title: pop.querySelector('.fc-popover-title')?.textContent,
            };
        }));
        await page.keyboard.press('Escape');
        await page.waitForTimeout(120);
    }
    console.log(`\n== ${theme} ${width} more-links: ${count}`);
    console.log(JSON.stringify(results));
    if (count) {
        await links.first().click();
        await page.waitForTimeout(300);
        await page.screenshot({ path: `.screenshots/calendar-${theme}-popover-${width}x${height}.png` });
        await page.keyboard.press('Escape');
    }
}

await check('dark', 1366, 768);
await check('glass', 390, 844);
await browser.close();
