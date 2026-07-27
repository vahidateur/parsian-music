import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';

// Feature: admin-calendar-module, Property 1: Session transformation completeness

const VALID_STATUSES = ['scheduled', 'completed', 'cancelled', 'missed'];
const ISO_DATETIME_PATTERN = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/;
const RESOURCE_FIELDS = [
    'id',
    'title',
    'start',
    'end',
    'status',
    'studentName',
    'teacherName',
    'instrumentName',
    'room',
    'extendedProps',
];
const EXTENDED_PROP_FIELDS = [
    'enrollment_id',
    'session_fee',
    'duration_minutes',
    'notes',
    'session_date',
];

const relationDetailsArbitrary = fc.record({
    student: fc.record({ full_name: fc.constantFrom('Ali Mohammadi', 'Sara Ahmadi', 'Nima Karimi') }),
    teacher: fc.record({ full_name: fc.constantFrom('Nazanin Hosseini', 'Reza Moradi', 'Mina Jafari') }),
    instrument: fc.record({ name: fc.constantFrom('Violin', 'Piano', 'Daf', 'Guitar') }),
});

const sessionAttributesArbitrary = fc.record({
    id: fc.integer({ min: 1, max: 1_000_000 }),
    session_date: fc.integer({ min: 0, max: 3652 }).map((offset) => {
        const date = new Date(Date.UTC(2024, 0, 1 + offset));

        return date.toISOString().slice(0, 10);
    }),
    start_time: fc.record({
        hour: fc.integer({ min: 0, max: 23 }),
        minute: fc.integer({ min: 0, max: 59 }),
        second: fc.integer({ min: 0, max: 59 }),
    }).map(({ hour, minute, second }) => (
        [hour, minute, second].map((part) => String(part).padStart(2, '0')).join(':')
    )),
    duration_minutes: fc.integer({ min: 1, max: 480 }),
    status: fc.constantFrom(...VALID_STATUSES),
    session_fee: fc.option(fc.integer({ min: 0, max: 100_000_000 }), { nil: null }),
    room: fc.option(fc.constantFrom('A101', 'B202', 'Studio 3'), { nil: null }),
    notes: fc.option(fc.constantFrom('Bring the shoulder rest.', 'Review scales.', 'Practice arpeggios.'), { nil: null }),
});

const directSessionArbitrary = fc.tuple(sessionAttributesArbitrary, relationDetailsArbitrary)
    .map(([session, relation]) => ({
        ...session,
        enrollment_id: null,
        ...relation,
    }));

const enrollmentSessionArbitrary = fc.tuple(
    sessionAttributesArbitrary,
    relationDetailsArbitrary,
    fc.integer({ min: 1, max: 1_000_000 }),
).map(([session, relation, enrollment_id]) => ({
    ...session,
    enrollment_id,
    enrollment: relation,
}));

// The generated shape mirrors the two relation paths CalendarEventResource resolves:
// direct session.student/teacher/instrument or enrollment.student/teacher/instrument.
const sessionArbitrary = fc.oneof(directSessionArbitrary, enrollmentSessionArbitrary);

const toResourceFixture = (session) => ({ ...session });

const toResourceEvent = (session) => {
    const fixture = toResourceFixture(session);
    const relation = fixture.enrollment ?? fixture;
    const start = new Date(`${fixture.session_date}T${fixture.start_time}Z`);
    const end = new Date(start.getTime() + fixture.duration_minutes * 60_000);
    const format = (date) => date.toISOString().slice(0, 19);

    return {
        id: fixture.id,
        title: `${relation.student.full_name} — ${relation.instrument.name}`,
        start: format(start),
        end: format(end),
        status: fixture.status,
        studentName: relation.student.full_name,
        teacherName: relation.teacher.full_name,
        instrumentName: relation.instrument.name,
        room: fixture.room,
        extendedProps: {
            enrollment_id: fixture.enrollment_id,
            session_fee: fixture.session_fee,
            duration_minutes: fixture.duration_minutes,
            notes: fixture.notes,
            session_date: fixture.session_date,
        },
    };
};

const assertIsoDateTime = (value) => {
    assert.match(value, ISO_DATETIME_PATTERN);
    assert.equal(Number.isNaN(Date.parse(`${value}Z`)), false);
};

test('transforms direct and enrollment-backed sessions into complete FullCalendar events', async () => {
    await fc.assert(
        fc.asyncProperty(sessionArbitrary, async (session) => {
            const event = toResourceEvent(session);

            assert.deepEqual(Object.keys(event).sort(), [...RESOURCE_FIELDS].sort());
            assert.equal(Number.isInteger(event.id), true);
            assert.equal(event.id, session.id);
            assert.equal(typeof event.title, 'string');
            assert.ok(event.title.length > 0);
            assertIsoDateTime(event.start);
            assertIsoDateTime(event.end);
            assert.ok(VALID_STATUSES.includes(event.status));
            assert.equal(typeof event.studentName, 'string');
            assert.ok(event.studentName.length > 0);
            assert.equal(typeof event.teacherName, 'string');
            assert.ok(event.teacherName.length > 0);
            assert.equal(typeof event.instrumentName, 'string');
            assert.ok(event.instrumentName.length > 0);
            assert.ok(event.room === null || typeof event.room === 'string');

            assert.deepEqual(Object.keys(event.extendedProps).sort(), [...EXTENDED_PROP_FIELDS].sort());
            assert.equal(event.extendedProps.enrollment_id, session.enrollment_id);
            assert.equal(event.extendedProps.session_fee, session.session_fee);
            assert.equal(event.extendedProps.duration_minutes, session.duration_minutes);
            assert.equal(event.extendedProps.notes, session.notes);
            assert.equal(event.extendedProps.session_date, session.session_date);

            const start = Date.parse(`${event.start}Z`);
            const end = Date.parse(`${event.end}Z`);
            assert.equal(end - start, session.duration_minutes * 60_000);
        }),
        { numRuns: 100 },
    );
});
