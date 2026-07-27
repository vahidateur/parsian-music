import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import { authenticate } from '../support/auth-helper.js';
import { requestCalendarEvents } from '../support/request-generators.js';
import { MAX_RANGE_DAYS, oversizedRangeArbitrary } from '../support/date-generators.js';

// Feature: admin-calendar-module, Property 6: API validation rejects oversized date range
// **Validates: Requirements 4.8**

const PROPERTY_RUNS = 100;

// Property 6 is defined on the difference between end and start, so the
// generated ranges start one day beyond the maximum accepted difference.
const rangeArbitrary = oversizedRangeArbitrary({ maxSpanDays: MAX_RANGE_DAYS + 1 });

let sessionCookie;

test('rejects generated date ranges exceeding 92 days with a maximum-range validation error', async () => {
    sessionCookie = await authenticate();

    await fc.assert(
        fc.asyncProperty(rangeArbitrary, async ({ start, end }) => {
            const { response, body } = await requestCalendarEvents(
                { start, end },
                { cookie: sessionCookie },
            );

            assert.equal(
                response.status,
                422,
                `Expected 422 for oversized range start=${start} end=${end}, got ${response.status}`,
            );
            assert.ok(body && typeof body === 'object' && !Array.isArray(body));
            assert.ok(body.errors && typeof body.errors === 'object' && !Array.isArray(body.errors));
            assert.ok(Array.isArray(body.errors.end), 'Expected the maximum-range error on the end field');
            assert.ok(body.errors.end.length > 0, 'Expected a non-empty maximum-range validation error');
            assert.ok(
                body.errors.end.some((message) => (
                    typeof message === 'string'
                    && /date\s+range\s+may\s+not\s+exceed\s+92\s+days/i.test(message)
                )),
                `Expected the maximum-range validation error, received: ${JSON.stringify(body.errors.end)}`,
            );
        }),
        { numRuns: PROPERTY_RUNS },
    );
});
