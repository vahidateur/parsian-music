import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import {
    buildEventCardHtml,
    normalizeEventCollection,
} from '../../../resources/js/calendar/fullcalendar.js';
import {
    SCHEDULING_CASE_RUNS,
    SCHEDULING_CASE_SEED,
    assertSchedulingProperty,
} from '../support/scheduling-case-generators.js';

// Feature: calendar-persisted-session-projection, Property 2: Preservation
// **Validates: Requirements 3.2, 3.3, 3.4**

const statuses = ['scheduled', 'completed', 'cancelled', 'missed'];
const clone = (value) => JSON.parse(JSON.stringify(value));
const stableIds = (events) => events.map((event) => event.id);
const sameIds = (left, right) => left.length === right.length
    && left.every((id, index) => id === right[index]);

const eventArbitrary = () => fc.record({
    id: fc.integer({ min: 1, max: 1_000_000 }),
    day: fc.integer({ min: 0, max: 3650 }),
    duration: fc.integer({ min: 1, max: 180 }),
    status: fc.constantFrom(...statuses),
    studentName: fc.constantFrom('Ali Mohammadi', 'Sara Ahmadi', 'نازنین حسینی'),
    teacherName: fc.constantFrom('Nazanin Hosseini', 'Reza Moradi'),
    instrumentName: fc.constantFrom('Violin', 'Piano', 'Daf'),
    room: fc.constantFrom('A101', 'B202', 'Studio 3'),
}).map((value) => {
    const date = new Date(Date.UTC(2025, 0, 1 + value.day));
    const day = date.toISOString().slice(0, 10);
    const start = new Date(`${day}T09:00:00Z`);
    const end = new Date(start.getTime() + value.duration * 60_000);

    return {
        id: value.id,
        title: `${value.studentName} — ${value.instrumentName}`,
        start: start.toISOString().slice(0, 19),
        end: end.toISOString().slice(0, 19),
        status: value.status,
        studentName: value.studentName,
        teacherName: value.teacherName,
        instrumentName: value.instrumentName,
        room: value.room,
        extendedProps: { duration_minutes: value.duration, session_date: day },
    };
});

const eventCollectionArbitrary = () => fc.uniqueArray(eventArbitrary(), {
    selector: (event) => event.id,
    maxLength: 12,
});

test('preserves valid persisted event IDs, counts, and card metadata', () => {
    fc.assert(
        fc.property(eventCollectionArbitrary(), (events) => {
            const normalized = normalizeEventCollection(events);

            assert.equal(normalized.length, events.length);
            assert.deepEqual(stableIds(normalized), stableIds(events));
            normalized.forEach((event, index) => {
                const source = events[index];
                assert.equal(event.status, source.status);
                assert.equal(event.studentName, source.studentName);
                assert.equal(event.teacherName, source.teacherName);
                assert.equal(event.instrumentName, source.instrumentName);
                assert.equal(event.room, source.room);
                assert.equal(event.extendedProps.duration_minutes, source.extendedProps.duration_minutes);
                assert.match(buildEventCardHtml(event), new RegExp(source.studentName));
                assert.match(buildEventCardHtml(event), new RegExp(source.teacherName));
            });
        }),
        { numRuns: 100 },
    );
});

test('preserves valid empty collections and rejects malformed payloads without repair', () => {
    assert.deepEqual(normalizeEventCollection([]), []);
    fc.assert(
        fc.property(eventArbitrary(), fc.constantFrom('id', 'start', 'end', 'status'), (event, field) => {
            const malformed = {
                ...event,
                id: field === 'id' ? null : event.id,
                start: field === 'start' ? 'not-a-date' : event.start,
                end: field === 'end' ? event.start : event.end,
                status: field === 'status' ? 'invalid' : event.status,
            };

            assert.deepEqual(normalizeEventCollection([malformed]), []);
            assert.deepEqual(malformed, {
                ...event,
                id: field === 'id' ? null : event.id,
                start: field === 'start' ? 'not-a-date' : event.start,
                end: field === 'end' ? event.start : event.end,
                status: field === 'status' ? 'invalid' : event.status,
            });
        }),
        { numRuns: 100 },
    );
});

const matchesApprovedFilters = (session, filters) => (
    (filters.teacher_id === null || session.teacher_id === filters.teacher_id)
    && (filters.student_id === null || session.student_id === filters.student_id)
    && (filters.room === null || session.room === filters.room)
);

const membershipInputArbitrary = () => fc.record({
    sessions: fc.uniqueArray(fc.record({
        id: fc.integer({ min: 1, max: 1_000_000 }),
        teacher_id: fc.integer({ min: 1, max: 5 }),
        student_id: fc.integer({ min: 1, max: 5 }),
        room: fc.constantFrom('A101', 'B202', 'Studio 3'),
    }), { selector: (session) => session.id, maxLength: 12 }),
    teacher_id: fc.option(fc.integer({ min: 1, max: 5 }), { nil: null }),
    student_id: fc.option(fc.integer({ min: 1, max: 5 }), { nil: null }),
    room: fc.option(fc.constantFrom('A101', 'B202', 'Studio 3'), { nil: null }),
    instrument_id: fc.integer({ min: 1, max: 1_000_000 }),
});

test('keeps the approved membership oracle independent of instrument_id', () => {
    fc.assert(
        fc.property(membershipInputArbitrary(), fc.integer({ min: 1, max: 1_000_000 }), (input, independentInstrumentId) => {
            const approved = input.sessions
                .filter((session) => matchesApprovedFilters(session, input))
                .map((session) => session.id);
            const withInstrument = input.sessions
                .filter((session) => matchesApprovedFilters(session, {
                    ...input,
                    instrument_id: independentInstrumentId,
                }))
                .map((session) => session.id);

            assert.deepEqual(withInstrument, approved);
        }),
        { numRuns: 100 },
    );
});

const projectionBoundaries = [
    ['persisted→query', 'persisted', 'query', 'CalendarQueryService'],
    ['query→resource', 'query', 'resource', 'CalendarEventResource'],
    ['resource→endpoint', 'resource', 'endpoint', 'named admin.calendar.events JSON'],
    ['endpoint→normalizer', 'endpoint', 'normalized', 'existing normalization'],
];
const projectionStages = ['persisted', 'query', 'resource', 'endpoint', 'normalized'];
const projectionContractFields = [
    'start',
    'end',
    'status',
    'title',
    'studentName',
    'teacherName',
    'instrumentName',
    'room',
];

const projectionContract = (events) => events.map((event) => Object.fromEntries(
    projectionContractFields.map((field) => [
        field,
        Object.prototype.hasOwnProperty.call(event, field) ? event[field] : '<missing>',
    ]),
));

const projectionSummary = (events) => ({
    count: events.length,
    ids: stableIds(events),
    contract: projectionContract(events),
});

const projectionMismatchKind = (left, right) => {
    if (left.length !== right.length) return 'count';
    if (!sameIds(stableIds(left), stableIds(right))) return 'identity';
    if (JSON.stringify(projectionContract(left)) !== JSON.stringify(projectionContract(right))) return 'contract';

    return null;
};

const firstProjectionDiagnostic = (pipeline, fixture = null, input = null) => {
    const mismatch = projectionBoundaries
        .map(([boundary, leftStage, rightStage, owner]) => ({
            boundary,
            leftStage,
            rightStage,
            owner,
            left: pipeline[leftStage],
            right: pipeline[rightStage],
        }))
        .find(({ left, right }) => projectionMismatchKind(left, right) !== null);

    if (!mismatch) return null;

    return {
        boundary: mismatch.boundary,
        owner: mismatch.owner,
        kind: projectionMismatchKind(mismatch.left, mismatch.right),
        fixture,
        input,
        expected: projectionSummary(mismatch.left),
        observed: projectionSummary(mismatch.right),
    };
};

const firstProjectionMismatch = (persisted, query, resource, endpoint, normalized) => (
    firstProjectionDiagnostic({ persisted, query, resource, endpoint, normalized })?.boundary ?? null
);

const projectionFailureDiagnostic = (diagnostic) => (
    `boundary=${diagnostic.boundary}; fixture=${JSON.stringify(diagnostic.fixture)}; `
    + `input=${JSON.stringify(diagnostic.input)}; expected=${JSON.stringify(diagnostic.expected)}; `
    + `observed=${JSON.stringify(diagnostic.observed)}; owner=${diagnostic.owner}`
);

const projectionDiagnosticCaseArbitrary = () => eventCollectionArbitrary()
    .filter((events) => events.length > 0)
    .map((events) => ({
        events,
        input: {
            sourceCount: events.length,
            sourceIds: stableIds(events),
        },
    }));

const buildProjectionPipeline = (events) => ({
    persisted: clone(events),
    query: clone(events),
    resource: clone(events),
    endpoint: clone(events),
    normalized: normalizeEventCollection(events),
});

const injectProjectionMismatch = (events, kind) => {
    if (kind === 'count') return events.slice(1);

    const [first, ...rest] = clone(events);
    if (kind === 'identity') {
        return [{ ...first, id: first.id + 1_000_001 }, ...rest];
    }

    return [{ ...first, title: `${first.title} [contract-mismatch]` }, ...rest];
};

test('reports the first malformed projection boundary without changing later representations', () => {
    fc.assert(
        fc.property(eventCollectionArbitrary().filter((events) => events.length > 0), fc.constantFrom('resource', 'endpoint', 'normalizer'), (events, boundary) => {
            const query = clone(events);
            let resource = clone(events);
            let endpoint = clone(events);
            let normalized = normalizeEventCollection(events);
            const malformed = { ...events[0], id: null };
            const expectedBoundary = {
                resource: 'query→resource',
                endpoint: 'resource→endpoint',
                normalizer: 'endpoint→normalizer',
            }[boundary];

            if (boundary === 'resource') resource = [malformed, ...resource.slice(1)];
            if (boundary === 'endpoint') endpoint = [malformed, ...endpoint.slice(1)];
            if (boundary === 'normalizer') normalized = normalizeEventCollection([malformed, ...events.slice(1)]);

            const laterSnapshots = { endpoint: clone(endpoint), normalized: clone(normalized) };
            assert.equal(firstProjectionMismatch(events, query, resource, endpoint, normalized), expectedBoundary);
            assert.deepEqual(endpoint, laterSnapshots.endpoint);
            assert.deepEqual(normalized, laterSnapshots.normalized);
        }),
        { numRuns: 100 },
    );
});

// Feature: interactive-session-scheduling, Property 16: First-boundary diagnostic invariant
// **Validates: Requirements 1.1, 17.1, 21.5, 21.6**
test('reports the first count, identity, or contract mismatch with ownership and unchanged later representations', () => {
    const mismatchKinds = ['count', 'identity', 'contract'];

    for (const [boundaryIndex, [boundary, leftStage, rightStage]] of projectionBoundaries.entries()) {
        for (const [kindIndex, kind] of mismatchKinds.entries()) {
            const seed = SCHEDULING_CASE_SEED + (boundaryIndex * mismatchKinds.length) + kindIndex;
            let runs = 0;

            assertSchedulingProperty(
                `Property 16 ${boundary} ${kind}`,
                fc.property(projectionDiagnosticCaseArbitrary(), (testCase) => {
                    const caseIndex = runs;
                    runs += 1;
                    const fixture = {
                        ...testCase.input,
                        family: 'calendar-projection-diagnostic',
                        seed,
                        case: caseIndex,
                    };
                    const input = {
                        ...testCase.input,
                        boundary,
                        mismatch: kind,
                        case: caseIndex,
                    };
                    const pipeline = buildProjectionPipeline(testCase.events);
                    pipeline[rightStage] = injectProjectionMismatch(pipeline[rightStage], kind);
                    const laterSnapshots = Object.fromEntries(
                        projectionStages
                            .slice(projectionStages.indexOf(rightStage) + 1)
                            .map((stage) => [stage, clone(pipeline[stage])]),
                    );
                    const diagnostic = firstProjectionDiagnostic(pipeline, fixture, input);
                    const message = diagnostic ? projectionFailureDiagnostic(diagnostic) : 'missing diagnostic';

                    assert.ok(diagnostic, message);
                    assert.equal(diagnostic.boundary, boundary, message);
                    assert.equal(diagnostic.kind, kind, message);
                    assert.equal(diagnostic.owner, projectionBoundaries[boundaryIndex][3], message);
                    assert.deepEqual(diagnostic.fixture, fixture, message);
                    assert.deepEqual(diagnostic.input, input, message);
                    assert.deepEqual(diagnostic.expected, projectionSummary(pipeline[leftStage]), message);
                    assert.deepEqual(diagnostic.observed, projectionSummary(pipeline[rightStage]), message);
                    assert.match(message, /boundary=.*fixture=.*input=.*expected=.*observed=.*owner=/);

                    for (const [stage, snapshot] of Object.entries(laterSnapshots)) {
                        assert.deepEqual(pipeline[stage], snapshot, message);
                    }
                }),
                seed,
            );

            assert.equal(runs, SCHEDULING_CASE_RUNS);
        }
    }
});
