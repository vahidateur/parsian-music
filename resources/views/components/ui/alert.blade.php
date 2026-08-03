{{-- Core alert feedback component. Props: variant, role, title, message, meta. Phase: 0.5. --}}
@props([
    'variant' => 'info', 'title' => null, 'message' => null, 'meta' => null,
    'dismissible' => false, 'role' => 'alert', 'dismissLabel' => 'بستن',
])
@php($variant = in_array($variant, ['accent', 'success', 'warning', 'danger', 'info'], true) ? $variant : 'info')
@php($role = in_array($role, ['alert', 'status'], true) ? $role : 'alert')
<div x-data="{ visible: true }" x-show="visible" role="{{ $role }}"
    aria-live="{{ $role === 'alert' ? 'assertive' : 'polite' }}" aria-atomic="true"
    {{ $attributes->merge(['class' => 'ui-alert ui-alert--' . $variant]) }}>
    <div class="ui-alert__content">
        @if($title)<p class="ui-alert__title">{{ $title }}</p>@endif
        @if($message)<p class="ui-alert__message">{{ $message }}</p>@endif
        @if($meta)<x-ui.badge variant="{{ $variant === 'accent' ? 'accent' : $variant }}">{{ $meta }}</x-ui.badge>@endif
        {{ $slot }}
    </div>
    @if($dismissible)
        <button type="button" class="ui-alert__close" aria-label="{{ $dismissLabel }}" x-on:click="visible = false">&times;</button>
    @endif
</div>
