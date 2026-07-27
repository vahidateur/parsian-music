/**
 * Pure Jalali date and calendar formatting helpers.
 *
 * Dates are handled in the local calendar timezone. No DOM, locale package,
 * sibling-module, or presentation-state dependency is required.
 */

export const PERSIAN_DAY_NAMES = Object.freeze([
    'شنبه',
    'یکشنبه',
    'دوشنبه',
    'سه‌شنبه',
    'چهارشنبه',
    'پنجشنبه',
    'جمعه',
]);

export const PERSIAN_SHORT_DAY_NAMES = Object.freeze([
    'ش',
    'ی',
    'د',
    'س',
    'چ',
    'پ',
    'ج',
]);

export const JALALI_MONTH_NAMES = Object.freeze([
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
]);

const PERSIAN_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
const ARABIC_DIGITS = '٠١٢٣٤٥٦٧٨٩';

function assertValidDate(date) {
    if (Number.isNaN(date.getTime())) {
        throw new TypeError('Expected a valid date.');
    }

    return date;
}

export function toDate(value) {
    if (value instanceof Date) {
        return assertValidDate(new Date(value.getTime()));
    }

    if (typeof value === 'number') {
        return assertValidDate(new Date(value));
    }

    if (typeof value !== 'string' || value.trim() === '') {
        throw new TypeError('Expected a Date, timestamp, or date string.');
    }

    const input = value.trim();
    const localDateMatch = input.match(/^(\d{4})-(\d{2})-(\d{2})(?:$|T|\s)/);

    if (localDateMatch) {
        const [, year, month, day] = localDateMatch;
        const date = new Date(Number(year), Number(month) - 1, Number(day));
        if (date.getFullYear() !== Number(year) || date.getMonth() !== Number(month) - 1 || date.getDate() !== Number(day)) {
            throw new TypeError('Expected a valid date.');
        }

        if (input.length > 10) {
            const timeMatch = input.match(/T(\d{2}):(\d{2})(?::(\d{2})(?:\.(\d+))?)?/);
            if (timeMatch) {
                date.setHours(Number(timeMatch[1]), Number(timeMatch[2]), Number(timeMatch[3] || 0), Number((timeMatch[4] || '').slice(0, 3).padEnd(3, '0')) || 0);
            }
        }

        return assertValidDate(date);
    }

    return assertValidDate(new Date(input));
}

export function toWesternDigits(value) {
    return String(value)
        .replace(/[۰-۹]/g, (digit) => String(PERSIAN_DIGITS.indexOf(digit)))
        .replace(/[٠-٩]/g, (digit) => String(ARABIC_DIGITS.indexOf(digit)));
}

export const formatWesternDigits = toWesternDigits;

/**
 * Convert a Gregorian date to the Jalali calendar.
 * @returns {{ year: number, month: number, day: number }}
 */
export function toJalali(value) {
    const date = toDate(value);
    const gy = date.getFullYear();
    const gm = date.getMonth() + 1;
    const gd = date.getDate();
    const gregorianMonthDays = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    const adjustedYear = gm > 2 ? gy + 1 : gy;
    let days = 355666 + (365 * gy) + Math.floor((adjustedYear + 3) / 4)
        - Math.floor((adjustedYear + 99) / 100) + Math.floor((adjustedYear + 399) / 400)
        + gd + gregorianMonthDays[gm - 1];
    let year = -1595 + (33 * Math.floor(days / 12053));

    days %= 12053;
    year += 4 * Math.floor(days / 1461);
    days %= 1461;

    if (days > 365) {
        year += Math.floor((days - 1) / 365);
        days = (days - 1) % 365;
    }

    if (days < 186) {
        return {
            year,
            month: 1 + Math.floor(days / 31),
            day: 1 + (days % 31),
        };
    }

    return {
        year,
        month: 7 + Math.floor((days - 186) / 30),
        day: 1 + ((days - 186) % 30),
    };
}

export const getJalaliDate = toJalali;

export function getPersianDayIndex(value) {
    if (Number.isInteger(value) && value >= 0 && value <= 6) {
        return value;
    }

    const date = value instanceof Date || typeof value === 'string' || typeof value === 'number'
        ? toDate(value)
        : null;

    if (date) {
        return (date.getDay() + 1) % 7;
    }

    throw new TypeError('Expected a valid date or a Persian day index from 0 to 6.');
}

export function getPersianDayName(value) {
    return PERSIAN_DAY_NAMES[getPersianDayIndex(value)];
}

export function getPersianShortDayName(value) {
    return PERSIAN_SHORT_DAY_NAMES[getPersianDayIndex(value)];
}

export function getJalaliMonthName(month) {
    if (!Number.isInteger(month) || month < 1 || month > 12) {
        return '';
    }

    return JALALI_MONTH_NAMES[month - 1];
}

export function getPersianWeek(value) {
    const selectedDate = toDate(value);
    const start = new Date(selectedDate.getTime());
    start.setDate(start.getDate() - getPersianDayIndex(selectedDate));

    return Array.from({ length: 7 }, (_, index) => {
        const date = new Date(start.getTime());
        date.setDate(start.getDate() + index);
        return date;
    });
}

export const getPersianWeekDates = getPersianWeek;
export const getWeekDates = getPersianWeek;

function pad(value, length = 2) {
    return String(value).padStart(length, '0');
}

export function formatJalaliDate(value) {
    const { year, month, day } = toJalali(value);
    return `${toWesternDigits(year)}/${pad(month)}/${pad(day)}`;
}

export const formatDate = formatJalaliDate;

export function formatJalaliDay(value) {
    return toWesternDigits(String(toJalali(value).day));
}

export function formatJalaliMonth(value) {
    const month = typeof value === 'number' && Number.isInteger(value) ? value : toJalali(value).month;
    return getJalaliMonthName(month);
}

export function formatJalaliYear(value) {
    return toWesternDigits(String(toJalali(value).year).padStart(4, '0'));
}

export function formatJalaliFullDate(value) {
    const date = toDate(value);
    return `${getPersianDayName(date)} ${formatJalaliDay(date)} ${formatJalaliMonth(date)} ${formatJalaliYear(date)}`;
}

export const formatFullDate = formatJalaliFullDate;

export function formatJalaliMonthYear(value) {
    return `${formatJalaliMonth(value)} ${formatJalaliYear(value)}`;
}

export function toIsoDate(value) {
    const date = toDate(value);
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function parseTime(value) {
    if (typeof value === 'string') {
        const normalizedValue = toWesternDigits(value.trim());
        const timeMatch = normalizedValue.match(/^(\d{1,2}):(\d{2})(?::\d{2})?/);
        if (timeMatch) {
            const hours = Number(timeMatch[1]);
            const minutes = Number(timeMatch[2]);
            if (hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59) {
                return (hours * 60) + minutes;
            }
        }
    }

    const date = toDate(value);
    return (date.getHours() * 60) + date.getMinutes();
}

export function formatTime(value) {
    const totalMinutes = parseTime(value);
    const minutesInDay = 24 * 60;
    const normalizedMinutes = ((totalMinutes % minutesInDay) + minutesInDay) % minutesInDay;
    const hours = Math.floor(normalizedMinutes / 60);
    const minutes = normalizedMinutes % 60;

    return `${pad(hours)}:${pad(minutes)}`;
}

export function formatTimeRange(start, durationMinutes) {
    if (!Number.isInteger(durationMinutes) || durationMinutes < 0) {
        throw new TypeError('Duration must be a non-negative integer.');
    }

    const startMinutes = parseTime(start);
    return `${formatTime(startMinutesToDate(startMinutes))}–${formatTime(startMinutesToDate(startMinutes + durationMinutes))}`;
}

function startMinutesToDate(totalMinutes) {
    const date = new Date(0);
    date.setHours(0, totalMinutes, 0, 0);
    return date;
}

export function addDays(value, amount) {
    if (!Number.isInteger(amount)) {
        throw new TypeError('Day offset must be an integer.');
    }

    const date = toDate(value);
    date.setDate(date.getDate() + amount);
    return date;
}

export function nextDay(value) {
    return addDays(value, 1);
}

export function previousDay(value) {
    return addDays(value, -1);
}

export const getNextDay = nextDay;
export const getPreviousDay = previousDay;

export default Object.freeze({
    PERSIAN_DAY_NAMES,
    PERSIAN_SHORT_DAY_NAMES,
    JALALI_MONTH_NAMES,
    toDate,
    toJalali,
    getJalaliDate,
    toWesternDigits,
    formatWesternDigits,
    getPersianDayIndex,
    getPersianDayName,
    getPersianShortDayName,
    getJalaliMonthName,
    getPersianWeek,
    getPersianWeekDates,
    getWeekDates,
    formatJalaliDate,
    formatDate,
    formatJalaliDay,
    formatJalaliMonth,
    formatJalaliYear,
    formatJalaliFullDate,
    formatFullDate,
    formatJalaliMonthYear,
    toIsoDate,
    formatTime,
    formatTimeRange,
    addDays,
    nextDay,
    previousDay,
    getNextDay,
    getPreviousDay,
});
