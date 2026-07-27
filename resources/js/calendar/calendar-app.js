/**
 * Admin calendar orchestrator.
 *
 * This is the only module that composes calendar siblings. Sibling modules
 * communicate through callbacks supplied here and never import one another.
 */
import initFullCalendar from './fullcalendar.js';
import initSidebar from './sidebar.js';
import initDrawer from './drawer.js';
import initFilters from './filters.js';
import { formatJalaliFullDate, toDate, toIsoDate } from './utils/jalali.js';

const ROOT_SELECTOR = '[data-calendar-root]';

const getCalendarLabels = (root) => ({
    loading: root.dataset.calendarMessageLoading,
    empty: root.dataset.calendarMessageEmpty,
    error: root.dataset.calendarMessageError,
    noNotes: root.dataset.calendarMessageNoNotes,
    statusLabels: {
        scheduled: root.dataset.calendarStatusScheduled,
        completed: root.dataset.calendarStatusCompleted,
        cancelled: root.dataset.calendarStatusCancelled,
        missed: root.dataset.calendarStatusMissed,
    },
});

const isElement = (value) => Boolean(value && value.nodeType === 1 && typeof value.querySelector === 'function');

const find = (root, selector) => root?.querySelector(selector) || null;

const resolveDate = (value, fallback = new Date()) => {
    try {
        return toDate(value ?? fallback);
    } catch {
        return toDate(fallback);
    }
};

const setHidden = (element, hidden) => {
    if (element) {
        element.hidden = hidden;
    }
};

/**
 * Initialize one calendar root and return its lifecycle API.
 *
 * @param {Element|string|null} container Calendar root or selector.
 * @returns {Promise<Object|null>}
 */
export default async function initCalendarApp(container = null) {
    const root = typeof container === 'string' && typeof document !== 'undefined'
        ? document.querySelector(container)
        : (container || (typeof document !== 'undefined' ? document.querySelector(ROOT_SELECTOR) : null));

    if (!root) {
        return null;
    }

    if (!isElement(root)) {
        throw new TypeError('Calendar root must be a valid element.');
    }

    const labels = getCalendarLabels(root);
    const mount = find(root, '[data-calendar-mount], [data-fullcalendar-mount]');
    const sidebarElement = find(root, '[data-calendar-week-sidebar]');
    const filtersElement = find(root, '[data-calendar-filters]');
    const drawerElement = find(root, '[data-calendar-drawer]');
    const timeline = find(root, '[data-calendar-timeline]');
    const errorElement = find(timeline || root, '[data-calendar-error]');
    const errorMessageElement = find(timeline || root, '[data-calendar-error-message]');
    const retryElement = find(timeline || root, '[data-calendar-retry]');
    const currentDateElement = find(root, '[data-calendar-current-date]');
    const previousElement = find(root, '[data-calendar-prev-day], [data-calendar-action="previous"]');
    const nextElement = find(root, '[data-calendar-next-day], [data-calendar-action="next"]');
    const todayElement = find(root, '[data-calendar-today], [data-calendar-action="today"]');

    if (!mount || !sidebarElement || !filtersElement || !drawerElement) {
        throw new Error('Calendar markup is incomplete.');
    }

    let selectedDate = resolveDate(root.dataset.calendarDate || root.dataset.calendarSelectedDate);
    let calendar = null;
    let sidebar = null;
    let filters = null;
    let drawer = null;
    let destroyed = false;
    let initializing = false;
    let initializationError = null;

    const updateHeader = (date) => {
        selectedDate = resolveDate(date, selectedDate);
        if (currentDateElement) {
            currentDateElement.textContent = formatJalaliFullDate(selectedDate);
        }
        root.dataset.calendarSelectedDate = toIsoDate(selectedDate);
    };

    const clearInitializationError = () => {
        initializationError = null;
        if (errorElement && !calendar) {
            setHidden(errorElement, true);
        }
        root.dataset.calendarAppState = 'ready';
    };

    const showInitializationError = (error) => {
        initializationError = error;
        root.dataset.calendarAppState = 'error';
        setHidden(errorElement, false);
        if (errorMessageElement) {
            errorMessageElement.textContent = labels.error;
        }
    };

    const handleDrawerClose = (detail) => {
        root.dataset.calendarDrawerState = 'closed';
        return detail;
    };

    const handleFocusRestoration = (trigger) => {
        if (trigger && typeof trigger.focus === 'function' && root.contains(trigger)) {
            return trigger;
        }

        return null;
    };

    const handleDateChange = (date) => {
        updateHeader(date);
        sidebar?.setDate(selectedDate, { notify: false });
    };

    const handleSidebarDateSelect = (date) => {
        const nextDate = resolveDate(date, selectedDate);
        updateHeader(nextDate);
        calendar?.gotoDate(nextDate);
    };

    const handleFilterChange = () => {
        calendar?.refetchEvents();
    };

    const handleEventClick = (event, clickInfo) => {
        const trigger = clickInfo?.el || clickInfo?.jsEvent?.currentTarget || clickInfo?.jsEvent?.target;
        const detail = drawer?.open(event, trigger);
        if (detail) {
            root.dataset.calendarDrawerState = 'open';
        }
        return detail;
    };

    const navigate = (method, fallbackOffset = 0) => {
        if (calendar?.[method]) {
            calendar[method]();
            return;
        }

        if (fallbackOffset !== 0) {
            const fallbackDate = new Date(selectedDate.getTime());
            fallbackDate.setDate(fallbackDate.getDate() + fallbackOffset);
            handleSidebarDateSelect(fallbackDate);
        }
    };

    const handlePrevious = () => navigate('prev', -1);
    const handleNext = () => navigate('next', 1);
    const handleToday = () => {
        const today = resolveDate(new Date());
        if (toIsoDate(today) === toIsoDate(selectedDate)) {
            return;
        }

        if (calendar?.today) {
            calendar.today();
            return;
        }

        handleSidebarDateSelect(today);
    };

    const handleNavigationKeydown = (event) => {
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            handlePrevious();
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            handleNext();
        }
    };

    const destroyModules = () => {
        calendar?.destroy?.();
        drawer?.destroy?.();
        filters?.destroy?.();
        sidebar?.destroy?.();
        calendar = null;
        drawer = null;
        filters = null;
        sidebar = null;
    };

    const initializeModules = async () => {
        if (destroyed) {
            return false;
        }

        initializing = true;
        clearInitializationError();
        destroyModules();
        updateHeader(selectedDate);

        try {
            sidebar = initSidebar(sidebarElement, {
                initialDate: selectedDate,
                onDaySelect: handleSidebarDateSelect,
            });

            filters = initFilters(filtersElement, {
                onFilterChange: handleFilterChange,
                onMobileToggle: (expanded) => {
                    root.dataset.calendarFiltersExpanded = String(expanded);
                },
            });

            drawer = initDrawer(drawerElement, {
                noNotes: labels.noNotes,
                statusLabels: labels.statusLabels,
                onClose: handleDrawerClose,
                onFocusRestore: handleFocusRestoration,
            });

            calendar = await initFullCalendar(mount, {
                initialDate: selectedDate,
                eventsUrl: root.dataset.eventsUrl,
                getSelectedDate: () => selectedDate,
                getFilters: () => filters?.getFilters() || {},
                messages: labels,
                onDateChange: handleDateChange,
                onEventClick: handleEventClick,
                onRetry: () => root.dataset.calendarAppState = 'ready',
            });

            root.dataset.calendarAppState = 'ready';
            return true;
        } catch (error) {
            destroyModules();
            showInitializationError(error);
            return false;
        } finally {
            initializing = false;
        }
    };

    const retryInitialization = async () => {
        if (destroyed || initializing) {
            return false;
        }

        return initializeModules();
    };

    const handleRetry = () => {
        if (!calendar) {
            void retryInitialization();
        }
    };

    previousElement?.addEventListener('click', handlePrevious);
    nextElement?.addEventListener('click', handleNext);
    todayElement?.addEventListener('click', handleToday);
    previousElement?.addEventListener('keydown', handleNavigationKeydown);
    nextElement?.addEventListener('keydown', handleNavigationKeydown);
    retryElement?.addEventListener('click', handleRetry);

    updateHeader(selectedDate);
    await initializeModules();

    return {
        root,
        calendar: () => calendar,
        sidebar: () => sidebar,
        filters: () => filters,
        drawer: () => drawer,
        getSelectedDate: () => new Date(selectedDate.getTime()),
        retry: retryInitialization,
        closeDrawer: (options) => {
            const wasOpen = drawer?.isOpen?.() === true;
            const trigger = drawer?.getState?.().trigger;
            const result = drawer?.close?.(options) || false;
            if (wasOpen && result) {
                handleDrawerClose(drawer.getState?.());
                handleFocusRestoration(trigger);
            }
            return result;
        },
        getState: () => ({
            initialized: Boolean(calendar),
            error: initializationError,
            selectedDate: toIsoDate(selectedDate),
        }),
        destroy: () => {
            if (destroyed) {
                return;
            }

            destroyed = true;
            previousElement?.removeEventListener('click', handlePrevious);
            nextElement?.removeEventListener('click', handleNext);
            todayElement?.removeEventListener('click', handleToday);
            previousElement?.removeEventListener('keydown', handleNavigationKeydown);
            nextElement?.removeEventListener('keydown', handleNavigationKeydown);
            retryElement?.removeEventListener('click', handleRetry);
            destroyModules();
            root.dataset.calendarAppState = 'destroyed';
        },
    };
}

const bootstrapCalendar = () => {
    const root = document.querySelector(ROOT_SELECTOR);
    if (!root || root.dataset.calendarAppBooted === 'true') {
        return;
    }

    root.dataset.calendarAppBooted = 'true';
    void initCalendarApp(root);
};

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrapCalendar, { once: true });
    } else {
        bootstrapCalendar();
    }
}
