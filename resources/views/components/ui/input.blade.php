{{--
/**
 * Input Component
 * 
 * Generic text input field with glassmorphism styling.
 * Completely generic - NO domain-specific logic.
 * Used in: Login, Register, Forms, Settings, etc.
 * 
 * @props
 * - type: Input type (text, tel, email, password, number, etc.) - default: 'text'
 * - name: Input name attribute (required)
 * - id: Input ID (default: same as name)
 * - value: Input value
 * - placeholder: Placeholder text
 * - required: Boolean, marks field as required
 * - disabled: Boolean, disables input
 * - readonly: Boolean, makes input readonly
 * - autocomplete: Autocomplete attribute
 * - dir: Text direction (default: 'rtl')
 * - hasIcon: Boolean, adds padding for icon (default: false)
 * - hasError: Boolean, shows error state (default: false)
 * - class: Additional CSS classes
 * 
 * @slots
 * None - this is a primitive input element
 * 
 * @example
 * x:ui.input 
 *     name="phone" 
 *     type="tel" 
 *     placeholder="شماره موبایل" 
 *     required 
 * />
 * 
 * x:ui.input 
 *     name="email" 
 *     type="email" 
 *     hasIcon 
 *     hasError 
 * />
 * 
 * @accessibility
 * - Proper input type for keyboard hints
 * - Placeholder provides context
 * - Focus indicator visible (golden ring)
 * - Error state has visual indicator
 * - Works with label (use form-field component for complete setup)
 */
--}}

@props([
    'type' => 'text',
    'name',
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'autocomplete' => null,
    'dir' => 'rtl',
    'hasIcon' => false,
    'hasError' => false,
])

<input 
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $id ?? $name }}"
    value="{{ old($name, $value) }}"
    placeholder="{{ $placeholder }}"
    @if($required) required @endif
    @if($disabled) disabled @endif
    @if($readonly) readonly @endif
    @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
    dir="{{ $dir }}"
    {{ $attributes->merge([
        'class' => 'w-full
                    [height:var(--input-height)]
                    [border-radius:var(--radius-md)]
                    [font-size:var(--text-base)]
                    px-6 ' . ($hasIcon ? 'pr-14' : '') . '
                    bg-white/[0.06]
                    border
                    [border-color:' . ($hasError ? 'var(--error-500)' : 'var(--glass-border-light)') . ']
                    text-[var(--text-primary)]
                    placeholder:text-[var(--text-tertiary)]
                    transition-all
                    [transition-duration:var(--duration-fast)]
                    [transition-timing-function:var(--ease-standard)]
                    focus:outline-none
                    focus:[border-color:var(--gold-300)]
                    focus:[box-shadow:var(--shadow-input-focus)]
                    disabled:opacity-50
                    disabled:cursor-not-allowed
                    readonly:bg-transparent
                    readonly:cursor-default'
    ]) }}
>
