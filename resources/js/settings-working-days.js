/**
 * Settings-owned Alpine state for working-day chip presentation.
 * Responsibility: keep selected working days reflected in their visual state.
 */

const WORKING_DAY_SELECTOR = 'input[name="working_days[]"]';

export default function settingsWorkingDays() {
    return {
        selectedDays: [],
        activeClasses: 'border-amber-500/40 bg-amber-500/10 text-amber-300',
        inactiveClasses: 'border-gray-700/60 bg-gray-800/40 text-gray-400',

        init() {
            this.selectedDays = Array.from(
                this.$root?.querySelectorAll?.(`${WORKING_DAY_SELECTOR}:checked`) ?? [],
                (input) => input.value,
            );
        },

        isDayActive(day) {
            return this.selectedDays.includes(day);
        },
    };
}
