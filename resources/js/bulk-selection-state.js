/**
 * Alpine state and owner-side orchestration for teacher/student bulk actions.
 *
 * The server remains authoritative for context, authorization, counts and
 * mutation results. This module owns transient selection, request and feedback
 * state only. Requirements: 1.2–1.6, 1.8, 2.1–2.8, 4.1–4.4, 4.12, 12.2–12.5.
 */

export const BULK_SELECTION_MODES = Object.freeze({
    CURRENT_PAGE: 'current_page',
    ALL_FILTERED: 'all_filtered',
});

export const BULK_HEADER_STATES = Object.freeze({
    CHECKED: 'checked',
    INDETERMINATE: 'indeterminate',
    UNCHECKED: 'unchecked',
});

const LIFECYCLE_EVENTS = Object.freeze(['pagehide', 'beforeunload', 'admin:logout', 'logout']);
const ACTIONS = Object.freeze(['activate', 'deactivate', 'delete']);

const isObject = (value) => value !== null && typeof value === 'object';
const idKey = (id) => String(id);

const uniqueIds = (ids = []) => {
    const seen = new Set();
    const result = [];
    for (const id of Array.isArray(ids) ? ids : []) {
        if (id === null || id === undefined || seen.has(idKey(id))) continue;
        seen.add(idKey(id));
        result.push(id);
    }
    return result;
};

const withoutPage = (context) => {
    if (!isObject(context) || Array.isArray(context)) return context ?? null;
    const { page: _page, ...snapshot } = context;
    return snapshot;
};

const stableValue = (value) => {
    if (Array.isArray(value)) return value.map(stableValue);
    if (isObject(value)) {
        return Object.keys(value).sort().reduce((result, key) => {
            result[key] = stableValue(value[key]);
            return result;
        }, {});
    }
    return value;
};

export const sameSelectionContext = (left, right) => JSON.stringify(stableValue(left)) === JSON.stringify(stableValue(right));

export const selectableVisibleIds = (visibleIds = [], selectableIds = []) => {
    const selectable = new Set(uniqueIds(selectableIds).map(idKey));
    return uniqueIds(visibleIds).filter((id) => selectable.has(idKey(id)));
};

export const headerSelectionState = (selectedIds = [], visibleSelectableIds = []) => {
    const visible = uniqueIds(visibleSelectableIds);
    const selected = new Set(uniqueIds(selectedIds).map(idKey));
    const selectedVisibleCount = visible.filter((id) => selected.has(idKey(id))).length;
    if (visible.length > 0 && selectedVisibleCount === visible.length) return BULK_HEADER_STATES.CHECKED;
    if (selectedVisibleCount > 0) return BULK_HEADER_STATES.INDETERMINATE;
    return BULK_HEADER_STATES.UNCHECKED;
};

const hasDifferentValue = (left, right) => !sameSelectionContext(left, right);

const parseJson = (value, fallback = null) => {
    if (typeof value !== 'string' || value === '') return value ?? fallback;
    try { return JSON.parse(value); } catch { return fallback; }
};

const base64Url = (value) => {
    const json = JSON.stringify(value);
    if (typeof btoa === 'function' && typeof TextEncoder === 'function') {
        const bytes = new TextEncoder().encode(json);
        let binary = '';
        bytes.forEach((byte) => { binary += String.fromCharCode(byte); });
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }
    if (typeof Buffer !== 'undefined') return Buffer.from(json, 'utf8').toString('base64url');
    return json;
};

const csrfToken = () => {
    const meta = globalThis.document?.querySelector?.('meta[name="csrf-token"]')?.getAttribute('content');
    if (meta) return meta;
    const token = globalThis.document?.cookie?.split('; ').find((part) => part.startsWith('XSRF-TOKEN='))?.split('=').slice(1).join('=');
    return token ? decodeURIComponent(token) : null;
};

const requestId = () => globalThis.crypto?.randomUUID?.() ?? `bulk-${Date.now()}-${Math.random().toString(36).slice(2)}`;

const responsePayload = async (response) => {
    const payload = await response.json().catch(() => ({}));
    return payload?.data ?? payload;
};

const errorMessage = (payload, fallback) => {
    const errors = payload?.errors;
    if (errors && typeof errors === 'object') {
        const first = Object.values(errors).flat?.()[0];
        if (first) return String(first);
    }
    return payload?.message ? String(payload.message) : fallback;
};

export default function bulkSelectionState(options = {}) {
    const initialVisibleIds = uniqueIds(options.visibleIds ?? options.selectableIds ?? []);
    const initialSelectableIds = uniqueIds(options.selectableIds ?? initialVisibleIds);
    const initialSelectedIds = uniqueIds(options.selectedIds ?? []);

    return {
        entity: options.entity ?? null,
        selectedIds: initialSelectedIds,
        mode: options.mode === BULK_SELECTION_MODES.ALL_FILTERED ? BULK_SELECTION_MODES.ALL_FILTERED : BULK_SELECTION_MODES.CURRENT_PAGE,
        filterContext: withoutPage(options.filterContext),
        filterContextToken: options.filterContextToken ?? null,
        page: options.page ?? null,
        sort: options.sort ?? null,
        pending: Boolean(options.pending),
        previewing: false,
        dialogOpen: Boolean(options.dialogOpen),
        action: null,
        previewCount: null,
        previewFingerprint: null,
        result: options.result ?? null,
        lastOperation: null,
        lastRecovery: null,
        lastClearReason: null,
        visibleIds: initialVisibleIds,
        selectableIds: initialSelectableIds,
        allowedActions: options.allowedActions ?? {},
        endpoints: { preview: options.previewEndpoint ?? null, execute: options.executionEndpoint ?? null },
        messages: options.messages ?? {},
        _lifecycleCleanup: null,
        _domCleanup: null,
        _requestVersion: 0,

        init() {
            this.filterContext = withoutPage(this.filterContext);
            this.readDomContract();
            this.selectedIds = selectableVisibleIds(this.selectedIds, this.selectableVisibleIds);
            this.mode = this.canSelectAllFiltered ? this.mode : BULK_SELECTION_MODES.CURRENT_PAGE;
            this.bindDom();
            if (typeof window === 'undefined' || typeof window.addEventListener !== 'function') return;
            const clearForLifecycle = () => this.clearSelection('lifecycle');
            this._lifecycleCleanup = () => LIFECYCLE_EVENTS.forEach((eventName) => window.removeEventListener(eventName, clearForLifecycle));
            LIFECYCLE_EVENTS.forEach((eventName) => window.addEventListener(eventName, clearForLifecycle));
        },

        destroy() {
            this._lifecycleCleanup?.();
            this._domCleanup?.();
            this._lifecycleCleanup = null;
            this._domCleanup = null;
        },

        readDomContract() {
            const root = this.$el;
            if (!root?.dataset) return;
            this.entity = root.dataset.bulkEntity ?? this.entity;
            this.endpoints.preview = root.dataset.bulkPreviewEndpoint ?? this.endpoints.preview;
            this.endpoints.execute = root.dataset.bulkExecutionEndpoint ?? this.endpoints.execute;
            this.filterContext = withoutPage(parseJson(root.dataset.bulkFilterContext, this.filterContext));
            this.filterContextToken = root.dataset.bulkFilterContextToken ?? this.filterContextToken;
            this.visibleIds = uniqueIds(parseJson(root.dataset.bulkVisibleIds, this.visibleIds));
            this.selectableIds = uniqueIds(parseJson(root.dataset.bulkSelectableIds, this.selectableIds));
            const documentRef = root.ownerDocument ?? globalThis.document;
            documentRef?.querySelectorAll?.(`[data-bulk-row][data-bulk-entity="${this.entity}"]`).forEach((row) => {
                const rowId = this.rowId(row);
                if (!this.isSelectable(rowId)) return;
                this.allowedActions[idKey(rowId)] = (row.dataset.bulkAllowedActions ?? '')
                    .split(',').map((action) => action.trim()).filter(Boolean);
            });
            this.messages = { ...this.messages, ...parseJson(root.dataset.bulkMessages, {}) };
        },

        get selectableVisibleIds() { return selectableVisibleIds(this.visibleIds, this.selectableIds); },
        get selectedCount() { return selectableVisibleIds(this.selectedIds, this.selectableVisibleIds).length; },
        get headerState() { return headerSelectionState(this.selectedIds, this.selectableVisibleIds); },
        get headerChecked() { return this.headerState === BULK_HEADER_STATES.CHECKED; },
        get headerIndeterminate() { return this.headerState === BULK_HEADER_STATES.INDETERMINATE; },
        get headerUnchecked() { return this.headerState === BULK_HEADER_STATES.UNCHECKED; },
        get hasSelection() { return this.selectedCount > 0; },
        get canSelectAllFiltered() {
            const visible = this.selectableVisibleIds;
            const selected = new Set(this.selectedIds.map(idKey));
            return visible.length > 0 && visible.every((id) => selected.has(idKey(id)));
        },
        get confirmationCount() { return this.mode === BULK_SELECTION_MODES.ALL_FILTERED ? this.previewCount : this.selectedCount; },
        get availableActions() {
            if (!this.hasSelection) return [];
            const rows = this.selectedIds.map((id) => this.allowedActions[idKey(id)] ?? ACTIONS);
            return ACTIONS.filter((action) => rows.every((allowed) => allowed.includes(action)));
        },

        isSelected(id) { return this.selectedIds.some((selectedId) => idKey(selectedId) === idKey(id)); },
        isSelectable(id) { return this.selectableVisibleIds.some((selectableId) => idKey(selectableId) === idKey(id)); },
        rowId(row) {
            const candidate = row?.dataset?.bulkId ?? row?.value;
            return this.selectableVisibleIds.find((id) => idKey(id) === idKey(candidate)) ?? candidate;
        },
        toggleRow(row) { return this.toggle(this.rowId(row), row?.checked); },
        setVisibleIds(ids = []) { this.visibleIds = uniqueIds(ids); this.retainSelectableSelection(); },
        setSelectableIds(ids = []) { this.selectableIds = uniqueIds(ids); this.retainSelectableSelection(); },
        retainSelectableSelection() {
            const retained = selectableVisibleIds(this.selectedIds, this.selectableVisibleIds);
            const changed = !sameSelectionContext(this.selectedIds.map(idKey), retained.map(idKey));
            this.selectedIds = retained;
            if (changed && this.mode === BULK_SELECTION_MODES.ALL_FILTERED) this.mode = BULK_SELECTION_MODES.CURRENT_PAGE;
            this.syncDom();
        },
        toggle(id, checked = !this.isSelected(id)) {
            const selectedId = this.selectableVisibleIds.find((selectableId) => idKey(selectableId) === idKey(id));
            if (selectedId === undefined) return false;
            const key = idKey(selectedId);
            this.selectedIds = checked ? uniqueIds([...this.selectedIds, selectedId]) : this.selectedIds.filter((selectedId) => idKey(selectedId) !== key);
            this.mode = BULK_SELECTION_MODES.CURRENT_PAGE;
            this.previewCount = null;
            this.syncDom();
            return true;
        },
        toggleAll() {
            const visible = this.selectableVisibleIds;
            if (visible.length === 0) return false;
            if (this.headerChecked) {
                const visibleKeys = new Set(visible.map(idKey));
                this.selectedIds = this.selectedIds.filter((id) => !visibleKeys.has(idKey(id)));
            } else this.selectedIds = uniqueIds([...this.selectedIds, ...visible]);
            this.mode = BULK_SELECTION_MODES.CURRENT_PAGE;
            this.previewCount = null;
            this.syncDom();
            return true;
        },
        enterAllFiltered() {
            if (!this.canSelectAllFiltered || this.pending) return false;
            this.mode = BULK_SELECTION_MODES.ALL_FILTERED;
            return true;
        },
        setMode(mode) { return mode === BULK_SELECTION_MODES.CURRENT_PAGE ? (this.mode = mode, true) : this.enterAllFiltered(); },
        clearSelection(reason = 'manual', { preserveResult = false } = {}) {
            this._requestVersion += 1;
            this.selectedIds = [];
            this.mode = BULK_SELECTION_MODES.CURRENT_PAGE;
            this.dialogOpen = false;
            this.pending = false;
            this.previewing = false;
            this.previewCount = null;
            this.previewFingerprint = null;
            this.action = null;
            this.lastRecovery = null;
            if (!preserveResult) this.result = null;
            this.lastClearReason = reason;
            this.syncDom();
        },
        transitionContext(next = {}) {
            const nextEntity = Object.prototype.hasOwnProperty.call(next, 'entity') ? next.entity : this.entity;
            const nextFilterContext = Object.prototype.hasOwnProperty.call(next, 'filterContext') ? next.filterContext : this.filterContext;
            const nextPage = Object.prototype.hasOwnProperty.call(next, 'page') ? next.page : this.page;
            const nextSort = Object.prototype.hasOwnProperty.call(next, 'sort') ? next.sort : this.sort;
            const nextContext = withoutPage(nextFilterContext);
            const changed = hasDifferentValue(this.entity, nextEntity) || hasDifferentValue(this.filterContext, nextContext) || hasDifferentValue(this.page, nextPage) || hasDifferentValue(this.sort, nextSort);
            const nextToken = Object.prototype.hasOwnProperty.call(next, 'filterContextToken')
                ? next.filterContextToken
                : (changed ? null : this.filterContextToken);
            if (changed) this.clearSelection('context');
            this.entity = nextEntity; this.filterContext = nextContext; this.filterContextToken = nextToken; this.page = nextPage; this.sort = nextSort;
            return changed;
        },
        setEntity(entity) { return this.transitionContext({ entity }); },
        setFilterContext(filterContext) { return this.transitionContext({ filterContext }); },
        setPage(page) { return this.transitionContext({ page }); },
        setSort(sort) { return this.transitionContext({ sort }); },
        refresh() { this.clearSelection('refresh'); },
        logout() { this.clearSelection('logout'); },
        bindDom() {
            const root = this.$el;
            const documentRef = root?.ownerDocument ?? globalThis.document;
            if (!root || !documentRef?.querySelectorAll) return;
            const entitySelector = `[data-bulk-entity="${this.entity}"]`;
            const rows = [...documentRef.querySelectorAll(`[data-bulk-row]${entitySelector}`)];
            const header = documentRef.querySelector(`[data-bulk-select-all]${entitySelector}`);
            const confirmation = documentRef.querySelector(`[data-bulk-confirmation]${entitySelector}`);
            const resultSummary = documentRef.querySelector(`[data-bulk-result-summary]${entitySelector}`);
            const listeners = [];
            const listen = (target, type, handler) => {
                if (!target?.addEventListener) return;
                target.addEventListener(type, handler);
                listeners.push(() => target.removeEventListener(type, handler));
            };
            rows.forEach((row) => listen(row, 'change', () => this.toggleRow(row)));
            listen(header, 'change', () => this.toggleAll());
            root.querySelectorAll('[data-bulk-action]').forEach((button) => listen(button, 'click', () => this.requestAction(button.dataset.bulkAction)));
            listen(root.querySelector('[data-bulk-all-filtered]'), 'click', () => this.selectAllFiltered());
            listen(confirmation?.querySelector('[data-bulk-confirm-delete]'), 'click', () => this.confirmDelete());
            listen(confirmation?.querySelector('[data-bulk-confirm-cancel]'), 'click', () => this.cancelConfirmation());
            listen(resultSummary?.querySelector('[data-bulk-result-retry]'), 'click', () => this.retryLastOperation());
            listen(documentRef, 'click', (event) => {
                const modal = confirmation?.closest?.('.ui-modal');
                if (this.dialogOpen && modal?.contains?.(event.target) && event.target?.closest?.('.ui-modal__backdrop')) {
                    this.cancelConfirmation();
                }
            });
            listen(documentRef, 'keydown', (event) => {
                if (event.key === 'Escape' && this.dialogOpen) this.cancelConfirmation();
            });
            this._domCleanup = () => listeners.forEach((cleanup) => cleanup());
            this.syncDom();
        },

        syncDom() {
            const root = this.$el;
            const documentRef = root?.ownerDocument ?? globalThis.document;
            if (!root || !documentRef?.querySelectorAll) return;
            root.setAttribute('aria-busy', String(this.pending));
            const entitySelector = `[data-bulk-entity="${this.entity}"]`;
            const rows = [...documentRef.querySelectorAll(`[data-bulk-row]${entitySelector}`)];
            const header = documentRef.querySelector(`[data-bulk-select-all]${entitySelector}`);
            rows.forEach((row) => { row.checked = this.isSelected(this.rowId(row)); });
            if (header) {
                const hasSelectableRows = this.selectableVisibleIds.length > 0;
                header.checked = this.headerChecked;
                header.indeterminate = this.headerIndeterminate;
                header.disabled = !hasSelectableRows || this.pending;
                header.setAttribute('aria-checked', this.headerIndeterminate ? 'mixed' : String(this.headerChecked));
                header.setAttribute('aria-disabled', String(header.disabled));
            }
            const count = root.querySelector('[data-bulk-selected-count]');
            if (count) count.textContent = String(this.selectedCount);
            root.querySelectorAll('[data-bulk-action]').forEach((button) => {
                const available = this.availableActions.includes(button.dataset.bulkAction);
                const enabled = available && !this.pending;
                button.disabled = !enabled;
                button.setAttribute('aria-disabled', String(!enabled));
            });
            const allFiltered = root.querySelector('[data-bulk-all-filtered]');
            if (allFiltered) {
                allFiltered.hidden = !this.canSelectAllFiltered;
                allFiltered.disabled = !this.canSelectAllFiltered || this.pending;
                allFiltered.setAttribute('aria-disabled', String(allFiltered.disabled));
                allFiltered.setAttribute('aria-pressed', String(this.mode === BULK_SELECTION_MODES.ALL_FILTERED));
            }
            const confirmation = documentRef.querySelector(`[data-bulk-confirmation]${entitySelector}`);
            if (confirmation) {
                const countNode = confirmation.querySelector('[data-bulk-confirmation-count]');
                if (countNode) countNode.textContent = this.messages.selectedCount?.replace(':count', String(this.confirmationCount ?? 0)) ?? String(this.confirmationCount ?? 0);
                const confirm = confirmation.querySelector('[data-bulk-confirm-delete]');
                const cancel = confirmation.querySelector('[data-bulk-confirm-cancel]');
                if (confirm) {
                    confirm.disabled = this.pending || !(this.confirmationCount > 0);
                    confirm.setAttribute('aria-disabled', String(confirm.disabled));
                    confirm.setAttribute('aria-busy', String(this.pending));
                }
                if (cancel) {
                    cancel.disabled = this.pending;
                    cancel.setAttribute('aria-disabled', String(cancel.disabled));
                }
            }
            this.renderResult();
        },

        announce(message, error = false) {
            const root = this.$el;
            const resultNode = root?.querySelector('[data-bulk-live-result]');
            const errorNode = root?.querySelector('[data-bulk-live-error]');
            const target = error ? errorNode : resultNode;
            const other = error ? resultNode : errorNode;
            if (target) {
                target.textContent = message ?? '';
                target.hidden = !message;
            }
            if (other) {
                other.textContent = '';
                other.hidden = true;
            }
        },

        renderResult() {
            const root = this.$el;
            const documentRef = root?.ownerDocument ?? globalThis.document;
            const summary = documentRef?.querySelector?.(`[data-bulk-result-summary][data-bulk-entity="${this.entity}"]`);
            if (!summary) return;
            const message = summary.querySelector('[data-bulk-result-message]');
            const items = summary.querySelector('[data-bulk-result-items]');
            const retry = summary.querySelector('[data-bulk-result-retry]');
            const result = this.result;
            summary.hidden = !result;
            if (!result) return;
            if (result.error) {
                if (message) message.textContent = result.error;
                if (items) items.replaceChildren();
                if (retry) retry.hidden = !this.lastRecovery;
                this.announce(result.error, true);
                return;
            }
            const outcome = result.outcome === 'complete_success' ? (this.messages.complete ?? '') : (this.messages.partial ?? '');
            if (message) message.textContent = outcome
                .replace(':succeeded', String(result.succeeded ?? 0))
                .replace(':skipped', String(result.skipped ?? 0))
                .replace(':failed', String(result.failed ?? 0));
            if (items) {
                items.replaceChildren(...(Array.isArray(result.items) ? result.items.map((item) => {
                    const li = documentRef.createElement('li');
                    const reason = item.reason?.message ?? item.reason?.category;
                    li.textContent = reason ? `${item.id}: ${reason}` : `${item.id}`;
                    return li;
                }) : []));
            }
            if (retry) retry.hidden = true;
            this.announce(message?.textContent ?? '', false);
        },

        contextToken() {
            if (this.filterContextToken) return this.filterContextToken;
            if (!this.filterContext?.signature) return null;
            return base64Url(this.filterContext);
        },

        requestPayload(action) {
            const payload = {
                entity: this.entity,
                action,
                mode: this.mode,
                selection_reference: `${this.entity}-${this.mode}-${this.filterContext?.context_fingerprint ?? requestId()}`,
                request_fingerprint: requestId(),
            };
            if (this.mode === BULK_SELECTION_MODES.ALL_FILTERED) payload.filter_context = this.contextToken();
            else payload.ids = uniqueIds(this.selectedIds);
            return payload;
        },

        async post(endpoint, payload) {
            const token = csrfToken();
            const headers = { Accept: 'application/json', 'Content-Type': 'application/json' };
            if (token) headers['X-CSRF-TOKEN'] = token;
            const response = await fetch(endpoint, { method: 'POST', credentials: 'same-origin', headers, body: JSON.stringify(payload) });
            const body = await responsePayload(response);
            if (!response.ok) throw new Error(errorMessage(body, this.messages.error ?? 'Bulk request failed.'));
            return body;
        },

        async selectAllFiltered() {
            if (!this.canSelectAllFiltered || !this.endpoints.preview || !this.contextToken() || this.pending) return false;
            if (!this.enterAllFiltered()) return false;
            const version = ++this._requestVersion;
            const payload = {
                entity: this.entity,
                mode: BULK_SELECTION_MODES.ALL_FILTERED,
                filter_context: this.contextToken(),
            };
            this.lastRecovery = { type: 'preview' };
            this.pending = true;
            this.previewing = true;
            this.result = null;
            this.syncDom();
            this.announce(this.messages.previewing ?? this.messages.pending ?? 'Loading…');
            try {
                const body = await this.post(this.endpoints.preview, payload);
                if (version !== this._requestVersion) return false;
                const count = Number(body.count);
                if (!Number.isSafeInteger(count) || count < 0) throw new Error(this.messages.error ?? 'Bulk preview failed.');
                this.previewCount = count;
                this.previewFingerprint = body.context_fingerprint ?? null;
                this.pending = false;
                this.previewing = false;
                this.lastRecovery = null;
                this.announce((this.messages.previewReady ?? '').replace(':count', String(this.previewCount)));
                this.syncDom();
                return true;
            } catch (error) {
                if (version !== this._requestVersion) return false;
                this.pending = false;
                this.previewing = false;
                this.previewCount = null;
                this.mode = BULK_SELECTION_MODES.CURRENT_PAGE;
                this.result = { error: error.message };
                this.syncDom();
                return false;
            }
        },

        requestAction(action) {
            if (!ACTIONS.includes(action) || this.pending || !this.availableActions.includes(action) || !this.endpoints.execute) return false;
            this.action = action;
            if (action === 'delete') return this.openDeleteConfirmation();
            return this.execute(action);
        },

        openDeleteConfirmation() {
            if (!(this.confirmationCount > 0)) return false;
            this.dialogOpen = true;
            this.syncDom();
            const button = this.$el?.querySelector('[data-bulk-action="delete"]');
            const confirmation = this.$el?.ownerDocument?.querySelector?.(`[data-bulk-confirmation][data-bulk-entity="${this.entity}"]`);
            const name = confirmation?.dataset.bulkModalName;
            if (button && name) button.dispatchEvent(new CustomEvent('open-modal', { bubbles: true, detail: name }));
            return true;
        },

        cancelConfirmation() {
            this.dialogOpen = false;
            this.action = null;
            const confirmation = this.$el?.ownerDocument?.querySelector?.(`[data-bulk-confirmation][data-bulk-entity="${this.entity}"]`);
            confirmation?.dispatchEvent(new CustomEvent('close', { bubbles: true }));
            this.syncDom();
        },

        confirmDelete() {
            if (this.pending || this.action !== 'delete' || !(this.confirmationCount > 0)) return false;
            return this.execute('delete');
        },

        async execute(action = this.action) {
            if (this.pending || !ACTIONS.includes(action) || (!this.hasSelection && this.mode !== BULK_SELECTION_MODES.ALL_FILTERED) || !this.endpoints.execute) return false;
            if (this.mode === BULK_SELECTION_MODES.ALL_FILTERED && !(this.previewCount > 0)) return false;
            const operation = this.requestPayload(action);
            const version = ++this._requestVersion;
            this.lastOperation = { action, mode: this.mode, payload: operation };
            this.lastRecovery = { type: 'execute', action };
            this.pending = true;
            this.result = null;
            this.syncDom();
            this.announce(this.messages.pending ?? 'Submitting…');
            try {
                const body = await this.post(this.endpoints.execute, operation);
                if (version !== this._requestVersion) return false;
                this.pending = false;
                this.dialogOpen = false;
                this.selectedIds = [];
                this.mode = BULK_SELECTION_MODES.CURRENT_PAGE;
                this.previewCount = null;
                this.previewFingerprint = null;
                this.action = null;
                this.result = body;
                this.lastRecovery = null;
                this.lastClearReason = 'executed';
                this.cancelConfirmation();
                this.syncDom();
                if (typeof globalThis.location?.reload === 'function') globalThis.location.reload();
                return true;
            } catch (error) {
                if (version !== this._requestVersion) return false;
                this.pending = false;
                this.result = { error: error.message };
                this.syncDom();
                return false;
            }
        },

        async retryLastOperation() {
            if (!this.lastRecovery || this.pending) return false;
            if (this.lastRecovery.type === 'preview') return this.selectAllFiltered();
            this.action = this.lastRecovery.action;
            if (this.action === 'delete') return this.openDeleteConfirmation();
            return this.execute(this.action);
        },
    };
}
