{{--
 Brand English Title Component
 Text: Customizable from login settings (login_academy_name)
 Font: Cinzel 12px/600 uppercase — elegant serif, matches the crest emblem
 Letter-spacing: 2.5px
 Color: --color-accent (semantic token)
 Alignment: center
 lang="en" / dir="ltr"
--}}

@php
    $academyName = $academyName ?? (isset($loginSettings) ? $loginSettings['academy_name'] : null);
@endphp

@if($academyName)
    <p
        {{ $attributes->merge(['class' => 'uppercase text-center w-full'])
                       ->merge(['style' => '
                           font-family: "Cinzel", serif;
                           font-size: 12px;
                           font-weight: 600;
                           letter-spacing: 2.5px;
                           color: var(--color-accent);
                       ']) }}
        lang="en"
        dir="ltr"
    >{{ $academyName }}</p>
@endif
