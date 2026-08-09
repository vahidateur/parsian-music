import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import {
    SCHEDULING_CASE_RUNS,
    SCHEDULING_CASE_SEED,
    assertSchedulingProperty,
    concurrencyCaseArbitrary,
    firstSchedulingFailure,
    intervalCaseArbitrary,
    relationPathCaseArbitrary,
    roomCaseArbitrary,
    ruleCaseArbitrary,
    schedulingCaseParameters,
    versionCaseArbitrary,
} from '../support/scheduling-case-generators.js';

// Feature: interactive-session-scheduling, Property 0: Deterministic scheduling-case support
// **Validates: Requirements 1.1-1.8, 17.1-17.7, 18.5-18.7, 20.5, 21.1-21.8**

test('scheduling case builders run at least one hundred reproducible cases with seed metadata', () => {
    const builders = [
        ['interval', intervalCaseArbitrary],
        ['relation-path', relationPathCaseArbitrary],
        ['rule', ruleCaseArbitrary],
        ['room', roomCaseArbitrary],
        ['version', versionCaseArbitrary],
        ['concurrency', concurrencyCaseArbitrary],
    ];

    for (const [index, [family, builder]] of builders.entries()) {
        const seed = SCHEDULING_CASE_SEED + index;
        let runs = 0;

        assertSchedulingProperty(family, fc.property(builder(seed), (value) => {
            runs += 1;
            assert.equal(value.fixture.family, family);
            assert.equal(value.fixture.seed, seed);
        }), seed);

        assert.equal(runs, SCHEDULING_CASE_RUNS);
    }
});

test('scheduling property diagnostics retain the property name, seed, and first failure detail', () => {
    const diagnostic = firstSchedulingFailure('interval', SCHEDULING_CASE_SEED, new Error('counterexample=42'));

    assert.match(diagnostic, /\[interval\]/);
    assert.match(diagnostic, new RegExp(`seed=${SCHEDULING_CASE_SEED}`));
    assert.match(diagnostic, /first failure: counterexample=42/);
    assert.deepEqual(schedulingCaseParameters(), {
        seed: SCHEDULING_CASE_SEED,
        numRuns: SCHEDULING_CASE_RUNS,
        endOnFailure: true,
        verbose: 2,
    });
});
