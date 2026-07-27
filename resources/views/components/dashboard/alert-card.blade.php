{{--
    Dashboard compatibility adapter for the reusable alert component.
    Props: title, message, priority, meta; slot: additional content.
    Phase: 0.5 foundation compatibility.
--}}
@props([
    'title' => null,
    'message' => null,
    'priority' => 'mid',
    'meta' => null,
])

@php
    $variant = match ($priority) {
        'high' => 'danger',
        'mid' => 'warning',
        'low' => 'accent',
        'info' => 'info',
        'success' => 'success',
        default => 'warning',
    };
@endphp

<x-ui.alert
    :variant="$variant"
    :title="$title"
    :message="$message"
    :meta="$meta"
    {{ $attributes }}
>
    {{ $slot }}
</x-ui.alert>