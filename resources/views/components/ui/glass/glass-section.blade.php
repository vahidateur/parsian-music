{{--
    Glass Section Component
    
    Semantic section wrapper with consistent spacing.
    Used in: Layout sections throughout the system.
    
    Props:
    - spacing: spacing after section (default: medium = 32px)
    - class: additional CSS classes
--}}

@props([
    'spacing' => 'medium', // large (40px), medium (32px), small (24px), none
])

@php
    $spacingClass = match($spacing) {
        'large' => 'mb-10',
        'medium' => 'mb-8',
        'small' => 'mb-6',
        'none' => '',
        default => 'mb-8',
    };
@endphp

<section {{ $attributes->merge(['class' => $spacingClass]) }}>
    {{ $slot }}
</section>
