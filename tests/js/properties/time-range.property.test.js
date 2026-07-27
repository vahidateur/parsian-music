import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import { formatTimeRange } from '../../../resources/js/calendar/utils/jalali.js';

// Feature: admin-calendar-module, Property 8: Time range formatting
// **Validates: Requirements 5.3, 9.4**

const MINUTES_PER_DAY = 24 * 60;
const TIME_RANGE_PATTERN = /^\d{2}:\d{2}–\d{2}:\d{2}$/;

const timeArbitrary = fc.record({
    hour: fc.integer({ min: 0, max: 23 }),
    minute: fc.integer({ min: 0, max: 59 }),
}).map(({ hour, minute }) => ({
    value: `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`,
    totalMinutes: (hour * 60) + minute,
}));

const formatExpectedTime = (totalMinutes) => {
    const normalizedMinutes = ((totalMinutes % MINUTES_PER_DAY) + MINUTES_PER_DAY) % MINUTES_PER_DAY;
    const hour = Math.floor(normalizedMinutes / 60);
    const minute = normalizedMinutes % 60;

    return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
};

test('formats generated start times and durations as a correct Western-digit 24-hour range', () => {
    fc.assert(
        fc.property(
            timeArbitrary,
            fc.integer({ min: 1, max: 480 }),
            ({ value: startTime, totalMinutes: startMinutes }, durationMinutes) => {
                const range = formatTimeRange(startTime, durationMinutes);
                const expectedStart = formatExpectedTime(startMinutes);
                const expectedEnd = formatExpectedTime(startMinutes + durationMinutes);

                assert.match(range, TIME_RANGE_PATTERN);
                assert.equal(range, `${expectedStart}–${expectedEnd}`);
                assert.equal(/[^0-9:–]/u.test(range), false);
            },
        ),
        { numRuns: 100 },
    );
});
