import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import adminDateForm, {
    ADMIN_DATE_MAX_YEAR,
    ADMIN_DATE_MIN_YEAR,
    daysInMonth,
    gregorianToJalali,
    isValidGregorianDate,
    jalaliToGregorian,
} from '../../../resources/js/admin-date-form.js';
import {
    formatJalaliDate,
    toGregorian,
    toJalali,
} from '../../../resources/js/calendar/utils/jalali.js';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../../');
const CONSUMERS = [
    'resources/views/admin/teachers/create.blade.php',
    'resources/views/admin/teachers/edit.blade.php',
    'resources/views/admin/students/create.blade.php',
    'resources/views/admin/students/edit.blade.php',
    'resources/views/admin/sessions/create.blade.php',
];

const read = (relativePath) => fs.readFileSync(path.resolve(ROOT, relativePath), 'utf8');

test('initializes persisted edit dates and preserves old validation input', () => {
    const persisted = adminDateForm('hire_date', '2024-02-29');
    persisted.init();
    assert.deepEqual(
        { year: persisted.year, month: persisted.month, day: persisted.day },
        { year: '2024', month: '2', day: '29' },
    );
    assert.equal(persisted.isoValue, '2024-02-29');
    assert.equal(persisted.jalali, '1402/12/10');

    const oldInput = adminDateForm('join_date', '2026-07-14');
    oldInput.init();
    assert.equal(oldInput.isoValue, '2026-07-14');
    assert.equal(oldInput.fieldName, 'join_date');
});

test('keeps the hidden ISO value synchronized with valid date state', () => {
    const form = adminDateForm('session_date', '2025-03-20');
    form.init();
    assert.equal(form.isoValue, '2025-03-20');
    assert.equal(form.jalali, '1403/12/30');

    form.month = '2';
    form.day = '31';
    form.onDateChange();
    assert.equal(form.isoValue, '');
    assert.equal(form.jalali, '');
});

test('round-trips Gregorian and Jalali dates, including a leap date', () => {
    const cases = [
        [2025, 3, 20],
        [2024, 2, 29],
        [2099, 12, 31],
    ];

    cases.forEach(([year, month, day]) => {
        const jalali = gregorianToJalali(year, month, day).split('/').map(Number);
        assert.deepEqual(jalaliToGregorian(...jalali), { year, month, day });
        assert.deepEqual(toGregorian({ year: jalali[0], month: jalali[1], day: jalali[2] }), { year, month, day });
        assert.deepEqual(toJalali(`${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`), {
            year: jalali[0],
            month: jalali[1],
            day: jalali[2],
        });
    });
});

test('enforces year bounds and Gregorian month-length/leap-day behavior', () => {
    assert.equal(ADMIN_DATE_MIN_YEAR, 2010);
    assert.equal(ADMIN_DATE_MAX_YEAR, 2099);
    assert.equal(daysInMonth(2024, 2), 29);
    assert.equal(daysInMonth(2023, 2), 28);
    assert.equal(daysInMonth(2025, 4), 30);
    assert.equal(isValidGregorianDate(2024, 2, 29), true);
    assert.equal(isValidGregorianDate(2023, 2, 29), false);
    assert.equal(isValidGregorianDate(2025, 4, 31), false);
    assert.equal(isValidGregorianDate(2009, 12, 31), false);

    const form = adminDateForm('hire_date', '2025-01-01');
    form.init();
    form.year = '2000';
    form.padYear();
    assert.equal(form.year, String(ADMIN_DATE_MIN_YEAR));
    form.year = '2200';
    form.padYear();
    assert.equal(form.year, String(ADMIN_DATE_MAX_YEAR));
});

test('migrates all five consumers to one registered module and removes the legacy implementation', () => {
    CONSUMERS.forEach((consumer) => {
        const source = read(consumer);
        assert.match(source, /x-data="adminDateForm\(/u, consumer);
        assert.match(source, /:value="isoValue"/u, consumer);
        assert.match(source, /:max="daysInMonth\(\)"/u, consumer);
        assert.doesNotMatch(source, /x-data="dateForm\(/u, consumer);
        assert.doesNotMatch(source, /date-form-script/u, consumer);
    });

    const appSource = read('resources/js/app.js');
    assert.equal((appSource.match(/from '\.\/admin-date-form'/gu) || []).length, 1);
    assert.equal((appSource.match(/Alpine\.data\('adminDateForm'/gu) || []).length, 1);
    assert.equal(fs.existsSync(path.resolve(ROOT, 'resources/views/admin/partials/date-form-script.blade.php')), false);
});

test('preserves existing pure calendar Jalali formatting behavior', () => {
    assert.equal(formatJalaliDate('2025-03-20'), '1403/12/30');
    assert.equal(formatJalaliDate('2024-02-29'), '1402/12/10');
});
