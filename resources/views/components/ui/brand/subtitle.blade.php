{{--
 Brand Subtitle Component
 Text: "تالار هنر، جادو و موسیقی"
 Font: Vazirmatn 15px/400
 Color: --color-text-muted (semantic token)
 Alignment: center
 dir="rtl" / lang="fa"
--}}

<p
    {{ $attributes->merge(['class' => 'font-vazirmatn text-center w-full'])
                   ->merge(['style' => '
                       font-size: var(--text-base);
                       font-weight: 400;
                       line-height: 1.6;
                       color: var(--color-text-muted);
                   ']) }}
    dir="rtl"
    lang="fa"
>تالار هنر، جادو و موسیقی</p>
