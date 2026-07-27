/**
 * Persian Saturday-to-Friday week sidebar.
 *
 * The sidebar owns only its DOM state. Date changes are communicated through
 * onDaySelect so the calendar orchestrator remains the sole module coordinator.
 */
import {
    addDays,
    formatJalaliDay,
    getPersianDayName,
    getPersianWeek,
    toDate,
    toIsoDate,
} from './utils/jalali.js';

const DAY_COUNT = 7;

function isSameDate(first, second) {
    return toIsoDate(first) === toIsoDate(second);
}

function normalizeDate(value, fallback = new Date()) {
    try {
        return toDate(value);
    } catch {
        return toDate(fallback);
    }
}

function createDayButton(document, index) {
    const item = document.createElement('li');
    item.className = 'calendar-week-sidebar__day';
    item.dataset.calendarWeekDayItem = '';

    const button = document.createElement('button');
    button.className = 'calendar-week-sidebar__day-button';
    button.type = 'button';
    button.dataset.calendarWeekDay = '';
    button.dataset.calendarDayIndex = String(index);

    const name = document.createElement('span');
    name.dataset.calendarWeekDayName = '';

    const number = document.createElement('span');
    number.dataset.calendarWeekDayNumber = '';
    number.setAttribute('aria-hidden', 'true');

    button.append(name, number);
    item.append(button);

    return { item, button, name, number };
}

function ensureDayButtons(container) {
    const list = container.querySelector('[data-calendar-week-days]') || container;
    let buttons = Array.from(list.querySelectorAll('[data-calendar-week-day]')).slice(0, DAY_COUNT);

    while (buttons.length < DAY_COUNT) {
        const index = buttons.length;
        const created = createDayButton(list.ownerDocument, index);
        list.append(created.item);
        buttons.push(created.button);
    }

    return { list, buttons };
}

function getInitialDate(container, options) {
    return normalizeDate(
        options.initialDate
            ?? options.selectedDate
            ?? container.dataset.calendarDate
            ?? container.dataset.calendarSelectedDate,
    );
}

function setDayContent(button, date, index) {
    const name = button.querySelector('[data-calendar-week-day-name]');
    const number = button.querySelector('[data-calendar-week-day-number]');
    const dayName = getPersianDayName(date);
    const dayNumber = formatJalaliDay(date);

    if (name) {
        name.textContent = dayName;
    }

    if (number) {
        number.textContent = dayNumber;
    }

    button.dataset.calendarDayIndex = String(index);
    button.dataset.calendarDate = toIsoDate(date);
    button.setAttribute('aria-label', `${dayName} ${dayNumber}`);
}

function keepVisible(button) {
    if (typeof button.scrollIntoView !== 'function') {
        return;
    }

    try {
        button.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    } catch {
        button.scrollIntoView();
    }
}

/**
 * Initialize a keyboard-accessible Persian week sidebar.
 *
 * @param {HTMLElement} element Sidebar root or an element containing the week list.
 * @param {{initialDate?: Date|string|number, selectedDate?: Date|string|number, onDaySelect?: (date: Date) => void}} options
 * @returns {{render: (date?: Date|string|number) => Date, setDate: (date: Date|string|number, options?: {notify?: boolean, focus?: boolean}) => Date, getSelectedDate: () => Date, destroy: () => void}}
 */
export default function initSidebar(element, options = {}) {
    if (!element || typeof element.querySelector !== 'function') {
        throw new TypeError('Sidebar element is required.');
    }

    const { list, buttons } = ensureDayButtons(element);
    const onDaySelect = typeof options.onDaySelect === 'function' ? options.onDaySelect : () => {};
    let selectedDate = getInitialDate(element, options);
    let weekDates = [];
    let destroyed = false;

    function render(date = selectedDate) {
        selectedDate = normalizeDate(date, selectedDate);
        weekDates = getPersianWeek(selectedDate);
        const today = new Date();

        buttons.forEach((button, index) => {
            const dayDate = weekDates[index];
            const selected = isSameDate(dayDate, selectedDate);
            const current = isSameDate(dayDate, today);

            setDayContent(button, dayDate, index);
            button.toggleAttribute('aria-current', current);
            if (current) {
                button.setAttribute('aria-current', 'date');
                button.dataset.today = 'true';
            } else {
                button.removeAttribute('aria-current');
                delete button.dataset.today;
            }

            button.setAttribute('aria-selected', String(selected));
            button.setAttribute('aria-pressed', String(selected));
            button.dataset.selected = String(selected);
            button.tabIndex = selected ? 0 : -1;
        });

        list.dataset.calendarSelectedDate = toIsoDate(selectedDate);
        keepVisible(buttons.find((button) => button.dataset.selected === 'true'));
        return new Date(selectedDate.getTime());
    }

    function selectDate(date, { notify = true, focus = false } = {}) {
        const nextDate = normalizeDate(date, selectedDate);
        const changed = !isSameDate(nextDate, selectedDate);
        selectedDate = nextDate;
        render(selectedDate);

        const selectedButton = buttons.find((button) => button.dataset.selected === 'true');
        if (focus && selectedButton) {
            selectedButton.focus({ preventScroll: true });
            keepVisible(selectedButton);
        }

        if (notify && changed && !destroyed) {
            onDaySelect(new Date(selectedDate.getTime()));
        }

        return new Date(selectedDate.getTime());
    }

    function handleDayClick(event) {
        const button = event.currentTarget;
        const index = Number(button.dataset.calendarDayIndex);
        if (!Number.isInteger(index) || !weekDates[index]) {
            return;
        }

        selectDate(weekDates[index], { notify: true, focus: false });
    }

    function handleDayKeydown(event) {
        const button = event.currentTarget;
        const index = Number(button.dataset.calendarDayIndex);
        if (!Number.isInteger(index)) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            if (weekDates[index]) {
                selectDate(weekDates[index], { notify: true, focus: true });
            }
            return;
        }

        const direction = {
            ArrowRight: 1,
            ArrowDown: 1,
            ArrowLeft: -1,
            ArrowUp: -1,
        }[event.key];

        if (direction === undefined) {
            if (event.key === 'Home') {
                event.preventDefault();
                selectDate(weekDates[0], { notify: true, focus: true });
            } else if (event.key === 'End') {
                event.preventDefault();
                selectDate(weekDates[DAY_COUNT - 1], { notify: true, focus: true });
            }
            return;
        }

        event.preventDefault();
        selectDate(addDays(weekDates[index], direction), { notify: true, focus: true });
    }

    function handleFocus(event) {
        event.currentTarget.dataset.focused = 'true';
    }

    function handleBlur(event) {
        delete event.currentTarget.dataset.focused;
    }

    buttons.forEach((button) => {
        button.addEventListener('click', handleDayClick);
        button.addEventListener('keydown', handleDayKeydown);
        button.addEventListener('focus', handleFocus);
        button.addEventListener('blur', handleBlur);
    });

    render(selectedDate);

    return {
        render,
        setDate: selectDate,
        getSelectedDate: () => new Date(selectedDate.getTime()),
        destroy() {
            if (destroyed) {
                return;
            }

            destroyed = true;
            buttons.forEach((button) => {
                button.removeEventListener('click', handleDayClick);
                button.removeEventListener('keydown', handleDayKeydown);
                button.removeEventListener('focus', handleFocus);
                button.removeEventListener('blur', handleBlur);
            });
        },
    };
}
