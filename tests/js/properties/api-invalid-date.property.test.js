import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';

// Feature: admin-calendar-module, Property 4: API validation rejects invalid date parameters

const API_URL = process.env.CALENDAR_API_URL ?? 'http://127.0.0.1:8000/admin/calendar/events';
const VALID_DATE = '2026-01-15';

const invalidDateArbitrary = fc.oneof(
    fc.constantFrom(''),
    fc.constantFrom('2', '20', '2026', '2026-', '2026-0', '2026-01'),
    fc.constantFrom('abc', 'not-a-date', '202x-01-15', '2026-aa-bb', '۲۰۲۶-۰۱-۱۵'),
    fc.constantFrom('2026--01-15', '2026-01--15', '2026-13-15', '2026-00-15', '2026-01-00', '2026-1-15'),
    fc.constantFrom('2026/01/15', '01-15-2026', '20260115', '2026-01-15T00:00:00Z', '2026-W03-4'),
);

const requestForInvalidDate = async (field, invalidValue) => {
    const params = new URLSearchParams({
        start: field === 'start' ? invalidValue : VALID_DATE,
        end: field === 'end' ? invalidValue : VALID_DATE,
    });

    const response = await fetch(`${API_URL}?${params.toString()}`, {
        headers: { Accept: 'application/json' },
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

test('rejects generated invalid start and end dates with field-specific 422 errors', async () => {
    await fc.assert(
        fc.asyncProperty(
            fc.constantFrom('start', 'end'),
            invalidDateArbitrary,
            async (field, invalidValue) => {
                const { response, body } = await requestForInvalidDate(field, invalidValue);

                assert.equal(response.status, 422);
                assert.ok(body && typeof body === 'object' && !Array.isArray(body));
                assert.ok(body.errors && typeof body.errors === 'object' && !Array.isArray(body.errors));
                assert.ok(Array.isArray(body.errors[field]), `Expected validation errors for ${field}`);
                assert.ok(body.errors[field].length > 0, `Expected a non-empty validation error for ${field}`);
            },
        ),
        { numRuns: 100 },
    );
});
