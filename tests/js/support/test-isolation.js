/**
 * Isolation hooks for the `node --test` runner.
 *
 * Preloaded with `node --test --import ./tests/js/support/test-isolation.js`, so
 * the hooks below apply to every JavaScript test file. They guarantee that a
 * test cannot change the result of another test through process environment
 * variables or shared module/global state (browser-shaped globals that modules
 * under test may install).
 *
 * Requirements: 1.6, 1.7, 1.8, 3.7
 */
import { afterEach, beforeEach } from 'node:test';

/**
 * Globals a module under test may define. Node does not own most of them, so
 * restoring them keeps DOM harness state from leaking between tests.
 */
const GUARDED_GLOBALS = [
    'window',
    'document',
    'navigator',
    'localStorage',
    'sessionStorage',
    'requestAnimationFrame',
    'cancelAnimationFrame',
    'Alpine',
    'fetch',
    'FullCalendar',
];

let envSnapshot = null;
let globalSnapshot = null;

const snapshotGlobals = () => {
    const snapshot = new Map();

    for (const key of GUARDED_GLOBALS) {
        snapshot.set(key, {
            present: key in globalThis,
            value: key in globalThis ? globalThis[key] : undefined,
        });
    }

    return snapshot;
};

const restoreGlobals = (snapshot) => {
    for (const [key, previous] of snapshot.entries()) {
        const presentNow = key in globalThis;

        if (!previous.present && presentNow) {
            try {
                delete globalThis[key];
            } catch {
                // Non-configurable global: nothing further to reset.
            }
            continue;
        }

        if (previous.present && (!presentNow || globalThis[key] !== previous.value)) {
            try {
                globalThis[key] = previous.value;
            } catch {
                // Accessor-backed global (e.g. navigator): already owned by Node.
            }
        }
    }
};

const restoreEnv = (snapshot) => {
    for (const key of Object.keys(process.env)) {
        if (!(key in snapshot)) {
            delete process.env[key];
        }
    }

    for (const [key, value] of Object.entries(snapshot)) {
        if (process.env[key] !== value) {
            process.env[key] = value;
        }
    }
};

beforeEach(() => {
    envSnapshot = { ...process.env };
    globalSnapshot = snapshotGlobals();
});

afterEach(() => {
    if (envSnapshot !== null) {
        restoreEnv(envSnapshot);
        envSnapshot = null;
    }

    if (globalSnapshot !== null) {
        restoreGlobals(globalSnapshot);
        globalSnapshot = null;
    }
});
