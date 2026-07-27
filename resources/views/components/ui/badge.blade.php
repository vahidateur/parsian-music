{{-- Core status badge. Props: variant. Slot: label/content. Phase: 0.5. --}}
@props(['variant' => 'neutral'])
@php($variant = in_array($variant, ['neutral', 'accent', 'success', 'warning', 'danger', 'info'], true) ? $variant : 'neutral')
<span {{ $attributes->merge(['class' => 'ui-badge ui-badge--' . $variant]) }}>{{ $slot }}</span>