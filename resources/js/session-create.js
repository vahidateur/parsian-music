/**
 * Canonical Alpine owner for Session Create transient state.
 *
 * The server prepares persisted options and remains authoritative for
 * authorization, validation, conflicts, quota writes, and persistence.
 */

const asId = (value) => String(value ?? '');

export const filterStudents = (students = [], query = '') => {
    const normalized = String(query).trim().toLocaleLowerCase();
    if (normalized.length < 2) return [];

    return students.filter((student) =>
        String(student.full_name ?? '').toLocaleLowerCase().includes(normalized)
        || asId(student.id).includes(normalized)
    ).slice(0, 10);
};

export const instrumentsForTeacher = (map = {}, teacherId = '') => {
    const options = map[asId(teacherId)] ?? [];
    return Array.isArray(options) ? options : [];
};

export const subscriptionFor = (subscriptions = [], teacherId = '', instrumentId = '') =>
    subscriptions.find((subscription) =>
        asId(subscription.teacher_id) === asId(teacherId)
        && asId(subscription.instrument_id) === asId(instrumentId)
    ) ?? null;

const readJson = (value, fallback) => {
    try {
        return value ? JSON.parse(value) : fallback;
    } catch {
        return fallback;
    }
};

const option = (documentRef, value, label) => {
    const element = documentRef.createElement('option');
    element.value = value;
    element.textContent = label;
    return element;
};

export default function sessionCreate() {
    return {
        students: [],
        teacherInstrumentMap: {},
        results: [],
        highlighted: -1,
        query: '',
        selectedId: '',
        selectedStudent: '',
        selectedSubscriptions: [],
        teacherId: '',
        instrumentId: '',
        subscription: null,
        listeners: [],

        init() {
            const root = this.$el;
            this.students = readJson(root.dataset.students, []);
            this.teacherInstrumentMap = readJson(root.dataset.teacherInstruments, {});
            this.selectedId = root.dataset.initialStudentId ?? '';
            this.teacherId = root.dataset.initialTeacherId ?? '';
            this.instrumentId = root.dataset.initialInstrumentId ?? '';
            this.bindElements(root);
            this.restoreSelectedStudent();
            this.populateTeachers();
            this.populateInstruments(this.instrumentId);
            this.syncQuota();
        },

        bind(target, event, handler) {
            target?.addEventListener?.(event, handler);
            if (target?.removeEventListener) this.listeners.push(() => target.removeEventListener(event, handler));
        },

        bindElements(root) {
            const documentRef = root.ownerDocument ?? globalThis.document;
            this.searchInput = root.querySelector('[data-session-student-search]');
            this.studentIdInput = root.querySelector('[data-session-student-id]');
            this.resultsList = root.querySelector('[data-session-student-results]');
            this.emptyMessage = root.querySelector('[data-session-student-empty]');
            this.selectedLabel = root.querySelector('[data-session-selected-student-label]');
            this.selectedDisplay = root.querySelector('[data-session-selected-student]');
            this.teacherSelect = root.querySelector('[data-session-teacher]');
            this.instrumentSelect = root.querySelector('[data-session-instrument]');
            this.overageBox = root.querySelector('[data-session-overage]');
            this.standardNotes = root.querySelector('[data-session-standard-notes]');
            this.overageNotes = root.querySelector('[data-session-overage-notes]');
            this.form = root;
            this.bind(this.searchInput, 'input', () => this.search());
            this.bind(this.searchInput, 'keydown', (event) => this.onSearchKeydown(event));
            this.bind(this.teacherSelect, 'change', () => this.onTeacherChange());
            this.bind(this.instrumentSelect, 'change', () => this.onInstrumentChange());
            this.bind(this.form, 'submit', () => this.disableInactiveNotes());
            this.documentRef = documentRef;
        },

        destroy() {
            this.listeners.forEach((cleanup) => cleanup());
            this.listeners = [];
        },

        populateTeachers() {
            if (this.teacherSelect) this.teacherSelect.value = this.teacherId;
        },

        populateInstruments(selectedId = '') {
            if (!this.instrumentSelect) return;
            const instruments = instrumentsForTeacher(this.teacherInstrumentMap, this.teacherId);
            this.instrumentSelect.replaceChildren(option(this.documentRef, '', this.teacherId
                ? (instruments.length ? 'انتخاب ساز...' : 'ساز تعریف‌نشده')
                : 'ابتدا معلم را انتخاب کنید'));
            instruments.forEach((instrument) => this.instrumentSelect.appendChild(
                option(this.documentRef, instrument.id, instrument.name)
            ));
            this.instrumentSelect.disabled = !this.teacherId;
            this.instrumentSelect.value = instruments.some((item) => asId(item.id) === asId(selectedId)) ? selectedId : '';
            this.instrumentId = this.instrumentSelect.value;
            this.syncQuota();
        },

        onTeacherChange() {
            this.teacherId = this.teacherSelect?.value ?? '';
            this.instrumentId = '';
            this.populateInstruments();
            this.syncQuota();
        },

        onInstrumentChange() {
            this.instrumentId = this.instrumentSelect?.value ?? '';
            this.syncQuota();
        },

        restoreSelectedStudent() {
            const student = this.students.find((item) => asId(item.id) === asId(this.selectedId));
            if (student) this.selectResult(student, false);
        },

        search() {
            this.highlighted = -1;
            this.results = filterStudents(this.students, this.searchInput?.value ?? '');
            this.renderResults();
        },

        onSearchKeydown(event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.highlighted = Math.min(this.highlighted + 1, this.results.length - 1);
                this.renderResults();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.highlighted = Math.max(this.highlighted - 1, 0);
                this.renderResults();
            } else if (event.key === 'Enter' && this.results[this.highlighted]) {
                event.preventDefault();
                this.selectResult(this.results[this.highlighted]);
            } else if (event.key === 'Escape') {
                this.results = [];
                this.renderResults();
            }
        },

        renderResults() {
            if (!this.resultsList) return;
            this.resultsList.replaceChildren(...this.results.map((result, index) => {
                const item = this.documentRef.createElement('li');
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', String(index === this.highlighted));
                const button = this.documentRef.createElement('button');
                button.type = 'button';
                button.className = 'block w-full cursor-pointer px-4 py-2.5 text-start text-sm text-gray-200 transition hover:bg-gray-800';
                button.textContent = `${result.full_name} (${result.id})`;
                this.bind(button, 'click', () => this.selectResult(result));
                item.appendChild(button);
                return item;
            }));
            this.resultsList.classList.toggle('hidden', this.results.length === 0);
            this.emptyMessage?.classList.toggle('hidden', !(String(this.searchInput?.value ?? '').length >= 2 && this.results.length === 0));
        },

        selectResult(result, clearSearch = true) {
            this.selectedId = result.id;
            this.selectedStudent = String(result.full_name ?? '');
            this.selectedSubscriptions = result.subscriptions ?? [];
            if (this.studentIdInput) this.studentIdInput.value = result.id;
            if (this.selectedLabel) this.selectedLabel.textContent = this.selectedStudent;
            this.selectedDisplay?.classList.remove('hidden');
            if (clearSearch && this.searchInput) this.searchInput.value = '';
            this.results = [];
            this.renderResults();
            this.syncQuota();
        },

        syncQuota() {
            this.subscription = subscriptionFor(this.selectedSubscriptions, this.teacherId, this.instrumentId);
            const overage = Boolean(this.subscription
                && Number(this.subscription.sessions_used) >= Number(this.subscription.sessions_allocated));
            this.overageBox?.classList.toggle('hidden', !overage);
            this.standardNotes?.classList.toggle('hidden', overage);
            this.overageBox?.setAttribute('aria-hidden', String(!overage));
            this.standardNotes?.setAttribute('aria-hidden', String(overage));
            if (this.overageNotes) this.overageNotes.disabled = !overage;
            const standardInput = this.standardNotes?.querySelector('[name="notes"]');
            if (standardInput) standardInput.disabled = overage;
        },

        disableInactiveNotes() {
            this.syncQuota();
        },
    };
}
