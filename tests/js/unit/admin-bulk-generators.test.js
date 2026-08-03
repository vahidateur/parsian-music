import assert from 'node:assert/strict';
import test from 'node:test';
import fc from 'fast-check';
import {
    BULK_ACTIONS,
    BULK_ENTITIES,
    PROTECTED_DEPENDENCIES,
    relationConflictArbitrary,
    protectedDependencyGraphArbitrary,
    roomDoubleArbitrary,
    policyActorArbitrary,
} from '../support/admin-bulk-generators.js';

test('admin-bulk generators produce valid policy actors and rooms', () => {
    fc.assert(fc.property(policyActorArbitrary(), roomDoubleArbitrary(), (actor, room) => {
        assert.ok(actor.id > 0);
        assert.ok(['admin', 'super_admin', 'teacher', 'student'].includes(actor.role));
        assert.equal(typeof actor.is_active, 'boolean');
        assert.ok(room.id > 0);
        assert.ok(room.name.trim().length > 0);
        assert.equal(typeof room.is_active, 'boolean');
    }), { numRuns: 100 });
});

test('relation-conflict generator always differs on one stable relation id', () => {
    fc.assert(fc.property(relationConflictArbitrary(), ({ enrollment, direct }) => {
        assert.ok(
            enrollment.student_id !== direct.student_id
            || enrollment.teacher_id !== direct.teacher_id
            || enrollment.instrument_id !== direct.instrument_id,
        );
    }), { numRuns: 100 });
});

test('protected dependency graph generator selects only supported categories', () => {
    fc.assert(fc.property(protectedDependencyGraphArbitrary(), ({ entity, dependencies }) => {
        assert.ok(BULK_ENTITIES.includes(entity));
        assert.ok(dependencies.length >= 1);
        assert.ok(dependencies.every((dependency) => PROTECTED_DEPENDENCIES.includes(dependency)));
    }), { numRuns: 100 });
});

// Keep the exported action/entity contract exercised by the fixture test itself.
test('bulk fixture generator constants expose the initial request contract', () => {
    assert.deepEqual(BULK_ENTITIES, ['teacher', 'student']);
    assert.deepEqual(BULK_ACTIONS, ['activate', 'deactivate', 'delete']);
});
