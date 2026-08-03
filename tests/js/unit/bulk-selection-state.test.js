import test from 'node:test';
import assert from 'node:assert/strict';
import bulkSelectionState, {
    BULK_HEADER_STATES,
    BULK_SELECTION_MODES,
    headerSelectionState,
    selectableVisibleIds,
} from '../../../resources/js/bulk-selection-state.js';

const createState = (options = {}) => {
    const state = bulkSelectionState({
        entity: 'teacher',
        visibleIds: [1, 2, 3, 4],
        selectableIds: [1, 2, 4],
        filterContext: { status: 'active', search: '' },
        page: 1,
        sort: { field: 'full_name', direction: 'asc' },
        ...options,
    });
    state.init();
    return state;
};

const selectionUiState = (state) => ({
    selectedIds: state.selectedIds,
    selectedCount: state.selectedCount,
    headerState: state.headerState,
    headerChecked: state.headerChecked,
    headerIndeterminate: state.headerIndeterminate,
    toolbarEnabledActions: state.availableActions,
});

test('selects only visible selectable rows and derives the header state', () => {
    const state = createState();

    assert.deepEqual(state.selectableVisibleIds, [1, 2, 4]);
    assert.equal(state.toggle(3), false);
    assert.equal(state.toggle(1), true);
    assert.equal(state.selectedCount, 1);
    assert.equal(state.headerState, BULK_HEADER_STATES.INDETERMINATE);
    assert.equal(state.headerChecked, false);
    assert.equal(state.headerIndeterminate, true);

    state.toggleAll();
    assert.deepEqual(state.selectedIds, [1, 2, 4]);
    assert.equal(state.headerState, BULK_HEADER_STATES.CHECKED);
    assert.equal(state.selectedCount, 3);

    state.toggleAll();
    assert.deepEqual(state.selectedIds, []);
    assert.equal(state.headerState, BULK_HEADER_STATES.UNCHECKED);
});

test('individual row and header selection paths produce identical toolbar state', () => {
    const options = {
        visibleIds: [1, 2],
        selectableIds: [1, 2],
        allowedActions: {
            1: ['activate', 'delete'],
            2: ['activate', 'delete'],
        },
    };
    const individual = createState(options);
    const header = createState(options);

    assert.equal(individual.toggleRow({ value: '1', dataset: {}, checked: true }), true);
    assert.equal(individual.toggleRow({ value: '2', dataset: {}, checked: true }), true);
    assert.equal(header.toggleAll(), true);

    assert.deepEqual(selectionUiState(individual), selectionUiState(header));
    assert.deepEqual(selectionUiState(individual), {
        selectedIds: [1, 2],
        selectedCount: 2,
        headerState: BULK_HEADER_STATES.CHECKED,
        headerChecked: true,
        headerIndeterminate: false,
        toolbarEnabledActions: ['activate', 'delete'],
    });
});

test('removes rows that cease to be selectable without retaining stale identifiers', () => {
    const state = createState({ selectedIds: [1, 2] });
    state.init();

    state.setSelectableIds([2, 4]);
    assert.deepEqual(state.selectedIds, [2]);
    assert.equal(state.isSelected(1), false);
    assert.equal(state.isSelectable(1), false);
});

test('clears selection and all-filtered mode on entity and list-context changes', () => {
    const state = createState();
    state.toggleAll();
    assert.equal(state.enterAllFiltered(), true);
    assert.equal(state.mode, BULK_SELECTION_MODES.ALL_FILTERED);

    assert.equal(state.setFilterContext({ status: 'inactive', search: '' }), true);
    assert.deepEqual(state.selectedIds, []);
    assert.equal(state.mode, BULK_SELECTION_MODES.CURRENT_PAGE);

    state.toggle(1);
    assert.equal(state.setPage(2), true);
    assert.deepEqual(state.selectedIds, []);

    state.toggle(1);
    assert.equal(state.setSort({ field: 'status', direction: 'desc' }), true);
    assert.deepEqual(state.selectedIds, []);

    state.toggle(1);
    assert.equal(state.setEntity('student'), true);
    assert.deepEqual(state.selectedIds, []);
});

test('does not treat page as part of the immutable filter context snapshot', () => {
    const state = createState({ filterContext: { page: 9, status: 'active' } });

    assert.deepEqual(state.filterContext, { status: 'active' });
    state.toggle(1);
    assert.equal(state.setFilterContext({ page: 12, status: 'active' }), false);
    assert.deepEqual(state.selectedIds, [1]);
});

test('refresh and logout clear transient state without using browser persistence', () => {
    const state = createState();
    state.toggle(1);
    state.dialogOpen = true;
    state.pending = true;
    state.result = { outcome: 'partial_success' };

    state.refresh();
    assert.deepEqual(state.selectedIds, []);
    assert.equal(state.pending, false);
    assert.equal(state.dialogOpen, false);
    assert.equal(state.result, null);

    state.toggle(2);
    state.logout();
    assert.deepEqual(state.selectedIds, []);
    assert.equal(state.lastClearReason, 'logout');
});

test('clears selection from lifecycle logout events and removes its listeners on destroy', () => {
    const previousWindow = globalThis.window;
    const listeners = new Map();
    globalThis.window = {
        addEventListener(name, callback) {
            listeners.set(name, callback);
        },
        removeEventListener(name, callback) {
            if (listeners.get(name) === callback) {
                listeners.delete(name);
            }
        },
    };

    try {
        const state = createState();
        state.toggle(1);
        listeners.get('admin:logout')();
        assert.deepEqual(state.selectedIds, []);
        assert.equal(state.lastClearReason, 'lifecycle');

        state.toggle(2);
        state.destroy();
        assert.equal(listeners.size, 0);
    } finally {
        if (previousWindow === undefined) {
            delete globalThis.window;
        } else {
            globalThis.window = previousWindow;
        }
    }
});

test('pure helpers keep non-selectable rows out of selection and header counts', () => {
    assert.deepEqual(selectableVisibleIds([1, 2, 2, 3], [2, 3, 4]), [2, 3]);
    assert.equal(headerSelectionState([2], [2, 3]), BULK_HEADER_STATES.INDETERMINATE);
    assert.equal(headerSelectionState([2, 3], [2, 3]), BULK_HEADER_STATES.CHECKED);
    assert.equal(headerSelectionState([4], [2, 3]), BULK_HEADER_STATES.UNCHECKED);
});

test('previews all-filtered selection with a signed context and server count', async () => {
    const previousFetch = globalThis.fetch;
    const requests = [];
    globalThis.fetch = async (endpoint, options) => {
        requests.push({ endpoint, options, payload: JSON.parse(options.body) });
        return { ok: true, json: async () => ({ count: 7, context_fingerprint: 'server-context' }) };
    };

    try {
        const state = bulkSelectionState({
            entity: 'teacher',
            visibleIds: [1, 2],
            selectableIds: [1, 2],
            selectedIds: [1, 2],
            filterContext: {
                entity: 'teachers',
                filters: { status: 'active' },
                page: 3,
                sort: 'full_name',
                direction: 'asc',
                context_fingerprint: 'client-context',
                expires_at: '2099-01-01T00:00:00+00:00',
                signature: 'signed-context',
            },
            previewEndpoint: '/admin/teachers/bulk/preview',
            executionEndpoint: '/admin/teachers/bulk',
        });
        state.init();

        assert.equal(await state.selectAllFiltered(), true);
        assert.equal(state.mode, BULK_SELECTION_MODES.ALL_FILTERED);
        assert.equal(state.previewCount, 7);
        assert.equal(requests.length, 1);
        assert.equal(requests[0].endpoint, '/admin/teachers/bulk/preview');
        assert.equal(requests[0].payload.mode, BULK_SELECTION_MODES.ALL_FILTERED);
        assert.equal(typeof requests[0].payload.filter_context, 'string');
        const signedContext = JSON.parse(Buffer.from(requests[0].payload.filter_context, 'base64url').toString('utf8'));
        assert.equal(signedContext.signature, 'signed-context');
        assert.equal(Object.prototype.hasOwnProperty.call(signedContext, 'page'), false);
    } finally {
        globalThis.fetch = previousFetch;
    }
});

test('cancelling delete confirmation preserves selection and sends no request', () => {
    const previousFetch = globalThis.fetch;
    let requestCount = 0;
    globalThis.fetch = async () => {
        requestCount += 1;
        return { ok: true, json: async () => ({}) };
    };

    try {
        const state = bulkSelectionState({
            entity: 'student',
            visibleIds: [4],
            selectableIds: [4],
            selectedIds: [4],
            executionEndpoint: '/admin/students/bulk',
        });
        state.init();
        assert.equal(state.requestAction('delete'), true);
        state.cancelConfirmation();
        assert.deepEqual(state.selectedIds, [4]);
        assert.equal(state.pending, false);
        assert.equal(requestCount, 0);
    } finally {
        globalThis.fetch = previousFetch;
    }
});

test('one confirmation cannot submit duplicate execution requests and clears executed selection', async () => {
    const previousFetch = globalThis.fetch;
    let requestCount = 0;
    globalThis.fetch = async () => {
        requestCount += 1;
        return { ok: true, json: async () => ({ outcome: 'complete_success', succeeded: 1, skipped: 0, failed: 0, items: [] }) };
    };

    try {
        const state = bulkSelectionState({
            entity: 'student',
            visibleIds: [9],
            selectableIds: [9],
            selectedIds: [9],
            executionEndpoint: '/admin/students/bulk',
        });
        state.init();
        assert.equal(state.requestAction('delete'), true);
        const first = state.confirmDelete();
        const second = state.confirmDelete();
        assert.equal(second, false);
        await first;
        assert.equal(requestCount, 1);
        assert.deepEqual(state.selectedIds, []);
        assert.equal(state.pending, false);
        assert.equal(state.result.outcome, 'complete_success');
    } finally {
        globalThis.fetch = previousFetch;
    }
});

test('offers all-filtered preview only after every visible selectable row and requires a signed context', async () => {
    const previousFetch = globalThis.fetch;
    let requestCount = 0;
    globalThis.fetch = async () => {
        requestCount += 1;
        return { ok: true, json: async () => ({ count: 2 }) };
    };

    try {
        const state = bulkSelectionState({
            entity: 'teacher',
            visibleIds: [1, 2],
            selectableIds: [1, 2],
            selectedIds: [1],
            filterContext: { entity: 'teachers', filters: {}, sort: 'full_name', direction: 'asc' },
            previewEndpoint: '/admin/teachers/bulk/preview',
        });
        state.init();
        assert.equal(await state.selectAllFiltered(), false);
        assert.equal(requestCount, 0);

        state.toggle(2);
        assert.equal(await state.selectAllFiltered(), false);
        assert.equal(requestCount, 0);
        assert.equal(state.mode, BULK_SELECTION_MODES.CURRENT_PAGE);
    } finally {
        globalThis.fetch = previousFetch;
    }
});

test('failed all-filtered preview exposes a retry recovery action', async () => {
    const previousFetch = globalThis.fetch;
    let requestCount = 0;
    globalThis.fetch = async () => {
        requestCount += 1;
        if (requestCount === 1) {
            return { ok: false, json: async () => ({ message: 'Preview unavailable' }) };
        }
        return { ok: true, json: async () => ({ count: 3, context_fingerprint: 'server-context' }) };
    };

    try {
        const state = bulkSelectionState({
            entity: 'teacher',
            visibleIds: [1, 2],
            selectableIds: [1, 2],
            selectedIds: [1, 2],
            filterContext: {
                entity: 'teachers',
                filters: {},
                sort: 'full_name',
                direction: 'asc',
                context_fingerprint: 'client-context',
                expires_at: '2099-01-01T00:00:00+00:00',
                signature: 'signed-context',
            },
            previewEndpoint: '/admin/teachers/bulk/preview',
        });
        state.init();

        assert.equal(await state.selectAllFiltered(), false);
        assert.deepEqual(state.result, { error: 'Preview unavailable' });
        assert.deepEqual(state.lastRecovery, { type: 'preview' });
        assert.equal(await state.retryLastOperation(), true);
        assert.equal(state.previewCount, 3);
        assert.equal(state.lastRecovery, null);
        assert.equal(requestCount, 2);
    } finally {
        globalThis.fetch = previousFetch;
    }
});
