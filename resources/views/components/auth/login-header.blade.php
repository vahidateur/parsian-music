{{--
/**
 * Login Header Component (Auth Domain)
 * 
 * Composes generic brand components for login page header.
 * This component ONLY arranges UI primitives - NO custom styling.
 * 
 * @props
 * - class: Additional CSS classes for wrapper
 * 
 * @composition
 * Uses these generic UI components:
 * - x:ui.brand.logo />
 * - x:ui.brand.title />
 * - x:ui.brand.subtitle />
 * - x:ui.brand.english-title />
 * - x:ui.brand.divider />
 * 
 * @example
 * x:auth.login-header />
 * 
 * @accessibility
 * - Semantic header landmark
 * - Proper heading hierarchy (h1 for title)
 * - Logical reading order
 */
--}}

<header {{ $attributes->merge(['class' => 'flex flex-col items-center text-center']) }}>
    
    {{-- Logo --}}
    <x-ui.brand.logo size="md" class="mb-4" />
    
    {{-- Persian Title --}}
    <x-ui.brand.title tag="h1" class="mb-2" />
    
    {{-- Persian Subtitle --}}
    <x-ui.brand.subtitle class="mb-3" />
    
    {{-- English Brand Name --}}
    <x-ui.brand.english-title class="mb-4" />
    
    {{-- Decorative Divider --}}
    <x-ui.brand.divider />
    
</header>
