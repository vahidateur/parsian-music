/**
 * Calendar filter state and interaction module.
 *
 * The module owns only filter state and DOM hooks. Calendar refreshes are
 * communicated through callbacks so this module remains independent of all
 * other calendar modules.
 */

const FILTER_KEYS = Object.freeze([
    'teacher_id',
    'student_id',
    'room',
    'instrument_id',
]);

const DEFAULT_FILTER_VALUE = '';
const DEFAULT_DEBOUNCE_MS = 300;

const isMobilePresentation = (root) => {
    const ownerWindow = root?.ownerDocument?.defaultView
        || (typeof window !== 'undefined' ? window : null);

    return !ownerWindow
        || typeof ownerWindow.matchMedia !== 'function'
        || ownerWindow.matchMedia('(max-width: 767px)').matches;
};

function resolveElement(element) {
    if (typeof element === 'string' && typeof document !== 'undefined') {
        return document.querySelector(element);
    }

    return element || null;
}

function isSupportedFilter(key) {
    return FILTER_KEYS.includes(key);
}

function normalizeFilterValue(value) {
    if (value === null || value === undefined) {
        return DEFAULT_FILTER_VALUE;
    }

    const normalized = String(value).trim();
    return normalized.toLowerCase() === 'all' ? DEFAULT_FILTER_VALUE : normalized;
}

function createDefaultState() {
    return FILTER_KEYS.reduce((state, key) => {
        state[key] = DEFAULT_FILTER_VALUE;
        return state;
    }, {});
}

function readInitialState(root) {
    const state = createDefaultState();

    if (!root || typeof root.querySelectorAll !== 'function') {
        return state;
    }

    root.querySelectorAll('[data-calendar-filter]').forEach((control) => {
        const key = control.getAttribute('data-calendar-filter');
        if (isSupportedFilter(key)) {
            state[key] = normalizeFilterValue(control.value);
        }
    });

    return state;
}

function cloneState(state) {
    return FILTER_KEYS.reduce((snapshot, key) => {
        snapshot[key] = state[key];
        return snapshot;
    }, {});
}

function countActiveFilters(state) {
    return FILTER_KEYS.reduce((count, key) => {
        return count + (normalizeFilterValue(state[key]) !== DEFAULT_FILTER_VALUE ? 1 : 0);
    }, 0);
}

function serializeFilters(state) {
    return FILTER_KEYS.reduce((serialized, key) => {
        const value = normalizeFilterValue(state[key]);
        if (value !== DEFAULT_FILTER_VALUE) {
            serialized[key] = value;
        }
        return serialized;
    }, {});
}

function serializedKey(serialized) {
    return FILTER_KEYS
        .filter((key) => Object.prototype.hasOwnProperty.call(serialized, key))
        .map((key) => `${key}=${serialized[key]}`)
        .join('&');
}

function findFilterControl(root, key) {
    if (!root || typeof root.querySelector !== 'function') {
        return null;
    }

    return root.querySelector(`[data-calendar-filter="${key}"]`)
        || root.querySelector(`[name="${key}"]`);
}

function invoke(callback, ...args) {
    if (typeof callback === 'function') {
        callback(...args);
    }
}

/**
 * Initialize the calendar filter panel.
 *
 * @param {Element|string|null} element Filter panel root or selector.
 * @param {Object} callbacks Callback hooks for filter and mobile-state changes.
 * @returns {Object} Filter state and lifecycle API.
 */
export default function initFilters(element, callbacks = {}) {
    const root = resolveElement(element);
    const onFilterChange = callbacks.onFilterChange || callbacks.onChange;
    const onMobileToggle = callbacks.onMobileToggle || callbacks.onToggle;
    const onMobileExpand = callbacks.onMobileExpand || callbacks.onExpand;
    const onMobileCollapse = callbacks.onMobileCollapse || callbacks.onCollapse;
    const debounceMs = Number.isFinite(callbacks.debounceMs)
        ? Math.max(0, callbacks.debounceMs)
        : DEFAULT_DEBOUNCE_MS;

    const state = readInitialState(root);
    let debounceTimer = null;
    let destroyed = false;
    let mobileExpanded = false;
    let lastEmittedKey = null;

    const getFilterFields = () => {
        if (!root || typeof root.querySelector !== 'function') {
            return null;
        }

        return root.querySelector('[data-calendar-filter-fields]')
            || root.querySelector('#calendar-filter-fields')
            || root.querySelector('.calendar-filters__fields');
    };

    const getMobileToggle = () => {
        if (!root || typeof root.querySelector !== 'function') {
            return null;
        }

        return root.querySelector('[data-calendar-filters-toggle]')
            || root.querySelector('.calendar-filters__toggle');
    };

    const updateMobileHooks = () => {
        const toggle = getMobileToggle();
        const fields = getFilterFields();
        const isMobile = isMobilePresentation(root);
        const isCollapsed = isMobile && !mobileExpanded;
        const activeFilterCount = countActiveFilters(state);
        const count = root?.querySelector?.('[data-calendar-filters-count]');

        if (toggle) {
            toggle.hidden = !isMobile;
            toggle.setAttribute('aria-expanded', String(mobileExpanded));
            toggle.setAttribute('data-expanded', String(mobileExpanded));
        }

        if (count) {
            count.textContent = String(activeFilterCount);
            count.hidden = activeFilterCount === 0;
        }

        if (fields) {
            fields.setAttribute('aria-hidden', String(isCollapsed));
            fields.classList.toggle('calendar-filters__fields--collapsed', isCollapsed);
        }

        if (root) {
            root.setAttribute('data-calendar-filters-expanded', String(isMobile && mobileExpanded));
            root.setAttribute('data-calendar-active-filter-count', String(activeFilterCount));
        }
    };

    const emit = () => {
        debounceTimer = null;
        if (destroyed) {
            return;
        }

        const serialized = serializeFilters(state);
        const key = serializedKey(serialized);
        if (key === lastEmittedKey) {
            return;
        }

        lastEmittedKey = key;
        invoke(onFilterChange, serialized, cloneState(state));
    };

    const scheduleEmit = () => {
        if (destroyed) {
            return;
        }

        if (debounceTimer !== null) {
            clearTimeout(debounceTimer);
        }

        const currentKey = serializedKey(serializeFilters(state));
        if (currentKey === lastEmittedKey) {
            debounceTimer = null;
            return;
        }

        debounceTimer = setTimeout(emit, debounceMs);
    };

    const setFilterValue = (key, value, shouldSchedule = true) => {
        if (!isSupportedFilter(key)) {
            return false;
        }

        const normalizedValue = normalizeFilterValue(value);
        if (state[key] === normalizedValue) {
            return false;
        }

        state[key] = normalizedValue;
        updateMobileHooks();
        const control = findFilterControl(root, key);
        if (control && control.value !== normalizedValue) {
            control.value = normalizedValue;
        }

        if (shouldSchedule) {
            scheduleEmit();
        }

        return true;
    };

    const handleChange = (event) => {
        const control = event.target;
        if (!control || typeof control.getAttribute !== 'function') {
            return;
        }

        const key = control.getAttribute('data-calendar-filter') || control.name;
        if (isSupportedFilter(key)) {
            setFilterValue(key, control.value);
        }
    };

    const clearAll = () => {
        let changed = false;
        FILTER_KEYS.forEach((key) => {
            changed = setFilterValue(key, DEFAULT_FILTER_VALUE, false) || changed;
        });

        if (changed) {
            scheduleEmit();
        }

        return changed;
    };

    const setMobileExpanded = (expanded) => {
        const nextValue = Boolean(expanded);
        if (mobileExpanded === nextValue) {
            updateMobileHooks();
            return mobileExpanded;
        }

        mobileExpanded = nextValue;
        updateMobileHooks();
        invoke(onMobileToggle, mobileExpanded);
        invoke(mobileExpanded ? onMobileExpand : onMobileCollapse, mobileExpanded);

        return mobileExpanded;
    };

    const handleMobileToggle = () => {
        setMobileExpanded(!mobileExpanded);
    };

    const clearButton = root && typeof root.querySelector === 'function'
        ? root.querySelector('[data-calendar-filters-clear]') || root.querySelector('.calendar-filters__clear')
        : null;
    const mobileToggle = getMobileToggle();
    const ownerWindow = root?.ownerDocument?.defaultView || null;
    const handleViewportChange = () => updateMobileHooks();

    if (root && typeof root.addEventListener === 'function') {
        root.addEventListener('change', handleChange);
    }
    if (clearButton && typeof clearButton.addEventListener === 'function') {
        clearButton.addEventListener('click', clearAll);
    }
    if (mobileToggle && typeof mobileToggle.addEventListener === 'function') {
        mobileToggle.addEventListener('click', handleMobileToggle);
    }
    if (ownerWindow) {
        ownerWindow.addEventListener('resize', handleViewportChange);
    }

    updateMobileHooks();

    const api = {
        getState: () => cloneState(state),
        getFilters: () => cloneState(state),
        getFilterState: () => cloneState(state),
        getActiveFilterCount: () => countActiveFilters(state),
        countActiveFilters: () => countActiveFilters(state),
        serialize: () => serializeFilters(state),
        serializeFilters: () => serializeFilters(state),
        getRequestParams: () => serializeFilters(state),
        setFilter: (key, value) => setFilterValue(key, value),
        setFilters: (values = {}) => {
            let changed = false;
            FILTER_KEYS.forEach((key) => {
                if (Object.prototype.hasOwnProperty.call(values, key)) {
                    changed = setFilterValue(key, values[key], false) || changed;
                }
            });
            if (changed) {
                scheduleEmit();
            }
            return cloneState(state);
        },
        clearAll,
        clearFilters: clearAll,
        expandMobile: () => setMobileExpanded(true),
        collapseMobile: () => setMobileExpanded(false),
        toggleMobile: handleMobileToggle,
        setMobileExpanded,
        isMobileExpanded: () => mobileExpanded,
        flush: () => {
            if (debounceTimer !== null) {
                clearTimeout(debounceTimer);
                emit();
            }
        },
        cancel: () => {
            if (debounceTimer !== null) {
                clearTimeout(debounceTimer);
                debounceTimer = null;
            }
        },
        destroy: () => {
            if (destroyed) {
                return;
            }

            destroyed = true;
            if (debounceTimer !== null) {
                clearTimeout(debounceTimer);
                debounceTimer = null;
            }
            if (root && typeof root.removeEventListener === 'function') {
                root.removeEventListener('change', handleChange);
            }
            if (clearButton && typeof clearButton.removeEventListener === 'function') {
                clearButton.removeEventListener('click', clearAll);
            }
            if (mobileToggle && typeof mobileToggle.removeEventListener === 'function') {
                mobileToggle.removeEventListener('click', handleMobileToggle);
            }
            if (ownerWindow) {
                ownerWindow.removeEventListener('resize', handleViewportChange);
            }
        },
    };

    Object.defineProperties(api, {
        activeFilterCount: {
            enumerable: true,
            get: () => countActiveFilters(state),
        },
        filters: {
            enumerable: true,
            get: () => cloneState(state),
        },
        mobileExpanded: {
            enumerable: true,
            get: () => mobileExpanded,
        },
    });

    return api;
}
