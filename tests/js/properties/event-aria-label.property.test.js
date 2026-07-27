import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import {
    getEventAriaLabel,
    STATUS_LABELS,
    VALID_STATUSES,
} from '../../../resources/js/calendar/drawer.js';

// Feature: admin-calendar-module, Property 13: Event aria-label format
// **Validates: Requirements 10.3**

const PROPERTY_RUNS = 100;
const statusArbitrary = fc.constantFrom(...VALID_STATUSES);
const studentNameArbitrary = fc.string({ minLength: 1, maxLength: 80 }).filter(
    (name) => name.trim() === name && !/\s/u.test(name),
);

test('constructs the exact student and status aria-label format for every valid status', () => {
    fc.assert(
        fc.property(studentNameArbitrary, statusArbitrary, (studentName, status) => {
            const ariaLabel = getEventAriaLabel(studentName, status);

            assert.equal(ariaLabel, `${studentName} – ${STATUS_LABELS[status]}`);
            assert.equal(ariaLabel.startsWith(`${studentName} – `), true);
            assert.equal(ariaLabel.endsWith(STATUS_LABELS[status]), true);
        }),
        { numRuns: PROPERTY_RUNS },
    );
});
