/**
 * Reusable fast-check generators for calendar sessions and FullCalendar events.
 *
 * The generated session shape mirrors the two relation paths
 * `CalendarEventResource` resolves: direct `session.student/teacher/instrument`
 * or `session.enrollment.student/teacher/instrument`.
 */

import fc from 'fast-check';
import {
    durationMinutesArbitrary,
    isoDateArbitrary,
    timeWithSecondsArbitrary,
} from './date-generators.js';

export const VALID_STATUSES = ['scheduled', 'completed', 'cancelled', 'missed'];

export const STUDENT_NAMES = ['Ali Mohammadi', 'Sara Ahmadi', 'Nima Karimi', 'نازنین حسینی'];
export const TEACHER_NAMES = ['Nazanin Hosseini', 'Reza Moradi', 'Mina Jafari'];
export const INSTRUMENT_NAMES = ['Violin', 'Piano', 'Daf', 'Guitar'];
export const ROOM_NAMES = ['A101', 'B202', 'Studio 3'];

/** Arbitrary session status value. */
export const statusArbitrary = () => fc.constantFrom(...VALID_STATUSES);

/** Arbitrary non-empty student name. */
export const studentNameArbitrary = () => fc.oneof(
    fc.constantFrom(...STUDENT_NAMES),
    fc.string({ minLength: 1, maxLength: 40 }).filter((value) => value.trim().length > 0),
);

/** Arbitrary relation bundle shared by direct and enrollment-backed sessions. */
export const relationDetailsArbitrary = () => fc.record({
    student: fc.record({ full_name: studentNameArbitrary() }),
    teacher: fc.record({ full_name: fc.constantFrom(...TEACHER_NAMES) }),
    instrument: fc.record({ name: fc.constantFrom(...INSTRUMENT_NAMES) }),
});

/** Arbitrary session attributes without relations. */
export const sessionAttributesArbitrary = () => fc.record({
    id: fc.integer({ min: 1, max: 1_000_000 }),
    session_date: isoDateArbitrary({}),
    start_time: timeWithSecondsArbitrary(),
    duration_minutes: durationMinutesArbitrary(),
    status: statusArbitrary(),
    session_fee: fc.option(fc.integer({ min: 0, max: 100_000_000 }), { nil: null }),
    room: fc.option(fc.constantFrom(...ROOM_NAMES), { nil: null }),
    notes: fc.option(fc.constantFrom('Bring the shoulder rest.', 'Review scales.', 'Practice arpeggios.'), { nil: null }),
});

/** Arbitrary session using direct student/teacher/instrument relations. */
export const directSessionArbitrary = () => fc.tuple(sessionAttributesArbitrary(), relationDetailsArbitrary())
    .map(([session, relation]) => ({ ...session, enrollment_id: null, ...relation }));

/** Arbitrary session whose relations come from its enrollment. */
export const enrollmentSessionArbitrary = () => fc.tuple(
    sessionAttributesArbitrary(),
    relationDetailsArbitrary(),
    fc.integer({ min: 1, max: 1_000_000 }),
).map(([session, relation, enrollmentId]) => ({
    ...session,
    enrollment_id: enrollmentId,
    enrollment: relation,
}));

/** Arbitrary session covering both relation paths. */
export const sessionArbitrary = () => fc.oneof(directSessionArbitrary(), enrollmentSessionArbitrary());

/** Arbitrary session bound to fixed filter identities, for filter scoping checks. */
export const filterableSessionArbitrary = ({ teacherId, studentId, room, instrumentId }) => fc.tuple(
    sessionAttributesArbitrary(),
    relationDetailsArbitrary(),
).map(([session, relation]) => ({
    ...session,
    ...relation,
    enrollment_id: null,
    room,
    teacher_id: teacherId,
    student_id: studentId,
    instrument_id: instrumentId,
}));

/**
 * Transform a generated session into the FullCalendar event contract, mirroring
 * `CalendarEventResource`.
 */
export const toCalendarEvent = (session) => {
    const relation = session.enrollment ?? session;
    const start = new Date(`${session.session_date}T${session.start_time}Z`);
    const end = new Date(start.getTime() + session.duration_minutes * 60_000);
    const format = (date) => date.toISOString().slice(0, 19);

    return {
        id: session.id,
        title: `${relation.student.full_name} — ${relation.instrument.name}`,
        start: format(start),
        end: format(end),
        status: session.status,
        studentName: relation.student.full_name,
        teacherName: relation.teacher.full_name,
        instrumentName: relation.instrument.name,
        room: session.room,
        extendedProps: {
            enrollment_id: session.enrollment_id,
            session_fee: session.session_fee,
            duration_minutes: session.duration_minutes,
            notes: session.notes,
            session_date: session.session_date,
        },
    };
};

/** Arbitrary FullCalendar event object as returned by the events endpoint. */
export const calendarEventArbitrary = () => sessionArbitrary().map(toCalendarEvent);
