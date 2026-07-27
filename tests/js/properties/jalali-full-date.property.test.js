import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import {
    formatJalaliFullDate,
    getJalaliMonthName,
    getPersianDayName,
    toDate,
    toJalali,
} from '../../../resources/js/calendar/utils/jalali.js';

// Feature: admin-calendar-module, Property 14: Jalali full date format
// **Validates: Requirements 3.1**

const DAY_MS = 24 * 60 * 60 * 1000;
const MIN_DAY = Math.floor(Date.UTC(2000, 0, 1) / DAY_MS);
const MAX_DAY = Math.floor(Date.UTC(2035, 11, 31) / DAY_MS);
const PROPERTY_RUNS = 100;

const validDateArbitrary = fc.integer({ min: MIN_DAY, max: MAX_DAY }).map((dayNumber) => (
    new Date(dayNumber * DAY_MS).toISOString().slice(0, 10)
));

const PERSIAN_DAY_NAMES = [
    'شنبه',
    'یکشنبه',
    'دوشنبه',
    'سه‌شنبه',
    'چهارشنبه',
    'پنجشنبه',
    'جمعه',
];

const JALALI_MONTH_NAMES = [
    'فروردین',
    'اردیبهشت',
    'خرداد',
    'تیر',
    'مرداد',
    'شهریور',
    'مهر',
    'آبان',
    'آذر',
    'دی',
    'بهمن',
    'اسفند',
];

test('formats generated dates as ordered Persian weekday, day, month, and year components', () => {
    fc.assert(
        fc.property(validDateArbitrary, (dateValue) => {
            const date = toDate(dateValue);
            const { year, month, day } = toJalali(date);
            const components = formatJalaliFullDate(date).split(' ');

            assert.equal(components.length, 4, 'A full date must contain exactly four components');
            assert.ok(PERSIAN_DAY_NAMES.includes(components[0]));
            assert.equal(components[0], getPersianDayName(date));
            assert.match(components[1], /^\d{1,2}$/u);
            assert.equal(components[1], String(day));
            assert.ok(JALALI_MONTH_NAMES.includes(components[2]));
            assert.equal(components[2], getJalaliMonthName(month));
            assert.match(components[3], /^\d{4}$/u);
            assert.equal(components[3], String(year).padStart(4, '0'));
            assert.equal(/^[0-9]+$/u.test(components[1]), true);
            assert.equal(/^[0-9]{4}$/u.test(components[3]), true);
        }),
        { numRuns: PROPERTY_RUNS },
    );
});
