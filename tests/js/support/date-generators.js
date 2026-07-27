/**
 * Reusable fast-check date generators for the admin calendar module.
 *
 * All generated values use Western digits and the `Y-m-d` / `HH:MM(:SS)`
 * formats the calendar API and Jalali helpers expect.
 */

import fc from 'fast-check';

export const DAY_MS = 24 * 60 * 60 * 1000;
export const MAX_RANGE_DAYS = 92;

const dayNumber = (year, month, day) => Math.floor(Date.UTC(year, month, day) / DAY_MS);

export const DEFAULT_MIN_DAY = dayNumber(2000, 0, 1);
export const DEFAULT_MAX_DAY = dayNumber(2035, 11, 31);

/** Format a `Date` as an ISO `Y-m-d` string. */
export const toIsoDate = (date) => date.toISOString().slice(0, 10);

/** Convert a UTC day number into an ISO `Y-m-d` string. */
export const dayToIsoDate = (day) => toIsoDate(new Date(day * DAY_MS));

/** Add whole days to an ISO date string. */
export const addDays = (isoDate, days) => dayToIsoDate(
    Math.floor(Date.parse(`${isoDate}T00:00:00Z`) / DAY_MS) + days,
);

/** Inclusive day span between two ISO dates. */
export const inclusiveSpanInDays = (start, end) => (
    (Date.parse(`${end}T00:00:00Z`) - Date.parse(`${start}T00:00:00Z`)) / DAY_MS + 1
);

/** Arbitrary valid ISO `Y-m-d` date. */
export const isoDateArbitrary = ({ minDay = DEFAULT_MIN_DAY, maxDay = DEFAULT_MAX_DAY } = {}) => (
    fc.integer({ min: minDay, max: maxDay }).map(dayToIsoDate)
);

/** Arbitrary 24-hour `HH:MM` time with Western digits. */
export const timeArbitrary = () => fc.record({
    hour: fc.integer({ min: 0, max: 23 }),
    minute: fc.integer({ min: 0, max: 59 }),
}).map(({ hour, minute }) => [hour, minute].map((part) => String(part).padStart(2, '0')).join(':'));

/** Arbitrary 24-hour `HH:MM:SS` time with Western digits. */
export const timeWithSecondsArbitrary = () => fc.record({
    hour: fc.integer({ min: 0, max: 23 }),
    minute: fc.integer({ min: 0, max: 59 }),
    second: fc.integer({ min: 0, max: 59 }),
}).map(({ hour, minute, second }) => (
    [hour, minute, second].map((part) => String(part).padStart(2, '0')).join(':')
));

/** Arbitrary session duration in minutes (1 to 480). */
export const durationMinutesArbitrary = () => fc.integer({ min: 1, max: 480 });

/** Arbitrary valid `{ start, end }` range within the inclusive maximum span. */
export const validRangeArbitrary = ({ maxSpanDays = MAX_RANGE_DAYS, ...bounds } = {}) => fc.tuple(
    isoDateArbitrary(bounds),
    fc.integer({ min: 0, max: maxSpanDays - 1 }),
).map(([start, offset]) => ({ start, end: addDays(start, offset) }));

/** Arbitrary reversed `{ start, end }` range where start is strictly after end. */
export const reversedRangeArbitrary = ({ maxGapDays = 365, ...bounds } = {}) => fc.tuple(
    isoDateArbitrary(bounds),
    fc.integer({ min: 1, max: maxGapDays }),
).map(([start, gap]) => ({ start, end: addDays(start, -gap) }));

/** Arbitrary `{ start, end }` range whose inclusive span exceeds the maximum. */
export const oversizedRangeArbitrary = ({ maxSpanDays = MAX_RANGE_DAYS, maxExtraDays = 365, ...bounds } = {}) => fc.tuple(
    isoDateArbitrary(bounds),
    fc.integer({ min: 0, max: maxExtraDays }),
).map(([start, extra]) => ({ start, end: addDays(start, maxSpanDays + extra) }));

/** Arbitrary malformed date value, covering empty, partial, and non-`Y-m-d` inputs. */
export const invalidDateArbitrary = () => fc.oneof(
    fc.constantFrom(''),
    fc.constantFrom('2', '20', '2026', '2026-', '2026-0', '2026-01'),
    fc.constantFrom('abc', 'not-a-date', '202x-01-15', '2026-aa-bb', '۲۰۲۶-۰۱-۱۵'),
    fc.constantFrom('2026--01-15', '2026-01--15', '2026-13-15', '2026-00-15', '2026-01-00', '2026-1-15'),
    fc.constantFrom('2026/01/15', '01-15-2026', '20260115', '2026-01-15T00:00:00Z', '2026-W03-4'),
);
