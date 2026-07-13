{{--
    Glassmorphism login card.
    Fixed size per design spec: 470x760, radius 28px, padding 48px.
    Responsive: tablet 420px width, mobile 95% (max 420px), auto height.
--}}
<div
    x-data="{ showPw: false }"
    class="relative z-10 w-[min(95vw,420px)] md:w-[420px] lg:w-[470px]
           h-[760px] max-h-[92vh] md:h-auto md:max-h-[90vh]
           rounded-[28px] p-12
           bg-[rgba(10,12,18,.42)] backdrop-blur-3xl
           border border-[rgba(255,208,120,.18)]
           [box-shadow:0_25px_80px_rgba(0,0,0,.45),inset_0_0_1px_rgba(255,255,255,.12)]
           flex flex-col overflow-y-auto
           animate-card-in"
>
    {{-- ===== TOP: LOGO / TITLE / SUBTITLE ===== --}}
    <div class="text-center flex-shrink-0">
        <div class="mx-auto w-12 h-12 rounded-full border border-p-gold/50 bg-p-gold/5
                    flex items-center justify-center shadow-[0_0_24px_rgba(213,175,88,.18)]">
            {{-- star icon --}}
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="text-p-gold">
                <path d="M12 2l2.6 6.6L21 11l-6.4 2.4L12 20l-2.6-6.6L3 11l6.4-2.4L12 2z"
                      fill="currentColor" opacity=".85"/>
            </svg>
        </div>

        <h1 class="mt-6 text-[26px] font-bold text-p-gold-light leading-snug">
            آموزشگاه موسیقی پارسیان
        </h1>

        <p class="mt-2 text-[15px] font-normal text-white/60">
            تالار هنر، جادو و موسیقی
        </p>

        <p class="mt-4 font-serif_en text-[13px] tracking-[3px] text-p-gold uppercase">
            Parsian Music Academy
        </p>
    </div>

    {{-- divider --}}
    <div class="mt-10 h-px w-full bg-gradient-to-r from-transparent via-p-gold/40 to-transparent flex-shrink-0"></div>

    {{-- ===== ERRORS ===== --}}
    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-red-400/25 bg-red-400/10 px-4 py-2.5">
            @foreach ($errors->all() as $error)
                <p class="text-[13px] text-red-400">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ===== FORM ===== --}}
    <form method="POST" action="{{ route('login') }}" class="mt-8 flex flex-col flex-1">
        @csrf

        <div class="space-y-6">
            @include('auth.partials.login-input', [
                'id' => 'phone',
                'type' => 'tel',
                'name' => 'phone',
                'label' => 'شماره موبایل',
                'placeholder' => '09123456789',
                'autocomplete' => 'tel',
                'autofocus' => true,
                'value' => old('phone'),
            ])

            @include('auth.partials.login-input', [
                'id' => 'password',
                'type' => 'password',
                'name' => 'password',
                'label' => 'رمز عبور',
                'placeholder' => '••••••••',
                'autocomplete' => 'current-password',
                'toggle' => true,
            ])
        </div>

        {{-- remember + forgot --}}
        <div class="mt-6 flex items-center justify-between">
            <label class="flex items-center gap-2 text-[14px] text-white/70 cursor-pointer select-none">
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}
                       class="w-4 h-4 rounded border border-p-gold/50 bg-transparent text-p-gold
                              accent-p-gold cursor-pointer focus:ring-2 focus:ring-p-gold/40 focus:ring-offset-0">
                مرا به خاطر بسپار
            </label>

            <a href="{{ route('password.phone.request') }}"
               class="text-[14px] text-p-gold-light/80 hover:text-p-gold-light transition-colors duration-200
                      focus:outline-none focus-visible:ring-2 focus-visible:ring-p-gold/50 rounded">
                رمز عبور را فراموش کرده‌اید؟
            </a>
        </div>

        {{-- submit button --}}
        <button
            type="submit"
            class="mt-8 w-full h-[68px] rounded-[18px] text-[18px] font-bold text-[#14100a]
                   bg-gradient-to-b from-p-gold to-p-gold-light
                   shadow-[0_10px_30px_rgba(213,175,88,.35)]
                   transition-all duration-300 ease-out
                   hover:-translate-y-0.5 hover:shadow-[0_14px_40px_rgba(213,175,88,.5)]
                   active:translate-y-0
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-p-gold-light focus-visible:ring-offset-2 focus-visible:ring-offset-[#0E1018]"
        >
            ورود
        </button>
    </form>

    {{-- ===== SOCIAL DIVIDER ===== --}}
    <div class="mt-8 flex items-center gap-3 text-[13px] text-p-gold/50 flex-shrink-0">
        <span class="flex-1 h-px bg-p-gold/25"></span>
        <span>یا ورود با</span>
        <span class="flex-1 h-px bg-p-gold/25"></span>
    </div>

    {{-- ===== SOCIAL BUTTONS ===== --}}
    <div class="mt-6 flex items-center justify-center gap-4 flex-shrink-0">
        @foreach ([
            ['label' => 'Google',   'path' => 'M21.35 11.1H12v2.9h5.35c-.5 2.55-2.7 4.4-5.35 4.4-3.24 0-5.85-2.6-5.85-5.9s2.6-5.9 5.85-5.9c1.5 0 2.85.55 3.9 1.5l2.1-2.1C16.5 4.35 14.4 3.5 12 3.5 6.9 3.5 2.75 7.65 2.75 12.5S6.9 21.5 12 21.5c6 0 9.6-4.2 9.6-9.9 0-.5-.05-.9-.25-1.5z'],
            ['label' => 'Telegram', 'path' => 'M21 4L2.5 11.2c-.9.35-.9 1.6 0 1.95l4.4 1.6 1.7 5.4c.25.8 1.3 1 1.9.35l2.6-2.7 4.4 3.25c.75.55 1.85.15 2-.75l3-16c.15-.85-.7-1.55-1.5-1.25z'],
            ['label' => 'Apple',    'path' => 'M16.3 4c.15 1.1-.3 2.15-1 2.9-.75.8-1.9 1.35-2.9 1.25-.15-1.1.3-2.2 1-2.95.75-.8 2-1.35 2.9-1.2zM19.9 17.1c-.55 1.2-.8 1.75-1.5 2.8-1 1.45-2.4 3.25-4.15 3.25-1.55 0-1.95-1-4.05-1s-2.55.95-4.1 1c-1.75.05-3.05-1.6-4.05-3.05C0 16.9-.5 13 1.15 10.55c.9-1.35 2.5-2.2 4-2.2 1.6 0 2.6 1 3.95 1s2.15-1 4.05-1c1.35 0 2.8.7 3.85 1.9-3.4 1.85-2.85 6.65 1.9 6.85z'],
        ] as $social)
            <button type="button" title="{{ $social['label'] }}"
                    class="w-14 h-14 rounded-full border border-p-gold/40 bg-transparent
                           flex items-center justify-center text-p-gold-light
                           transition-all duration-300
                           hover:border-p-gold hover:shadow-[0_0_20px_rgba(213,175,88,.4)] hover:-translate-y-0.5
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-p-gold/50">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="{{ $social['path'] }}"/>
                </svg>
                <span class="sr-only">ورود با {{ $social['label'] }}</span>
            </button>
        @endforeach
    </div>

    {{-- ===== QUOTE ===== --}}
    <p class="mt-8 text-center text-[13px] italic text-p-gold/70 flex-shrink-0">
        «موسیقی جادوی بی‌کلام است»
    </p>
</div>
