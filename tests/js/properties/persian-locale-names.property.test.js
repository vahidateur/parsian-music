import test from 'node:test';
import assert from 'node:assert/strict';
import {
    JALALI_MONTH_NAMES,
    PERSIAN_DAY_NAMES,
    getJalaliMonthName,
    getPersianDayName,
} from '../../../resources/js/calendar/utils/jalali.js';

// Feature: admin-calendar-module, Property 12: Persian locale name mapping
// **Validates: Requirements 9.3, 9.6**

const EXPECTED_PERSIAN_DAY_NAMES = [
    'شنبه',
    'یکشنبه',
    'دوشنبه',
    'سه‌شنبه',
    'چهارشنبه',
    'پنجشنبه',
    'جمعه',
];

const EXPECTED_JALALI_MONTH_NAMES = [
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

test('maps every Persian week day index to its canonical Persian name', () => {
    assert.deepEqual(PERSIAN_DAY_NAMES, EXPECTED_PERSIAN_DAY_NAMES);

    EXPECTED_PERSIAN_DAY_NAMES.forEach((expectedName, dayIndex) => {
        assert.equal(getPersianDayName(dayIndex), expectedName);
    });
});

test('maps every Jalali month number to its canonical Persian name', () => {
    assert.deepEqual(JALALI_MONTH_NAMES, EXPECTED_JALALI_MONTH_NAMES);

    EXPECTED_JALALI_MONTH_NAMES.forEach((expectedName, monthIndex) => {
        assert.equal(getJalaliMonthName(monthIndex + 1), expectedName);
    });
});
