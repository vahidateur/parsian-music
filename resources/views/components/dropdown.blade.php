{{--
    Core dropdown shell.
    Props: align, width, contentClasses; slots: trigger, content.
    Phase: 0.5 — Admin Foundation.
--}}
@props([
    'align' => 'right',
    'width' => '48',
    'contentClasses' => '',
])

@php
    $align = in_array($align, ['left', 'right', 'top'], true) ? $align : 'right';
    $widthClass = $width === '48' ? 'ui-dropdown__panel--w-48' : '';
@endphp

<div
    class="ui-dropdown"
    x-data="{ open: false }"
    x-on:click.outside="open = false"
    x-on:close.stop="open = false"
    x-on:keydown.escape.window="open = false"
>
    <div class="ui-dropdown__trigger" x-on:click="open = ! open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition
        x-on:click="open = false"
        class="ui-dropdown__panel ui-dropdown__panel--{{ $align }} {{ $widthClass }}"
        role="menu"
    >
        <div class="ui-dropdown__content {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>