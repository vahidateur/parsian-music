{{--
/**
 * Brand Title Component
 *
 * Displays the academy's primary Persian name.
 * Text: "آموزشگاه موسیقی پارسیان"
 *
 * @props
 * - tag:    HTML element to render (default: 'h1')
 *           Use 'h2' when the page already has an h1 (e.g. dashboard).
 * - class:  Extra CSS classes
 *
 * @example
 * // Default (h1)
 * brand.title
 *
 * // Secondary heading
 * brand.title tag="h2"
 *
 * // With spacing
 * brand.title class="mb-2"
 *
 * @accessibility
 * - Semantic heading — caller chooses level via `tag` prop
 * - dir="rtl" forces correct Persian text direction
 * - lang="fa" signals Persian to assistive technologies
 *
 * @tokens
 * --color-primary  (#D5AF58 in dark theme)
 * --text-2xl       (26px)
 */
--}}

@props([
    'tag'      => 'h1',
    'fontSize' => 'var(--text-2xl)',
])

@php
    $title = settings()->login()['title'] ?? \App\Models\AppSetting::getValue('login', 'login_title', 'آموزشگاه موسیقی پارسیان');
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'class' => 'font-vazirmatn text-center w-full max-w-full',
    ])->merge([
        'style' => "
            font-size: {$fontSize};
            font-weight: 700;
            line-height: 1.4;
            color: var(--color-primary);
        ",
    ]) }}
    dir="rtl"
    lang="fa"
>{{ $title }}</{{ $tag }}>
