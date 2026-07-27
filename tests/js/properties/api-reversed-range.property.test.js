import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import { authenticate } from '../support/auth-helper.js';

// Feature: admin-calendar-module, Property 5: API validation rejects reversed date range
// **Validates: Requirements 4.7**

const API_URL = process.env.CALENDAR_API_URL ?? 'http://127.0.0.1:8000/admin/calendar/events';
const DAY_MS = 24 * 60 * 60 * 1000;
const MIN_END_DAY = Date.UTC(2020, 0, 1) / DAY_MS;
const MAX_END_DAY = Date.UTC(2030, 11, 31) / DAY_MS - 92;
const PROPERTY_RUNS = 100;

const dateFromDayNumber = (dayNumber) => new Date(dayNumber * DAY_MS).toISOString().slice(0, 10);

const reversedDateRangeArbitrary = fc.record({
    endDay: fc.integer({ min: MIN_END_DAY, max: MAX_END_DAY }),
    dayGap: fc.integer({ min: 1, max: 92 }),
}).map(({ endDay, dayGap }) => ({
    start: dateFromDayNumber(endDay + dayGap),
    end: dateFromDayNumber(endDay),
}));

let sessionCookie;

test('rejects generated reversed date ranges with a start/end ordering validation error', async () => {
    sessionCookie = await authenticate();

    await fc.assert(
        fc.asyncProperty(
            reversedDateRangeArbitrary,
            async (dateRange) => {
                const params = new URLSearchParams({ start: dateRange.start, end: dateRange.end });
                const response = await fetch(`${API_URL}?${params.toString()}`, {
                    headers: {
                        Accept: 'application/json',
                        Cookie: sessionCookie,
                    },
                    redirect: 'manual',
                });

                let body;
                try {
                    body = await response.json();
                } catch {
                    body = null;
                }

                assert.equal(
                    response.status,
                    422,
                    `Expected 422 for reversed range start=${dateRange.start} end=${dateRange.end}, got ${response.status}`,
                );
                assert.ok(body && typeof body === 'object' && !Array.isArray(body));
                assert.ok(body.errors && typeof body.errors === 'object' && !Array.isArray(body.errors));
                assert.ok(Array.isArray(body.errors.end), 'Expected the ordering error on the end field');
                assert.ok(body.errors.end.length > 0, 'Expected a non-empty ordering validation error');
                assert.ok(
                    body.errors.end.some((message) => (
                        typeof message === 'string'
                        && /end\s+date\s+must\s+be\s+after\s+or\s+equal\s+to\s+the\s+start\s+date/i.test(message)
                    )),
                    `Expected the reversed-range validation error, received: ${JSON.stringify(body.errors.end)}`,
                );
            },
        ),
        { numRuns: PROPERTY_RUNS },
    );
});
