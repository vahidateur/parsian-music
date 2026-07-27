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
await page.goto(`${baseUrl}/admin/calendar`, { waitUntil: 'networkidle' });
await page.waitForTimeout(2000);
await page.locator('#calendar-mount .fc-timegrid-more-link').first().click();
await page.waitForTimeout(400);
await page.evaluate(() => document.querySelector('.fc-popover')?.scrollIntoView({ block: 'center' }));
await page.waitForTimeout(300);
console.log(JSON.stringify(await page.evaluate(() => {
    const pop = document.querySelector('.fc-popover');
    const chain = [];
    let node = pop;
    while (node && node !== document.documentElement) {
        const s = getComputedStyle(node);
        chain.push({
            tag: `${node.tagName}.${(node.className || '').toString().split(' ').slice(0, 2).join('.')}`,
            overflow: `${s.overflowX}/${s.overflowY}`,
            clipMargin: s.overflowClipMargin,
            position: s.position,
            zIndex: s.zIndex,
            left: Math.round(node.getBoundingClientRect().left),
        });
        node = node.parentElement;
    }
    const p = pop.getBoundingClientRect();
    const midY = Math.round(p.top + p.height / 2);
    return {
        popRect: { l: Math.round(p.left), r: Math.round(p.right) },
        probe: [11, 13, 16, 20, 23, 26, 30, 50].map((x) => {
            const h = document.elementFromPoint(x, midY);
            return `${x}:${h && pop.contains(h) ? 'POPOVER' : (h ? h.tagName : 'null')}`;
        }),
        chain,
    };
}), null, 1));
await browser.close();
