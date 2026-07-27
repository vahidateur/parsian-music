{{-- Core loading state. Props: message. Phase: 0.5. --}}
@props(['message' => 'در حال بارگذاری…'])
<div {{ $attributes->merge(['class' => 'ui-loading-state']) }} role="status" aria-live="polite">
    <span class="ui-loading-state__spinner" aria-hidden="true"></span>
    <span>{{ $message }}</span>
</div>