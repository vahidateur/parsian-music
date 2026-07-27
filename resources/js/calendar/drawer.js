/**
 * Calendar event detail drawer.
 *
 * The drawer owns event detail state and DOM/Alpine integration. It does not
 * import sibling calendar modules; the orchestrator supplies event data and
 * the triggering event element through callbacks.
 */

export const VALID_STATUSES = Object.freeze([
    'scheduled',
    'completed',
    'cancelled',
    'missed',
]);

export const STATUS_BADGE_CLASSES = Object.freeze({
    scheduled: 'calendar-drawer__status-badge--scheduled',
    completed: 'calendar-drawer__status-badge--completed',
    cancelled: 'calendar-drawer__status-badge--cancelled',
    missed: 'calendar-drawer__status-badge--missed',
});

export const STATUS_LABELS = Object.freeze({
    scheduled: 'scheduled',
    completed: 'completed',
    cancelled: 'cancelled',
    missed: 'missed',
});

const MISSING_VALUE = '—';
const NO_NOTES = 'بدون یادداشت';
const MOBILE_BREAKPOINT = 768;
const DRAWER_ROOT_SELECTOR = '[data-calendar-drawer]';

const isValidStatus = (status) => VALID_STATUSES.includes(status);

/**
 * Return the exact status badge modifier for every supported status.
 * Invalid values intentionally return an empty class rather than borrowing a
 * different status colour.
 */
export function getStatusBadgeClass(status) {
    return isValidStatus(status) ? STATUS_BADGE_CLASSES[status] : '';
}

/**
 * Build the accessible event trigger label required by the calendar contract.
 */
export function getEventAriaLabel(studentName, status) {
    const student = normalizeText(studentName, MISSING_VALUE);
    const label = isValidStatus(status) ? STATUS_LABELS[status] : MISSING_VALUE;

    return `${student} – ${label}`;
}

const normalizeText = (value, fallback = MISSING_VALUE) => {
    const text = String(value ?? '').trim();
    return text || fallback;
};

const normalizeStatus = (value) => {
    const status = String(value ?? '').trim().toLowerCase();
    return isValidStatus(status) ? status : '';
};

const toWesternDigits = (value) => String(value ?? '')
    .replace(/[۰-۹]/g, (digit) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
    .replace(/[٠-٩]/g, (digit) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)));

const dateFromValue = (value) => {
    if (value instanceof Date && !Number.isNaN(value.getTime())) {
        return new Date(value.getTime());
    }

    if (typeof value !== 'string' && typeof value !== 'number') {
        return null;
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
};

const dateOnlyFromValue = (value) => {
    if (typeof value === 'string') {
        const match = value.match(/^(\d{4}-\d{2}-\d{2})/);
        if (match) {
            const date = dateFromValue(`${match[1]}T12:00:00`);
            if (date) {
                return date;
            }
        }
    }

    return dateFromValue(value);
};

const dateFormatter = () => {
    try {
        return new Intl.DateTimeFormat('fa-IR-u-ca-persian-nu-latn', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return null;
    }
};

/** Format a date as Persian weekday, Jalali day, month, and year. */
export function formatJalaliDate(value) {
    const date = dateOnlyFromValue(value);
    if (!date) {
        return MISSING_VALUE;
    }

    const formatter = dateFormatter();
    if (!formatter) {
        return toWesternDigits(date.toISOString().slice(0, 10));
    }

    const parts = formatter.formatToParts(date).reduce((result, part) => {
        if (part.type !== 'literal') {
            result[part.type] = part.value;
        }
        return result;
    }, {});

    return [parts.weekday, parts.day, parts.month, parts.year]
        .filter(Boolean)
        .map(toWesternDigits)
        .join(' ')
        .trim() || MISSING_VALUE;
}

const extractTime = (value) => {
    if (typeof value === 'string') {
        const match = value.match(/(?:T|\s)(\d{1,2}):(\d{2})/);
        if (match) {
            return `${match[1].padStart(2, '0')}:${match[2]}`;
        }

        const timeOnly = value.match(/^(\d{1,2}):(\d{2})/);
        if (timeOnly) {
            return `${timeOnly[1].padStart(2, '0')}:${timeOnly[2]}`;
        }
    }

    const date = dateFromValue(value);
    return date
        ? `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`
        : MISSING_VALUE;
};

const resolveRoot = (element) => {
    if (typeof element === 'string' && typeof document !== 'undefined') {
        return document.querySelector(element);
    }

    return element || null;
};

const queryFirst = (root, selectors) => {
    if (!root || typeof root.querySelector !== 'function') {
        return null;
    }

    for (const selector of selectors) {
        const element = root.querySelector(selector);
        if (element) {
            return element;
        }
    }

    return null;
};

const fieldSelectors = (field) => [
    `[data-calendar-drawer-field="${field}"]`,
    `[x-text="${field}"]`,
    ...(field === 'notes' ? ['[x-text^="notes"]'] : []),
];

const getField = (root, field) => queryFirst(root, fieldSelectors(field));

const setFieldText = (root, field, value) => {
    const element = getField(root, field);
    if (element) {
        element.textContent = value;
    }
};

const eventProperties = (eventData) => {
    if (!eventData || typeof eventData !== 'object') {
        return {};
    }

    return eventData.extendedProps && typeof eventData.extendedProps === 'object'
        ? eventData.extendedProps
        : eventData;
};

const eventValue = (eventData, properties, key) => eventData?.[key] ?? properties[key];

/** Normalize FullCalendar or API event data into the drawer's display model. */
export function normalizeDrawerEvent(eventData, options = {}) {
    const properties = eventProperties(eventData);
    const status = normalizeStatus(eventValue(eventData, properties, 'status'));
    const studentName = normalizeText(eventValue(eventData, properties, 'studentName'));
    const teacherName = normalizeText(eventValue(eventData, properties, 'teacherName'));
    const instrumentName = normalizeText(eventValue(eventData, properties, 'instrumentName'));
    const sessionDate = eventValue(eventData, properties, 'session_date')
        ?? eventValue(eventData, properties, 'sessionDate')
        ?? eventData?.start;
    const start = eventValue(eventData, properties, 'start');
    const notesValue = eventValue(eventData, properties, 'notes');
    const durationValue = eventValue(eventData, properties, 'duration_minutes')
        ?? eventValue(eventData, properties, 'duration');
    const duration = durationValue !== null
        && durationValue !== undefined
        && String(durationValue).trim() !== ''
        && Number.isFinite(Number(durationValue))
        ? toWesternDigits(String(durationValue))
        : MISSING_VALUE;
    const statusLabels = options.statusLabels || {};
    const statusLabel = status && statusLabels[status]
        ? String(statusLabels[status])
        : (status ? STATUS_LABELS[status] : MISSING_VALUE);

    return {
        id: eventData?.id ?? properties.id ?? null,
        status,
        statusLabel,
        statusClass: getStatusBadgeClass(status),
        studentName,
        teacherName,
        instrumentName,
        sessionDate: formatJalaliDate(sessionDate),
        startTime: extractTime(start),
        duration,
        room: normalizeText(eventValue(eventData, properties, 'room')),
        notes: normalizeText(notesValue, options.noNotes ?? NO_NOTES),
        ariaLabel: getEventAriaLabel(studentName, status),
        raw: eventData ?? null,
    };
}

const getAlpineData = (root) => {
    const alpine = globalThis.Alpine;
    if (!alpine || typeof alpine.$data !== 'function' || !root) {
        return null;
    }

    try {
        return alpine.$data(root) || null;
    } catch {
        return null;
    }
};

const focusElement = (element) => {
    if (!element || typeof element.focus !== 'function') {
        return false;
    }

    try {
        element.focus({ preventScroll: true });
    } catch {
        element.focus();
    }

    return true;
};

const isFocusableTrigger = (element) => Boolean(
    element
    && typeof element.focus === 'function'
    && (element.nodeType === 1 || element.nodeType === 9),
);

/**
 * Initialize the event detail drawer.
 *
 * @param {Element|string} element Drawer root or selector.
 * @param {{statusLabels?: Object, noNotes?: string}} options
 * @returns {{open: Function, close: Function, populate: Function, isOpen: Function, getState: Function, destroy: Function}}
 */
export default function initDrawer(element, options = {}) {
    const root = resolveRoot(element);
    if (!root || typeof root.querySelector !== 'function') {
        throw new TypeError('Drawer element is required.');
    }

    const overlay = queryFirst(root, [
        '[data-calendar-drawer-overlay]',
        '.calendar-drawer__overlay',
    ]);
    const panel = queryFirst(root, [
        '[data-calendar-drawer-panel]',
        '.calendar-drawer__panel',
    ]);
    const closeButton = queryFirst(root, [
        '[data-calendar-drawer-close]',
        '.calendar-drawer__close',
    ]);
    const drawerDocument = root.ownerDocument || (typeof document !== 'undefined' ? document : null);
    const drawerWindow = drawerDocument?.defaultView || (typeof window !== 'undefined' ? window : null);
    let alpineData = getAlpineData(root);
    let openState = false;
    let currentEvent = null;
    let triggerElement = null;
    let destroyed = false;
    let reducedMotion = false;
    let presentation = 'side';
    let mediaQuery = null;

    const setFallbackVisibility = () => {
        if (alpineData) {
            return;
        }

        if (overlay) {
            overlay.hidden = !openState;
        }
        if (panel) {
            panel.hidden = !openState;
        }
    };

    const updateResponsiveHooks = () => {
        const isBottomSheet = Boolean(drawerWindow && drawerWindow.innerWidth < MOBILE_BREAKPOINT);
        presentation = isBottomSheet ? 'bottom-sheet' : 'side';
        root.dataset.calendarDrawerPresentation = presentation;
        root.dataset.calendarDrawerResponsive = presentation;
        if (panel) {
            panel.dataset.calendarDrawerPresentation = presentation;
        }
    };

    const updateMotionHooks = () => {
        reducedMotion = Boolean(mediaQuery?.matches);
        root.dataset.calendarDrawerReducedMotion = String(reducedMotion);
        root.dataset.reducedMotion = String(reducedMotion);
    };

    const syncDomState = (detail) => {
        root.dataset.calendarDrawerOpen = String(openState);
        root.dataset.calendarDrawerState = openState ? 'open' : 'closed';
        root.setAttribute('aria-hidden', String(!openState));

        if (panel) {
            panel.setAttribute('aria-hidden', String(!openState));
        }
        if (overlay) {
            overlay.setAttribute('aria-hidden', 'true');
        }
        if (triggerElement && typeof triggerElement.setAttribute === 'function') {
            triggerElement.setAttribute('aria-expanded', String(openState));
            if (panel?.id) {
                triggerElement.setAttribute('aria-controls', panel.id);
            }
            if (detail?.ariaLabel) {
                triggerElement.setAttribute('aria-label', detail.ariaLabel);
            }
        }

        setFallbackVisibility();
    };

    const syncAlpineState = (detail) => {
        alpineData = alpineData || getAlpineData(root);
        if (!alpineData) {
            return;
        }

        alpineData.open = openState;
        alpineData.status = detail?.status || '';
        alpineData.statusLabel = detail?.statusLabel || MISSING_VALUE;
        alpineData.statusClass = detail?.statusClass || '';
        alpineData.studentName = detail?.studentName || MISSING_VALUE;
        alpineData.teacherName = detail?.teacherName || MISSING_VALUE;
        alpineData.instrumentName = detail?.instrumentName || MISSING_VALUE;
        alpineData.sessionDate = detail?.sessionDate || MISSING_VALUE;
        alpineData.startTime = detail?.startTime || MISSING_VALUE;
        alpineData.duration = detail?.duration || MISSING_VALUE;
        alpineData.room = detail?.room || MISSING_VALUE;
        alpineData.notes = detail?.notes || options.noNotes || NO_NOTES;
    };

    const populateDom = (detail) => {
        setFieldText(root, 'studentName', detail.studentName);
        setFieldText(root, 'teacherName', detail.teacherName);
        setFieldText(root, 'instrumentName', detail.instrumentName);
        setFieldText(root, 'sessionDate', detail.sessionDate);
        setFieldText(root, 'startTime', detail.startTime);
        setFieldText(root, 'duration', detail.duration);
        setFieldText(root, 'room', detail.room);
        setFieldText(root, 'notes', detail.notes);

        const statusHook = queryFirst(root, [
            '[data-calendar-status-hook]',
            '.calendar-drawer__status-badge',
        ]);
        if (statusHook) {
            Object.values(STATUS_BADGE_CLASSES).forEach((className) => {
                statusHook.classList.remove(className);
            });
            if (detail.statusClass) {
                statusHook.classList.add(detail.statusClass);
            }
            statusHook.dataset.status = detail.status;
            statusHook.textContent = detail.statusLabel;
        }
    };

    const captureTrigger = (eventData, explicitTrigger) => {
        const candidate = explicitTrigger
            || eventData?.trigger
            || eventData?.el
            || eventData?.jsEvent?.currentTarget
            || eventData?.jsEvent?.target;

        if (isFocusableTrigger(candidate)) {
            triggerElement = candidate;
            return triggerElement;
        }

        const activeElement = drawerDocument?.activeElement;
        triggerElement = isFocusableTrigger(activeElement) && !root.contains(activeElement)
            ? activeElement
            : null;
        return triggerElement;
    };

    const populate = (eventData) => {
        currentEvent = normalizeDrawerEvent(eventData, options);
        populateDom(currentEvent);
        syncAlpineState(currentEvent);
        return currentEvent;
    };

    const open = (eventData, explicitTrigger) => {
        if (destroyed) {
            return null;
        }

        captureTrigger(eventData, explicitTrigger);
        const detail = populate(eventData);
        openState = true;
        syncAlpineState(detail);
        syncDomState(detail);
        return detail;
    };

    const close = ({ restoreFocus = true } = {}) => {
        if (destroyed || !openState) {
            return false;
        }

        openState = false;
        syncAlpineState(currentEvent || {});
        syncDomState(currentEvent || {});

        const previousTrigger = triggerElement;
        triggerElement = null;
        if (restoreFocus) {
            focusElement(previousTrigger);
        }

        return true;
    };

    const handleOverlayClick = (event) => {
        if (event.target === overlay) {
            close();
        }
    };

    const handleKeydown = (event) => {
        if (openState && event.key === 'Escape') {
            event.preventDefault();
            close();
        }
    };

    const handleResize = () => {
        updateResponsiveHooks();
    };

    const attachAlpine = () => {
        const data = getAlpineData(root);
        if (!data) {
            return false;
        }

        alpineData = data;
        data.close = close;
        data.openDrawer = open;
        data.populate = populate;
        data.statusClass = currentEvent?.statusClass || '';
        syncAlpineState(currentEvent || {});
        syncDomState(currentEvent || {});
        return true;
    };

    const handleAlpineReady = () => {
        attachAlpine();
    };

    const handleMediaChange = () => {
        updateMotionHooks();
    };

    if (overlay) {
        overlay.addEventListener('click', handleOverlayClick);
    }
    if (closeButton) {
        closeButton.addEventListener('click', close);
    }
    if (drawerDocument) {
        drawerDocument.addEventListener('keydown', handleKeydown, true);
        drawerDocument.addEventListener('alpine:initialized', handleAlpineReady);
    }
    if (drawerWindow) {
        drawerWindow.addEventListener('resize', handleResize);
        mediaQuery = typeof drawerWindow.matchMedia === 'function'
            ? drawerWindow.matchMedia('(prefers-reduced-motion: reduce)')
            : null;
        if (mediaQuery) {
            if (typeof mediaQuery.addEventListener === 'function') {
                mediaQuery.addEventListener('change', handleMediaChange);
            } else if (typeof mediaQuery.addListener === 'function') {
                mediaQuery.addListener(handleMediaChange);
            }
        }
    }

    updateResponsiveHooks();
    updateMotionHooks();
    attachAlpine();
    syncDomState({});

    return {
        open,
        close,
        populate,
        isOpen: () => openState,
        getState: () => ({
            open: openState,
            event: currentEvent,
            trigger: triggerElement,
            reducedMotion,
            presentation,
        }),
        destroy() {
            if (destroyed) {
                return;
            }

            close({ restoreFocus: false });
            destroyed = true;
            if (overlay) {
                overlay.removeEventListener('click', handleOverlayClick);
            }
            if (closeButton) {
                closeButton.removeEventListener('click', close);
            }
            if (drawerDocument) {
                drawerDocument.removeEventListener('keydown', handleKeydown, true);
                drawerDocument.removeEventListener('alpine:initialized', handleAlpineReady);
            }
            if (drawerWindow) {
                drawerWindow.removeEventListener('resize', handleResize);
            }
            if (mediaQuery) {
                if (typeof mediaQuery.removeEventListener === 'function') {
                    mediaQuery.removeEventListener('change', handleMediaChange);
                } else if (typeof mediaQuery.removeListener === 'function') {
                    mediaQuery.removeListener(handleMediaChange);
                }
            }
        },
    };
}
