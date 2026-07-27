{{--
/**
 * Glass Card Component
 * 
 * Generic glassmorphism card shell.
 * Completely generic - NO domain-specific logic.
 * Used across entire academy: Login, Register, Dashboard, Modals, Panels, etc.
 * 
 * @props
 * - width: Card width (default: 'var(--card-width)' = 470px)
 * - maxWidth: Maximum width (default: same as width)
 * - height: Card height (default: 'auto')
 * - maxHeight: Maximum height (default: 'var(--card-height)' = 760px)
 * - padding: Inner padding (default: 'var(--space-6)' = 48px)
 * - radius: Border radius (default: 'var(--radius-lg)' = 28px)
 * - class: Additional CSS classes
 * 
 * @slots
 * - default: Card content
 * 
 * @example
 * {{-- Standard login card --}}
 * x:ui.glass.card>
 *     <h1>Card Content</h1>
 * </x-ui.glass.card>
 * 
 * {{-- Custom sized card --}}
 * x:ui.glass.card width="600px" height="auto" padding="var(--space-4)">
 *     <p>Custom card</p>
 * </x-ui.glass.card>
 * 
 * {{-- Dashboard widget --}}
 * x:ui.glass.card 
 *     width="100%" 
 *     maxWidth="400px" 
 *     radius="var(--radius-md)"
 * >
 *     <div>Widget Content</div>
 * </x-ui.glass.card>
 * 
 * @accessibility
 * - Uses semantic structure (div container)
 * - Content remains readable on glass background
 * - Focus management handled by child elements
 * - Respects prefers-reduced-motion (blur disabled)
 */
--}}

@props([
    'width' => 'var(--card-width)',
    'maxWidth' => null,
    'height' => 'auto',
    'maxHeight' => 'var(--card-height)',
    'padding' => 'var(--space-6)',
    'radius' => 'var(--radius-lg)',
])

@php
    $isCompact = $width === '100%' || $maxWidth === '400px' || $padding === 'var(--space-4)' || $radius === 'var(--radius-md)';
    $isFluid = $width === '100%';
    $cardVariant = $isCompact ? 'ui-card--compact' : 'ui-card--default';
    $cardVariant .= $isFluid ? ' ui-card--fluid' : '';
@endphp

<div {{ $attributes->merge(['class' => 'ui-card ui-card--glass ' . $cardVariant]) }}>
    {{ $slot }}
</div>
