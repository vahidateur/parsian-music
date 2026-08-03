import { expect, test } from 'playwright/test';

/**
 * Admin operational UX viewport/theme matrix for task 14.4.
 *
 * This is verification-only coverage: it does not mutate application data and
 * deliberately excludes calendar rendering and accessibility task 14.5.
 *
 * Validates: Requirements 12.2, 12.3, 12.4, 12.5, 12.6, 12.9, 13.1, 13.8
 */

const baseUrl = process.env.TEST_BASE_URL ?? 'http://127.0.0.1:8000';
const adminPhone = process.env.TEST_ADMIN_PHONE;
const adminPassword = process.env.TEST_ADMIN_PASSWORD;
const widths = [390, 430, 768, 1024, 1366, 1600, 1920];
const themes = ['dark', 'glass'];
const surfaces = [
    { name: 'dashboard', path: '/admin/dashboard' },
    { name: 'students-list', path: '/admin/students' },
    { name: 'teachers-list', path: '/admin/teachers' },
    { name: 'invoices-list', path: '/admin/invoices' },
    { name: 'leads-list', path: '/admin/leads' },
    { name: 'student-form', path: '/admin/students/create' },
];

const hasRuntime = Boolean(adminPhone && adminPassword);

function rgb(value) {
    const channels = value.match(/[\d.]+/g)?.map(Number) ?? [];
    if (channels.length < 3) return null;
    const alpha = channels.length > 3 ? channels[3] : 1;
    return { r: channels[0], g: channels[1], b: channels[2], alpha };
}

function luminance(color) {
    return [color.r, color.g, color.b].map((channel) => {
        const normalized = channel / 255;
        return normalized <= 0.03928 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4;
    }).reduce((total, channel, index) => total + channel * [0.2126, 0.7152, 0.0722][index], 0);
}

function contrast(foreground, background) {
    const [light, dark] = [luminance(foreground), luminance(background)].sort((a, b) => b - a);
    return (light + 0.05) / (dark + 0.05);
}

async function loginAsAdmin(page) {
    await page.goto(new URL('/login', baseUrl).toString(), { waitUntil: 'domcontentloaded' });
    await page.locator('input[name="phone"], #phone').first().fill(adminPhone);
    await page.locator('input[name="password"], #password').first().fill(adminPassword);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.endsWith('/login')),
        page.locator('button[type="submit"], button[form="login-form"]').first().click(),
    ]);
}

async function setTheme(page, theme) {
    await page.context().addCookies([{
        name: 'pm_admin_theme',
        value: theme,
        url: new URL('/', baseUrl).toString(),
    }]);
}

async function assertDocumentContainment(page) {
    const overflow = await page.evaluate(() => Math.max(
        document.documentElement.scrollWidth - document.documentElement.clientWidth,
        document.body.scrollWidth - document.body.clientWidth,
    ));
    expect(overflow, 'document horizontal overflow must be <= 1 CSS px').toBeLessThanOrEqual(1);
}

async function assertTablesContained(page) {
    const tables = page.locator('table:visible');
    for (let index = 0; index < await tables.count(); index += 1) {
        const table = tables.nth(index);
        const wrapper = table.locator('xpath=ancestor::*[contains(@class,"overflow-x-auto") or contains(@class,"ui-table-wrap")][1]');
        if (await wrapper.count() === 0) continue;

        const [tableBox, wrapperBox, overflowX] = await Promise.all([
            table.boundingBox(),
            wrapper.boundingBox(),
            wrapper.evaluate((element) => getComputedStyle(element).overflowX),
        ]);
        expect(wrapperBox, 'table wrapper should be rendered').not.toBeNull();
        expect(wrapperBox.x).toBeGreaterThanOrEqual(-1);
        expect(wrapperBox.x + wrapperBox.width).toBeLessThanOrEqual(page.viewportSize().width + 1);
        expect(['auto', 'scroll', 'hidden', 'clip']).toContain(overflowX);
        if (tableBox.width <= wrapperBox.width + 1) {
            expect(tableBox.x).toBeGreaterThanOrEqual(wrapperBox.x - 1);
            expect(tableBox.x + tableBox.width).toBeLessThanOrEqual(wrapperBox.x + wrapperBox.width + 1);
        }
    }
}

async function assertOverlayContained(page, selector) {
    const overlay = page.locator(selector);
    await expect(overlay).toBeVisible();
    const box = await overlay.boundingBox();
    expect(box, `${selector} should have a viewport box`).not.toBeNull();
    expect(box.x).toBeGreaterThanOrEqual(-1);
    expect(box.y).toBeGreaterThanOrEqual(-1);
    expect(box.x + box.width).toBeLessThanOrEqual(page.viewportSize().width + 1);
    expect(box.y + box.height).toBeLessThanOrEqual(page.viewportSize().height + 1);
}

async function assertShellOverlays(page, width) {
    const notification = page.locator('[aria-controls="admin-notif-panel"]');
    await notification.click();
    await assertOverlayContained(page, '#admin-notif-panel');
    await page.keyboard.press('Escape');

    const userMenu = page.locator('[aria-controls="admin-user-panel"]');
    await userMenu.click();
    await assertOverlayContained(page, '#admin-user-panel');
    await page.keyboard.press('Escape');

    if (width < 1024) {
        const mobileToggle = page.locator('.admin-shell__mobile-toggle');
        await expect(mobileToggle).toBeVisible();
        await mobileToggle.click();
        await assertOverlayContained(page, '#admin-mobile-drawer');
        await page.keyboard.press('Escape');
        await expect(page.locator('#admin-mobile-drawer')).toBeHidden();
    } else {
        await expect(page.locator('.admin-shell__sidebar')).toBeVisible();
        await expect(page.locator('.admin-shell__mobile-toggle')).toBeHidden();
    }
}

async function assertCoarseTargets(page) {
    if (!(await page.evaluate(() => matchMedia('(pointer: coarse)').matches))) return;

    const controls = page.locator('button:visible, a:visible, input:visible, select:visible, textarea:visible');
    for (let index = 0; index < await controls.count(); index += 1) {
        const control = controls.nth(index);
        const box = await control.boundingBox();
        if (!box) continue;
        const details = await control.evaluate((element) => ({
            tag: element.tagName,
            className: element.className,
            ariaLabel: element.getAttribute('aria-label'),
            text: element.textContent?.trim().slice(0, 80),
        }));
        expect(box.width, `coarse-pointer target width must be >= 44px (${JSON.stringify(details)})`).toBeGreaterThanOrEqual(44);
        expect(box.height, `coarse-pointer target height must be >= 44px (${JSON.stringify(details)})`).toBeGreaterThanOrEqual(44);
    }
}

async function assertReadabilityAndContracts(page, theme) {
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await expect(page.locator('html')).toHaveAttribute('data-admin-theme', theme);
    await expect(page.locator('h1')).toHaveCount(1);

    const assessment = await page.locator('h1').evaluate((heading) => {
        const text = getComputedStyle(heading);
        const shell = document.querySelector('.admin-page');
        const shellStyle = getComputedStyle(shell);
        return {
            foreground: text.color,
            background: shellStyle.backgroundColor,
            visible: Boolean(heading.getBoundingClientRect().width && heading.getBoundingClientRect().height),
        };
    });
    const foreground = rgb(assessment.foreground);
    const background = rgb(assessment.background);

    expect(assessment.visible).toBe(true);
    expect(foreground).not.toBeNull();
    expect(background).not.toBeNull();
    expect(contrast(foreground, background), `${theme} heading should remain readable`).toBeGreaterThanOrEqual(3);

    const activeNavigation = page.locator('[aria-current="page"]');
    await expect.soft(activeNavigation.first(), 'admin page should expose an active aria-current navigation item').toBeAttached();
}

async function assertBreakpoint(page, width) {
    const mobile = width < 1024;
    await expect(page.locator('.admin-shell__mobile-toggle')).toBeVisible({ visible: mobile });
    await expect(page.locator('.admin-shell__sidebar')).toBeVisible({ visible: !mobile });
    await expect(page.locator('.admin-shell__content-inner')).toHaveCSS('max-width', /.+/);
}

for (const width of widths) {
    for (const theme of themes) {
        test.describe(`admin 14.4 matrix ${width}px ${theme}`, () => {
            test.skip(!hasRuntime, 'Set TEST_ADMIN_PHONE and TEST_ADMIN_PASSWORD for authenticated browser verification.');
            test.use({ viewport: { width, height: width <= 430 ? 844 : 900 }, hasTouch: width <= 430 });
            test.setTimeout(90_000);

            test(`verifies ${surfaces.length} operational surfaces and shared containment contracts`, async ({ page }) => {
                await loginAsAdmin(page);
                await setTheme(page, theme);

                for (const surface of surfaces) {
                    await page.goto(new URL(surface.path, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
                    await expect(page.locator('.admin-shell')).toBeVisible();
                    await assertDocumentContainment(page);
                    await assertTablesContained(page);
                    await assertBreakpoint(page, width);
                    await assertReadabilityAndContracts(page, theme);
                    await assertCoarseTargets(page);

                    if (surface.name === 'dashboard' || surface.name === 'students-list') {
                        await assertShellOverlays(page, width);
                    }
                }
            });
        });
    }
}
