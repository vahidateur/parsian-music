{{-- Core accessible switch control. Props: name, label, checked, disabled. Phase: 0.5. --}}
@props([
    'name', 'id' => null, 'value' => '1', 'checked' => false,
    'disabled' => false, 'label' => null, 'dir' => 'rtl',
])

@php($id = $id ?? $name)
<label class="ui-switch" dir="{{ $dir }}">
    <input class="ui-switch__control" type="checkbox" name="{{ $name }}" id="{{ $id }}"
        value="{{ $value }}" @checked($checked) @disabled($disabled)>
    <span class="ui-switch__track" aria-hidden="true"><span class="ui-switch__thumb"></span></span>
    @if($label)
        <span class="ui-switch__label{{ $disabled ? ' ui-switch__label--disabled' : '' }}">{{ $label }}</span>
    @endif
</label>