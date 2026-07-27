import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import { getPersianWeek, toDate, toIsoDate } from '../../../resources/js/calendar/utils/jalali.js';

// Feature: admin-calendar-module, Property 2: Persian week computation
// **Validates: Requirements 3.2, 3.7, 9.5**

const DAY_MS = 24 * 60 * 60 * 1000;
const MIN_DAY = Math.floor(Date.UTC(2000, 0, 1) / DAY_MS);
const MAX_DAY = Math.floor(Date.UTC(2035, 11, 31) / DAY_MS);
const PROPERTY_RUNS = 100;

const validDateArbitrary = fc.integer({ min: MIN_DAY, max: MAX_DAY }).map((dayNumber) => (
    new Date(dayNumber * DAY_MS).toISOString().slice(0, 10)
));

test('computes a seven-day Saturday-to-Friday Persian week containing the selected date', async () => {
    await fc.assert(
        fc.asyncProperty(validDateArbitrary, async (selectedDate) => {
            const week = getPersianWeek(selectedDate);

            assert.equal(week.length, 7);
            assert.equal(week[0].getDay(), 6, 'The Persian week must start on Saturday');
            assert.equal(week[6].getDay(), 5, 'The Persian week must end on Friday');

            const weekDates = week.map(toIsoDate);
            assert.equal(new Set(weekDates).size, 7, 'The week must contain seven distinct dates');
            assert.ok(weekDates.includes(toIsoDate(toDate(selectedDate))));

            for (let index = 1; index < week.length; index += 1) {
                const expectedNextDate = new Date(week[index - 1].getTime());
                expectedNextDate.setDate(expectedNextDate.getDate() + 1);
                assert.equal(weekDates[index], toIsoDate(expectedNextDate));
            }
        }),
        { numRuns: PROPERTY_RUNS },
    );
});
