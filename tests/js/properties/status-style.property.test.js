import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import {
    STATUS_BADGE_CLASSES,
    VALID_STATUSES,
    getStatusBadgeClass,
} from '../../../resources/js/calendar/drawer.js';

// Feature: admin-calendar-module, Property 9: Status-to-style mapping completeness
// **Validates: Requirements 5.6, 6.4**

const statusArbitrary = fc.constantFrom(...VALID_STATUSES);

const EXPECTED_STATUS_CLASSES = {
    scheduled: STATUS_BADGE_CLASSES.scheduled,
    completed: STATUS_BADGE_CLASSES.completed,
    cancelled: STATUS_BADGE_CLASSES.cancelled,
    missed: STATUS_BADGE_CLASSES.missed,
};

test('maps every SessionStatus value to its defined non-empty style class', () => {
    assert.deepEqual(VALID_STATUSES, ['scheduled', 'completed', 'cancelled', 'missed']);

    fc.assert(
        fc.property(statusArbitrary, (status) => {
            const styleClass = getStatusBadgeClass(status);

            assert.equal(styleClass, EXPECTED_STATUS_CLASSES[status]);
            assert.equal(typeof styleClass, 'string');
            assert.ok(styleClass.length > 0);
            assert.equal(styleClass.includes('undefined'), false);
        }),
        { numRuns: 100 },
    );
});
