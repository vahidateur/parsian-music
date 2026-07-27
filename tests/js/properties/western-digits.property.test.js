import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import {
    formatJalaliDate,
    formatJalaliFullDate,
    formatTime,
} from '../../../resources/js/calendar/utils/jalali.js';

// Feature: admin-calendar-module, Property 11: Western digit output in date/time formatting
// **Validates: Requirements 9.2, 9.4**

const DAY_MS = 24 * 60 * 60 * 1000;
const MIN_DAY = Math.floor(Date.UTC(1900, 0, 1) / DAY_MS);
const MAX_DAY = Math.floor(Date.UTC(2100, 11, 31) / DAY_MS);
const PROPERTY_RUNS = 100;
const NON_WESTERN_DIGIT_PATTERN = /[۰-۹٠-٩]/u;
const JALALI_DATE_PATTERN = /^\d{4}\/\d{2}\/\d{2}$/u;
const TIME_PATTERN = /^\d{2}:\d{2}$/u;

const validDateArbitrary = fc.integer({ min: MIN_DAY, max: MAX_DAY }).map((dayNumber) => (
    new Date(dayNumber * DAY_MS).toISOString().slice(0, 10)
));

const validTimeArbitrary = fc.record({
    hour: fc.integer({ min: 0, max: 23 }),
    minute: fc.integer({ min: 0, max: 59 }),
}).map(({ hour, minute }) => (
    `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`
));

test('formats generated dates and times with Western digits only', () => {
    fc.assert(
        fc.property(validDateArbitrary, validTimeArbitrary, (date, time) => {
            const jalaliDate = formatJalaliDate(date);
            const jalaliFullDate = formatJalaliFullDate(date);
            const formattedTime = formatTime(time);

            assert.match(jalaliDate, JALALI_DATE_PATTERN);
            assert.match(formattedTime, TIME_PATTERN);
            assert.equal(NON_WESTERN_DIGIT_PATTERN.test(jalaliDate), false);
            assert.equal(NON_WESTERN_DIGIT_PATTERN.test(jalaliFullDate), false);
            assert.equal(NON_WESTERN_DIGIT_PATTERN.test(formattedTime), false);
        }),
        { numRuns: PROPERTY_RUNS },
    );
});
