/**
 * Canonical admin date-form Alpine component.
 * Responsibility: split Gregorian date input state and Jalali conversion only.
 * Business state for teachers, students, and sessions remains in its owner form.
 */

import { toGregorian, toJalali } from './calendar/utils/jalali.js';

export const ADMIN_DATE_MIN_YEAR = 2010;
export const ADMIN_DATE_MAX_YEAR = 2099;

const ISO_DATE_PATTERN = /^(\d{4})-(\d{2})-(\d{2})$/u;

export function daysInMonth(year, month) {
    if (!Number.isInteger(year) || !Number.isInteger(month) || month < 1 || month > 12) {
        return 31;
    }

    return new Date(year, month, 0).getDate();
}

export function isValidGregorianDate(year, month, day) {
    return Number.isInteger(year)
        && year >= ADMIN_DATE_MIN_YEAR
        && year <= ADMIN_DATE_MAX_YEAR
        && Number.isInteger(month)
        && month >= 1
        && month <= 12
        && Number.isInteger(day)
        && day >= 1
        && day <= daysInMonth(year, month);
}

export function formatJalaliDateParts(year, month, day) {
    const jalali = toJalali(`${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`);
    return `${jalali.year}/${String(jalali.month).padStart(2, '0')}/${String(jalali.day).padStart(2, '0')}`;
}

export function gregorianToJalali(year, month, day) {
    if (!isValidGregorianDate(year, month, day)) {
        return '';
    }

    return formatJalaliDateParts(year, month, day);
}

export function jalaliToGregorian(year, month, day) {
    return toGregorian({ year, month, day });
}

function parseInitialDate(initial) {
    if (typeof initial !== 'string') {
        return null;
    }

    const match = initial.match(ISO_DATE_PATTERN);
    if (!match) {
        return null;
    }

    return {
        year: match[1],
        month: String(Number(match[2])),
        day: String(Number(match[3])),
    };
}

function todayDateParts() {
    const today = new Date();
    const year = Math.min(Math.max(today.getFullYear(), ADMIN_DATE_MIN_YEAR), ADMIN_DATE_MAX_YEAR);

    return {
        year: String(year),
        month: String(today.getMonth() + 1),
        day: String(today.getDate()),
    };
}

/**
 * @param {string} fieldName Date field name used for owner-specific form binding.
 * @param {string} initial Initial Gregorian ISO date or an empty string.
 */
export default function adminDateForm(fieldName, initial = '') {
    return {
        fieldName,
        year: '',
        month: '',
        day: '',
        jalali: '',
        isoValue: '',

        init() {
            const initialParts = parseInitialDate(initial) || todayDateParts();
            this.year = initialParts.year;
            this.month = initialParts.month;
            this.day = initialParts.day;
            this.onDateChange();
        },

        padYear() {
            let year = Number.parseInt(this.year, 10) || 0;
            year = Math.min(Math.max(year, ADMIN_DATE_MIN_YEAR), ADMIN_DATE_MAX_YEAR);
            this.year = String(year);
            this.onDateChange();
        },

        daysInMonth() {
            return daysInMonth(Number(this.year), Number(this.month));
        },

        onDateChange() {
            const year = Number(this.year);
            const month = Number(this.month);
            const day = Number(this.day);

            if (!isValidGregorianDate(year, month, day)) {
                this.isoValue = '';
                this.jalali = '';
                return;
            }

            this.isoValue = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            this.jalali = gregorianToJalali(year, month, day);
        },

        toJalali(year, month, day) {
            return gregorianToJalali(Number(year), Number(month), Number(day));
        },

        toGregorian(year, month, day) {
            return jalaliToGregorian(Number(year), Number(month), Number(day));
        },
    };
}
