import { expect, test } from 'playwright/test';
import { assessContrast } from './support/contrast.js';

/**
 * Admin operational UX browser accessibility verification for optional task 14.5.
 * Reuses the authenticated runtime and shared shell/modal/feedback contracts from task 14.4.
 *
 * Validates: Requirements 11.1, 11.2, 11.3, 11.7, 11.8, 11.9, 11.11, 13.9
 */

const baseUrl = process.env.TEST_BASE_URL ?? 'http://127.0.0.1:8000';
const adminPhone = process.env.TEST_ADMIN_PHONE;
const adminPassword = process.env.TEST_ADMIN_PASSWORD;
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

const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"]):not([aria-hidden="true"])',
].join(',');

async function loginAsAdmin(page) {
    await page.goto(new URL('/login', baseUrl).toString(), { waitUntil: 'domcontentloaded' });
    await page.locator('input[name="phone"], #phone').first().fill(adminPhone);
    await page.locator('input[name="password"], #password').first().fill(adminPassword);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.endsWith('/login')),
        page.locator('button[type="submit"], button[form="login-form"]').first().click(),
    ]);
}

async function openSurface(page, surface, theme) {
    await page.context().addCookies([{
        name: 'pm_admin_theme',
        value: theme,
        url: new URL('/', baseUrl).toString(),
    }]);
    await page.goto(new URL(surface.path, baseUrl).toString(), { waitUntil: 'domcontentloaded' });
    await expect(page.locator('.admin-shell')).toBeVisible();
    await expect(page.locator('html')).toHaveAttribute('data-admin-theme', theme);
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
}

function parseColor(value) {
    const channels = value.match(/[\d.]+/g)?.map(Number) ?? [];
    if (channels.length < 3) return null;
    return { r: channels[0], g: channels[1], b: channels[2], alpha: channels.length > 3 ? channels[3] : 1 };
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

async function assertAccessibleNamesAndRoles(page) {
    const violations = await page.evaluate((selector) => {
        const visible = (element) => {
            const style = getComputedStyle(element);
            const box = element.getBoundingClientRect();
            return style.display !== 'none' && style.visibility !== 'hidden' && box.width > 0 && box.height > 0;
        };
        const nameOf = (element) => {
            const labelledBy = element.getAttribute('aria-labelledby');
            const labelledText = labelledBy
                ? labelledBy.split(/\s+/).map((id) => document.getElementById(id)?.textContent ?? '').join(' ')
                : '';
            const label = element.labels?.[0]?.textContent ?? '';
            return (element.getAttribute('aria-label') || labelledText || label || element.innerText || element.value || '').trim();
        };
        return [...document.querySelectorAll(selector)]
            .filter(visible)
            .filter((element) => !element.closest('[aria-hidden="true"]'))
            .map((element) => ({
                tag: element.tagName.toLowerCase(),
                type: element.getAttribute('type'),
                name: nameOf(element),
                role: element.getAttribute('role'),
                text: element.textContent?.trim().slice(0, 80),
            }))
            .filter((control) => !control.name);
    }, focusableSelector);

    expect(violations, 'visible operational controls must have accessible names').toEqual([]);

    const semanticViolations = await page.evaluate(() => [...document.querySelectorAll('a[href], button, input, select, textarea')]
        .filter((element) => !element.closest('[aria-hidden="true"]'))
        .map((element) => ({ tag: element.tagName.toLowerCase(), role: element.getAttribute('role') }))
        .filter(({ tag, role }) => (tag === 'a' && role === 'button') || (tag === 'button' && role === 'link')));
    expect(semanticViolations, 'navigation and actions must retain native semantics').toEqual([]);
}

async function assertKeyboardOrder(page) {
    const focusables = page.locator(focusableSelector).filter({ visible: true });
    const count = await focusables.count();
    expect(count, 'operational surface should expose keyboard controls').toBeGreaterThan(0);

    const positiveTabIndexes = await page.evaluate((selector) => [...document.querySelectorAll(selector)]
        .filter((element) => element.offsetParent !== null)
        .map((element) => Number(element.getAttribute('tabindex')))
        .filter((tabIndex) => tabIndex > 0), focusableSelector);
    expect(positiveTabIndexes, 'tab order must be DOM-driven').toEqual([]);

    const steps = Math.min(count - 1, 10);
    await focusables.first().focus();
    for (let index = 0; index < steps; index += 1) {
        await page.keyboard.press('Tab');
        await expect(page.locator(':focus')).toHaveCount(1);
        const activeInSurface = await page.evaluate((selector) => {
            const active = document.activeElement;
            return Boolean(active && active.matches(selector) && !active.closest('[aria-hidden="true"]'));
        }, focusableSelector);
        expect(activeInSurface, 'Tab must move through visible operational controls').toBe(true);
    }
}

async function focusWithKeyboard(page, locator) {
    await locator.focus();
    await page.keyboard.press('Shift+Tab');
    await page.keyboard.press('Tab');
    await expect(locator).toBeFocused();
}

async function assertFocusRing(page, locator, description) {
    await focusWithKeyboard(page, locator);
    const ring = await locator.evaluate((element) => {
        const style = getComputedStyle(element);
        const colors = style.boxShadow.match(/rgba?\([^)]*\)/g) ?? [];
        return {
            focusVisible: element.matches(':focus-visible'),
            outlineStyle: style.outlineStyle,
            outlineWidth: Number.parseFloat(style.outlineWidth),
            outlineColor: style.outlineColor,
            boxShadow: style.boxShadow,
            shadowColors: colors,
        };
    });
    expect(ring.focusVisible, `${description} should be focus-visible`).toBe(true);
    const hasTwoPixelRing = ring.outlineStyle === 'solid' && ring.outlineWidth >= 2;
    const hasShadowRing = ring.boxShadow !== 'none' && ring.boxShadow !== '' && ring.shadowColors.length > 0;
    expect(hasTwoPixelRing || hasShadowRing, `${description} should expose a visible focus ring`).toBe(true);

    const focusContrast = await locator.evaluate((element) => {
        const parse = (value) => {
            const channels = value.match(/[\d.]+/g)?.map(Number) ?? [];
            return channels.length >= 3 ? { r: channels[0], g: channels[1], b: channels[2], alpha: channels.length > 3 ? channels[3] : 1 } : null;
        };
        const luminanceOf = (color) => [color.r, color.g, color.b].map((channel) => {
            const normalized = channel / 255;
            return normalized <= 0.03928 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4;
        }).reduce((total, channel, index) => total + channel * [0.2126, 0.7152, 0.0722][index], 0);
        const getContrast = (a, b) => {
            const [light, dark] = [luminanceOf(a), luminanceOf(b)].sort((first, second) => second - first);
            return (light + 0.05) / (dark + 0.05);
        };
        const flatten = (foreground, background) => ({
            r: foreground.r * foreground.alpha + background.r * (1 - foreground.alpha),
            g: foreground.g * foreground.alpha + background.g * (1 - foreground.alpha),
            b: foreground.b * foreground.alpha + background.b * (1 - foreground.alpha),
            alpha: 1,
        });
        const backgroundFor = (node) => {
            let current = node;
            while (current) {
                const color = parse(getComputedStyle(current).backgroundColor);
                if (color && color.alpha > 0) return color;
                current = current.parentElement;
            }
            return parse(getComputedStyle(document.body).backgroundColor);
        };
        const style = getComputedStyle(element);
        const background = backgroundFor(element);
        const outline = parse(style.outlineColor);
        const shadow = (style.boxShadow.match(/rgba?\([^)]*\)/g) ?? []).map(parse).find(Boolean);
        return { ratio: outline ? getContrast(flatten(outline, background), background) : (shadow ? getContrast(flatten(shadow, background), background) : 0) };
    });
    expect(focusContrast.ratio, `${description} focus indicator should have 3:1 contrast`).toBeGreaterThanOrEqual(3);
}

async function assertLiveRegions(page) {
    const invalid = await page.evaluate(() => [...document.querySelectorAll('[role="status"], [role="alert"], [aria-live]')]
        .filter((element) => !element.hasAttribute('aria-live'))
        .map((element) => element.outerHTML.slice(0, 200)));
    expect(invalid, 'feedback/loading live regions need aria-live').toEqual([]);

    if (await page.locator('[data-admin-form-state]').count() > 0) {
        await expect(page.locator('[data-admin-form-state] [role="status"][aria-live]').first()).toBeAttached();
    }
    if (await page.locator('[data-bulk-live-result], [data-bulk-live-error]').count() > 0) {
        await expect(page.locator('[data-bulk-live-result][aria-live], [data-bulk-live-error][aria-live]').first()).toBeAttached();
    }
}

async function assertOperationalContrast(page, theme) {
    const assessment = await assessContrast(
        page,
        'h1, h2, label, button, a, input, select, textarea, [role="status"], [role="alert"]',
    );
    const failures = assessment.filter(({ ratio }) => ratio < 4.5);
    expect(failures, `${theme} operational text/control contrast must meet WCAG AA`).toEqual([]);
}

async function assertNativeActivation(page) {
    const toggle = page.locator('.admin-shell__theme-toggle');
    await expect(toggle).toHaveAccessibleName(/پوسته/);
    const before = await page.locator('html').getAttribute('data-admin-theme');
    await focusWithKeyboard(page, toggle);
    await page.keyboard.press('Enter');
    await expect(page.locator('html')).toHaveAttribute('data-admin-theme', before === 'dark' ? 'glass' : 'dark');
    await toggle.focus();
    await page.keyboard.press('Space');
    await expect(page.locator('html')).toHaveAttribute('data-admin-theme', before);
}

async function assertDialogContract(page) {
    const trigger = page.locator('button[x-on*="open-modal"]').first();
    if (await trigger.count() === 0) return;

    await trigger.focus();
    await page.keyboard.press('Enter');
    const dialog = page.locator('.ui-modal:visible').first();
    await expect(dialog).toBeVisible();
    await expect(dialog).toHaveAttribute('role', 'dialog');
    await expect(dialog).toHaveAttribute('aria-modal', 'true');
    await expect(dialog).toHaveAttribute('aria-labelledby', /\S/);
    await expect(dialog).toHaveAttribute('aria-describedby', /\S/);
    await expect.poll(() => dialog.evaluate((element) => element.contains(document.activeElement))).toBe(true);

    for (let index = 0; index < 5; index += 1) {
        await page.keyboard.press('Tab');
        expect(await dialog.evaluate((element) => element.contains(document.activeElement)), 'dialog focus must remain trapped').toBe(true);
    }

    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(trigger).toBeFocused();
}

test.describe('admin operational accessibility task 14.5', () => {
    test.skip(!hasRuntime, 'Set TEST_ADMIN_PHONE and TEST_ADMIN_PASSWORD for authenticated browser verification.');
    test.describe.configure({ mode: 'serial', timeout: 120_000 });
    test.use({ viewport: { width: 1366, height: 900 } });

    for (const theme of themes) {
        test(`verifies keyboard, focus, dialog, live-region, naming, and contrast contracts in ${theme}`, async ({ page }) => {
            await loginAsAdmin(page);

            for (const surface of surfaces) {
                await test.step(`${surface.name} (${theme})`, async () => {
                    await openSurface(page, surface, theme);
                    await assertAccessibleNamesAndRoles(page);
                    await assertKeyboardOrder(page);
                    await assertLiveRegions(page);
                    await assertOperationalContrast(page, theme);

                    const firstNavLink = page.locator('.admin-navigation__link, .admin-navigation__sublink').filter({ visible: true }).first();
                    const search = page.locator('.admin-shell__search-input');
                    await assertFocusRing(page, firstNavLink, `${surface.name} navigation`);
                    await assertFocusRing(page, search, `${surface.name} search`);
                    await assertNativeActivation(page);

                    if (surface.name === 'students-list') {
                        await assertDialogContract(page);
                    }
                });
            }
        });
    }
});
