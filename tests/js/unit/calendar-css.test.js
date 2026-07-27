/**
 * Feature: admin-calendar-module — calendar CSS / design-system compliance.
 * **Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 9.1, 11.1, 11.2, 11.3, 11.4, 11.5, 12.5**
 *
 * The checks are static: the stylesheet contract is evaluated per viewport width by
 * resolving the cascade of `resources/css/admin/calendar.css` (media queries included),
 * together with the compiled Blade composition that carries the calendar class contract.
 */

import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../../');
const CSS_PATH = 'resources/css/admin/calendar.css';
const REQUIRED_VIEWPORTS = [390, 430, 768, 1024, 1366, 1600, 1920];
const MIN_HEIGHT_CONTRACT = 'calc(var(--space-10) * 16)';
const REDUCED_MOTION = '@media (prefers-reduced-motion: reduce)';
const COARSE_POINTER = '@media (pointer: coarse)';

const read = (relativePath) => fs.readFileSync(path.resolve(ROOT, relativePath), 'utf8');

const listCssFiles = (directory) => fs.readdirSync(path.resolve(ROOT, directory), { withFileTypes: true })
    .flatMap((entry) => (entry.isDirectory()
        ? listCssFiles(`${directory}/${entry.name}`)
        : [`${directory}/${entry.name}`]))
    .filter((file) => file.endsWith('.css'));

const stripComments = (css) => css.replace(/\/\*[\s\S]*?\*\//g, '');

/** Parses a stylesheet into flat rules, carrying the at-rule conditions of each rule. */
const parseRules = (css, conditions = []) => {
    const rules = [];
    let index = 0;

    while (index < css.length) {
        const open = css.indexOf('{', index);
        if (open === -1) break;

        const prelude = css.slice(index, open).trim();
        let depth = 1;
        let cursor = open + 1;
        while (cursor < css.length && depth > 0) {
            if (css[cursor] === '{') depth += 1;
            else if (css[cursor] === '}') depth -= 1;
            cursor += 1;
        }
        const body = css.slice(open + 1, cursor - 1);

        if (prelude.startsWith('@')) {
            rules.push(...parseRules(body, [...conditions, prelude.replace(/\s+/g, ' ')]));
        } else {
            const declarations = body.split(';')
                .map((declaration) => declaration.trim())
                .filter(Boolean)
                .map((declaration) => {
                    const separator = declaration.indexOf(':');
                    return {
                        property: declaration.slice(0, separator).trim(),
                        value: declaration.slice(separator + 1).trim(),
                    };
                })
                .filter((declaration) => declaration.property && declaration.value);

            rules.push({
                selectors: prelude.split(',').map((selector) => selector.trim().replace(/\s+/g, ' ')),
                declarations,
                conditions,
            });
        }

        index = cursor;
    }

    return rules;
};

/** Fine pointer, no reduced-motion preference: only width features can match. */
const conditionMatchesViewport = (condition, width) => {
    if (!condition.startsWith('@media')) return false;

    return [...condition.matchAll(/\(([^)]+)\)/g)]
        .map((match) => match[1].split(':').map((part) => part.trim()))
        .every(([feature, value]) => {
            if (feature === 'min-width') return width >= Number.parseInt(value, 10);
            if (feature === 'max-width') return width <= Number.parseInt(value, 10);
            return false;
        });
};

const activeRules = (rules, width) => rules.filter((rule) => rule.conditions
    .every((condition) => conditionMatchesViewport(condition, width)));

const resolve = (rules, selector, property, width) => {
    let resolved = null;
    activeRules(rules, width).forEach((rule) => {
        if (!rule.selectors.includes(selector)) return;
        rule.declarations.forEach((declaration) => {
            if (declaration.property === property) resolved = declaration.value;
        });
    });
    return resolved;
};

const rulesUnder = (rules, condition) => rules
    .filter((rule) => rule.conditions.includes(condition));

const declarationOf = (rules, condition, selector, property) => {
    let resolved = null;
    rulesUnder(rules, condition).forEach((rule) => {
        if (!rule.selectors.includes(selector)) return;
        rule.declarations.forEach((declaration) => {
            if (declaration.property === property) resolved = declaration.value;
        });
    });
    return resolved;
};

const classTokens = (selector) => [...selector.matchAll(/\.(-?[_a-zA-Z][\w-]*)/g)].map((match) => match[1]);

const source = stripComments(read(CSS_PATH));
const rules = parseRules(source);
const allDeclarations = rules.flatMap((rule) => rule.declarations);

test('ships a scoped calendar stylesheet using BEM selectors only', () => {
    assert.ok(fs.existsSync(path.resolve(ROOT, CSS_PATH)), `${CSS_PATH} must exist`);
    assert.ok(rules.length > 0, 'stylesheet must declare rules');

    const bem = /^calendar(?:-[a-z0-9]+)*(?:__[a-z0-9]+(?:-[a-z0-9]+)*)?(?:--[a-z0-9]+(?:-[a-z0-9]+)*)?$/;

    rules.forEach((rule) => rule.selectors.forEach((selector) => {
        classTokens(selector).forEach((className) => {
            if (className.startsWith('fc')) {
                assert.ok(
                    selector.startsWith('.calendar'),
                    `third-party selector must stay scoped to the calendar block: ${selector}`,
                );
                return;
            }
            assert.match(className, bem, `selector must follow BEM: ${selector}`);
        });
    }));

    assert.doesNotMatch(source, /!important/, 'calendar CSS must never use !important');
});

test('uses design-system tokens only, with no hardcoded colors or duplicate token definitions', () => {
    const withoutTokens = allDeclarations
        .map((declaration) => declaration.value.replace(/var\(\s*--[\w-]+\s*(,[^)]*)?\)/g, ' '));

    withoutTokens.forEach((value, position) => {
        const { property } = allDeclarations[position];
        assert.doesNotMatch(value, /#[0-9a-fA-F]{3,8}\b/, `hardcoded hex color in ${property}`);
        assert.doesNotMatch(value, /\b(?:rgba?|hsla?)\(/, `hardcoded color function in ${property}`);
        assert.doesNotMatch(
            value,
            /\b(?:white|black|red|green|blue|gray|grey|silver|orange|gold)\b/,
            `hardcoded color keyword in ${property}`,
        );
    });

    allDeclarations.forEach((declaration) => {
        assert.ok(
            !declaration.property.startsWith('--'),
            `calendar CSS must not define tokens: ${declaration.property}`,
        );
    });
    rules.forEach((rule) => assert.ok(
        !rule.selectors.includes(':root'),
        'calendar CSS must not open a :root token block',
    ));

    const definedTokens = new Set();
    listCssFiles('resources/css')
        .filter((file) => file !== CSS_PATH)
        .forEach((file) => {
            for (const match of read(file).matchAll(/(?:^|[;{\s])(--[\w-]+)\s*:/g)) definedTokens.add(match[1]);
        });

    const usedTokens = new Set([...source.matchAll(/var\(\s*(--[\w-]+)/g)].map((match) => match[1]));
    assert.ok(usedTokens.size > 0, 'calendar CSS must consume design-system tokens');
    [...usedTokens].forEach((token) => assert.ok(
        definedTokens.has(token),
        `token ${token} must be defined by the design system, not the calendar module`,
    ));

    allDeclarations.forEach(({ property, value }) => assert.doesNotMatch(
        value,
        /\b\d+(?:\.\d+)?px\b/,
        `raw px value in ${property} (only media conditions may use px)`,
    ));
});

test('references the required glass, button, shadow, typography, and motion tokens', () => {
    [
        '--glass-bg',
        '--glass-border',
        '--glass-blur',
        '--glass-overlay',
        '--admin-color-surface-glass',
        '--admin-surface-blur',
        '--admin-color-accent',
        '--admin-radius-control',
        '--admin-radius-card',
        '--shadow-sm',
        '--shadow-md',
        '--shadow-lg',
        '--shadow-input-focus',
        '--text-sm',
        '--text-base',
        '--text-lg',
        '--duration-fast',
        '--duration-normal',
        '--ease-standard',
        '--admin-touch-target',
        '--admin-z-dialog',
    ].forEach((token) => assert.match(source, new RegExp(`var\\(${token}\\b`), `missing token ${token}`));

    assert.equal(resolve(rules, '.calendar', 'font-family', 1366), 'inherit');
    assert.match(
        read('resources/css/app.css'),
        /font-family:\s*'Vazirmatn'/,
        'the inherited font stack must provide Vazirmatn',
    );
    assert.equal(resolve(rules, '.calendar .calendar-timegrid-event-content', 'background', 1366), 'var(--glass-bg)');
    assert.equal(resolve(rules, '.calendar-day-timeline', 'box-shadow', 1366), 'var(--shadow-md)');
    assert.equal(resolve(rules, '.calendar-drawer__panel', 'box-shadow', 1366), 'var(--shadow-lg)');

    rules
        .filter((rule) => !rule.conditions.includes(REDUCED_MOTION))
        .flatMap((rule) => rule.declarations)
        .filter((declaration) => declaration.property === 'transition')
        .forEach((declaration) => {
            assert.match(declaration.value, /var\(--duration-/, 'transitions must use duration tokens');
            assert.match(declaration.value, /var\(--ease-/, 'transitions must use easing tokens');
        });
});

test('applies the glass card contract to the calendar surfaces', () => {
    // 8.2 — main container plus the sidebar and filter panels share the glass treatment.
    ['.calendar-day-timeline', '.calendar-week-sidebar', '.calendar-filters'].forEach((selector) => {
        assert.equal(resolve(rules, selector, 'background', 1366), 'var(--admin-color-surface-glass)');
        assert.equal(
            resolve(rules, selector, 'backdrop-filter', 1366),
            'blur(var(--admin-surface-blur))',
            `${selector} must blur its glass surface`,
        );
        assert.equal(
            resolve(rules, selector, '-webkit-backdrop-filter', 1366),
            'blur(var(--admin-surface-blur))',
            `${selector} needs the prefixed blur fallback`,
        );
        assert.equal(resolve(rules, selector, 'border-radius', 1366), 'var(--admin-radius-card)');
        assert.match(
            resolve(rules, selector, 'border', 1366) ?? '',
            /var\(--ui-border-width\) solid var\(--admin-color-border\)/,
            `${selector} must use border tokens`,
        );
    });

    assert.equal(resolve(rules, '.calendar-drawer__panel', 'backdrop-filter', 1366), 'blur(var(--glass-blur))');
    assert.equal(resolve(rules, '.calendar .calendar-timegrid-event-content', 'border-radius', 1366), 'var(--radius-sm)');
    assert.match(
        resolve(rules, '.calendar .calendar-timegrid-event-content', 'border', 1366) ?? '',
        /var\(--glass-border\)/,
        'event cards must use the glass border token',
    );
});

test('styles slot labels, day headers, grid lines, and the toolbar with semantic and type-scale tokens', () => {
    // 8.3 / 8.4 — semantic color tokens and the required type-scale mapping.
    assert.equal(
        resolve(rules, '.calendar .fc .fc-timegrid-slot-label', 'color', 1366),
        'var(--admin-color-text-muted)',
    );
    assert.equal(resolve(rules, '.calendar .fc .fc-timegrid-slot-label', 'font-size', 1366), 'var(--text-sm)');
    assert.equal(resolve(rules, '.calendar .fc .fc-col-header-cell', 'color', 1366), 'var(--admin-color-text)');
    assert.equal(resolve(rules, '.calendar .fc .fc-col-header-cell', 'font-size', 1366), 'var(--text-base)');
    assert.equal(resolve(rules, '.calendar .fc .fc-toolbar-title', 'font-size', 1366), 'var(--text-lg)');
    assert.equal(resolve(rules, '.calendar .fc td', 'border-color', 1366), 'var(--admin-color-border)');

    // 8.5 — toolbar buttons follow the design-system button variant contract.
    assert.equal(resolve(rules, '.calendar .fc .fc-button', 'border-radius', 1366), 'var(--admin-radius-control)');
    assert.equal(
        resolve(rules, '.calendar .fc .fc-button-primary:not(:disabled).fc-button-active', 'background', 1366),
        'var(--admin-color-accent)',
    );
    const buttonTransition = resolve(rules, '.calendar .fc .fc-button', 'transition', 1366) ?? '';
    assert.match(buttonTransition, /var\(--duration-fast\)/);
    assert.match(buttonTransition, /var\(--ease-standard\)/);
    assert.equal(
        resolve(rules, '.calendar .fc .fc-button:focus-visible', 'box-shadow', 1366),
        'var(--shadow-input-focus)',
    );

    // 5.6 / 6.4 — every session status owns a distinct semantic border token, with no fallback.
    ['scheduled', 'completed', 'cancelled', 'missed'].forEach((status) => assert.match(
        resolve(rules, `.calendar .calendar-timegrid-event-content[data-status="${status}"]`, 'border-inline-start-color', 1366) ?? '',
        /^var\(--(?:info|success|error|warning)-\d{3}\)$/,
        `status ${status} must map to a semantic status token`,
    ));
});

test('uses logical properties for RTL spacing and no physical directional properties', () => {
    [
        'margin-inline',
        'padding-inline',
        'padding-block',
        'inset-inline',
        'inset-block',
        'border-inline-start',
        'border-block-end',
    ].forEach((property) => assert.match(source, new RegExp(`${property}[\\w-]*\\s*:`), `missing ${property}`));

    allDeclarations.forEach(({ property, value }) => {
        assert.doesNotMatch(property, /^(?:margin|padding|border)-(?:left|right)$/, `physical property ${property}`);
        assert.doesNotMatch(property, /^(?:left|right|top|bottom)$/, `physical offset ${property}`);
        assert.doesNotMatch(property, /^float$/, 'float must not be used for RTL layout');
        if (property === 'text-align') {
            assert.doesNotMatch(value, /^(?:left|right)$/, 'text-align must use logical start/end');
        }
    });

    assert.equal(resolve(rules, '.calendar__time-column', 'inset-inline-end', 1366), '0');
    assert.match(read('resources/views/components/calendar-layout.blade.php'), /dir="rtl"/);
});

test('disables calendar motion when reduced motion is preferred', () => {
    const reducedMotionRules = rulesUnder(rules, REDUCED_MOTION);
    assert.ok(reducedMotionRules.length > 0, 'a prefers-reduced-motion block is required');

    const selectors = reducedMotionRules.flatMap((rule) => rule.selectors);
    assert.ok(selectors.includes('.calendar *'), 'reduced motion must cover the calendar subtree');
    assert.ok(selectors.includes('.calendar-drawer *'), 'reduced motion must cover the drawer subtree');

    assert.equal(declarationOf(rules, REDUCED_MOTION, '.calendar *', 'transition-duration'), '0ms');
    assert.equal(declarationOf(rules, REDUCED_MOTION, '.calendar *', 'animation'), 'none');
});

test('adapts sidebar, filters, drawer, and touch targets at the responsive breakpoints', () => {
    // 11.1 — below 1024px the week sidebar becomes a horizontal strip above the timeline.
    assert.equal(resolve(rules, '.calendar-week-sidebar__days', 'display', 1023), 'flex');
    assert.equal(resolve(rules, '.calendar-week-sidebar__days', 'overflow-x', 1023), 'auto');
    assert.equal(resolve(rules, '.calendar__sidebar', 'order', 1023), '0');
    assert.equal(resolve(rules, '.calendar__timeline', 'order', 1023), '1');
    assert.equal(resolve(rules, '.calendar-week-sidebar__days', 'display', 1024), 'grid');

    // 11.3 — below 768px filters collapse behind a toggle and expand as an overlay.
    assert.equal(resolve(rules, '.calendar-filters__toggle', 'display', 767), 'inline-flex');
    assert.equal(resolve(rules, '.calendar-filters__toggle', 'display', 768), 'none');
    assert.equal(resolve(rules, '.calendar-filters__fields', 'position', 767), 'absolute');
    assert.equal(resolve(rules, '.calendar-filters__fields', 'z-index', 767), 'var(--admin-z-dropdown)');
    assert.equal(resolve(rules, '.calendar-filters__fields--collapsed', 'display', 767), 'none');

    // 11.2 — below 768px the drawer is a full-width bottom sheet with a drag handle.
    assert.equal(resolve(rules, '.calendar-drawer__panel', 'width', 767), '100%');
    assert.equal(resolve(rules, '.calendar-drawer__panel', 'max-height', 767), '80vh');
    assert.equal(resolve(rules, '.calendar-drawer__panel', 'inset-block-end', 767), '0');
    assert.equal(resolve(rules, '.calendar-drawer__handle', 'display', 767), 'block');
    assert.equal(resolve(rules, '.calendar-drawer__handle', 'display', 768), 'none');
    assert.equal(resolve(rules, '.calendar-drawer__panel', 'width', 1366), 'min(calc(var(--space-5) * 10), 100vw)');

    // 11.5 — coarse pointers get token-sized touch targets, including event-card slot height.
    const coarseSelectors = rulesUnder(rules, COARSE_POINTER).flatMap((rule) => rule.selectors);
    [
        '.calendar-week-sidebar__day-button',
        '.calendar-header__control',
        '.calendar-filters__toggle',
        '.calendar-filters__clear',
        '.calendar-filters__select',
        '.calendar-drawer__close',
        '.calendar-day-timeline__retry',
    ].forEach((selector) => assert.ok(coarseSelectors.includes(selector), `${selector} needs a coarse-pointer size`));

    assert.equal(
        declarationOf(rules, COARSE_POINTER, '.calendar .fc .fc-timegrid-slot', 'min-height'),
        'var(--admin-touch-target)',
    );
    assert.equal(
        declarationOf(rules, COARSE_POINTER, '.calendar-week-sidebar__day-button', 'min-width'),
        'var(--admin-touch-target)',
    );
});

test('keeps the compiled page free of horizontal overflow at every required viewport', () => {
    const tokenLengths = new Map();
    listCssFiles('resources/css').forEach((file) => {
        for (const match of read(file).matchAll(/(--[\w-]+)\s*:\s*(\d+(?:\.\d+)?)(px|rem)\s*;/g)) {
            tokenLengths.set(match[1], match[3] === 'rem' ? Number(match[2]) * 16 : Number(match[2]));
        }
    });

    const fixedLength = (value) => {
        if (/min\(|clamp\(|max\(|%|vw/.test(value)) return null;
        const token = value.match(/^var\(\s*(--[\w-]+)\s*\)$/);
        if (token) return tokenLengths.has(token[1]) ? tokenLengths.get(token[1]) : null;
        const length = value.match(/^(\d+(?:\.\d+)?)(px|rem)$/);
        if (!length) return null;
        return length[2] === 'rem' ? Number(length[1]) * 16 : Number(length[1]);
    };

    const overflowSafeChain = [
        '.calendar',
        '.calendar__workspace',
        '.calendar__content',
        '.calendar__sidebar',
        '.calendar__timeline',
        '.calendar-day-timeline',
        '.calendar-day-timeline__mount',
        '.calendar .fc',
    ];

    REQUIRED_VIEWPORTS.forEach((width) => {
        assert.equal(resolve(rules, '.calendar', 'overflow-x', width), 'clip', `root must clip overflow at ${width}px`);

        overflowSafeChain.forEach((selector) => assert.equal(
            resolve(rules, selector, 'min-width', width),
            '0',
            `${selector} must allow shrinking at ${width}px`,
        ));

        activeRules(rules, width).forEach((rule) => rule.declarations
            .filter((declaration) => ['width', 'min-width', 'max-width'].includes(declaration.property))
            .forEach((declaration) => {
                const length = fixedLength(declaration.value);
                if (length === null) return;
                assert.ok(
                    length <= width,
                    `${rule.selectors.join(', ')} sets ${declaration.property}: ${declaration.value} wider than ${width}px`,
                );
            }));
    });

    // The horizontally scrollable day strip contains its own scrolling instead of the page.
    assert.equal(resolve(rules, '.calendar-week-sidebar__days', 'overscroll-behavior-inline', 1023), 'contain');
    assert.equal(resolve(rules, '.calendar-week-sidebar__navigation', 'overflow', 1023), 'hidden');
});

test('preserves the calendar minimum-height contract at every required viewport', () => {
    const stableHeightSelectors = [
        '.calendar-day-timeline',
        '.calendar-day-timeline__mount',
        '.calendar .fc',
    ];

    REQUIRED_VIEWPORTS.forEach((width) => stableHeightSelectors.forEach((selector) => assert.equal(
        resolve(rules, selector, 'min-height', width),
        MIN_HEIGHT_CONTRACT,
        `${selector} must keep the reserved height at ${width}px`,
    )));

    ['.calendar-day-timeline__skeleton', '.calendar-day-timeline__empty', '.calendar-day-timeline__error']
        .forEach((selector) => assert.equal(
            resolve(rules, selector, 'min-height', 390),
            MIN_HEIGHT_CONTRACT,
            `${selector} must match the reserved height so loading states do not shift layout`,
        ));

    assert.equal(resolve(rules, '.calendar__timeline', 'min-height', 1366), MIN_HEIGHT_CONTRACT);
    assert.equal(resolve(rules, '.calendar .fc', 'height', 1366), null, 'FullCalendar height stays auto in JS config');

    // The compiled markup carries the classes that own the reserved dimensions.
    const timeline = read('resources/views/components/day-timeline.blade.php');
    ['calendar-day-timeline', 'calendar-day-timeline__mount', 'calendar-day-timeline__skeleton']
        .forEach((className) => assert.match(timeline, new RegExp(`class="[^"]*${className}`)));
    assert.match(read('resources/views/components/calendar-layout.blade.php'), /class="calendar"/);
});
