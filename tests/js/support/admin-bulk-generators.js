/**
 * Pure domain doubles for admin-bulk property tests.
 *
 * These generators never touch Eloquent or the database. Integration tests use
 * Tests\\Support\\AdminBulkFixtures for persisted graphs instead.
 */
import fc from 'fast-check';

export const BULK_ENTITIES = ['teacher', 'student'];
export const BULK_ACTIONS = ['activate', 'deactivate', 'delete'];
export const SELECTION_MODES = ['current_page', 'all_filtered'];
export const TEACHER_STATUSES = ['active', 'inactive'];
export const STUDENT_STATUSES = ['active', 'paused', 'inactive', 'graduated'];
export const ROOM_RESOLUTIONS = ['resolved_active', 'resolved_inactive', 'unresolved_legacy'];
export const PROTECTED_DEPENDENCIES = [
    'enrollment',
    'subscription',
    'invoice',
    'attendance',
    'class_session',
    'converted_lead',
];

export const bulkEntityArbitrary = () => fc.constantFrom(...BULK_ENTITIES);
export const bulkActionArbitrary = () => fc.constantFrom(...BULK_ACTIONS);
export const selectionModeArbitrary = () => fc.constantFrom(...SELECTION_MODES);
export const teacherStatusArbitrary = () => fc.constantFrom(...TEACHER_STATUSES);
export const studentStatusArbitrary = () => fc.constantFrom(...STUDENT_STATUSES);
export const roomResolutionArbitrary = () => fc.constantFrom(...ROOM_RESOLUTIONS);
export const protectedDependencyArbitrary = () => fc.constantFrom(...PROTECTED_DEPENDENCIES);

export const policyActorArbitrary = () => fc.record({
    id: fc.integer({ min: 1, max: 1_000_000 }),
    role: fc.constantFrom('admin', 'super_admin', 'teacher', 'student'),
    is_active: fc.boolean(),
});

export const roomDoubleArbitrary = () => fc.record({
    id: fc.integer({ min: 1, max: 1_000_000 }),
    name: fc.string({ minLength: 1, maxLength: 20 }).filter((name) => name.trim() !== ''),
    capacity: fc.option(fc.integer({ min: 1, max: 100 }), { nil: null }),
    is_active: fc.boolean(),
});

export const teacherDoubleArbitrary = () => fc.record({
    id: fc.integer({ min: 1, max: 1_000_000 }),
    status: teacherStatusArbitrary(),
});

export const studentDoubleArbitrary = () => fc.record({
    id: fc.integer({ min: 1, max: 1_000_000 }),
    status: studentStatusArbitrary(),
});

export const enrollmentDoubleArbitrary = () => fc.record({
    id: fc.integer({ min: 1, max: 1_000_000 }),
    student_id: fc.integer({ min: 1, max: 1_000_000 }),
    teacher_id: fc.integer({ min: 1, max: 1_000_000 }),
    instrument_id: fc.integer({ min: 1, max: 1_000_000 }),
});

export const sessionDoubleArbitrary = () => fc.record({
    id: fc.integer({ min: 1, max: 1_000_000 }),
    enrollment_id: fc.option(fc.integer({ min: 1, max: 1_000_000 }), { nil: null }),
    student_id: fc.option(fc.integer({ min: 1, max: 1_000_000 }), { nil: null }),
    teacher_id: fc.option(fc.integer({ min: 1, max: 1_000_000 }), { nil: null }),
    instrument_id: fc.option(fc.integer({ min: 1, max: 1_000_000 }), { nil: null }),
    room: fc.option(fc.string({ minLength: 1, maxLength: 20 }), { nil: null }),
});

/** A relation conflict always differs on at least one stable identity. */
export const relationConflictArbitrary = () => fc.record({
    enrollment: enrollmentDoubleArbitrary(),
    direct: enrollmentDoubleArbitrary(),
}).filter(({ enrollment, direct }) => (
    enrollment.student_id !== direct.student_id
    || enrollment.teacher_id !== direct.teacher_id
    || enrollment.instrument_id !== direct.instrument_id
));

export const protectedDependencyGraphArbitrary = () => fc.record({
    entity: bulkEntityArbitrary(),
    dependencies: fc.uniqueArray(protectedDependencyArbitrary(), { minLength: 1 }),
});
