/**
 * Minimal DOM harness for calendar module tests.
 *
 * Node's test runner has no DOM, so the calendar modules are exercised against
 * these lightweight element/document fakes. Only the APIs the calendar modules
 * actually use are implemented: attributes, dataset, classList, events, focus,
 * and selector matching for tag/class/id/attribute selectors.
 */

class FakeClassList {
    constructor() {
        this.values = new Set();
    }

    add(...names) { names.forEach((name) => this.values.add(name)); }
    remove(...names) { names.forEach((name) => this.values.delete(name)); }
    toggle(name, force) {
        const next = force === undefined ? !this.values.has(name) : Boolean(force);
        if (next) this.values.add(name); else this.values.delete(name);
        return next;
    }
    contains(name) { return this.values.has(name); }
}

class FakeEventTarget {
    constructor() {
        this.listeners = new Map();
    }

    addEventListener(type, listener) {
        const handlers = this.listeners.get(type) || [];
        handlers.push(listener);
        this.listeners.set(type, handlers);
    }

    removeEventListener(type, listener) {
        this.listeners.set(type, (this.listeners.get(type) || []).filter((handler) => handler !== listener));
    }

    dispatchEvent(event) {
        event.target ??= this;
        event.currentTarget = this;
        (this.listeners.get(event.type) || []).slice().forEach((listener) => listener(event));
        return true;
    }
}

class FakeElement extends FakeEventTarget {
    constructor(tagName = 'div', ownerDocument = null) {
        super();
        this.nodeType = 1;
        this.tagName = tagName.toUpperCase();
        this.ownerDocument = ownerDocument;
        this.parentElement = null;
        this.children = [];
        this.attributes = new Map();
        this.dataset = {};
        this.classList = new FakeClassList();
        this.className = '';
        this.hidden = false;
        this.textContent = '';
        this.value = '';
        this.tabIndex = 0;
        this.focusCount = 0;
    }

    append(...children) { children.forEach((child) => this.appendChild(child)); }
    appendChild(child) {
        child.parentElement = this;
        child.ownerDocument = this.ownerDocument;
        this.children.push(child);
        return child;
    }
    setAttribute(name, value) {
        const normalized = String(value);
        this.attributes.set(name, normalized);
        if (name === 'id') {
            this.id = normalized;
        }
        if (name.startsWith('data-')) {
            this.dataset[datasetKey(name)] = normalized;
        }
    }
    getAttribute(name) { return this.attributes.has(name) ? this.attributes.get(name) : null; }
    hasAttribute(name) { return this.attributes.has(name); }
    removeAttribute(name) { this.attributes.delete(name); }
    toggleAttribute(name, force) {
        const present = force === undefined ? !this.attributes.has(name) : Boolean(force);
        if (present) this.setAttribute(name, ''); else this.removeAttribute(name);
        return present;
    }
    focus() { this.focusCount += 1; if (this.ownerDocument) this.ownerDocument.activeElement = this; }
    scrollIntoView() { this.scrolledIntoView = true; }
    contains(element) { return element === this || this.children.some((child) => child.contains(element)); }

    matches(selector) {
        const attribute = selector.match(/^\[([^=\]]+)(?:="([^"]*)")?\]$/);
        if (attribute) {
            const [, name, expected] = attribute;
            const actual = this.getAttribute(name)
                ?? (name.startsWith('data-') ? this.dataset[datasetKey(name)] : null);
            return expected === undefined ? actual !== null && actual !== undefined : String(actual) === expected;
        }
        if (selector.startsWith('.')) return this.className.split(/\s+/).includes(selector.slice(1));
        if (selector.startsWith('#')) return this.getAttribute('id') === selector.slice(1);
        return this.tagName.toLowerCase() === selector.toLowerCase();
    }
    closest(selector) {
        const selectors = selector.split(',').map((part) => part.trim());
        let current = this;
        while (current && current.nodeType === 1) {
            if (selectors.some((part) => current.matches(part))) return current;
            current = current.parentElement;
        }
        return null;
    }
    querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
    querySelectorAll(selector) {
        const selectors = selector.split(',').map((part) => part.trim());
        const matches = [];
        const visit = (element) => {
            element.children.forEach((child) => {
                if (selectors.some((part) => child.matches(part))) matches.push(child);
                visit(child);
            });
        };
        visit(this);
        return matches;
    }
}

class FakeDocument extends FakeElement {
    constructor(viewportWidth = 1024) {
        super('#document', null);
        this.nodeType = 9;
        this.ownerDocument = this;
        this.activeElement = null;
        this.defaultView = {
            innerWidth: viewportWidth,
            addEventListener: () => {},
            removeEventListener: () => {},
            matchMedia: () => ({ matches: false, addEventListener: () => {}, removeEventListener: () => {} }),
        };
    }

    createElement(tagName) { return new FakeElement(tagName, this); }
}

function datasetKey(attributeName) {
    return attributeName.slice(5).replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
}

/**
 * Create an isolated fake document.
 *
 * @param {number} [viewportWidth] simulated `window.innerWidth`
 */
export function createDocument(viewportWidth = 1024) {
    return new FakeDocument(viewportWidth);
}

/**
 * Create an element with the given attributes inside a fake document.
 */
export function createElement(document, tagName, attributes = {}) {
    const element = document.createElement(tagName);
    Object.entries(attributes).forEach(([name, value]) => element.setAttribute(name, value));

    return element;
}

/**
 * Week sidebar fixture: an empty day list the sidebar module populates itself.
 */
export function createSidebarFixture(viewportWidth = 1024) {
    const document = createDocument(viewportWidth);
    const root = createElement(document, 'nav', { 'data-calendar-week-sidebar': '' });
    const list = createElement(document, 'ul', { 'data-calendar-week-days': '' });
    root.append(list);

    return { document, root, list };
}

/**
 * Event filters fixture with the four supported filter controls.
 */
export function createFilterFixture(viewportWidth = 1024) {
    const document = createDocument(viewportWidth);
    const root = createElement(document, 'section', { 'data-calendar-filters': '' });
    const fields = createElement(document, 'div', { 'data-calendar-filter-fields': '' });
    const toggle = createElement(document, 'button', { 'data-calendar-filters-toggle': '' });
    const clear = createElement(document, 'button', { 'data-calendar-filters-clear': '' });
    root.append(fields, toggle, clear);

    const controls = {};
    ['teacher_id', 'student_id', 'room', 'instrument_id'].forEach((key) => {
        const control = createElement(document, 'select', { 'data-calendar-filter': key });
        control.value = '';
        fields.append(control);
        controls[key] = control;
    });

    return { document, root, fields, toggle, clear, controls };
}

/**
 * Event drawer fixture with overlay, panel, close button, status hook, and fields.
 */
export function createDrawerFixture(viewportWidth = 1024) {
    const document = createDocument(viewportWidth);
    const root = createElement(document, 'aside', { 'data-calendar-drawer': '' });
    const overlay = createElement(document, 'button', { 'data-calendar-drawer-overlay': '' });
    const panel = createElement(document, 'section', { 'data-calendar-drawer-panel': '' });
    panel.setAttribute('id', 'calendar-drawer-panel');
    const close = createElement(document, 'button', { 'data-calendar-drawer-close': '' });
    const status = createElement(document, 'span', { 'data-calendar-status-hook': '' });
    root.append(overlay, panel, close, status);

    const fields = {};
    ['studentName', 'teacherName', 'instrumentName', 'sessionDate', 'startTime', 'duration', 'room', 'notes']
        .forEach((field) => {
            fields[field] = createElement(document, 'span', { 'data-calendar-drawer-field': field });
            root.append(fields[field]);
        });

    return { document, root, overlay, panel, close, status, fields };
}

/**
 * Full calendar page fixture: root, header controls, timeline states, and mount point.
 */
export function createCalendarFixture(viewportWidth = 1024) {
    const document = createDocument(viewportWidth);
    const root = createElement(document, 'div', { 'data-calendar-root': '', dir: 'rtl' });
    const currentDate = createElement(document, 'p', { 'data-calendar-current-date': '' });
    const previous = createElement(document, 'button', { 'data-calendar-prev-day': '' });
    const next = createElement(document, 'button', { 'data-calendar-next-day': '' });
    const today = createElement(document, 'button', { 'data-calendar-today': '' });
    const timeline = createElement(document, 'section', { 'data-calendar-timeline': '' });
    const mount = createElement(document, 'div', { 'data-calendar-mount': '' });
    const loading = createElement(document, 'div', { 'data-calendar-loading': '' });
    const empty = createElement(document, 'div', { 'data-calendar-empty': '' });
    const error = createElement(document, 'div', { 'data-calendar-error': '' });
    const errorMessage = createElement(document, 'p', { 'data-calendar-error-message': '' });
    const retry = createElement(document, 'button', { 'data-calendar-retry': '' });
    const status = createElement(document, 'p', { 'data-calendar-status': '' });

    timeline.append(mount, loading, empty, error, errorMessage, retry, status);

    const sidebar = createElement(document, 'nav', { 'data-calendar-week-sidebar': '' });
    sidebar.append(createElement(document, 'ul', { 'data-calendar-week-days': '' }));
    const filters = createElement(document, 'section', { 'data-calendar-filters': '' });
    const drawer = createElement(document, 'aside', { 'data-calendar-drawer': '' });

    root.append(currentDate, previous, next, today, sidebar, filters, timeline, drawer);
    document.append(root);

    return {
        document,
        root,
        header: { currentDate, previous, next, today },
        timeline,
        mount,
        states: { loading, empty, error, errorMessage, retry, status },
        sidebar,
        filters,
        drawer,
    };
}
