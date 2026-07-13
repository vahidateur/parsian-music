{{--
 Brand Subtitle Component
 Text: Loaded from database (login settings)
 Font: Vazirmatn 15px/400
 Color: --color-text-muted (semantic token)
 Alignment: center
 dir="rtl" / lang="fa"
--}}

@php
    $subtitle = settings()->login()['subtitle'] ?? \App\Models\AppSetting::getValue('login', 'login_subtitle', 'تالار هنر، جادو و موسیقی');
@endphp

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
>{{ $subtitle }}</p>
