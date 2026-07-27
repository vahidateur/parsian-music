{{--
    Reusable page header.
    Props: title, subtitle, badge; slots: actions, default.
    Phase: 0.5 — Admin Foundation.
--}}
@props([
    'title' => '',
    'subtitle' => null,
    'badge' => null,
])

<div {{ $attributes->merge(['class' => 'ui-page-header']) }}>
    <div class="ui-page-header__main">
        <div class="ui-page-header__title-row">
            @if ($title)
                <h2 class="ui-page-header__title">{{ $title }}</h2>
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