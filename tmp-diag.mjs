import { chromium } from 'playwright';

const baseUrl = process.env.TEST_BASE_URL ?? 'http://127.0.0.1:8000';
const phone = process.env.TEST_ADMIN_PHONE ?? '09999999999';
const password = process.env.TEST_ADMIN_PASSWORD ?? 'testpass123';

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1366, height: 768 } });
const page = await context.newPage();

const logs = [];
page.on('console', (m) => logs.push(`[console:${m.type()}] ${m.text()}`));
page.on('pageerror', (e) => logs.push(`[pageerror] ${e.message}`));
page.on('requestfailed', (r) => logs.push(`[requestfailed] ${r.url()} :: ${r.failure()?.errorText}`));
page.on('response', (r) => { if (r.status() >= 400) logs.push(`[http ${r.status()}] ${r.url()}`); });

await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
await page.locator('#phone').fill(phone);
await page.locator('#password').fill(password);
await Promise.all([
    page.waitForURL((u) => !u.pathname.endsWith('/login')),
    page.locator('button[form="login-form"]').click(),
]);

async function inspect(theme, width, height) {
    await context.addCookies([{ name: 'pm_admin_theme', value: theme, url: baseUrl }]);
    await page.setViewportSize({ width, height });
    await page.goto(`${baseUrl}/admin/calendar`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(2200);

    const data = await page.evaluate(() => {
        const q = (s) => document.querySelector(s);
        const box = (el) => { if (!el) return null; const r = el.getBoundingClientRect(); return { w: Math.round(r.width), h: Math.round(r.height) }; };
        const cs = (el, props) => { if (!el) return null; const s = getComputedStyle(el); return Object.fromEntries(props.map((p) => [p, s[p]])); };
        const root = q('[data-calendar-root]');
        const card = q('.calendar-timegrid-event-content');
        const labels = [...document.querySelectorAll('#calendar-mount .fc-timegrid-slot-label-cushion')].map((n) => n.textContent.trim());
        return {
            theme: document.documentElement.dataset.adminTheme,
            appState: root?.dataset.calendarAppState,
            headerDate: q('[data-calendar-current-date]')?.textContent?.trim(),
            toolbarTitle: q('#calendar-mount .fc-toolbar-title')?.textContent,
            colHeader: q('#calendar-mount .fc-col-header-cell-cushion')?.textContent,
            slotLabels: `${labels.length}: ${labels.slice(0, 4).join(' | ')} ... ${labels.slice(-2).join(' | ')}`,
            slotLaneHeight: box(q('#calendar-mount .fc-timegrid-slot-lane'))?.h,
            cardCount: document.querySelectorAll('.calendar-timegrid-event-content').length,
            moreLinks: document.querySelectorAll('#calendar-mount .fc-timegrid-more-link').length,
            cardText: card?.textContent,
            cardLines: {
                primary: q('.calendar-timegrid-event-content__primary')?.textContent,
                secondary: q('.calendar-timegrid-event-content__secondary')?.textContent,
                time: q('.calendar-timegrid-event-content__time')?.textContent,
                room: q('.calendar-timegrid-event-content__room')?.textContent,
                instrument: q('.calendar-timegrid-event-content__instrument')?.textContent,
            },
            cardBox: box(card),
            cardStyles: cs(card, ['backgroundColor', 'color', 'borderInlineStartColor']),
            cardScrollH: card ? card.scrollHeight : null,
            fcEventStyles: cs(q('#calendar-mount .fc-timegrid-event'), ['backgroundColor', 'borderTopColor', 'borderTopWidth', 'boxShadow']),
            rootOverflow: root ? root.scrollWidth - root.clientWidth : null,
            docOverflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
            emptyHidden: q('[data-calendar-empty]')?.hidden,
            errorHidden: q('[data-calendar-error]')?.hidden,
            loadingHidden: q('[data-calendar-loading]')?.hidden,
        };
    });
    console.log(`\n===== ${theme} ${width}x${height} =====`);
    console.log(JSON.stringify(data, null, 1));
    await page.screenshot({ path: `.screenshots/calendar-${theme}-${width}x${height}.png`, fullPage: false });
    await page.screenshot({ path: `.screenshots/calendar-${theme}-${width}x${height}-full.png`, fullPage: true });
    return data;
}

await inspect('dark', 1366, 768);
await inspect('glass', 1366, 768);

// interaction checks at desktop / glass
const more = page.locator('#calendar-mount .fc-timegrid-more-link').first();
if (await more.count()) {
    await more.click();
    await page.waitForTimeout(600);
    const pop = await page.evaluate(() => {
        const p = document.querySelector('.fc-popover');
        if (!p) return null;
        const r = p.getBoundingClientRect();
        const host = document.querySelector('#calendar-mount').getBoundingClientRect();
        return { w: Math.round(r.width), h: Math.round(r.height), insideHost: r.left >= host.left - 2 && r.right <= host.right + 2, events: p.querySelectorAll('.calendar-timegrid-event-content').length };
    });
    console.log('\n===== more-link popover =====\n', JSON.stringify(pop));
    await page.screenshot({ path: '.screenshots/calendar-glass-popover-1366x768.png' });
    await page.keyboard.press('Escape');
    await page.locator('body').click({ position: { x: 5, y: 5 } });
    await page.waitForTimeout(300);
}

// drawer
await page.locator('.calendar-timegrid-event-content').first().click();
await page.waitForTimeout(500);
const drawer = await page.evaluate(() => {
    const p = document.querySelector('[data-calendar-drawer-panel]');
    const r = p?.getBoundingClientRect();
    return { open: document.querySelector('[data-calendar-drawer]')?.dataset.calendarDrawerOpen, w: r ? Math.round(r.width) : null, text: p?.textContent.replace(/\s+/g, ' ').trim().slice(0, 300) };
});
console.log('\n===== drawer =====\n', JSON.stringify(drawer, null, 1));
await page.screenshot({ path: '.screenshots/calendar-glass-drawer-1366x768.png' });
await page.keyboard.press('Escape');

await inspect('dark', 390, 844);
await inspect('glass', 390, 844);

console.log('\n===== logs =====');
console.log(logs.join('\n') || '(none)');

await browser.close();
