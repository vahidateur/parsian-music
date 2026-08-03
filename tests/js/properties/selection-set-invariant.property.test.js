import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import bulkSelectionState, {
    BULK_HEADER_STATES,
    BULK_SELECTION_MODES,
    selectableVisibleIds,
} from '../../../resources/js/bulk-selection-state.js';

// Feature: admin-operational-ux-baseline, Property 3: Selection Set Invariant
// **Validates: Requirements 9.3, 9.5, 9.7**

const PROPERTY_RUNS = 100;
const idArbitrary = fc.integer({ min: 1, max: 40 });
const transitionArbitrary = fc.record({
    kind: fc.constantFrom('entity', 'filter', 'sort', 'page'),
    token: fc.integer({ min: -1000, max: 1000 }),
});

const scenarioArbitrary = fc.record({
    visibleIds: fc.uniqueArray(idArbitrary, { minLength: 2, maxLength: 8 }),
    selectedIds: fc.array(idArbitrary, { maxLength: 12 }),
    staleSelectableIds: fc.uniqueArray(fc.integer({ min: 100, max: 140 }), { maxLength: 4 }),
    transitions: fc.tuple(
        transitionArbitrary,
        transitionArbitrary,
        transitionArbitrary,
        transitionArbitrary,
    ),
});

const storageDouble = (calls) => ({
    getItem(key) { calls.push(['get', key]); return null; },
    setItem(key, value) { calls.push(['set', key, value]); },
    removeItem(key) { calls.push(['remove', key]); },
    clear() { calls.push(['clear']); },
});

const expectedSelectedKeys = (selectedIds, visibleSelectableIds) => {
    const selectableKeys = new Set(visibleSelectableIds.map((id) => String(id)));
    const seen = new Set();
    return selectedIds
        .map((id) => String(id))
        .filter((id) => selectableKeys.has(id) && !seen.has(id) && (seen.add(id), true));
};

const expectedHeaderState = (selectedKeys, visibleSelectableIds) => {
    const selected = new Set(selectedKeys);
    const selectedVisibleCount = visibleSelectableIds.filter((id) => selected.has(String(id))).length;
    if (visibleSelectableIds.length > 0 && selectedVisibleCount === visibleSelectableIds.length) {
        return BULK_HEADER_STATES.CHECKED;
    }
    if (selectedVisibleCount > 0) return BULK_HEADER_STATES.INDETERMINATE;
    return BULK_HEADER_STATES.UNCHECKED;
};

const createAssertionCounter = () => {
    let count = 0;
    return {
        get count() { return count; },
        equal(actual, expected, message) {
            count += 1;
            assert.equal(actual, expected, message);
        },
        deepEqual(actual, expected, message) {
            count += 1;
            assert.deepEqual(actual, expected, message);
        },
        ok(value, message) {
            count += 1;
            assert.ok(value, message);
        },
    };
};

const assertSelectionInvariant = (state, assertions, label) => {
    const visibleSelectableIds = selectableVisibleIds(state.visibleIds, state.selectableIds);
    const selectedKeys = expectedSelectedKeys(state.selectedIds, visibleSelectableIds);
    const headerState = expectedHeaderState(selectedKeys, visibleSelectableIds);

    assertions.deepEqual(state.selectedIds.map((id) => String(id)), selectedKeys, `${label}: authorized unique selection`);
    assertions.equal(state.selectedCount, selectedKeys.length, `${label}: selected count`);
    assertions.equal(state.headerState, headerState, `${label}: header state`);
    assertions.equal(state.headerChecked, headerState === BULK_HEADER_STATES.CHECKED, `${label}: checked flag`);
    assertions.equal(state.headerIndeterminate, headerState === BULK_HEADER_STATES.INDETERMINATE, `${label}: indeterminate flag`);
};

const applyGeneratedTransition = (state, transition, index) => {
    switch (transition.kind) {
        case 'entity':
            return state.setEntity(state.entity === 'teacher' ? 'student' : 'teacher');
        case 'filter':
            return state.setFilterContext({
                status: transition.token % 2 === 0 ? 'active' : 'inactive',
                search: `generated-${index}-${transition.token}`,
            });
        case 'sort': {
            const candidate = transition.token % 2 === 0
                ? { field: 'status', direction: 'desc' }
                : { field: 'full_name', direction: 'asc' };
            const nextSort = candidate.field === state.sort?.field && candidate.direction === state.sort?.direction
                ? (candidate.field === 'status'
                    ? { field: 'full_name', direction: 'asc' }
                    : { field: 'status', direction: 'desc' })
                : candidate;
            return state.setSort(nextSort);
        }
        case 'page':
            return state.setPage(state.page + 1 + Math.abs(transition.token) % 3);
        default:
            return false;
    }
};

test('preserves the Selection Set invariant across generated list-context transitions', async () => {
    const previousLocalStorage = globalThis.localStorage;
    const previousSessionStorage = globalThis.sessionStorage;
    const assertions = createAssertionCounter();
    let propertyRuns = 0;

    const localStorageCalls = [];
    const sessionStorageCalls = [];
    globalThis.localStorage = storageDouble(localStorageCalls);
    globalThis.sessionStorage = storageDouble(sessionStorageCalls);

    try {
        await fc.assert(
            fc.asyncProperty(scenarioArbitrary, async (scenario) => {
                propertyRuns += 1;
                const [firstVisibleId, ...nonSelectableVisibleIds] = scenario.visibleIds;
                const state = bulkSelectionState({
                    entity: 'teacher',
                    visibleIds: scenario.visibleIds,
                    selectableIds: [firstVisibleId, ...scenario.staleSelectableIds],
                    selectedIds: scenario.selectedIds,
                    filterContext: { status: 'active', search: '' },
                    page: 1,
                    sort: { field: 'full_name', direction: 'asc' },
                    executionEndpoint: '/admin/teachers/bulk',
                });
                state.init();

                assertSelectionInvariant(state, assertions, 'initialization');
                const selectedBeforeRejectedToggle = [...state.selectedIds];
                assertions.equal(state.toggle(nonSelectableVisibleIds[0]), false, 'non-selectable visible row is rejected');
                assertions.deepEqual(state.selectedIds, selectedBeforeRejectedToggle, 'rejected row cannot alter selection');
                assertSelectionInvariant(state, assertions, 'after rejected toggle');

                assertions.equal(state.toggleAll(), true, 'header selects visible authorized rows');
                assertSelectionInvariant(state, assertions, 'after select all');
                assertions.equal(state.toggleAll(), true, 'header clears visible authorized rows');
                assertSelectionInvariant(state, assertions, 'after clear all');

                assertions.equal(state.toggle(firstVisibleId), true, 'authorized visible row is selectable');
                assertSelectionInvariant(state, assertions, 'after authorized toggle');
                const retainedSelection = [...state.selectedIds];
                assertions.equal(
                    state.transitionContext({
                        entity: state.entity,
                        filterContext: { ...state.filterContext, page: 999 },
                        page: state.page,
                        sort: state.sort,
                    }),
                    false,
                    'page embedded in immutable filter context does not invalidate selection',
                );
                assertions.deepEqual(state.selectedIds, retainedSelection, 'unchanged context retains selection');
                assertSelectionInvariant(state, assertions, 'after unchanged context');

                for (const [index, transition] of scenario.transitions.entries()) {
                    state.clearSelection('property-seed');
                    assertions.equal(state.toggleAll(), true, `${transition.kind}: seed selection`);
                    assertions.equal(state.enterAllFiltered(), true, `${transition.kind}: enter all-filtered mode`);
                    const changed = applyGeneratedTransition(state, transition, index);

                    assertions.equal(changed, true, `${transition.kind}: transition changes context`);
                    assertSelectionInvariant(state, assertions, `${transition.kind}: invalidated selection`);
                    assertions.equal(state.selectedIds.length, 0, `${transition.kind}: selection is cleared`);
                    assertions.equal(state.mode, BULK_SELECTION_MODES.CURRENT_PAGE, `${transition.kind}: all-filtered mode is discarded`);
                    assertions.equal(state.lastClearReason, 'context', `${transition.kind}: clear reason`);
                    assertions.equal(await state.execute('activate'), false, `${transition.kind}: stale selection cannot execute`);
                }

                state.clearSelection('property-seed');
                assertions.equal(state.toggleAll(), true, 'refresh: seed selection');
                assertions.equal(state.enterAllFiltered(), true, 'refresh: enter all-filtered mode');
                state.refresh();
                assertSelectionInvariant(state, assertions, 'after refresh');
                assertions.equal(state.selectedIds.length, 0, 'refresh clears selection');
                assertions.equal(state.mode, BULK_SELECTION_MODES.CURRENT_PAGE, 'refresh discards all-filtered mode');
                assertions.equal(state.lastClearReason, 'refresh', 'refresh clear reason');
                assertions.equal(await state.execute('activate'), false, 'refresh cannot execute stale selection');
            }),
            { numRuns: PROPERTY_RUNS },
        );

        assertions.equal(propertyRuns, PROPERTY_RUNS, 'fast-check completed the configured run count');
        assertions.equal(localStorageCalls.length, 0, 'selection is not written to localStorage');
        assertions.equal(sessionStorageCalls.length, 0, 'selection is not written to sessionStorage');
        process.stdout.write(`Selection Set Invariant: ${propertyRuns} runs, ${assertions.count} assertions\n`);
    } finally {
        if (previousLocalStorage === undefined) delete globalThis.localStorage;
        else globalThis.localStorage = previousLocalStorage;
        if (previousSessionStorage === undefined) delete globalThis.sessionStorage;
        else globalThis.sessionStorage = previousSessionStorage;
    }
});
