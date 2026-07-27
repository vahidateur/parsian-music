import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import { nextDay, previousDay, toIsoDate } from '../../../resources/js/calendar/utils/jalali.js';

// Feature: admin-calendar-module, Property 3: Day navigation correctness
// **Validates: Requirements 3.5**

const DAY_MS = 24 * 60 * 60 * 1000;
const MIN_DAY = Date.UTC(2000, 0, 1) / DAY_MS;
const MAX_DAY = Date.UTC(2030, 11, 31) / DAY_MS;
const PROPERTY_RUNS = 100;

const dateFromDayNumber = (dayNumber) => new Date(dayNumber * DAY_MS).toISOString().slice(0, 10);

const calendarDateArbitrary = fc.integer({ min: MIN_DAY + 1, max: MAX_DAY - 1 })
    .map((dayNumber) => ({
        dayNumber,
        date: dateFromDayNumber(dayNumber),
    }));

test('navigates generated calendar dates by exactly one day in each direction', () => {
    fc.assert(
        fc.property(calendarDateArbitrary, ({ dayNumber, date }) => {
            assert.equal(toIsoDate(nextDay(date)), dateFromDayNumber(dayNumber + 1));
            assert.equal(toIsoDate(previousDay(date)), dateFromDayNumber(dayNumber - 1));
        }),
        { numRuns: PROPERTY_RUNS },
    );
});
