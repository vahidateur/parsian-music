{{--
/**
 * Button Component
 * 
 * Generic button with multiple variants.
 * Completely generic - NO domain-specific logic.
 * Used across all pages: Login, Dashboard, Forms, Actions, etc.
 * 
 * @props
 * - variant: Button style variant (default: 'primary')
 *   Options: 'primary', 'secondary', 'ghost', 'danger', 'success'
 * - type: Button type (default: 'button')
 *   Options: 'button', 'submit', 'reset'
 * - size: Button size (default: 'md')
 *   Options: 'sm', 'md', 'lg'
 * - disabled: Boolean, disables button (default: false)
 * - fullWidth: Boolean, makes button full width (default: false)
 * - loading: Boolean, shows loading state (default: false)
 * - class: Additional CSS classes
 * 
 * @slots
 * - default: Button content (text, icons, etc.)
 * 
 * @example
 * Primary button example:
 * x:ui.button variant="primary" type="submit">
 *     ورود
 * </x-ui.button>
 * 
 * Button with icon example:
 * x:ui.button variant="secondary" size="sm">
 *     <i data-lucide="settings" class="w-4 h-4"></i>
 *     <span>تنظیمات</span>
 * </x-ui.button>
 * 
 * Loading state example:
 * x:ui.button variant="primary" :loading="true">
 *     در حال ورود...
 * </x-ui.button>
 * 
 * @accessibility
 * - Proper button type for semantics
 * - Disabled state prevents interaction
 * - Loading state shows aria-busy
 * - Focus indicator visible (ring)
 * - Keyboard accessible (native button element)
 */
--}}

@props([
    'variant' => 'primary',
    'type' => 'button',
    'size' => 'md',
    'disabled' => false,
    'fullWidth' => false,
    'loading' => false,
])

@php
    $allowedVariants = ['primary', 'secondary', 'ghost', 'danger', 'success'];
    $allowedSizes = ['sm', 'md', 'lg'];
    $variant = in_array($variant, $allowedVariants, true) ? $variant : 'primary';
    $size = in_array($size, $allowedSizes, true) ? $size : 'md';
@endphp

<button
    type="{{ $type }}"
    @if($disabled || $loading) disabled @endif
    @if($loading) aria-busy="true" @endif
    {{ $attributes->merge(['class' => 'ui-button ui-button--' . $variant . ' ui-button--' . $size . ($fullWidth ? ' ui-button--full' : '')]) }}
>
    @if($loading)
        <span class="ui-button__spinner" aria-hidden="true"></span>
    @endif

    {{ $slot }}
</button>
