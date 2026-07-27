{{--
    Reusable empty state.
    Props: title, message, description, compact; slots: icon, action, default.
    Phase: 0.5 — Admin Foundation.
--}}
@props([
    'title' => null,
    'message' => '',
    'description' => null,
    'compact' => false,
])

@php($message = $description ?? $message)

<div {{ $attributes->merge(['class' => 'ui-empty-state' . ($compact ? ' ui-empty-state--compact' : '')]) }}>
    @isset($icon)
        <div class="ui-empty-state__icon" aria-hidden="true">
            {{ $icon }}
        </div>
    @endisset

    @if ($title)
        <p class="ui-empty-state__title">{{ $title }}</p>
    @endif

    @if ($message)
        <p class="ui-empty-state__message">{{ $message }}</p>
    @endif

    @isset($action)
        <div class="ui-empty-state__action">
            {{ $action }}
        </div>
    @endisset

    {{ $slot }}
</div>