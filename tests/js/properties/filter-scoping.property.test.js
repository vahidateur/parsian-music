import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import { authenticate } from '../support/auth-helper.js';
import { eventsFrom, requestCalendarEvents } from '../support/request-generators.js';
import { addDays, MAX_RANGE_DAYS, toIsoDate } from '../support/date-generators.js';

// Feature: admin-calendar-module, Property 7: Filter scoping correctness
// **Validates: Requirements 4.3**

const PAGE_URL = process.env.CALENDAR_PAGE_URL ?? 'http://127.0.0.1:8000/admin/calendar';
const BASE_URL = process.env.TEST_BASE_URL ?? 'http://127.0.0.1:8000';
const TEST_NAME = 'scopes each independent filter to matching events without applying absent filters';
const QUARANTINE = Object.freeze({
    identifier: 'Q-ADMIN-CALENDAR-FILTER-SCOPING-ENV',
    reason: 'Requires TEST_ADMIN_PHONE, TEST_ADMIN_PASSWORD, and a reachable calendar base URL.',
    gapReference: 'admin-operational-ux-baseline#3.4',
});
const ENVIRONMENT_PROBE_TIMEOUT_MS = 2_000;
const PROPERTY_RUNS = 100;
const MAX_IDENTITIES_PER_FILTER = 6;
const MAX_WINDOWS = 6;
const WINDOW_DAYS = 14;
const PROBE_CHUNKS_BACK = 4;
const PROBE_CHUNKS_FORWARD = 4;

// Each filter maps to the event field the API contract says it must scope.
const FILTER_DEFINITIONS = [
    { key: 'teacher_id', eventField: 'teacherName' },
    { key: 'student_id', eventField: 'studentName' },
    { key: 'room', eventField: 'room' },
    { key: 'instrument_id', eventField: 'instrumentName' },
];

const HTML_ENTITIES = {
    '&amp;': '&',
    '&lt;': '<',
    '&gt;': '>',
    '&quot;': '"',
    '&#039;': "'",
    '&#39;': "'",
    '&nbsp;': ' ',
};

const decodeHtml = (value) => value
    .replace(/&amp;|&lt;|&gt;|&quot;|&#0?39;|&nbsp;/g, (entity) => HTML_ENTITIES[entity] ?? entity)
    .trim();

const parseSelectOptions = (html, filterKey) => {
    const select = new RegExp(`data-calendar-filter="${filterKey}"[\\s\\S]*?</select>`).exec(html);

    assert.ok(select, `Could not locate the ${filterKey} filter select on the calendar page`);

    const options = [];
    const optionPattern = /<option[^>]*value="([^"]*)"[^>]*>([\s\S]*?)<\/option>/g;
    let match = optionPattern.exec(select[0]);

    while (match !== null) {
        const value = decodeHtml(match[1]);
        const label = decodeHtml(match[2]);

        if (value !== '' && label !== '') {
            options.push({ value, label });
        }

        match = optionPattern.exec(select[0]);
    }

    return options;
};

// Two identities sharing a display name are indistinguishable in the event
// payload, so only unambiguous labels can assert scoping.
const uniqueLabelOptions = (options) => {
    const labelCounts = options.reduce((counts, option) => {
        counts.set(option.label, (counts.get(option.label) ?? 0) + 1);
        return counts;
    }, new Map());

    return options.filter((option) => labelCounts.get(option.label) === 1);
};

const sortedIds = (events) => events.map((event) => event.id).sort((a, b) => a - b);

const isUrlReachable = async (url) => {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), ENVIRONMENT_PROBE_TIMEOUT_MS);

    try {
        const response = await fetch(url, {
            headers: { Accept: 'text/html' },
            redirect: 'manual',
            signal: controller.signal,
        });

        await response.body?.cancel();
        return true;
    } catch {
        return false;
    } finally {
        clearTimeout(timeout);
    }
};

// The test needs the login base URL (authentication) and the calendar page URL
// (filter option markup); either one being unreachable makes the run impossible.
const isEnvironmentReachable = async () => {
    const probes = await Promise.all([`${BASE_URL}/login`, PAGE_URL].map(isUrlReachable));

    return probes.every(Boolean);
};

const hasCredentials = Boolean(process.env.TEST_ADMIN_PHONE && process.env.TEST_ADMIN_PASSWORD);
const shouldQuarantine = !hasCredentials || !(await isEnvironmentReachable());

// Stable skip inventory in the default runner output: identifier, reason, gap.
if (shouldQuarantine) {
    process.stdout.write(
        `quarantine: ${QUARANTINE.identifier} | test: ${TEST_NAME} | reason: ${QUARANTINE.reason} | gap: ${QUARANTINE.gapReference}\n`,
    );
}

let sessionCookie;

const fetchEvents = async (query) => {
    const { response, body } = await requestCalendarEvents(query, { cookie: sessionCookie });

    assert.equal(response.status, 200, `Expected 200 for ${JSON.stringify(query)}`);

    const events = eventsFrom(body);

    assert.ok(Array.isArray(events), 'Expected an events collection payload');

    return events;
};

/**
 * Probe wide chunks around today to locate the seeded session dates, then build
 * a handful of small windows that actually contain events.
 */
const discoverWindows = async () => {
    const today = toIsoDate(new Date());
    const sessionDates = new Set();

    for (let chunk = -PROBE_CHUNKS_BACK; chunk <= PROBE_CHUNKS_FORWARD; chunk += 1) {
        const start = addDays(today, chunk * MAX_RANGE_DAYS);
        const events = await fetchEvents({ start, end: addDays(start, MAX_RANGE_DAYS - 1) });

        events.forEach((event) => sessionDates.add(event.extendedProps.session_date));
    }

    const dates = [...sessionDates].sort();

    assert.ok(dates.length > 0, 'No seeded sessions were found near the current date');

    const windows = [];

    dates.forEach((date) => {
        const last = windows.at(-1);

        if (windows.length < MAX_WINDOWS && (last === undefined || date > last.end)) {
            windows.push({ start: date, end: addDays(date, WINDOW_DAYS - 1) });
        }
    });

    return windows;
};

test(TEST_NAME, { skip: shouldQuarantine ? QUARANTINE.reason : false }, async () => {
    sessionCookie = await authenticate();

    const pageResponse = await fetch(PAGE_URL, {
        headers: { Accept: 'text/html', Cookie: sessionCookie },
        redirect: 'manual',
    });

    assert.equal(pageResponse.status, 200, 'Expected the calendar page to render for an admin');

    const html = await pageResponse.text();
    const windows = await discoverWindows();
    const baselines = new Map();

    for (const window of windows) {
        baselines.set(window.start, await fetchEvents(window));
    }

    const baselineEvents = [...baselines.values()].flat();

    // Only identities that actually appear in the seeded windows can be asserted.
    const cases = FILTER_DEFINITIONS.flatMap(({ key, eventField }) => {
        const observed = new Set(baselineEvents.map((event) => event[eventField]));
        const identities = uniqueLabelOptions(parseSelectOptions(html, key))
            .filter((option) => observed.has(option.label))
            .slice(0, MAX_IDENTITIES_PER_FILTER);

        assert.ok(identities.length > 0, `No usable ${key} identities were found in the seeded sessions`);

        return identities.map((identity) => ({ key, eventField, identity }));
    });

    await fc.assert(
        fc.asyncProperty(
            fc.constantFrom(...cases),
            fc.constantFrom(...windows),
            async ({ key, eventField, identity }, window) => {
                const events = await fetchEvents({ ...window, [key]: identity.value });

                events.forEach((event) => {
                    assert.equal(
                        event[eventField],
                        identity.label,
                        `Filter ${key}=${identity.value} returned a non-matching ${eventField}`,
                    );
                });

                // Absent filters must not narrow the result: the response has to
                // equal the baseline restricted to the requested dimension only.
                const expected = sortedIds(
                    baselines.get(window.start).filter((event) => event[eventField] === identity.label),
                );

                assert.deepEqual(
                    sortedIds(events),
                    expected,
                    `Filter ${key}=${identity.value} did not scope to the requested dimension alone`,
                );
            },
        ),
        { numRuns: PROPERTY_RUNS },
    );
});
