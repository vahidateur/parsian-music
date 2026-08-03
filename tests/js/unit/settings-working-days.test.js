import test from 'node:test';
import assert from 'node:assert/strict';
import settingsWorkingDays from '../../../resources/js/settings-working-days.js';

test('derives selected working days from checked inputs and exposes visual state', () => {
    const selectors = [];
    const state = settingsWorkingDays();
    state.$root = {
        querySelectorAll(selector) {
            selectors.push(selector);
            return [{ value: 'monday' }, { value: 'friday' }];
        },
    };

    state.init();

    assert.deepEqual(selectors, ['input[name="working_days[]"]:checked']);
    assert.deepEqual(state.selectedDays, ['monday', 'friday']);
    assert.equal(state.isDayActive('monday'), true);
    assert.equal(state.isDayActive('saturday'), false);
    assert.match(state.activeClasses, /border-amber-500\/40/);
    assert.match(state.inactiveClasses, /border-gray-700\/60/);
});

test('updates chip state from the native checkbox model without changing form values', () => {
    const state = settingsWorkingDays();
    state.selectedDays = ['sunday'];

    assert.equal(state.isDayActive('sunday'), true);
    assert.equal(state.isDayActive('tuesday'), false);

    state.selectedDays = [];
    assert.equal(state.isDayActive('sunday'), false);
});
