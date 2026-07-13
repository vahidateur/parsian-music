{{--
 Brand Logo Component
 Rendering priority: src → variant → default (treble-clef)
 Sizes: sm=40px  md=64px  lg=96px
--}}

@props([
    'size'       => 'md',
    'src'        => null,
    'variant'    => 'treble-clef',
    'customSize' => null,
])

@php
    $sizes = ['sm' => '40px', 'md' => '64px', 'lg' => '96px'];
    $dim   = $customSize ?? ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($src)
    <img
        src="{{ $src }}"
        alt="لوگوی آموزشگاه موسیقی پارسیان"
        {{ $attributes->merge(['class' => 'inline-block object-contain'])
                       ->merge(['style' => "width:{$dim};height:{$dim};"]) }}
    >
@else
    {{--
        Crest logo: crown + lyre (harp) + wings + olive branches.
        Matches the reference emblem used across header and bottom bar.
    --}}
    <svg
        {{ $attributes->merge([
            'class'      => 'inline-block',
            'role'       => 'img',
            'aria-label' => 'لوگوی آموزشگاه موسیقی پارسیان',
        ])->merge(['style' => "width:{$dim};height:{$dim};"]) }}
        viewBox="0 0 100 100"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
    >
        <g stroke="var(--gold-300)" stroke-linecap="round" stroke-linejoin="round" fill="none">

            {{-- Crown, top centre --}}
            <path
                d="M42 20 L45 26 L50 17 L55 26 L58 20 L56 28 L44 28 Z"
                stroke-width="1.3"
                fill="var(--gold-300)"
                fill-opacity="0.12"
            />
            <circle cx="50" cy="16" r="1.6" fill="var(--gold-300)" stroke-width="0.8" />
            <circle cx="42" cy="19" r="1.1" fill="var(--gold-300)" stroke-width="0.8" />
            <circle cx="58" cy="19" r="1.1" fill="var(--gold-300)" stroke-width="0.8" />
            <line x1="44" y1="28" x2="56" y2="28" stroke-width="1.2" />

            {{-- Lyre body (central harp shape) --}}
            <path
                d="M50 30 C50 30 41 32 41 42 C41 50 46 55 50 58"
                stroke-width="1.4"
            />
            <path
                d="M50 30 C50 30 59 32 59 42 C59 50 54 55 50 58"
                stroke-width="1.4"
            />
            {{-- Lyre strings --}}
            <line x1="45" y1="34" x2="45" y2="52" stroke-width="0.7" opacity="0.85" />
            <line x1="47.5" y1="32.5" x2="47.5" y2="55" stroke-width="0.7" opacity="0.85" />
            <line x1="50" y1="32" x2="50" y2="58" stroke-width="0.7" opacity="0.85" />
            <line x1="52.5" y1="32.5" x2="52.5" y2="55" stroke-width="0.7" opacity="0.85" />
            <line x1="55" y1="34" x2="55" y2="52" stroke-width="0.7" opacity="0.85" />
            {{-- Base --}}
            <ellipse cx="50" cy="59" rx="4.5" ry="2" stroke-width="1.1" />

            {{-- Wings, left and right of lyre --}}
            <path
                d="M41 40 C34 39 28 41 24 45 C29 44 34 45 38 47 C33 47 28 49 25 53 C31 51 36 51 40 52"
                stroke-width="1"
            />
            <path
                d="M59 40 C66 39 72 41 76 45 C71 44 66 45 62 47 C67 47 72 49 75 53 C69 51 64 51 60 52"
                stroke-width="1"
            />

            {{-- Olive branches, lower left and right --}}
            <path d="M38 62 C34 65 31 69 30 74" stroke-width="1" />
            <ellipse cx="35.5" cy="64" rx="1.6" ry="0.9" fill="var(--gold-300)" stroke-width="0.5" transform="rotate(-30 35.5 64)" />
            <ellipse cx="33" cy="68" rx="1.6" ry="0.9" fill="var(--gold-300)" stroke-width="0.5" transform="rotate(-35 33 68)" />
            <ellipse cx="31" cy="72" rx="1.6" ry="0.9" fill="var(--gold-300)" stroke-width="0.5" transform="rotate(-40 31 72)" />

            <path d="M62 62 C66 65 69 69 70 74" stroke-width="1" />
            <ellipse cx="64.5" cy="64" rx="1.6" ry="0.9" fill="var(--gold-300)" stroke-width="0.5" transform="rotate(30 64.5 64)" />
            <ellipse cx="67" cy="68" rx="1.6" ry="0.9" fill="var(--gold-300)" stroke-width="0.5" transform="rotate(35 67 68)" />
            <ellipse cx="69" cy="72" rx="1.6" ry="0.9" fill="var(--gold-300)" stroke-width="0.5" transform="rotate(40 69 72)" />

        </g>
    </svg>
@endif
