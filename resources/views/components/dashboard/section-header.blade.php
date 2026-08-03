{{--
    Reusable page header.
    Props: title, subtitle, badge; slots: actions, default.
    Phase: 0.5 — Admin Foundation.
--}}
@props([
    'title' => '',
    'subtitle' => null,
    'badge' => null,
    'headingLevel' => 'h2',
])

@php
    $headingLevel = in_array($headingLevel, ['h1', 'h2', 'h3'], true) ? $headingLevel : 'h2';
@endphp

<div {{ $attributes->merge(['class' => 'ui-page-header']) }}>
    <div class="ui-page-header__main">
        <div class="ui-page-header__title-row">
            @if ($title)
                <{{ $headingLevel }} class="ui-page-header__title">{{ $title }}</{{ $headingLevel }}>
            @endif
            @if ($badge)
                <x-ui.badge variant="accent">{{ $badge }}</x-ui.badge>
            @endif
        </div>
        @if ($subtitle)
            <p class="ui-page-header__subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="ui-page-header__actions">
            {{ $actions }}
        </div>
    @endisset

    {{ $slot }}
</div>