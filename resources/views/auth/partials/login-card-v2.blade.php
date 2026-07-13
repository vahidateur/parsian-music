{{--
    Glassmorphism Login Card - Pixel Perfect Implementation
    Card: 470×760px, radius 28px, padding 48px
--}}
@php
    $loginSettings = \App\Models\AppSetting::getGroup('login');
@endphp

<div x-data="{ showPassword: false }"
     class="relative w-[min(95vw,420px)] lg:w-[470px] 
            h-auto max-h-[92vh] overflow-y-auto
            rounded-[28px] p-12
            bg-[rgba(10,12,18,0.42)] backdrop-blur-3xl
            border border-[rgba(255,208,120,0.18)]
            shadow-[0_25px_80px_rgba(0,0,0,0.45),inset_0_0_1px_rgba(255,255,255,0.15)]
            animate-card-fade-up">

    {{-- HEADER --}}
    <div class="flex flex-col items-center text-center">
        
        {{-- Logo Circle: 64px diameter --}}
        <div class="w-16 h-16 rounded-full 
                    border border-p-gold/40 
                    bg-p-gold/5
                    flex items-center justify-center
                    shadow-[0_0_24px_rgba(213,175,88,0.15)]">
            @if($loginSettings->get('login_logo'))
                <img 
                    src="{{ Storage::url($loginSettings->get('login_logo')) }}" 
                    alt="Logo"
                    class="w-14 h-14 object-contain"
                >
            @else
                {{-- Star Icon --}}
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" class="text-p-gold">
                    <path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4L12 2z" 
                          fill="currentColor" opacity="0.85"/>
                </svg>
            @endif
        </div>

        {{-- Title (24px spacing from logo) --}}
        <h1 class="mt-6 text-[26px] font-bold text-p-gold-light leading-tight">
            {{ $loginSettings->get('login_title', 'آموزشگاه موسیقی پارسیان') }}
        </h1>

        {{-- Subtitle (8px spacing from title) --}}
        <p class="mt-2 text-[15px] font-normal text-p-text-muted">
            {{ $loginSettings->get('login_subtitle', 'تالار هنر، جادو و موسیقی') }}
        </p>

        {{-- English Subtitle (10px spacing from Persian subtitle) --}}
        <p class="mt-2.5 font-playfair text-[13px] font-semibold tracking-[3px] uppercase text-p-gold">
            Parsian Music Academy
        </p>

        {{-- Divider (140px width, 20% opacity) --}}
        <div class="mt-8 w-[140px] h-px bg-p-gold/20"></div>
    </div>

    {{-- ERRORS --}}
    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-red-400/25 bg-red-400/10 p-3">
            @foreach ($errors->all() as $error)
                <p class="text-[13px] text-red-400 leading-relaxed">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- FORM --}}
    <form method="POST" action="{{ route('login') }}" class="mt-10">
        @csrf

        {{-- Phone Input --}}
        <div class="mb-6">
            <div class="relative flex items-center">
                <input 
                    id="phone"
                    type="tel" 
                    name="phone" 
                    value="{{ old('phone') }}"
                    required 
                    autofocus
                    autocomplete="tel"
                    dir="rtl"
                    placeholder="شماره موبایل"
                    class="w-full h-[70px] rounded-[18px] px-6 pr-14
                           bg-white/[0.06] 
                           border border-[rgba(213,175,88,0.35)]
                           text-white text-[15px] placeholder:text-white/55
                           transition-all duration-250
                           focus:border-p-gold focus:shadow-[0_0_0_4px_rgba(213,175,88,0.12)]
                           focus:outline-none">
                {{-- Phone Icon (Right side, RTL) --}}
                <span class="absolute right-5 text-p-gold pointer-events-none">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <rect x="6" y="2" width="12" height="20" rx="2.5"/>
                        <line x1="10" y1="18.5" x2="14" y2="18.5"/>
                    </svg>
                </span>
            </div>
            @error('phone')
                <p class="mt-1.5 text-[13px] text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password Input (24px spacing from phone) --}}
        <div class="mb-6">
            <div class="relative flex items-center">
                <input 
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password" 
                    required
                    autocomplete="current-password"
                    dir="rtl"
                    placeholder="رمز عبور"
                    class="w-full h-[70px] rounded-[18px] px-6 pr-14 pl-14
                           bg-white/[0.06] 
                           border border-[rgba(213,175,88,0.35)]
                           text-white text-[15px] placeholder:text-white/55
                           transition-all duration-250
                           focus:border-p-gold focus:shadow-[0_0_0_4px_rgba(213,175,88,0.12)]
                           focus:outline-none">
                {{-- Lock Icon (Right side, RTL) --}}
                <span class="absolute right-5 text-p-gold pointer-events-none">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <rect x="5" y="10" width="14" height="10" rx="2"/>
                        <path d="M8 10V7a4 4 0 018 0v3"/>
                    </svg>
                </span>
                {{-- Password Toggle (Left side) --}}
                <button 
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute left-5 text-p-gold/60 hover:text-p-gold transition-colors"
                    :aria-label="showPassword ? 'پنهان کردن رمز' : 'نمایش رمز'">
                    <svg x-show="!showPassword" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg x-show="showPassword" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M17.94 17.94A10.94 10.94 0 0112 20c-7 0-11-7-11-7a19.7 19.7 0 014.22-5.94M9.9 4.24A10.4 10.4 0 0112 4c7 0 11 7 11 7a19.7 19.7 0 01-2.16 3.19M14.12 14.12a3 3 0 11-4.24-4.24"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-[13px] text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember Me + Forgot Password --}}
        <div class="flex items-center justify-between mb-8">
            <label class="flex items-center gap-2 text-[14px] text-white/70 cursor-pointer select-none">
                <input 
                    type="checkbox" 
                    name="remember" 
                    value="1"
                    {{ old('remember') ? 'checked' : '' }}
                    class="w-4 h-4"
                    id="remember-checkbox"
                >
                <span>مرا به خاطر بسپار</span>
            </label>

            <a href="{{ route('password.phone.request') }}" 
               class="text-[14px] text-p-gold-light/80 hover:text-p-gold-light 
                      transition-colors duration-200">
                رمز عبور را فراموش کرده‌اید؟
            </a>
        </div>

        {{-- Login Button: 68px height, gradient, golden glow --}}
        <button 
            type="submit"
            class="w-full h-[68px] rounded-[18px] 
                   bg-gradient-to-b from-p-gold-light to-p-gold
                   text-[16px] font-bold text-[#14100a]
                   shadow-[0_10px_30px_rgba(213,175,88,0.35)]
                   transition-all duration-300
                   hover:-translate-y-0.5 hover:shadow-[0_14px_40px_rgba(213,175,88,0.5)]
                   active:translate-y-px
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-p-gold-light focus-visible:ring-offset-2 focus-visible:ring-offset-p-bg">
            ورود به سامانه
        </button>
    </form>

    {{-- Social Divider --}}
    <div class="flex items-center gap-3 my-8 text-[13px] text-p-gold/60">
        <span class="flex-1 h-px bg-p-gold/20"></span>
        <span>یا ورود با</span>
        <span class="flex-1 h-px bg-p-gold/20"></span>
    </div>

    {{-- Social Login Buttons: 3 circles, 56px --}}
    <div class="flex items-center justify-center gap-5">
        @foreach([
            ['name' => 'Google', 'path' => 'M21.35 11.1H12v2.9h5.35c-.5 2.55-2.7 4.4-5.35 4.4-3.24 0-5.85-2.6-5.85-5.9s2.6-5.9 5.85-5.9c1.5 0 2.85.55 3.9 1.5l2.1-2.1C16.5 4.35 14.4 3.5 12 3.5 6.9 3.5 2.75 7.65 2.75 12.5S6.9 21.5 12 21.5c6 0 9.6-4.2 9.6-9.9 0-.5-.05-.9-.25-1.5z'],
            ['name' => 'Telegram', 'path' => 'M21 4L2.5 11.2c-.9.35-.9 1.6 0 1.95l4.4 1.6 1.7 5.4c.25.8 1.3 1 1.9.35l2.6-2.7 4.4 3.25c.75.55 1.85.15 2-.75l3-16c.15-.85-.7-1.55-1.5-1.25z'],
            ['name' => 'Apple', 'path' => 'M16.3 4c.15 1.1-.3 2.15-1 2.9-.75.8-1.9 1.35-2.9 1.25-.15-1.1.3-2.2 1-2.95.75-.8 2-1.35 2.9-1.2zM19.9 17.1c-.55 1.2-.8 1.75-1.5 2.8-1 1.45-2.4 3.25-4.15 3.25-1.55 0-1.95-1-4.05-1s-2.55.95-4.1 1c-1.75.05-3.05-1.6-4.05-3.05C0 16.9-.5 13 1.15 10.55c.9-1.35 2.5-2.2 4-2.2 1.6 0 2.6 1 3.95 1s2.15-1 4.05-1c1.35 0 2.8.7 3.85 1.9-3.4 1.85-2.85 6.65 1.9 6.85z'],
        ] as $social)
            <button 
                type="button"
                title="ورود با {{ $social['name'] }}"
                class="w-14 h-14 rounded-full 
                       border border-p-gold/40 bg-transparent
                       flex items-center justify-center
                       text-p-gold-light
                       transition-all duration-300
                       hover:bg-[rgba(213,175,88,0.08)] hover:scale-105 hover:shadow-[0_0_20px_rgba(213,175,88,0.3)]
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-p-gold/50">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="{{ $social['path'] }}"/>
                </svg>
                <span class="sr-only">ورود با {{ $social['name'] }}</span>
            </button>
        @endforeach
    </div>

    {{-- Quote --}}
    <p class="mt-10 text-center text-[14px] italic text-p-gold/80">
        «موسیقی جادوی بی‌کلام است»
    </p>

</div>

@push('scripts')
<script>
    // Ensure checkbox works
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('remember-checkbox');
        if (checkbox) {
            // Add click listener for debugging
            checkbox.addEventListener('click', function(e) {
                console.log('Checkbox clicked!', e.target.checked);
            });
            
            // Ensure it's clickable
            checkbox.style.cursor = 'pointer';
            checkbox.style.appearance = 'auto';
        }
    });
</script>
@endpush
