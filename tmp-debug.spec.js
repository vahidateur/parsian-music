import { expect, test } from 'playwright/test';

const baseUrl = process.env.TEST_BASE_URL ?? 'http://127.0.0.1:8000';

test.use({ viewport: { width: 1366, height: 900 } });

test('debug calendar mount inside runner', async ({ page }) => {
    page.on('console', (message) => console.log('[console]', message.type(), message.text()));
    page.on('pageerror', (error) => console.log('[pageerror]', error.message));
    page.on('requestfailed', (request) => console.log('[requestfailed]', request.url(), request.failure()?.errorText));
    page.on('response', (response) => {
        if (response.status() >= 400) {
            console.log('[response]', response.status(), response.url());
        }
    });

    await page.route('**/admin/calendar/events*', async (route) => {
        const url = new URL(route.request().url());
        console.log('[stub]', url.search);
        await route.fulfill({ status: 200, contentType: 'application/json', body: '[]' });
    });

    await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
    await page.locator('#phone').fill(process.env.TEST_ADMIN_PHONE);
    await page.locator('#password').fill(process.env.TEST_ADMIN_PASSWORD);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.endsWith('/login')),
        page.locator('button[form="login-form"]').click(),
    ]);
    console.log('[url after login]', page.url());
    await page.goto(`${baseUrl}/admin/calendar`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(6000);

    const snapshot = await page.evaluate(() => ({
        appState: document.querySelector('[data-calendar-root]')?.dataset.calendarAppState,
        mountChildren: document.querySelector('#calendar-mount')?.children.length ?? -1,
        scripts: [...document.querySelectorAll('script[src]')].map((s) => s.src),
    }));
    console.log('[snapshot]', JSON.stringify(snapshot));
    expect(snapshot.mountChildren).toBeGreaterThan(0);
});
