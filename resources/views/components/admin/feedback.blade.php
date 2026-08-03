{{--
    Feedback_Channel — the single shared operational feedback channel of the admin panel.

    Responsibility: render success, failure and validation feedback for every admin
    screen through one contract instead of per-page flash markup.

    Props:
    - field      : render only the field-level validation message of that field.
    - success    : explicit success message (defaults to the `success` flash key).
    - failure    : explicit failure message (defaults to the `error` flash key).
    - validation : render the validation summary (default true; disable it on forms
                   that already render field-level messages).
    - retryUrl   : Error_State recovery path that retries the failed request
                   (defaults to the current URL, so the applied List_Context or
                   the open form survives the retry; pass `false` to omit it).
    - returnUrl  : Error_State recovery path back to a safe screen.

    Contract:
    - success feedback renders with role="status"
    - failure and validation feedback render with role="alert"
    - a failure is the shared Error_State: localized bounded message plus a
      retry/return path, rendered beside the persisted data it never replaces
    - a field message carries the id `{field}-error` and pairs with the
      aria-invalid / aria-describedby wiring of `feedback_field_attributes()`
    - every message is normalized, bounded and stripped of sensitive content by
      App\Support\Feedback\FeedbackChannel
    - a dismiss control is always present and nothing auto-hides before the
      minimum visible window, exposed as data-feedback-min-visible-ms

    Phase: Operational UX baseline — task 11.1.
--}}
@props([
    'field' => null,
    'success' => null,
    'failure' => null,
    'validation' => true,
    'retryUrl' => null,
    'returnUrl' => null,
    'returnLabel' => null,
])

@php
    $channel = \App\Support\Feedback\FeedbackChannel::class;
    $minVisibleMs = $channel::MIN_VISIBLE_MS;
    $dismissLabel = __('admin.feedback_dismiss');

    $fieldMessage = $field === null ? null : $channel::fieldMessage($field, $errors);
    $fieldErrorId = $field === null ? null : $channel::fieldErrorId($field);

    $successMessage = $field !== null ? null : $channel::success($success ?? session('success'));
    $failureMessage = $field !== null ? null : $channel::failure($failure ?? session('error'));
    $validationMessages = ($field === null && $validation) ? $channel::validationSummary($errors) : [];

    $channelAttributes = $attributes->merge(['class' => 'admin-feedback']);

    // Error_State recovery path: retry the same request with the current context
    // unless the screen explicitly opts out, plus an optional return target.
    $resolvedRetryUrl = $retryUrl === false ? null : ($retryUrl ?: url()->full());
    $resolvedReturnUrl = $returnUrl ?: null;
@endphp

@if ($fieldMessage !== null)
    <p id="{{ $fieldErrorId }}"
       role="alert"
       data-feedback="field"
       data-feedback-field="{{ $field }}"
       {{ $attributes->merge(['class' => 'admin-feedback__field']) }}>{{ $fieldMessage }}</p>
@endif

@if ($successMessage !== null)
    <x-ui.alert
        variant="success"
        role="status"
        dismissible
        :dismissLabel="$dismissLabel"
        :message="$successMessage"
        data-feedback="success"
        :data-feedback-min-visible-ms="$minVisibleMs"
        :attributes="$channelAttributes" />
@endif

@if ($failureMessage !== null)
    <x-ui.alert
        variant="danger"
        role="alert"
        dismissible
        :dismissLabel="$dismissLabel"
        :message="$failureMessage"
        data-feedback="failure"
        data-error-state="failure"
        :data-feedback-min-visible-ms="$minVisibleMs"
        :attributes="$channelAttributes">
        @if ($resolvedRetryUrl !== null || $resolvedReturnUrl !== null)
            <p class="admin-feedback__recovery">
                @if ($resolvedRetryUrl !== null)
                    <a href="{{ $resolvedRetryUrl }}" data-error-retry>{{ __('admin.state_error_retry') }}</a>
                @endif
                @if ($resolvedReturnUrl !== null)
                    <a href="{{ $resolvedReturnUrl }}" data-error-return>{{ $returnLabel ?? __('admin.state_error_return') }}</a>
                @endif
            </p>
        @endif
    </x-ui.alert>
@endif

@if ($validationMessages !== [])
    <x-ui.alert
        variant="danger"
        role="alert"
        dismissible
        :dismissLabel="$dismissLabel"
        :title="__('admin.feedback_validation_title')"
        data-feedback="validation"
        :data-feedback-min-visible-ms="$minVisibleMs"
        :attributes="$channelAttributes">
        <ul class="admin-feedback__list">
            @foreach ($validationMessages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
