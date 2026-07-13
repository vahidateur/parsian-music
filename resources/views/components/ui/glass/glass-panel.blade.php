{{--
    Glass Panel Component
    
    Lighter glassmorphism panel for nested content.
    Used in: Dashboard sections, Settings panels, etc.
    
    Props:
    - padding: spacing inside panel
    - class: additional CSS classes
--}}

@props([
    'padding' => '24px',
])

<div {{ $attributes->merge([
    'class' => 'overflow-hidden rounded-2xl border
                bg-white/[0.03] backdrop-blur-lg
                border-white/[0.08]
                shadow-lg'
    ])->merge([
        'style' => "padding: {$padding};"
    ]) }}>
    {{ $slot }}
</div>
