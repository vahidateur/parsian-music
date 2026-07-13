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
 * {{-- Primary button --}}
 * x:ui.button variant="primary" type="submit">
 *     ورود
 * </x-ui.button>
 * 
 * {{-- Button with icon --}}
 * x:ui.button variant="secondary" size="sm">
 *     <i data-lucide="settings" class="w-4 h-4"></i>
 *     <span>تنظیمات</span>
 * </x-ui.button>
 * 
 * {{-- Loading state --}}
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
    $baseClasses = 'inline-flex items-center justify-center gap-2
                    [border-radius:var(--radius-md)]
                    font-bold
                    transition-all
                    [transition-duration:var(--duration-fast)]
                    [transition-timing-function:var(--ease-standard)]
                    focus:outline-none
                    focus-visible:ring-2
                    focus-visible:ring-offset-2
                    focus-visible:ring-offset-[var(--neutral-950)]
                    disabled:opacity-50
                    disabled:cursor-not-allowed
                    disabled:transform-none';
    
    $sizeClasses = [
        'sm' => '[height:var(--space-6)] px-4 [font-size:var(--text-sm)]',
        'md' => '[height:var(--button-height)] px-6 [font-size:var(--text-md)]',
        'lg' => '[height:var(--space-10)] px-8 [font-size:var(--text-lg)]',
    ][$size];
    
    $variantClasses = [
        'primary' => 'bg-gradient-to-b from-[var(--gold-200)] to-[var(--gold-300)]
                      text-[#14100a]
                      [box-shadow:var(--shadow-button)]
                      hover:-translate-y-0.5
                      hover:[box-shadow:var(--shadow-button-hover)]
                      active:translate-y-px
                      focus-visible:ring-[var(--gold-300)]',
        
        'secondary' => 'bg-white/[0.06]
                        border
                        [border-color:var(--glass-border)]
                        text-[var(--text-primary)]
                        hover:bg-white/[0.10]
                        hover:[border-color:var(--gold-300)]
                        active:bg-white/[0.04]
                        focus-visible:ring-[var(--gold-300)]',
        
        'ghost' => 'bg-transparent
                    text-[var(--gold-300)]
                    hover:bg-white/[0.06]
                    active:bg-white/[0.02]
                    focus-visible:ring-[var(--gold-300)]',
        
        'danger' => 'bg-[var(--error-500)]
                     text-white
                     [box-shadow:0_10px_30px_rgba(239,68,68,0.35)]
                     hover:-translate-y-0.5
                     hover:[box-shadow:0_14px_40px_rgba(239,68,68,0.5)]
                     active:translate-y-px
                     focus-visible:ring-[var(--error-500)]',
        
        'success' => 'bg-[var(--success-500)]
                      text-white
                      [box-shadow:0_10px_30px_rgba(16,185,129,0.35)]
                      hover:-translate-y-0.5
                      hover:[box-shadow:0_14px_40px_rgba(16,185,129,0.5)]
                      active:translate-y-px
                      focus-visible:ring-[var(--success-500)]',
    ][$variant];
    
    $widthClass = $fullWidth ? 'w-full' : '';
@endphp

<button 
    type="{{ $type }}"
    @if($disabled || $loading) disabled @endif
    @if($loading) aria-busy="true" @endif
    {{ $attributes->merge([
        'class' => "{$baseClasses} {$sizeClasses} {$variantClasses} {$widthClass}"
    ]) }}
>
    @if($loading)
        <i data-lucide="loader-2" class="[width:var(--icon-sm)] [height:var(--icon-sm)] animate-spin"></i>
    @endif
    
    {{ $slot }}
</button>
