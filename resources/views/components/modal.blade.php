{{--
    Core modal shell.
    Props: name, show, maxWidth; slot: dialog content.
    Phase: 0.5 — Admin Foundation.
--}}
@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
])

@php
    $allowedWidths = ['sm', 'md', 'lg', 'xl', '2xl'];
    $maxWidth = in_array($maxWidth, $allowedWidths, true) ? $maxWidth : '2xl';
@endphp

<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    x-trap.noscroll="show"
    class="ui-modal"
    role="dialog"
    aria-modal="true"
    aria-label="{{ $name }}"
>
    <div
        x-show="show"
        x-transition.opacity
        x-on:click="show = false"
        class="ui-modal__backdrop"
        aria-hidden="true"
    ></div>

    <div
        x-show="show"
        x-transition
        x-on:click.stop
        class="ui-modal__dialog ui-modal__dialog--{{ $maxWidth }}"
    >
        {{ $slot }}
    </div>
</div>