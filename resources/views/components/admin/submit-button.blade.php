{{--
    Submit control of an operational form plus its shared Loading_State.

    Must be rendered inside `<x-admin.form-state>`, which owns the `pending`
    state. While a submission is in flight the control is disabled and marked
    busy, and the shared `x-ui.loading-state` replaces no persisted data — it is
    rendered next to the trigger, so the form keeps showing what the actor typed.

    Props:
    - label          : visible label of the control
    - pendingMessage : Loading_State message (defaults to the shared one)
    - wrapper        : extra layout classes for the wrapper, so a page keeps its
                       existing button layout (every other class targets the
                       control itself through the attribute bag)

    Requirements: 7.1, 7.4
    Phase: Operational UX baseline — task 11.2.
--}}
@props([
    'label' => null,
    'pendingMessage' => null,
    'wrapper' => null,
])

<span class="admin-submit {{ $wrapper }}">
    <button type="submit"
            {{ $attributes->merge(['class' => 'admin-submit__control rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400']) }}
            data-admin-submit
            x-bind:disabled="pending"
            x-bind:aria-busy="pending">
        {{ $label ?? $slot }}
    </button>

    <x-ui.loading-state
        class="admin-submit__state"
        :message="$pendingMessage ?? __('admin.state_submitting')"
        data-admin-loading
        x-show="pending"
        x-cloak />
</span>
