{{--
    Global Breadcrumb — Parsian Music Academy
    Reusable across all pages (Teacher, Courses, Instruments, Blog, Gallery, Events, ...).
    Fully generic — no hardcoded labels. All content via $items prop.
    Premium, transparent, calm. RTL. Styling via teacher-theme.css.

    Props:
    - items: array of ['label' => string, 'url' => string|null]
             Last item (url = null) = current page.

    Example:
    <x-ui.navigation.breadcrumb :items="[
        ['label' => 'خانه', 'url' => route('home')],
        ['label' => 'اساتید', 'url' => route('teachers.index')],
        ['label' => 'نازنین حسینی', 'url' => null],
    ]" />
--}}
@props([
    'items' => [],
])

@php
    $lastIndex = count($items) - 1;
@endphp

<nav class="site-breadcrumb" aria-label="Breadcrumb">
    <ol class="site-breadcrumb__list">
        @foreach($items as $i => $item)
            @php $isCurrent = ($i === $lastIndex) || empty($item['url']); @endphp

            <li class="site-breadcrumb__item">
                @if($isCurrent)
                    <span class="site-breadcrumb__current" aria-current="page">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] }}" class="site-breadcrumb__link">{{ $item['label'] }}</a>
                @endif

                @unless($isCurrent)
                    <span class="site-breadcrumb__sep" aria-hidden="true">
                        <svg viewBox="0 0 12 12" width="12" height="12" fill="none">
                            <path d="M7.5 2.5 4 6l3.5 3.5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
