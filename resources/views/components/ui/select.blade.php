{{-- Core select control. Props: name, options, selected, placeholder, state. Phase: 0.5. --}}
@props([
    'name', 'options' => [], 'selected' => '', 'placeholder' => null,
    'required' => false, 'disabled' => false, 'hasError' => false, 'dir' => 'rtl',
])

<select name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" dir="{{ $dir }}"
    @required($required) @disabled($disabled)
    {{ $attributes->merge(['class' => 'ui-select' . ($hasError ? ' ui-select--error' : '')]) }}>
    @if($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach($options as $value => $label)
        <option value="{{ $value }}" @selected((string) old($name, $selected) === (string) $value)>{{ $label }}</option>
    @endforeach
    {{ $slot }}
</select>