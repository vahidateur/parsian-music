{{--
/**
 * Divider Component
 * 
 * Generic horizontal divider line with golden gradient.
 * Used to separate sections across all pages.
 * 
 * @props
 * - height: Divider height (default: 1px)
 * - width: Divider width (default: 100%)
 * - opacity: Opacity (default: 0.18)
 * - color: Base color (default: var(--gold-300))
 * - margin: Vertical margin (default: var(--space-4) = 32px)
 * - class: Additional CSS classes
 * 
 * @example
 * x:ui.divider />
 * x:ui.divider height="2px" opacity="0.3" />
 * x:ui.divider width="80%" margin="var(--space-2)" />
 * 
 * @accessibility
 * - Decorative element (role="separator")
 * - Does not interfere with content flow
 * - aria-hidden="true" to hide from screen readers
 */
--}}

@props([
    'height' => '1px',
    'width' => '100%',
    'opacity' => '0.18',
    'color' => 'var(--gold-300)',
    'margin' => 'var(--space-4)',
])

<hr
    {{ $attributes->merge([
        'class' => 'border-0',
        'role' => 'separator',
        'aria-hidden' => 'true'
    ])->merge([
        'style' => "
            height: {$height};
            width: {$width};
            background: {$color};
            opacity: {$opacity};
            margin-top: {$margin};
            margin-bottom: {$margin};
        "
    ]) }}
>
