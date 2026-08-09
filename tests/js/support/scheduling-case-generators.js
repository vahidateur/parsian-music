import fc from 'fast-check';

export const SCHEDULING_CASE_SEED = 20260714;
export const SCHEDULING_CASE_RUNS = 100;

export const schedulingCaseParameters = (seed = SCHEDULING_CASE_SEED) => ({
    seed,
    numRuns: SCHEDULING_CASE_RUNS,
    endOnFailure: true,
    verbose: 2,
});

export const firstSchedulingFailure = (property, seed, error) => (
    `[${property}] seed=${seed}; first failure: ${error.message}`
);

export const assertSchedulingProperty = (property, assertion, seed = SCHEDULING_CASE_SEED) => {
    try {
        fc.assert(assertion, schedulingCaseParameters(seed));
    } catch (error) {
        throw new Error(firstSchedulingFailure(property, seed, error), { cause: error });
    }
};

const withMetadata = (family, seed, arbitrary) => arbitrary.map((value) => ({
    ...value,
    fixture: { family, seed },
}));

export const intervalCaseArbitrary = (seed = SCHEDULING_CASE_SEED) => withMetadata('interval', seed, fc.record({
    startMinute: fc.integer({ min: 0, max: 1200 }),
    durationMinutes: fc.integer({ min: 15, max: 180 }),
    bufferBefore: fc.integer({ min: 0, max: 30 }),
    bufferAfter: fc.integer({ min: 0, max: 30 }),
}).map((value) => ({
    ...value,
    endMinute: value.startMinute + value.durationMinutes,
    adjacentStartMinute: value.startMinute + value.durationMinutes,
    overlapStartMinute: value.startMinute + Math.max(1, Math.floor(value.durationMinutes / 2)),
})));

export const relationPathCaseArbitrary = (seed = SCHEDULING_CASE_SEED) => withMetadata('relation-path', seed, fc.record({
    path: fc.constantFrom('direct', 'enrollment', 'mixed'),
    studentId: fc.integer({ min: 1, max: 1000 }),
    teacherId: fc.integer({ min: 1, max: 1000 }),
    instrumentId: fc.integer({ min: 1, max: 100 }),
    enrollmentId: fc.integer({ min: 1, max: 1000 }),
}));

export const ruleCaseArbitrary = (seed = SCHEDULING_CASE_SEED) => withMetadata('rule', seed, fc.record({
    enabledWeekdays: fc.uniqueArray(fc.integer({ min: 1, max: 7 }), { minLength: 1, maxLength: 7 }),
    openingMinute: fc.integer({ min: 420, max: 660 }),
    minimumDuration: fc.integer({ min: 15, max: 60 }),
    maximumIncrement: fc.integer({ min: 15, max: 180 }),
    dailyLimit: fc.integer({ min: 1, max: 10 }),
    consecutiveLimit: fc.integer({ min: 1, max: 6 }),
    bufferBefore: fc.integer({ min: 0, max: 30 }),
    bufferAfter: fc.integer({ min: 0, max: 30 }),
}).map((value) => ({
    ...value,
    closingMinute: value.openingMinute + 480,
    maximumDuration: value.minimumDuration + value.maximumIncrement,
})));

export const roomCaseArbitrary = (seed = SCHEDULING_CASE_SEED) => withMetadata('room', seed, fc.record({
    roomId: fc.integer({ min: 1, max: 1000 }),
    name: fc.stringMatching(/^Room-[1-9][0-9]{0,2}$/),
    active: fc.boolean(),
    authorized: fc.boolean(),
    occupied: fc.boolean(),
    capabilities: fc.uniqueArray(fc.constantFrom('piano', 'violin', 'daf'), { minLength: 1, maxLength: 3 }),
    requiredCapability: fc.constantFrom('piano', 'violin', 'daf'),
}));

export const versionCaseArbitrary = (seed = SCHEDULING_CASE_SEED) => withMetadata('version', seed, fc.record({
    currentVersion: fc.integer({ min: 1, max: 10_000 }),
    state: fc.constantFrom('current', 'stale', 'missing', 'malformed'),
}).map((value) => ({
    ...value,
    persistedVersion: `v${value.currentVersion}`,
    clientVersion: {
        current: `v${value.currentVersion}`,
        stale: `v${Math.max(0, value.currentVersion - 1)}`,
        missing: '',
        malformed: `bad-${value.currentVersion}`,
    }[value.state],
})));

export const concurrencyCaseArbitrary = (seed = SCHEDULING_CASE_SEED) => withMetadata('concurrency', seed, fc.record({
    sessionId: fc.integer({ min: 1, max: 1000 }),
    currentVersion: fc.integer({ min: 1, max: 10_000 }),
    firstActor: fc.integer({ min: 1, max: 1000 }),
    secondActor: fc.integer({ min: 1, max: 1000 }),
    winner: fc.constantFrom('first', 'second'),
}).map((value) => ({
    ...value,
    interleaving: value.winner === 'first' ? ['first', 'second'] : ['second', 'first'],
    firstVersion: `v${value.currentVersion}`,
    staleVersion: `v${Math.max(0, value.currentVersion - 1)}`,
})));
