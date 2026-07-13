{{--
/**
 * Icon Component
 * 
 * Generic icon wrapper for Lucide icons.
 * Provides consistent sizing, colors, and easy icon library switching.
 * 
 * @props
 * - name: Icon name from Lucide library (required)
 * - size: Icon size (default: 'md')
 *   Options: 'xs' (16px), 'sm' (20px), 'md' (24px), 'lg' (32px), 'xl' (48px)
 * - color: Icon color (default: 'var(--icon-default)')
 * - class: Additional CSS classes
 * 
 * @example
 * x:ui.icon name="phone" size="sm" />
 * x:ui.icon name="lock" color="var(--gold-300)" />
 * x:ui.icon name="settings" size="lg" class="hover:text-gold-200" />
 * 
 * @accessibility
 * - Icons are decorative by default (aria-hidden="true")
 * - For functional icons, wrap in element with aria-label
 * - Uses Lucide's createIcons() for initialization
 * 
 * @notes
 * - If icon library changes from Lucide, only this component needs updating
 * - Icons auto-initialize on page load via Lucide
 */
--}}

@props([
    'name',
    'size' => 'md',
    'color' => 'var(--icon-default)',
])

@php
    $sizeMap = [
        'xs' => 'var(--icon-xs)',
        'sm' => 'var(--icon-sm)',
        'md' => 'var(--icon-md)',
        'lg' => 'var(--icon-lg)',
        'xl' => 'var(--icon-xl)',
    ];
    
    $iconSize = $sizeMap[$size] ?? $sizeMap['md'];
@endphp

<i 
    data-lucide="{{ $name }}"
    {{ $attributes->merge([
        'class' => 'inline-block',
        'aria-hidden' => 'true'
    ])->merge([
        'style' => "width: {$iconSize}; height: {$iconSize}; color: {$color};"
    ]) }}
></i>
