{{--
/**
 * Checkbox Component
 * 
 * Generic checkbox input with label.
 * Completely generic - NO domain-specific logic.
 * Used in: Forms, Settings, Filters, etc.
 * 
 * @props
 * - name: Checkbox name attribute (required)
 * - id: Checkbox ID (default: same as name)
 * - value: Checkbox value (default: '1')
 * - checked: Boolean, checkbox checked state (default: false)
 * - disabled: Boolean, disables checkbox (default: false)
 * - label: Label text (required)
 * - dir: Text direction (default: 'rtl')
 * - class: Additional CSS classes
 * 
 * @example
 * x:ui.checkbox 
 *     name="remember" 
 *     label="مرا به خاطر بسپار" 
 *     :checked="old('remember')" 
 * />
 * 
 * x:ui.checkbox 
 *     name="terms" 
 *     label="شرایط و قوانین را می‌پذیرم" 
 *     required 
 * />
 * 
 * @accessibility
 * - Native checkbox input for proper semantics
 * - Label associated with checkbox via for/id
 * - Focus indicator visible (golden ring)
 * - Keyboard accessible (Space to toggle)
 * - Disabled state properly indicated
 */
--}}

@props([
    'name',
    'id' => null,
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'label',
    'dir' => 'rtl',
])

<div {{ $attributes->merge(['class' => 'flex items-center [gap:var(--space-2)]']) }} dir="{{ $dir }}">
    
    <input 
        type="checkbox"
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        value="{{ $value }}"
        @if($checked) checked @endif
        @if($disabled) disabled @endif
        class="w-5 h-5
               [border-radius:var(--radius-xs)]
               border
               [border-color:var(--glass-border)]
               bg-white/[0.06]
               text-[var(--gold-300)]
               transition-all
               [transition-duration:var(--duration-fast)]
               focus:outline-none
               focus:ring-2
               focus:[ring-color:var(--shadow-input-focus)]
               focus:ring-offset-0
               checked:bg-gradient-to-b
               checked:from-[var(--gold-200)]
               checked:to-[var(--gold-300)]
               checked:border-[var(--gold-300)]
               disabled:opacity-50
               disabled:cursor-not-allowed
               cursor-pointer"
    >
    
    <label 
        for="{{ $id ?? $name }}"
        class="[font-size:var(--text-base)]
               text-[var(--text-primary)]
               cursor-pointer
               select-none
               {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
    >
        {{ $label }}
    </label>
    
</div>
