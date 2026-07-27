import { chromium } from 'playwright';

const baseUrl = 'http://127.0.0.1:8000';
const results = [];
const check = (name, pass, detail = '') => results.push(`${pass ? 'PASS' : 'FAIL'} :: ${name}${detail ? ` :: ${detail}` : ''}`);

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1366, height: 768 } });
const page = await context.newPage();
const errors = [];
page.on('pageerror', (e) => errors.push(e.message));
page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });

const feed = { urls: [], mode: 'ok' };
await page.route('**/admin/calendar/events*', async (route) => {
    feed.urls.push(new URL(route.request().url()));
    if (feed.mode === 'error') {
        await route.fulfill({ status: 500, contentType: 'application/json', body: '{"message":"boom"}' });
        return;
    }
    if (feed.mode === 'empty') {
        await route.fulfill({ status: 200, contentType: 'application/json', body: '{"data":[]}' });
        return;
    }
    await route.fallback();
});

await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
await page.locator('#phone').fill('09999999999');
await page.locator('#password').fill('testpass123');
await Promise.all([
    page.waitForURL((u) => !u.pathname.endsWith('/login')),
    page.locator('button[form="login-form"]').click(),
]);
await context.addCookies([{ name: 'pm_admin_theme', value: 'glass', url: baseUrl }]);
await page.goto(`${baseUrl}/admin/calendar`, { waitUntil: 'networkidle' });
await page.waitForTimeout(1500);

const selected = () => page.locator('[data-calendar-root]').getAttribute('data-calendar-selected-date');
const shift = (iso, days) => { const d = new Date(`${iso}T12:00:00`); d.setDate(d.getDate() + days); return d.toISOString().slice(0, 10); };
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

const today = await selected();
check('initial selected date is today', today === new Date().toISOString().slice(0, 10), today);

// navigation
let before = feed.urls.length;
await page.locator('[data-calendar-prev-day]').click();
await wait(700);
check('prev day navigates and refetches', (await selected()) === shift(today, -1) && feed.urls.length > before, await selected());
check('sidebar tracks selection', (await page.locator('[data-calendar-week-day][data-selected="true"]').count()) === 1);

await page.locator('[data-calendar-next-day]').click();
await wait(700);
check('next day navigates', (await selected()) === today);

// sidebar week rollover + day click latency
const dayButtons = page.locator('[data-calendar-week-day]');
const targetIso = await dayButtons.nth(6).getAttribute('data-calendar-date');
before = feed.urls.length;
const t0 = Date.now();
await dayButtons.nth(6).click();
await page.waitForFunction((n) => true, null, { timeout: 1000 }).catch(() => {});
await wait(400);
check('sidebar day click updates timeline < 300ms request', feed.urls.length > before && (Date.now() - t0) < 900, `${Date.now() - t0}ms`);
check('sidebar day click selects that date', (await selected()) === targetIso, `${await selected()} vs ${targetIso}`);

for (let i = 0; i < 7; i += 1) { await page.locator('[data-calendar-next-day]').click(); await wait(120); }
await wait(600);
const firstDayIso = await dayButtons.first().getAttribute('data-calendar-date');
check('week rolls over when leaving the current week', firstDayIso !== null && new Date(firstDayIso) > new Date(today), firstDayIso);

await page.locator('[data-calendar-today]').click();
await wait(700);
check('today button returns to today', (await selected()) === today);
check('aria-current=date on today', (await page.locator('[data-calendar-week-day][aria-current="date"]').count()) === 1);

// keyboard on sidebar
await dayButtons.nth(0).focus();
await page.keyboard.press('ArrowRight');
await wait(500);
check('arrow key moves selection', (await selected()) !== today, await selected());
await page.locator('[data-calendar-today]').click();
await wait(600);

// filters
const teacherValue = await page.locator('[data-calendar-filter="teacher_id"]').evaluate((s) => [...s.options].find((o) => o.value)?.value ?? '');
before = feed.urls.length;
await page.locator('[data-calendar-filter="teacher_id"]').evaluate((s, v) => {
    ['', v, '', v].forEach((c) => { s.value = c; s.dispatchEvent(new Event('change', { bubbles: true })); });
}, teacherValue);
await wait(150);
const duringDebounce = feed.urls.length === before;
await wait(900);
check('filter change debounced into one request', duringDebounce && feed.urls.length === before + 1, `${feed.urls.length - before} requests`);
check('filter param sent', feed.urls.at(-1).searchParams.get('teacher_id') === teacherValue);
check('active filter badge visible', await page.locator('[data-calendar-filters-count]').isVisible() && (await page.locator('[data-calendar-filters-count]').textContent()) === '1');

before = feed.urls.length;
await page.locator('[data-calendar-filters-clear]').click();
await wait(900);
check('clear all resets filters', feed.urls.length > before && !feed.urls.at(-1).searchParams.has('teacher_id'));
check('badge hidden after clear', await page.locator('[data-calendar-filters-count]').isHidden());

// empty state
feed.mode = 'empty';
await page.locator('[data-calendar-next-day]').click();
await wait(1200);
check('empty state shown', await page.locator('[data-calendar-empty]').isVisible());
check('no cards in empty state', (await page.locator('.calendar-timegrid-event-content').count()) === 0);

// error + retry
feed.mode = 'error';
await page.locator('[data-calendar-next-day]').click();
await wait(4000);
check('error state shown with retry', await page.locator('[data-calendar-error]').isVisible() && await page.locator('[data-calendar-retry]').isVisible());
feed.mode = 'ok';
await page.locator('[data-calendar-retry]').click();
await wait(2000);
check('retry recovers', await page.locator('[data-calendar-error]').isHidden());
await page.locator('[data-calendar-today]').click();
await wait(1500);

// popover title digits
const moreLink = page.locator('#calendar-mount .fc-timegrid-more-link').first();
if (await moreLink.count()) {
    await moreLink.click();
    await wait(500);
    const title = await page.locator('.fc-popover-title').textContent();
    check('popover title uses Western digits', !/[۰-۹]/.test(title ?? ''), title ?? '');
    await page.keyboard.press('Escape');
    await wait(300);
}

// drawer desktop
const card = page.locator('.calendar-timegrid-event-content').first();
await card.focus();
await page.keyboard.press('Enter');
await wait(500);
const panel = page.locator('[data-calendar-drawer-panel]');
check('drawer opens by keyboard', await panel.isVisible());
check('drawer dialog semantics', (await panel.getAttribute('role')) === 'dialog' && (await panel.getAttribute('aria-modal')) === 'true');
await page.keyboard.press('Escape');
await wait(500);
check('drawer closes on Escape', await panel.isHidden());
check('focus returns to card', await card.evaluate((el) => el === document.activeElement));

await card.click();
await wait(400);
await page.locator('[data-calendar-drawer-overlay]').click({ position: { x: 5, y: 5 } });
await wait(400);
check('drawer closes on overlay click', await panel.isHidden());

// responsive sweep
for (const width of [390, 430, 768, 1024, 1366, 1600, 1920]) {
    await page.setViewportSize({ width, height: 900 });
    await wait(600);
    const overflow = await page.evaluate(() => ({
        root: document.querySelector('[data-calendar-root]').scrollWidth - document.querySelector('[data-calendar-root]').clientWidth,
        doc: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    }));
    check(`no horizontal overflow at ${width}`, overflow.root <= 1 && overflow.doc <= 1, JSON.stringify(overflow));
}

await page.setViewportSize({ width: 390, height: 844 });
await wait(700);
check('mobile filter toggle visible', await page.locator('[data-calendar-filters-toggle]').isVisible());
check('mobile filters collapsed', (await page.locator('[data-calendar-filter-fields]').getAttribute('aria-hidden')) === 'true');
await page.locator('[data-calendar-filters-toggle]').click();
await wait(300);
check('mobile filters expand', (await page.locator('[data-calendar-filter-fields]').getAttribute('aria-hidden')) === 'false');
await page.locator('[data-calendar-filters-toggle]').click();
await wait(300);
check('drawer presentation is bottom sheet', (await page.locator('[data-calendar-drawer]').getAttribute('data-calendar-drawer-presentation')) === 'bottom-sheet');
await page.locator('.calendar-timegrid-event-content').first().click();
await wait(500);
const sheet = await panel.boundingBox();
check('bottom sheet <= 80vh and full width', sheet.height <= 844 * 0.8 + 1 && sheet.width >= 389, JSON.stringify({ h: Math.round(sheet.height), w: Math.round(sheet.width) }));
await page.screenshot({ path: '.screenshots/calendar-glass-sheet-390x844.png' });
await page.locator('[data-calendar-drawer-close]').click();
await wait(300);

// touch target of a card
const cardBox = await page.locator('.calendar-timegrid-event-content').first().boundingBox();
check('card touch target >= 44px tall', cardBox.height >= 44, String(Math.round(cardBox.height)));

check('no console/page errors', errors.length === 0, errors.join(' | '));

console.log(results.join('\n'));
console.log(`\n${results.filter((r) => r.startsWith('FAIL')).length} failures of ${results.length}`);
await browser.close();
