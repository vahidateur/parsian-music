{{-- Core loading skeleton. Props: variant. Phase: 0.5. --}}
@props(['variant' => 'text'])
@php($variant = in_array($variant, ['text', 'title', 'avatar'], true) ? $variant : 'text')
<span {{ $attributes->merge(['class' => 'ui-skeleton ui-skeleton--' . $variant]) }} aria-hidden="true"></span>