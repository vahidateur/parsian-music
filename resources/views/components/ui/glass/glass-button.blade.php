{{--
    Glass Button Component
    
    Primary button with gradient and glow effect.
    Used in: Forms, Actions, CTAs throughout the system.
    
    Props:
    - type: button type (submit, button)
    - variant: primary (default), secondary, ghost
    - class: additional CSS classes
--}}

@props([
    'type' => 'submit',
    'variant' => 'primary',
])

<button 
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => 'w-full
                    [height:var(--button-height)]
                    [border-radius:var(--radius-button)]
                    [font-size:var(--font-button-size)]
                    font-bold
                    transition-all duration-300
                    focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-gold)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--color-bg)]
                    ' . ($variant === 'primary' 
                        ? 'bg-gradient-to-b from-[var(--color-gold-light)] to-[var(--color-gold)]
                           text-[#14100a]
                           [box-shadow:var(--shadow-button)]
                           hover:-translate-y-0.5 hover:[box-shadow:var(--shadow-button-hover)]
                           active:translate-y-px'
                        : '')
    ]) }}>
    {{ $slot }}
</button>
