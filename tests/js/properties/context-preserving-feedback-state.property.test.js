import test from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import adminState from '../../../resources/js/admin-state.js';

// Feature: admin-operational-ux-baseline, Property 11: Context-Preserving Feedback State
// **Validates: Requirements 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 7.10, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 8.10, 8.11**

const requestSequenceArbitrary = fc.array(
    fc.record({
        outcome: fc.constantFrom('success', 'error'),
        message: fc.string({ minLength: 1, maxLength: 80 }),
        duplicateAttempts: fc.integer({ min: 1, max: 5 }),
    }),
    { minLength: 1, maxLength: 25 },
);

const feedbackFor = (outcome, message) => ({ type: outcome, message });

const assertExactlyOneTerminalState = (state, expectedFeedback) => {
    assert.equal(state.pending, false);
    assert.deepEqual(state.feedback, expectedFeedback);
    assert.equal(
        Number(state.feedback?.type === 'success') + Number(state.feedback?.type === 'error'),
        1,
    );
};

test('preserves the newest feedback state across request sequences and duplicate submissions', () => {
    fc.assert(
        fc.property(requestSequenceArbitrary, (sequence) => {
            const state = adminState();
            const completedRequestIds = [];
            let newestFeedback = null;

            for (const step of sequence) {
                const requestId = state.beginRequest();

                assert.notEqual(requestId, null);
                assert.equal(state.pending, true);
                assert.equal(state.feedback, null);

                for (let attempt = 0; attempt < step.duplicateAttempts; attempt += 1) {
                    const duplicateEvent = {
                        prevented: false,
                        preventDefault() {
                            this.prevented = true;
                        },
                    };

                    assert.equal(state.onSubmit(duplicateEvent), false);
                    assert.equal(duplicateEvent.prevented, true);
                    assert.equal(state.lastRequestId, requestId);
                    assert.equal(state.pending, true);
                    assert.equal(state.feedback, null);
                }

                for (const staleRequestId of completedRequestIds) {
                    assert.equal(state.completeRequest(staleRequestId, { message: 'stale success' }), false);
                    assert.equal(state.failRequest(staleRequestId, { message: 'stale error' }), false);
                    assert.equal(state.pending, true);
                    assert.equal(state.feedback, null);
                }

                newestFeedback = feedbackFor(step.outcome, step.message);
                const completed = step.outcome === 'success'
                    ? state.completeRequest(requestId, { message: step.message })
                    : state.failRequest(requestId, { message: step.message });

                assert.equal(completed, true);
                assertExactlyOneTerminalState(state, newestFeedback);

                for (const staleRequestId of completedRequestIds) {
                    assert.equal(state.completeRequest(staleRequestId, { message: 'stale success' }), false);
                    assert.equal(state.failRequest(staleRequestId, { message: 'stale error' }), false);
                    assertExactlyOneTerminalState(state, newestFeedback);
                }

                completedRequestIds.push(requestId);
            }
        }),
        { numRuns: 100 },
    );
});
