import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import initFilters from '../../../resources/js/calendar/filters.js';

// Feature: admin-calendar-module, Property 10: Active filter count
// **Validates: Requirements 7.5**

const FILTER_KEYS = ['teacher_id', 'student_id', 'room', 'instrument_id'];
const PROPERTY_RUNS = 100;

const filterStateArbitrary = fc.record(
    Object.fromEntries(FILTER_KEYS.map((key) => [key, fc.boolean()])),
).map((flags) => Object.fromEntries(
    FILTER_KEYS.map((key) => [key, flags[key] ? `${key}-selected` : '']),
));

test('counts exactly the non-default filters from zero through four', () => {
    fc.assert(
        fc.property(filterStateArbitrary, (filterState) => {
            const filters = initFilters(null, { debounceMs: 0 });

            try {
                filters.setFilters(filterState);

                const expectedCount = FILTER_KEYS.reduce(
                    (count, key) => count + (filterState[key] === '' ? 0 : 1),
                    0,
                );

                assert.equal(filters.getActiveFilterCount(), expectedCount);
                assert.equal(filters.countActiveFilters(), expectedCount);
                assert.equal(filters.activeFilterCount, expectedCount);
                assert.equal(Number.isInteger(filters.activeFilterCount), true);
                assert.ok(filters.activeFilterCount >= 0 && filters.activeFilterCount <= 4);
            } finally {
                filters.destroy();
            }
        }),
        { numRuns: PROPERTY_RUNS },
    );
});
