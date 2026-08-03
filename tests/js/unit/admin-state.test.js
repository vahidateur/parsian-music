import test from 'node:test';
import assert from 'node:assert/strict';
import adminState from '../../../resources/js/admin-state.js';

test('keeps the shared operational state contract and blocks duplicate submits', () => {
    const state = adminState();
    const firstEvent = { prevented: false, preventDefault() { this.prevented = true; } };
    const duplicateEvent = { prevented: false, preventDefault() { this.prevented = true; } };

    assert.deepEqual(
        Object.keys(state).filter((key) => !['beginRequest', 'onSubmit', 'isCurrentRequest', 'completeRequest', 'failRequest', 'finishRequest', 'settle', 'openDialog', 'closeDialog'].includes(key)),
        ['pending', 'dialogOpen', 'feedback', 'lastRequestId', 'focus']
    );
    assert.equal(state.onSubmit(firstEvent), true);
    assert.equal(state.pending, true);
    assert.equal(state.lastRequestId, 1);
    assert.equal(state.onSubmit(duplicateEvent), false);
    assert.equal(duplicateEvent.prevented, true);
    assert.equal(state.feedback, null);
});

test('replaces loading with one current success and ignores stale completion', () => {
    const state = adminState();
    const firstRequest = state.beginRequest();

    assert.equal(state.completeRequest(firstRequest, { message: 'ذخیره شد' }), true);
    assert.equal(state.pending, false);
    assert.deepEqual(state.feedback, { type: 'success', message: 'ذخیره شد' });

    const secondRequest = state.beginRequest();
    assert.equal(state.completeRequest(firstRequest, { message: 'قدیمی' }), false);
    assert.equal(state.pending, true);
    assert.equal(state.feedback, null);
    assert.equal(state.failRequest(secondRequest, { message: 'خطا' }), true);
    assert.deepEqual(state.feedback, { type: 'error', message: 'خطا' });
});

test('times out the current request into an Error_State and restores dialog focus', async () => {
    const state = adminState();
    const trigger = { focusCount: 0, focus() { this.focusCount += 1; } };
    const requestId = state.beginRequest({ timeoutMs: 5, timeoutFeedback: { message: 'زمان درخواست تمام شد' } });

    await new Promise((resolve) => setTimeout(resolve, 15));
    assert.equal(state.failRequest(requestId, { message: 'قدیمی' }), false);
    assert.equal(state.pending, false);
    assert.deepEqual(state.feedback, { type: 'error', message: 'زمان درخواست تمام شد' });

    state.openDialog(trigger);
    assert.equal(state.dialogOpen, true);
    state.closeDialog();
    assert.equal(state.dialogOpen, false);
    assert.equal(trigger.focusCount, 1);
});

test('shows Loading_State synchronously and keeps the newest result after a stale response', () => {
    const state = adminState();
    const startedAt = performance.now();
    const firstRequest = state.beginRequest();

    assert.equal(state.pending, true);
    assert.ok(performance.now() - startedAt < 200);

    state.settle(firstRequest);
    const newestRequest = state.beginRequest();

    assert.equal(state.completeRequest(newestRequest, { message: 'جدیدترین نتیجه' }), true);
    assert.deepEqual(state.feedback, { type: 'success', message: 'جدیدترین نتیجه' });
    assert.equal(state.completeRequest(firstRequest, { message: 'نتیجه قدیمی' }), false);
    assert.deepEqual(state.feedback, { type: 'success', message: 'جدیدترین نتیجه' });
});
