/**
 * Reusable request generators and helpers for the calendar events API.
 *
 * Environment variables:
 *   CALENDAR_API_URL — events endpoint (default: http://127.0.0.1:8000/admin/calendar/events)
 */

import fc from 'fast-check';

export const CALENDAR_API_URL = process.env.CALENDAR_API_URL
    ?? 'http://127.0.0.1:8000/admin/calendar/events';

export const FILTER_KEYS = ['teacher_id', 'student_id', 'room', 'instrument_id'];

/** Arbitrary combination of default/non-default states for the four filters. */
export const filterStateArbitrary = () => fc.record({
    teacher_id: fc.oneof(fc.constant(''), fc.integer({ min: 1, max: 500 }).map(String)),
    student_id: fc.oneof(fc.constant(''), fc.integer({ min: 1, max: 500 }).map(String)),
    room: fc.oneof(fc.constant(''), fc.constantFrom('A101', 'B202', 'Studio 3')),
    instrument_id: fc.oneof(fc.constant(''), fc.integer({ min: 1, max: 50 }).map(String)),
});

/** Arbitrary single filter key. */
export const filterKeyArbitrary = () => fc.constantFrom(...FILTER_KEYS);

/** Build a query string from a range plus optional filters, omitting empty values. */
export const buildEventQuery = ({ start, end, ...filters }) => {
    const params = new URLSearchParams();

    if (start !== undefined) params.set('start', start);
    if (end !== undefined) params.set('end', end);

    FILTER_KEYS.forEach((key) => {
        const value = filters[key];
        if (value !== undefined && value !== null && value !== '') {
            params.set(key, String(value));
        }
    });

    return params.toString();
};

/**
 * Request the events endpoint and return the response together with its parsed body.
 *
 * @param {object} query range and optional filter values
 * @param {object} [options]
 * @param {string} [options.cookie] cookie header from `authenticate()`
 */
export const requestCalendarEvents = async (query, { cookie } = {}) => {
    const headers = { Accept: 'application/json' };

    if (cookie) {
        headers.Cookie = cookie;
    }

    const response = await fetch(`${CALENDAR_API_URL}?${buildEventQuery(query)}`, {
        headers,
        credentials: 'include',
    });

    let body;
    try {
        body = await response.json();
    } catch {
        body = null;
    }

    return { response, body };
};

/** Extract the event array from an events endpoint payload. */
export const eventsFrom = (body) => {
    if (Array.isArray(body)) return body;

    return Array.isArray(body?.data) ? body.data : [];
};
