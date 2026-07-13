{{--
 Brand Divider Component
 Width: 140px fixed  Height: 1px  Opacity: 0.20
 Color: --gold-300  Centered via margin auto
 aria-hidden decorative
--}}

@props([
    'width'   => '140px',
    'height'  => '1px',
    'opacity' => '0.20',
])

<hr
    {{ $attributes->merge([
        'class'       => 'border-0 mx-auto',
        'role'        => 'separator',
        'aria-hidden' => 'true',
    ])->merge([
        'style' => "
            width: {$width};
            height: {$height};
            background: var(--gold-300);
            opacity: {$opacity};
        ",
    ]) }}
>
