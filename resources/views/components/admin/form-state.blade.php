{{--
    Loading_State owner of one operational form.

    Wraps a Record_Form and holds the shared `adminState` interaction state.
    The `submit` event of the wrapped form bubbles up to this element, which is
    why the guard lives here: the first submission has already started when the
    guard runs, so the trigger can be disabled without cancelling it while a
    second submission of the same request is refused.

    Usage:
        <x-admin.form-state>
            <form method="POST" action="...">
                ...
                <x-admin.submit-button :label="__('admin.save')" />
            </form>
        </x-admin.form-state>

    Requirements: 7.1, 7.4, 7.10
    Phase: Operational UX baseline — task 11.2.
--}}
<div {{ $attributes->merge(['class' => 'admin-form-state']) }}
     x-data="adminState"
     x-on:submit="onSubmit($event)"
     data-admin-form-state>
    <p class="sr-only" x-show="pending" x-cloak role="status" aria-live="polite" aria-atomic="true">
        {{ __('admin.state_submitting') }}
    </p>
    <p class="sr-only" x-show="feedback" x-cloak
       :role="feedback?.type === 'error' ? 'alert' : 'status'"
       aria-live="polite" aria-atomic="true"
       x-text="feedback?.message || ''"></p>
    {{ $slot }}
</div>
