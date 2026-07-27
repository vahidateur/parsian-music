{{-- Core textarea control. Props: name, value, state. Phase: 0.5. --}}
@props([
    'name', 'value' => '', 'placeholder' => '', 'required' => false,
    'disabled' => false, 'readonly' => false, 'hasError' => false, 'dir' => 'rtl',
])

<textarea name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" dir="{{ $dir }}"
    placeholder="{{ $placeholder }}" @required($required) @disabled($disabled) @readonly($readonly)
    {{ $attributes->merge(['class' => 'ui-textarea' . ($hasError ? ' ui-textarea--error' : '')]) }}>{{ old($name, $value) }}{{ $slot }}</textarea>