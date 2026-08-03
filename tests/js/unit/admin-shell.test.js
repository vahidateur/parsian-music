import test from 'node:test';
import assert from 'node:assert/strict';
import adminShell, { normalizeAdminTheme } from '../../../resources/js/admin-shell.js';

test('normalizes only the supported admin themes and safely falls back to dark', () => {
    assert.equal(normalizeAdminTheme('dark'), 'dark');
    assert.equal(normalizeAdminTheme('glass'), 'glass');
    assert.equal(normalizeAdminTheme(undefined), 'dark');
    assert.equal(normalizeAdminTheme('light'), 'dark');
    assert.equal(normalizeAdminTheme({}), 'dark');
});

test('uses a safe dark default when the marker and persisted state are missing or malformed', () => {
    const previousDocument = globalThis.document;
    const previousStorage = globalThis.localStorage;

    try {
        globalThis.document = { documentElement: { dataset: {} }, cookie: '' };
        globalThis.localStorage = { getItem: () => 'unsupported', setItem() {} };

        const state = adminShell();
        assert.doesNotThrow(() => state.init());
        assert.equal(state.theme, 'dark');
        assert.equal(globalThis.document.documentElement.dataset.adminTheme, 'dark');
    } finally {
        globalThis.document = previousDocument;
        globalThis.localStorage = previousStorage;
    }
});

test('toggles the existing marker without navigation and persists the selected theme', () => {
    const previousDocument = globalThis.document;
    const previousStorage = globalThis.localStorage;
    const storage = new Map();

    try {
        globalThis.document = { documentElement: { dataset: { adminTheme: 'dark' } }, cookie: '' };
        globalThis.localStorage = {
            getItem: (key) => storage.get(key) ?? null,
            setItem: (key, value) => storage.set(key, value),
        };

        const state = adminShell();
        state.init();
        state.toggleTheme();

        assert.equal(state.theme, 'glass');
        assert.equal(globalThis.document.documentElement.dataset.adminTheme, 'glass');
        assert.equal(storage.get('pmAdminTheme'), 'glass');
        assert.match(globalThis.document.cookie, /pm_admin_theme=glass/);
    } finally {
        globalThis.document = previousDocument;
        globalThis.localStorage = previousStorage;
    }
});
