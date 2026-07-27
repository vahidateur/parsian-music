/**
 * FullCalendar integration for the admin calendar.
 * Responsibility: lazy library loading, event feed, rendering, and lifecycle state.
 * Sibling imports: none. The calendar-app orchestrator owns module composition.
 */
import {
    formatJalaliDay,
    formatJalaliFullDate,
    formatJalaliMonth,
    formatTime,
    getPersianDayName,
    toWesternDigits,
} from './utils/jalali.js';

export const MAX_RETRY_ATTEMPTS = 3;
export const RETRY_BACKOFF_MS = Object.freeze([250, 500, 1000]);
export const REQUEST_TIMEOUT_MS = 5000;

/** Concurrent sessions are stacked, not squeezed: narrow viewports show one card per slot. */
export const NARROW_VIEWPORT_QUERY = '(max-width: 767px)';
export const NARROW_EVENT_MAX_STACK = 1;
export const WIDE_EVENT_MAX_STACK = 3;

const VALID_STATUSES = new Set(['scheduled', 'completed', 'cancelled', 'missed']);

const isElement = (value) => Boolean(value && value.nodeType === 1 && typeof value.querySelector === 'function');

const toDate = (value) => {
    if (value instanceof Date) {
        return new Date(value.getTime());
    }

    if (typeof value !== 'string' && typeof value !== 'number') {
        return null;
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
};

export const formatDateOnly = (value) => {
    const date = toDate(value);

    if (!date) {
        return '';
    }

    const year = String(date.getFullYear()).padStart(4, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const displayValue = (value) => {
    const text = String(value ?? '').trim();
    return text || '—';
};

const eventProps = (event) => event?.extendedProps ?? event ?? {};

const eventDateTime = (event, key) => {
    const value = event?.[key] ?? event?.[`${key}Str`];
    return toDate(value);
};

const eventTime = (event, key) => {
    const value = event?.[key] ?? event?.[`${key}Str`];

    if (typeof value === 'string') {
        const match = value.match(/(?:T|\s)(\d{2}:\d{2})/);
        if (match) {
            return match[1];
        }
    }

    const date = eventDateTime(event, key);
    return date ? `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}` : '—';
};

export const normalizeEventPayload = (payload) => {
    if (!payload || typeof payload !== 'object') {
        return null;
    }

    const start = eventDateTime(payload, 'start');
    const end = eventDateTime(payload, 'end');
    const status = payload.status ?? eventProps(payload).status;
    const id = payload.id;

    if (id === null || id === undefined || String(id).trim() === '' || !start || !end || end <= start) {
        return null;
    }

    if (!VALID_STATUSES.has(status)) {
        return null;
    }

    const props = eventProps(payload);
    const studentName = payload.studentName ?? props.studentName;
    const teacherName = payload.teacherName ?? props.teacherName;
    const instrumentName = payload.instrumentName ?? props.instrumentName;

    return {
        ...payload,
        id,
        title: displayValue(payload.title ?? `${displayValue(studentName)} — ${displayValue(instrumentName)}`),
        start: payload.start,
        end: payload.end,
        status,
        studentName: displayValue(studentName),
        teacherName: displayValue(teacherName),
        instrumentName: displayValue(instrumentName),
        room: displayValue(payload.room ?? props.room),
        extendedProps: {
            ...props,
            status,
            studentName: displayValue(studentName),
            teacherName: displayValue(teacherName),
            instrumentName: displayValue(instrumentName),
            room: displayValue(payload.room ?? props.room),
        },
    };
};

export const normalizeEventCollection = (payload) => {
    const values = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : []);
    return values.map(normalizeEventPayload).filter(Boolean);
};

export const serializeFilters = (filters = {}) => Object.entries(filters).reduce((params, [key, value]) => {
    if (value !== null && value !== undefined && value !== '' && value !== 'all') {
        params.set(key, String(value));
    }

    return params;
}, new URLSearchParams());

export const buildEventCardHtml = (event) => {
    const props = eventProps(event);
    const status = event.status ?? props.status;
    const studentName = displayValue(event.studentName ?? props.studentName);
    const teacherName = displayValue(event.teacherName ?? props.teacherName);
    const instrumentName = displayValue(event.instrumentName ?? props.instrumentName);
    const room = displayValue(event.room ?? props.room);
    const timeRange = `${eventTime(event, 'start')}–${eventTime(event, 'end')}`;
    const ariaLabel = `${studentName} – ${displayValue(status)}`;

    return `<div class="calendar-timegrid-event-content" data-status="${escapeHtml(status)}" role="button" tabindex="0" aria-label="${escapeHtml(ariaLabel)}">` +
        `<strong class="calendar-timegrid-event-content__primary">${escapeHtml(studentName)}</strong>` +
        `<span class="calendar-timegrid-event-content__secondary">${escapeHtml(teacherName)}</span>` +
        `<span class="calendar-timegrid-event-content__meta">` +
        `<span class="calendar-timegrid-event-content__time">${escapeHtml(timeRange)}</span>` +
        `<span class="calendar-timegrid-event-content__room">${escapeHtml(room)}</span>` +
        `<span class="calendar-timegrid-event-content__instrument">${escapeHtml(instrumentName)}</span>` +
        `</span>` +
        `</div>`;
};

const getRoot = (mount) => mount.closest('[data-calendar-root]') || mount.closest('.calendar') || mount.parentElement;

const getStateElements = (mount) => {
    const timeline = mount.closest('[data-calendar-timeline]') || mount.parentElement;
    return {
        timeline,
        loading: timeline?.querySelector('[data-calendar-loading]'),
        empty: timeline?.querySelector('[data-calendar-empty]'),
        error: timeline?.querySelector('[data-calendar-error]'),
        errorMessage: timeline?.querySelector('[data-calendar-error-message]'),
        retry: timeline?.querySelector('[data-calendar-retry]'),
        status: timeline?.querySelector('[data-calendar-status]'),
    };
};

const setHidden = (element, hidden) => {
    if (element) {
        element.hidden = hidden;
    }
};

const resolveMessages = (root, options) => {
    const provided = options.messages || {};
    const dataset = root?.dataset || {};

    return {
        loading: provided.loading || dataset.calendarMessageLoading || '',
        empty: provided.empty || dataset.calendarMessageEmpty || '',
        error: provided.error || dataset.calendarMessageError || '',
    };
};

const wait = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

const createUrl = (eventsUrl, selectedDate, filters) => {
    const url = new URL(eventsUrl || '/admin/calendar/events', typeof window !== 'undefined' ? window.location.origin : 'http://localhost');
    url.searchParams.set('start', selectedDate);
    url.searchParams.set('end', selectedDate);
    serializeFilters(filters).forEach((value, key) => url.searchParams.set(key, value));
    return url.toString();
};

const fetchWithRetry = async (url, fetchImpl, state) => {
    let lastError;

    for (let attempt = 0; attempt < MAX_RETRY_ATTEMPTS; attempt += 1) {
        if (state.destroyed) {
            throw new DOMException('Calendar destroyed', 'AbortError');
        }

        const controller = new AbortController();
        state.controller = controller;
        const timeoutId = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

        try {
            const response = await fetchImpl(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(`Calendar feed failed with status ${response.status}`);
            }

            const payload = await response.json();
            clearTimeout(timeoutId);
            state.controller = null;
            return payload;
        } catch (error) {
            clearTimeout(timeoutId);
            state.controller = null;
            lastError = error;

            if (error?.name === 'AbortError' || state.destroyed || attempt === MAX_RETRY_ATTEMPTS - 1) {
                throw error;
            }

            await wait(RETRY_BACKOFF_MS[Math.min(attempt, RETRY_BACKOFF_MS.length - 1)]);
        }
    }

    throw lastError;
};

const resolveFetch = (options) => {
    if (typeof options.fetch === 'function') {
        return options.fetch;
    }

    if (typeof globalThis.fetch === 'function') {
        return globalThis.fetch.bind(globalThis);
    }

    throw new Error('A fetch implementation is required to load calendar events');
};

const resolveFilters = (options) => {
    if (typeof options.getFilters === 'function') {
        return options.getFilters() || {};
    }

    return typeof options.filters === 'function' ? options.filters() || {} : (options.filters || {});
};

const resolveSelectedDate = (fetchInfo, options, calendar) => {
    const selectedDate = typeof options.getSelectedDate === 'function' ? options.getSelectedDate() : options.selectedDate;
    return formatDateOnly(selectedDate || calendar?.getDate?.() || fetchInfo?.start);
};

const initializeStateUi = (elements) => {
    setHidden(elements.loading, false);
    setHidden(elements.empty, true);
    setHidden(elements.error, true);
};

/** FullCalendar hands verbose date parts to custom formatters; rebuild a local date from them. */
const dateFromVerboseArg = (verbose) => (verbose && Number.isInteger(verbose.year)
    ? new Date(verbose.year, verbose.month ?? 0, verbose.day ?? 1)
    : null);

/** Toolbar title in Jalali with Western digits, replacing the locale's Persian numerals. */
export const formatToolbarTitle = (info) => {
    const date = dateFromVerboseArg(info?.date) ?? dateFromVerboseArg(info?.start);
    return date ? formatJalaliFullDate(date) : '';
};

/** Day header in Jalali: Persian weekday plus Western-digit day and month name. */
export const formatDayHeader = (date) => (date
    ? `${getPersianDayName(date)} ${formatJalaliDay(date)} ${formatJalaliMonth(date)}`
    : '');

const resolveMediaQuery = (mount, query) => {
    const view = mount?.ownerDocument?.defaultView || (typeof window !== 'undefined' ? window : null);
    return typeof view?.matchMedia === 'function' ? view.matchMedia(query) : null;
};

const resolveEventMaxStack = (mediaQuery) => (mediaQuery?.matches
    ? NARROW_EVENT_MAX_STACK
    : WIDE_EVENT_MAX_STACK);

export default async function initFullCalendar(mount, callbacks = {}) {
    if (!isElement(mount)) {
        throw new TypeError('FullCalendar requires a valid mount element');
    }

    const options = callbacks || {};
    const root = getRoot(mount);
    const messages = resolveMessages(root, options);
    const elements = getStateElements(mount);
    const fetchImpl = resolveFetch(options);
    const stackQuery = resolveMediaQuery(mount, NARROW_VIEWPORT_QUERY);
    const state = {
        calendar: null,
        controller: null,
        destroyed: false,
        hasLoaded: false,
        lastEvents: [],
        lastSuccessfulMarkup: null,
        preservedContent: null,
        lastSuccessfulDate: null,
        failedDate: null,
        lastError: null,
        lastRequestId: 0,
        retrying: false,
    };

    initializeStateUi(elements);

    const updateUi = (kind, detail = {}) => {
        const hasPreviousContent = state.hasLoaded;

        if (kind === 'loading') {
            setHidden(elements.error, true);
            setHidden(elements.empty, true);
            setHidden(elements.loading, hasPreviousContent);
            if (elements.status) {
                elements.status.textContent = messages.loading;
            }
            options.onLoading?.(true, detail);
            return;
        }

        if (kind === 'success') {
            setHidden(elements.loading, true);
            setHidden(elements.error, true);
            setHidden(elements.empty, detail.events.length !== 0);
            if (elements.status) {
                elements.status.textContent = detail.events.length ? '' : messages.empty;
            }
            options.onLoading?.(false, detail);
            options.onEmpty?.(detail.events.length === 0, detail);
            return;
        }

        setHidden(elements.loading, true);
        setHidden(elements.empty, true);
        setHidden(elements.error, false);
        if (elements.errorMessage) {
            elements.errorMessage.textContent = messages.error;
        }
        if (elements.status) {
            elements.status.textContent = messages.error;
        }
        options.onLoading?.(false, detail);
        options.onError?.(detail.error, detail);
    };

    const removePreservedTimeline = () => {
        state.preservedContent?.remove();
        state.preservedContent = null;
    };

    const captureSuccessfulTimeline = () => {
        const timeline = mount.classList.contains('fc')
            ? mount
            : Array.from(mount.querySelectorAll('.fc'))
                .find((element) => !element.hasAttribute('data-calendar-preserved-content'));
        if (timeline) {
            state.lastSuccessfulMarkup = timeline.cloneNode(true);
        }
    };

    const restorePreservedTimeline = () => {
        if (state.preservedContent || state.lastEvents.length === 0) {
            return;
        }

        const snapshot = mount.ownerDocument.createElement('div');
        snapshot.innerHTML = state.lastEvents.map((event) =>
            `<div class="fc-event">${buildEventCardHtml(event)}</div>`
        ).join('');
        snapshot.setAttribute('data-calendar-preserved-content', 'true');
        snapshot.setAttribute('aria-hidden', 'true');
        snapshot.classList.add('calendar-day-timeline__preserved-content');
        (mount.parentElement || mount).append(snapshot);
        state.preservedContent = snapshot;
    };

    const retry = () => {
        if (state.destroyed || !state.calendar || state.retrying) {
            return Promise.resolve(false);
        }

        state.retrying = true;
        state.lastError = null;
        setHidden(elements.error, true);

        try {
            const currentDate = formatDateOnly(state.calendar.getDate?.());
            if (state.failedDate && state.failedDate !== currentDate) {
                state.calendar.gotoDate(state.failedDate);
            } else {
                state.calendar.refetchEvents();
            }
        } finally {
            state.retrying = false;
        }

        options.onRetry?.();
        return Promise.resolve(true);
    };

    if (elements.retry) {
        elements.retry.addEventListener('click', retry);
    }

    let Calendar;
    let timeGridPlugin;
    let interactionPlugin;
    let faLocale;

    try {
        [
            { Calendar },
            timeGridPlugin,
            interactionPlugin,
            faLocale,
        ] = await Promise.all([
            import('@fullcalendar/core'),
            import('@fullcalendar/timegrid'),
            import('@fullcalendar/interaction'),
            import('@fullcalendar/core/locales/fa'),
        ]);
    } catch (error) {
        updateUi('error', { error });
        elements.retry?.addEventListener('click', () => {
            if (!state.calendar) {
                initFullCalendar(mount, options);
            }
        }, { once: true });
        throw error;
    }

    const eventSource = async (fetchInfo, success) => {
        state.controller?.abort();
        const requestId = ++state.lastRequestId;
        const selectedDate = resolveSelectedDate(fetchInfo, options, state.calendar);
        const url = createUrl(options.eventsUrl || root?.dataset?.eventsUrl, selectedDate, resolveFilters(options));
        updateUi('loading', { selectedDate, url });

        try {
            const payload = await fetchWithRetry(url, fetchImpl, state);
            if (state.destroyed || requestId !== state.lastRequestId) {
                return;
            }

            const events = normalizeEventCollection(payload);
            removePreservedTimeline();
            state.hasLoaded = true;
            state.lastEvents = events;
            state.lastSuccessfulDate = selectedDate;
            state.failedDate = null;
            state.lastError = null;
            updateUi('success', { events, selectedDate, url });
            success(events);
        } catch (error) {
            if (state.destroyed || requestId !== state.lastRequestId || error?.name === 'AbortError') {
                return;
            }

            state.lastError = error;
            state.failedDate = state.lastSuccessfulDate === selectedDate && state.failedDate
                ? state.failedDate
                : selectedDate;
            updateUi('error', { error, selectedDate, url, events: state.lastEvents });
            success(state.lastEvents);
            setTimeout(restorePreservedTimeline, 0);
        }
    };

    const eventContent = ({ event }) => {
        const template = document.createElement('template');
        template.innerHTML = buildEventCardHtml(event);
        const content = template.content.firstElementChild;

        return content ? { domNodes: [content] } : { html: '' };
    };

    const eventDidMount = ({ event, el }) => {
        const content = el.querySelector('.calendar-timegrid-event-content');
        if (!content) {
            return;
        }

        const handleKeydown = (keyboardEvent) => {
            if (keyboardEvent.key !== 'Enter' && keyboardEvent.key !== ' ') {
                return;
            }

            keyboardEvent.preventDefault();
            keyboardEvent.stopPropagation();
            options.onEventClick?.(event, { event, el: content, jsEvent: keyboardEvent });
        };

        content.addEventListener('keydown', handleKeydown);
        content._calendarEventKeydown = handleKeydown;
    };

    const eventWillUnmount = ({ el }) => {
        const content = el.querySelector('.calendar-timegrid-event-content');
        if (content?._calendarEventKeydown) {
            content.removeEventListener('keydown', content._calendarEventKeydown);
            delete content._calendarEventKeydown;
        }
    };

    try {
        state.calendar = new Calendar(mount, {
            plugins: [timeGridPlugin.default ?? timeGridPlugin, interactionPlugin.default ?? interactionPlugin],
            initialView: 'timeGridDay',
            initialDate: options.initialDate,
            locale: 'fa',
            locales: [faLocale.default ?? faLocale],
            direction: 'rtl',
            firstDay: 6,
            slotDuration: '00:30:00',
            slotMinTime: '08:00:00',
            slotMaxTime: '22:00:00',
            slotLabelInterval: '00:30:00',
            slotLabelContent: (arg) => formatTime(arg.date),
            dayHeaderContent: (arg) => formatDayHeader(arg.date),
            titleFormat: formatToolbarTitle,
            dayPopoverFormat: formatToolbarTitle,
            allDaySlot: false,
            nowIndicator: true,
            expandRows: true,
            height: 'auto',
            slotEventOverlap: false,
            eventMaxStack: resolveEventMaxStack(stackQuery),
            moreLinkClick: 'popover',
            moreLinkContent: (arg) => toWesternDigits(`+${arg.num}`),
            headerToolbar: { start: 'prev,next today', center: 'title', end: '' },
            events: eventSource,
            eventContent,
            eventDidMount,
            eventWillUnmount,
            eventClick: (arg) => {
                const eventContent = arg.el.querySelector('.calendar-timegrid-event-content') ?? arg.el;
                options.onEventClick?.(arg.event, { ...arg, el: eventContent });
            },
            eventsSet: () => {
                if (state.hasLoaded && !state.lastError) {
                    captureSuccessfulTimeline();
                }
            },
            datesSet: (arg) => options.onDateChange?.(formatDateOnly(arg.start), arg),
            loading: (isLoading) => {
                if (!isLoading && state.hasLoaded && !state.lastError) {
                    updateUi('success', { events: state.lastEvents });
                    setTimeout(captureSuccessfulTimeline, 0);
                }
            },
        });

        state.calendar.render();
    } catch (error) {
        updateUi('error', { error });
        throw error;
    }

    const handleStackQueryChange = () => {
        state.calendar?.setOption('eventMaxStack', resolveEventMaxStack(stackQuery));
    };

    if (stackQuery) {
        if (typeof stackQuery.addEventListener === 'function') {
            stackQuery.addEventListener('change', handleStackQueryChange);
        } else if (typeof stackQuery.addListener === 'function') {
            stackQuery.addListener(handleStackQueryChange);
        }
    }

    return {
        calendar: state.calendar,
        getApi: () => state.calendar,
        gotoDate: (date) => state.calendar?.gotoDate(date),
        next: () => state.calendar?.next(),
        prev: () => state.calendar?.prev(),
        today: () => state.calendar?.today(),
        refetchEvents: () => state.calendar?.refetchEvents(),
        retry,
        getState: () => ({
            hasLoaded: state.hasLoaded,
            events: [...state.lastEvents],
            error: state.lastError,
        }),
        destroy: () => {
            if (state.destroyed) {
                return;
            }

            state.destroyed = true;
            state.controller?.abort();
            elements.retry?.removeEventListener('click', retry);
            if (stackQuery) {
                if (typeof stackQuery.removeEventListener === 'function') {
                    stackQuery.removeEventListener('change', handleStackQueryChange);
                } else if (typeof stackQuery.removeListener === 'function') {
                    stackQuery.removeListener(handleStackQueryChange);
                }
            }
            state.calendar?.destroy();
        },
    };
}
