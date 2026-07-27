{{-- Core alert feedback component. Props: variant, title, message, meta. Phase: 0.5. --}}
@props([
    'variant' => 'info', 'title' => null, 'message' => null, 'meta' => null,
    'dismissible' => false,
])
@php($variant = in_array($variant, ['accent', 'success', 'warning', 'danger', 'info'], true) ? $variant : 'info')
<div x-data="{ visible: true }" x-show="visible" role="alert"
    {{ $attributes->merge(['class' => 'ui-alert ui-alert--' . $variant]) }}>
    <div class="ui-alert__content">
        @if($title)<p class="ui-alert__title">{{ $title }}</p>@endif
        @if($message)<p class="ui-alert__message">{{ $message }}</p>@endif
        @if($meta)<x-ui.badge variant="{{ $variant === 'accent' ? 'accent' : $variant }}">{{ $meta }}</x-ui.badge>@endif
        {{ $slot }}
    </div>
    @if($dismissible)
        <button type="button" class="ui-alert__close" aria-label="بستن" x-on:click="visible = false">&times;</button>
    @endif
</div>