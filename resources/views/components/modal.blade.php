{{--
    Core modal shell.
    Props: name, show, maxWidth; slot: dialog content.
    Phase: 0.5 — Admin Foundation.
--}}
@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'variant' => 'default',
    'entity' => null,
    'action' => null,
    'consequence' => null,
    'title' => null,
])

@php
    $allowedWidths = ['sm', 'md', 'lg', 'xl', '2xl'];
    $maxWidth = in_array($maxWidth, $allowedWidths, true) ? $maxWidth : '2xl';
@endphp

<div
    x-data="{ show: @js($show), trigger: null }"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? (trigger = $event.target, show = true) : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-effect="if (!show && trigger) { $nextTick(() => trigger.focus()) }"
    x-show="show"
    x-cloak
    x-trap.noscroll="show"
    class="ui-modal"
    role="dialog"
    aria-modal="true"
    @if ($variant === 'confirmation')
        aria-labelledby="{{ $name }}-title"
        aria-describedby="{{ $name }}-description"
    @else
        aria-label="{{ $name }}"
    @endif
>
    <button
        type="button"
        x-show="show"
        x-transition.opacity
        x-on:click="show = false"
        class="ui-modal__backdrop"
        aria-label="{{ __('admin.close') }}"
    ></button>

    <div
        x-show="show"
        x-transition
        x-on:click.stop
        class="ui-modal__dialog ui-modal__dialog--{{ $maxWidth }}"
    >
        @if ($variant === 'confirmation')
            <div class="ui-modal__confirmation">
                <h2 id="{{ $name }}-title" class="ui-modal__confirmation-title">
                    {{ $title ?? __('admin.confirm_action_title') }}
                </h2>
                <div id="{{ $name }}-description" class="ui-modal__confirmation-summary">
                    @if ($entity !== null)
                        <div>{{ __('admin.confirm_entity', ['entity' => $entity]) }}</div>
                    @endif
                    @if ($action !== null)
                        <div>{{ __('admin.confirm_action', ['action' => $action]) }}</div>
                    @endif
                    @if ($consequence !== null)
                        <div><strong>{{ __('admin.confirm_consequence', ['consequence' => $consequence]) }}</strong></div>
                    @endif
                </div>
                <div class="ui-modal__confirmation-content">{{ $slot }}</div>
            </div>
        @else
            {{ $slot }}
        @endif
    </div>
</div>