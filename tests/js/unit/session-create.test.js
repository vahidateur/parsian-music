import test from 'node:test';
import assert from 'node:assert/strict';
import {
    filterStudents,
    instrumentsForTeacher,
    subscriptionFor,
} from '../../../resources/js/session-create.js';

const students = [
    { id: 1, full_name: 'آوا رضایی', subscriptions: [] },
    { id: 2, full_name: 'Nima Ahmadi', subscriptions: [] },
];

test('student autocomplete filters by name or identifier and limits results', () => {
    assert.deepEqual(filterStudents(students, 'آوا').map((student) => student.id), [1]);
    assert.deepEqual(filterStudents(students, 'Nim').map((student) => student.id), [2]);
    assert.deepEqual(filterStudents(students, 'a'), []);
});

test('teacher instrument options are limited to the prepared teacher map', () => {
    const map = { '7': [{ id: 3, name: 'Piano' }] };

    assert.deepEqual(instrumentsForTeacher(map, 7), [{ id: 3, name: 'Piano' }]);
    assert.deepEqual(instrumentsForTeacher(map, 8), []);
});

test('quota lookup keeps the selected student subscription and distinguishes combinations', () => {
    const subscriptions = [
        { teacher_id: 7, instrument_id: 3, sessions_used: 4, sessions_allocated: 4 },
        { teacher_id: 8, instrument_id: 3, sessions_used: 0, sessions_allocated: 4 },
    ];

    assert.equal(subscriptionFor(subscriptions, '7', '3').sessions_used, 4);
    assert.equal(subscriptionFor(subscriptions, 7, 8), null);
});
