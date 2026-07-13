{{--
    Glass Input Component
    
    Styled input field with glassmorphism effect.
    Used in: Forms throughout the academy system.
    
    Props:
    - type: input type (text, tel, email, password, etc.)
    - name: input name attribute
    - placeholder: placeholder text
    - icon: optional icon component slot
    - class: additional CSS classes
--}}

@props([
    'type' => 'text',
    'name',
    'placeholder' => '',
])

<div class="relative flex items-center">
    <input 
        type="{{ $type }}"
        name="{{ $name }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'w-full
                        [height:var(--input-height)]
                        [border-radius:var(--radius-input)]
                        [font-size:var(--font-input-size)]
                        px-6 pr-14
                        bg-white/[0.06]
                        border border-[var(--glass-border)]
                        text-white placeholder:text-white/55
                        transition-all duration-250
                        focus:border-[var(--color-gold)]
                        focus:[box-shadow:var(--shadow-input-focus)]
                        focus:outline-none'
        ]) }}
        dir="rtl"
    >
    
    @isset($icon)
        <span class="absolute right-5 text-[var(--color-gold)] pointer-events-none">
            {{ $icon }}
        </span>
    @endisset
</div>
