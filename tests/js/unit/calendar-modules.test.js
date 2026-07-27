import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import initSidebar from '../../../resources/js/calendar/sidebar.js';
import initFilters from '../../../resources/js/calendar/filters.js';
import initDrawer from '../../../resources/js/calendar/drawer.js';
import {
    buildEventCardHtml,
    normalizeEventCollection,
    normalizeEventPayload,
} from '../../../resources/js/calendar/fullcalendar.js';
import {
    createDrawerFixture,
    createElement,
    createFilterFixture,
    createSidebarFixture,
} from '../support/dom-harness.js';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const readModule = (relativePath) => fs.readFileSync(path.resolve(HERE, '../../../', relativePath), 'utf8');
const wait = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

test('keeps the FullCalendar configuration and callback contract explicit', () => {
    const source = readModule('resources/js/calendar/fullcalendar.js');

    [
        "initialView: 'timeGridDay'",
        "slotDuration: '00:30:00'",
        "slotMinTime: '08:00:00'",
        "slotMaxTime: '22:00:00'",
        'allDaySlot: false',
        'nowIndicator: true',
        'expandRows: true',
        "height: 'auto'",
        "locale: 'fa'",
        "direction: 'rtl'",
        'firstDay: 6',
        "start: 'prev,next today'",
        'eventContent',
        'eventClick',
        'datesSet',
    ].forEach((contract) => assert.match(source, new RegExp(contract.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))));

    assert.match(source, /import\('@fullcalendar\/core'\)/);
    assert.match(source, /const eventSource = async/);
    assert.match(source, /success\(state\.lastEvents\)/);
    assert.match(source, /MAX_RETRY_ATTEMPTS = 3/);
});

test('normalizes valid events and skips malformed event payloads', () => {
    const valid = {
        id: 42,
        title: 'Student — Piano',
        start: '2025-07-16T09:00:00',
        end: '2025-07-16T09:30:00',
        status: 'scheduled',
        studentName: 'Student',
        teacherName: 'Teacher',
        instrumentName: 'Piano',
        room: 'A101',
        extendedProps: { duration_minutes: 30 },
    };
    const malformed = [
        null,
        { ...valid, id: null },
        { ...valid, start: 'not-a-date' },
        { ...valid, end: '2025-07-16T08:30:00' },
        { ...valid, status: 'unknown' },
    ];

    assert.deepEqual(normalizeEventPayload(valid).extendedProps, {
        duration_minutes: 30,
        status: 'scheduled',
        studentName: 'Student',
        teacherName: 'Teacher',
        instrumentName: 'Piano',
        room: 'A101',
    });
    assert.equal(normalizeEventCollection([valid, ...malformed]).length, 1);
    assert.equal(normalizeEventPayload(malformed[0]), null);

    const html = buildEventCardHtml(valid);
    assert.match(html, /role="button"/);
    assert.match(html, /Student/);
    assert.match(html, /Teacher/);
    assert.match(html, /09:00–09:30/);
});

test('updates sidebar selection, current-day semantics, and keyboard navigation', () => {
    const { root, list } = createSidebarFixture();
    const selected = [];
    const sidebar = initSidebar(root, {
        initialDate: '2025-07-16T12:00:00',
        onDaySelect: (date) => selected.push(date.toISOString().slice(0, 10)),
    });

    const buttons = list.querySelectorAll('[data-calendar-week-day]');
    assert.equal(buttons.length, 7);
    assert.equal(buttons[0].dataset.calendarDate, '2025-07-12');
    assert.equal(buttons[6].dataset.calendarDate, '2025-07-18');
    assert.equal(buttons.filter((button) => button.dataset.selected === 'true').length, 1);
    assert.equal(list.dataset.calendarSelectedDate, '2025-07-16');

    buttons[4].dispatchEvent({ type: 'click' });
    assert.equal(sidebar.getSelectedDate().toISOString().slice(0, 10), '2025-07-16');
    buttons[4].dispatchEvent({ type: 'keydown', key: 'ArrowRight', preventDefault() {} });
    assert.equal(sidebar.getSelectedDate().toISOString().slice(0, 10), '2025-07-17');
    assert.deepEqual(selected, ['2025-07-17']);
    assert.equal(buttons.find((button) => button.dataset.selected === 'true').tabIndex, 0);
    assert.equal(buttons.find((button) => button.dataset.selected === 'true').getAttribute('aria-selected'), 'true');

    sidebar.setDate('2025-07-12', { notify: false });
    assert.equal(list.dataset.calendarSelectedDate, '2025-07-12');
    sidebar.destroy();
});

test('serializes only active filters, debounces changes, and clears all values', async () => {
    const { root, fields, toggle, clear } = createFilterFixture();
    const emissions = [];
    const filters = initFilters(root, {
        debounceMs: 15,
        onFilterChange: (serialized, state) => emissions.push({ serialized, state }),
    });

    assert.deepEqual(filters.serialize(), {});
    filters.setFilters({ teacher_id: '7', room: 'all', instrument_id: 'piano' });
    assert.equal(emissions.length, 0);
    await wait(25);
    assert.equal(emissions.length, 1);
    assert.deepEqual(emissions[0].serialized, { teacher_id: '7', instrument_id: 'piano' });
    assert.equal(filters.getActiveFilterCount(), 2);
    assert.equal(fields.querySelector('[data-calendar-filter="teacher_id"]').value, '7');

    filters.setFilter('teacher_id', '8');
    filters.setFilter('student_id', '12');
    await wait(25);
    assert.equal(emissions.length, 2);
    assert.deepEqual(emissions.at(-1).serialized, {
        teacher_id: '8',
        student_id: '12',
        instrument_id: 'piano',
    });

    toggle.dispatchEvent({ type: 'click' });
    assert.equal(filters.isMobileExpanded(), true);
    assert.equal(toggle.getAttribute('aria-expanded'), 'true');
    assert.equal(fields.getAttribute('aria-hidden'), 'false');

    clear.dispatchEvent({ type: 'click' });
    await wait(25);
    assert.deepEqual(filters.serialize(), {});
    assert.equal(filters.getActiveFilterCount(), 0);
    assert.equal(emissions.length, 3);
    assert.deepEqual(emissions.at(-1).serialized, {});
    assert.equal(filters.clearAll(), false);
    filters.destroy();
});

test('opens, populates, closes, and restores focus for drawer events', () => {
    const { document, root, overlay, panel, close, status } = createDrawerFixture();
    const trigger = createElement(document, 'button');
    document.append(root, trigger);
    const drawer = initDrawer(root, { noNotes: 'بدون یادداشت' });
    const event = {
        id: 9,
        start: '2025-07-16T09:00:00',
        status: 'completed',
        studentName: 'Student',
        teacherName: 'Teacher',
        instrumentName: 'Piano',
        room: null,
        extendedProps: {
            session_date: '2025-07-16',
            duration_minutes: 45,
            notes: null,
        },
    };

    const detail = drawer.open(event, trigger);
    assert.equal(drawer.isOpen(), true);
    assert.equal(detail.statusClass, 'calendar-drawer__status-badge--completed');
    assert.equal(detail.notes, 'بدون یادداشت');
    assert.equal(root.dataset.calendarDrawerState, 'open');
    assert.equal(panel.hidden, false);
    assert.equal(root.querySelector('[data-calendar-drawer-field="studentName"]').textContent, 'Student');
    assert.equal(root.querySelector('[data-calendar-drawer-field="room"]').textContent, '—');
    assert.equal(status.textContent, 'completed');
    assert.equal(status.classList.contains('calendar-drawer__status-badge--completed'), true);
    assert.equal(trigger.getAttribute('aria-label'), 'Student – completed');
    assert.equal(trigger.getAttribute('aria-controls'), 'calendar-drawer-panel');

    close.dispatchEvent({ type: 'click' });
    assert.equal(drawer.isOpen(), false);
    assert.equal(root.dataset.calendarDrawerState, 'closed');
    assert.equal(trigger.focusCount, 1);

    drawer.open(event, trigger);
    document.dispatchEvent({ type: 'keydown', key: 'Escape', preventDefault() {} });
    assert.equal(drawer.isOpen(), false);
    assert.equal(trigger.focusCount, 2);

    drawer.open(event, trigger);
    overlay.dispatchEvent({ type: 'click', target: overlay });
    assert.equal(drawer.isOpen(), false);
    drawer.destroy();
});

test('keeps drawer status and aria-label mappings exhaustive', async () => {
    const drawerSource = readModule('resources/js/calendar/drawer.js');
    ['scheduled', 'completed', 'cancelled', 'missed'].forEach((status) => {
        assert.match(drawerSource, new RegExp(`${status}:`));
    });
    assert.match(drawerSource, /event\.key === 'Escape'/);
    assert.match(drawerSource, /focusElement\(previousTrigger\)/);

    const siblingModules = ['fullcalendar', 'sidebar', 'drawer', 'filters'];
    siblingModules.forEach((moduleName) => {
        const source = readModule(`resources/js/calendar/${moduleName}.js`);
        siblingModules.filter((otherModule) => otherModule !== moduleName).forEach((otherModule) => {
            assert.doesNotMatch(source, new RegExp(`from '\\./${otherModule}\\.js'`));
        });
    });

    const appSource = readModule('resources/js/calendar/calendar-app.js');
    siblingModules.forEach((moduleName) => {
        assert.equal((appSource.match(new RegExp(`from '\\./${moduleName}\\.js'`, 'g')) || []).length, 1);
    });
    assert.match(appSource, /const retryInitialization = async/);
    assert.match(appSource, /if \(!calendar\)/);
    assert.match(appSource, /void retryInitialization\(\)/);
    assert.match(appSource, /return initializeModules\(\)/);
    await Promise.resolve();
});
