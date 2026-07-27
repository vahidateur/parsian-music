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

<div {{ $attributes->merge(['class' => 'ui-checkbox-field']) }} dir="{{ $dir }}">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        value="{{ $value }}"
        @if($checked) checked @endif
        @if($disabled) disabled @endif
        class="ui-checkbox"
    >

    <label
        for="{{ $id ?? $name }}"
        class="ui-checkbox-field__label{{ $disabled ? ' ui-checkbox-field__label--disabled' : '' }}"
    >
        {{ $label }}
    </label>
</div>
