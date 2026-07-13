@extends('layouts.auth')

@section('title', 'ورود به سامانه')

@section('content')
{{--
  Login Page — Mobile-First Responsive
  Breakpoints:
    <430px   : full-width card, 16px margin
    430–768  : centred card, max-w 420px
    768–1024 : centred card, max-w 520px, hero HIDDEN
    1024–1280: hero + card split (60/40 approx), hero SHOWN
    1280–1536: 60/40
    ≥1536    : 68/32
--}}

{{-- Overflow guard (body-level) --}}
@push('styles')
<style>
html, body { overflow-x: hidden; }

/* ── Safe area (iOS) ─────────────────────────────────── */
.login-safe-wrap {
    padding-top:    env(safe-area-inset-top,    0px);
    padding-bottom: env(safe-area-inset-bottom, 0px);
    padding-left:   env(safe-area-inset-left,   0px);
    padding-right:  env(safe-area-inset-right,  0px);
}

/* ── Input placeholder colour ────────────────────────── */
.login-input::placeholder { color: rgba(255,255,255,0.38); }

/* ── Card responsive padding ─────────────────────────── */
@media (max-width: 429px) {
    #login-card {
        padding: 22px 18px !important;
    }
    .login-header-logo { width:48px !important; height:48px !important; }
    .login-title    { font-size:20px !important; }
    .login-input    { height:46px !important; }
    .login-btn      { height:44px !important; }
    .login-spacer-header-form { height:12px !important; }
    .login-spacer-form-btn    { height:12px !important; }
    .login-spacer-btn-social  { height:14px !important; }
    /* Shrink header spacers */
    .login-header > div[style*="height:16px"] { height:10px !important; }
    .login-header > div[style*="height:7px"]  { height:5px !important; }
    .login-header > div[style*="height:9px"]  { height:6px !important; }
    .login-header > div[style*="height:20px"] { height:12px !important; }
    /* form gap */
    #login-form { gap: 10px !important; }
    /* Reduce header→form spacer */
    .login-spacer-header-form[style*="height:22px"] { height:14px !important; }
    /* hide quote on tiny screens to save vertical space */
    .login-footer { display:none !important; }
    /* Reduce button→footer gap */
    #login-card-content > div[style*="height:24px"] { height:0 !important; }
}

/* ── 430px mobile compact ────────────────────────────── */
@media (min-width: 430px) and (max-width: 767px) {
    #login-card { padding: 24px 20px !important; }
    .login-header-logo { width:52px !important; height:52px !important; }
    .login-title    { font-size:22px !important; }
    .login-input    { height:48px !important; }
    .login-btn      { height:46px !important; }
    #login-form { gap: 11px !important; }
    .login-header > div[style*="height:16px"] { height:12px !important; }
    .login-header > div[style*="height:20px"] { height:14px !important; }
    .login-spacer-header-form { height:14px !important; }
}
</style>
@endpush

<div
    class="login-safe-wrap"
    style="
        background: var(--neutral-950);
        min-height: 100dvh;
        width: 100%;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
    "
>

    {{-- ══════════════════════════════════════════════
         HERO — Full-bleed background covering entire safe-wrap
         (including bottom-bar area)
    ══════════════════════════════════════════════ --}}
    <section
        id="hero"
        aria-hidden="true"
        style="
            position: absolute;
            inset: 0;
            background:
                url('{{ asset('images/hero-orchestra.jpg') }}')
                center 35% / cover no-repeat,
                var(--neutral-900);
            z-index: 0;
        "
    >
        {{-- Right-side darkening so glass card is readable --}}
        <div style="position:absolute;inset:0;
            background:
                linear-gradient(
                    to right,
                    rgba(5,6,10,0.05) 0%,
                    rgba(5,6,10,0.08) 55%,
                    rgba(5,6,10,0.72) 78%,
                    rgba(5,6,10,0.88) 100%
                );
        " aria-hidden="true"></div>

        {{-- Bottom vignette feeding into the copyright bar --}}
        <div style="position:absolute;inset:0;
            background:
                linear-gradient(
                    to top,
                    rgba(5,6,10,0.85) 0%,
                    rgba(5,6,10,0.10) 18%,
                    transparent 40%
                );
        " aria-hidden="true"></div>

        {{-- Subtle golden glow — stained glass light source --}}
        <div style="position:absolute;inset:0;
            background: radial-gradient(ellipse 55% 50% at 42% 32%,
                rgba(213,175,88,0.08) 0%,
                transparent 65%
            );
            mix-blend-mode: screen;
        " aria-hidden="true"></div>
    </section>

<main
    id="login-page"
    style="
        position: relative;
        flex: 1 1 auto;
        width: 100%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
    "
    dir="ltr"
>

    {{-- ══════════════════════════════════════════════
         LOGIN COLUMN — overlaid on right side of hero
    ══════════════════════════════════════════════ --}}
    <section
        id="login"
        dir="rtl"
        aria-label="ورود به سامانه"
        style="
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 32px 16px;
            box-sizing: border-box;
        "
    >
        {{-- ── Glass Card ──────────────────────────────── --}}
        <div
            id="login-card"
            class="overflow-hidden border relative"
            style="
                width: 100%;
                max-width: 304px;
                border-radius: var(--radius-lg);
                padding: 36px 32px;
                background:
                    linear-gradient(
                        160deg,
                        rgba(255,255,255,0.055) 0%,
                        rgba(255,255,255,0.00) 45%
                    ),
                    var(--glass-bg);
                backdrop-filter: blur(var(--glass-blur));
                -webkit-backdrop-filter: blur(var(--glass-blur));
                border-color: var(--glass-border);
                box-shadow:
                    0 40px 120px rgba(0,0,0,0.50),
                    inset 0 1.5px 0 rgba(255,255,255,0.13),
                    inset 0 0 0 1px rgba(213,175,88,0.06);
            "
        >
            {{-- Radial glow — top-right corner --}}
            <div style="
                position:absolute; top:-20px; right:-20px;
                width:260px; height:240px;
                background: radial-gradient(ellipse at top right,
                    rgba(213,175,88,0.10) 0%,
                    transparent 65%
                );
                pointer-events:none;
            " aria-hidden="true"></div>

            <div id="login-card-content" style="position:relative; display:flex; flex-direction:column;">

                {{-- ── HEADER ──────────────────────── --}}
                <header class="login-header" style="display:flex;flex-direction:column;align-items:center;text-align:center;">

                    <x-ui.brand.logo custom-size="70px" class="login-header-logo" />

                    <div class="login-spacer-header-form" style="height:16px;flex-shrink:0;" aria-hidden="true"></div>
                    <x-ui.brand.title tag="h1" font-size="30px" class="login-title" />

                    <div style="height:7px;flex-shrink:0;" aria-hidden="true"></div>
                    <x-ui.brand.subtitle />

                    <div style="height:9px;flex-shrink:0;" aria-hidden="true"></div>
                    <x-ui.brand.english-title />

                    <div style="height:20px;flex-shrink:0;" aria-hidden="true"></div>
                    <x-ui.brand.divider width="160px" opacity="0.35" />

                </header>

                {{-- 22px gap header → form --}}
                <div class="login-spacer-header-form" style="height:22px;flex-shrink:0;" aria-hidden="true"></div>

                {{-- ── FORM ────────────────────────── --}}
                <section class="login-form" aria-label="فرم ورود">
                    <form
                        id="login-form"
                        method="POST"
                        action="{{ route('login') }}"
                        novalidate
                        style="display:flex;flex-direction:column;gap:14px;"
                    >
                        @csrf

                        {{-- Phone --}}
                        <div class="login-field" style="position:relative;" dir="rtl">
                            <input
                                id="phone"
                                type="tel"
                                name="phone"
                                dir="rtl"
                                value="{{ old('phone') }}"
                                placeholder="شماره موبایل"
                                autocomplete="tel"
                                required
                                class="login-input"
                                style="
                                    display:block;
                                    width:100%;
                                    box-sizing:border-box;
                                    height:52px;
                                    min-height:44px;
                                    border-radius:12px;
                                    padding:0 42px 0 16px;
                                    font-size:14px;
                                    font-family:inherit;
                                    background:rgba(255,255,255,0.07);
                                    border:1px solid rgba(213,175,88,0.22);
                                    color:var(--text-primary);
                                    outline:none;
                                    transition:border-color 200ms ease,box-shadow 200ms ease,background 200ms ease;
                                    direction:rtl;
                                    backdrop-filter:blur(16px);
                                    -webkit-backdrop-filter:blur(16px);
                                "
                                onfocus="this.style.borderColor='var(--gold-300)';this.style.background='rgba(255,255,255,0.10)';this.style.boxShadow='var(--shadow-input-focus)'"
                                onblur="this.style.borderColor='rgba(213,175,88,0.22)';this.style.background='rgba(255,255,255,0.07)';this.style.boxShadow='none'"
                            >
                            <span style="position:absolute;top:50%;right:14px;transform:translateY(-50%);color:rgba(213,175,88,0.65);pointer-events:none;display:flex;align-items:center;" aria-hidden="true">
                                <i data-lucide="phone" style="width:15px;height:15px;"></i>
                            </span>
                            @error('phone')
                                <p role="alert" style="margin-top:5px;font-size:var(--text-sm);color:var(--error-500);">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="login-field" style="position:relative;" dir="rtl" x-data="{ show: false }">
                            <input
                                id="password"
                                :type="show ? 'text' : 'password'"
                                name="password"
                                placeholder="رمز عبور"
                                autocomplete="current-password"
                                required
                                class="login-input"
                                style="
                                    display:block;
                                    width:100%;
                                    box-sizing:border-box;
                                    height:52px;
                                    min-height:44px;
                                    border-radius:12px;
                                    padding:0 42px 0 42px;
                                    font-size:14px;
                                    font-family:inherit;
                                    background:rgba(255,255,255,0.07);
                                    border:1px solid rgba(213,175,88,0.22);
                                    color:var(--text-primary);
                                    outline:none;
                                    transition:border-color 200ms ease,box-shadow 200ms ease,background 200ms ease;
                                    direction:rtl;
                                    backdrop-filter:blur(16px);
                                    -webkit-backdrop-filter:blur(16px);
                                "
                                onfocus="this.style.borderColor='var(--gold-300)';this.style.background='rgba(255,255,255,0.10)';this.style.boxShadow='var(--shadow-input-focus)'"
                                onblur="this.style.borderColor='rgba(213,175,88,0.22)';this.style.background='rgba(255,255,255,0.07)';this.style.boxShadow='none'"
                            >
                            <span style="position:absolute;top:50%;right:14px;transform:translateY(-50%);color:rgba(213,175,88,0.65);pointer-events:none;display:flex;align-items:center;" aria-hidden="true">
                                <i data-lucide="lock" style="width:15px;height:15px;"></i>
                            </span>
                            <button
                                type="button"
                                @click="show=!show"
                                :aria-label="show?'مخفی کردن رمز عبور':'نمایش رمز عبور'"
                                style="position:absolute;top:50%;left:12px;transform:translateY(-50%);background:none;border:none;padding:4px;cursor:pointer;color:rgba(213,175,88,0.50);display:flex;align-items:center;transition:color 200ms;min-width:36px;min-height:36px;justify-content:center;"
                                onmouseover="this.style.color='var(--gold-300)'"
                                onmouseout="this.style.color='rgba(213,175,88,0.50)'"
                            >
                                <i :data-lucide="show?'eye-off':'eye'" style="width:15px;height:15px;"></i>
                            </button>
                            @error('password')
                                <p role="alert" style="margin-top:5px;font-size:var(--text-sm);color:var(--error-500);">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Remember + Forgot --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;direction:rtl;padding:6px 2px 0 2px;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:var(--text-sm);color:var(--text-secondary);min-height:44px;">
                                <input
                                    type="checkbox" name="remember" id="remember"
                                    {{ old('remember') ? 'checked' : '' }}
                                    style="width:17px;height:17px;border-radius:var(--radius-xs);border:1px solid rgba(213,175,88,0.25);background:rgba(255,255,255,0.06);cursor:pointer;accent-color:var(--gold-300);flex-shrink:0;"
                                >
                                <span>مرا به خاطر بسپار</span>
                            </label>
                            <a href="{{ route('password.phone.request') }}"
                               style="font-size:var(--text-sm);color:var(--gold-300);text-decoration:none;opacity:0.85;white-space:nowrap;padding:10px 0;transition:color 200ms,opacity 200ms;"
                               onmouseover="this.style.color='var(--gold-200)';this.style.opacity='1'"
                               onmouseout="this.style.color='var(--gold-300)';this.style.opacity='0.85'"
                            >فراموشی رمز عبور؟</a>
                        </div>

                    </form>
                </section>

                {{-- 18px gap form → button --}}
                <div class="login-spacer-form-btn" style="height:18px;flex-shrink:0;" aria-hidden="true"></div>

                {{-- ── BUTTON ──────────────────────── --}}
                <section class="login-actions">
                    <button
                        type="submit"
                        form="login-form"
                        class="login-btn"
                        style="
                            display:flex;align-items:center;justify-content:center;gap:8px;
                            width:100%;height:50px;min-height:44px;
                            border-radius:12px;border:1px solid rgba(248,231,181,0.35);
                            background:linear-gradient(180deg,#F4D28B 0%,#D5AF58 100%);
                            color:#14100a;font-size:14px;font-weight:600;
                            font-family:inherit;cursor:pointer;letter-spacing:0.2px;
                            box-shadow:0 6px 18px rgba(213,175,88,0.28),0 1px 4px rgba(213,175,88,0.12);
                            transition:transform 200ms ease,box-shadow 200ms ease;
                            direction:rtl;
                            touch-action:manipulation;
                        "
                        onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 9px 24px rgba(213,175,88,0.40),0 2px 6px rgba(213,175,88,0.18)'"
                        onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 6px 18px rgba(213,175,88,0.28),0 1px 4px rgba(213,175,88,0.12)'"
                        onmousedown="this.style.transform='translateY(1px)'"
                        onmouseup="this.style.transform='translateY(-1px)'"
                    >
                        <i data-lucide="feather" aria-hidden="true" style="width:14px;height:14px;opacity:0.70;flex-shrink:0;"></i>
                        <span>ورود به تالار</span>
                    </button>
                </section>

                {{-- 24px gap button → quote --}}
                <div style="height:24px;flex-shrink:0;" aria-hidden="true"></div>

                {{-- ── QUOTE ────────────────────────── --}}
                <footer
                    class="login-footer"
                    style="text-align:center;direction:rtl;"
                >
                    <p style="
                        font-size:var(--text-sm);
                        font-style:italic;
                        color:rgba(213,175,88,0.55);
                        line-height:1.7;
                        letter-spacing:0.3px;
                    ">«موسیقی جادوی بی‌کلام است»</p>
                    <p style="
                        font-size:10px;
                        font-family:'Cinzel',serif;
                        color:rgba(213,175,88,0.35);
                        margin-top:4px;
                        letter-spacing:1.5px;
                        direction:ltr;
                    ">PARSIAN MUSIC</p>
                </footer>

            </div>{{-- /login-card-content --}}
        </div>{{-- /login-card --}}
    </section>

</main>

{{-- ══════════════════════════════════════════════════════
     BOTTOM BAR — simple copyright line
══════════════════════════════════════════════════════ --}}
<div
    id="bottom-bar"
    style="
        width: 100%;
        padding: 12px 24px;
        box-sizing: border-box;
        position: relative;
        z-index: 1;
        text-align: center;
    "
>
    <span style="font-size:12px;color:rgba(207,199,178,0.40);letter-spacing:0.3px;font-family:inherit;">
        &copy; {{ date('Y') }} Parsian Music Academy. All rights reserved.
    </span>
</div>{{-- /bottom-bar --}}
</div>{{-- /login-safe-wrap --}}

{{-- Responsive breakpoints via inline <style> --}}
@push('styles')
<style>
/* ── Mobile: card centred ────────────────────────────── */
@media (max-width: 1023px) {
    #login { justify-content: center !important; }
}

/* ── 430px+: card centred, max 304px ─────────────────── */
@media (min-width: 430px) {
    #login {
        align-items: center;
        padding-top: 24px;
        padding-bottom: 24px;
    }
    #login-card { max-width: 304px; }
}

/* ── 768px+: card slightly wider on tablet ───────────── */
@media (min-width: 768px) {
    #login-card {
        max-width: 340px;
        padding: 32px 28px;
    }
}

/* ── 1024px+: card on right side with margin from wall */
@media (min-width: 1024px) {
    #login {
        justify-content: flex-start !important;
        padding: 32px 40px 32px 0 !important;
        align-items: center;
    }
    #login-card {
        max-width: 360px !important;
        margin-left: 0;
        margin-right: 0;
    }
    #bottom-bar { margin-top: 0; }
}

/* ── 1280px+ ─────────────────────────────────────────── */
@media (min-width: 1280px) {
    #login { padding: 32px 56px 32px 0 !important; }
    #login-card { max-width: 370px !important; }
}

/* ── 1440px+ ─────────────────────────────────────────── */
@media (min-width: 1440px) {
    #login { padding: 32px 72px 32px 0 !important; }
    #login-card { max-width: 376px !important; }
}

/* ── 1600px+ ─────────────────────────────────────────── */
@media (min-width: 1600px) {
    #login { padding: 32px 88px 32px 0 !important; }
    #login-card { max-width: 376px !important; }
}

/* ── 1920px+ ─────────────────────────────────────────── */
@media (min-width: 1920px) {
    #login { padding: 32px 120px 32px 0 !important; }
    #login-card { max-width: 380px !important; }
}

/* ── Compact: short viewports ────────────────────────── */
@media (min-width: 1024px) and (max-height: 800px) {
    #login-card { padding: 22px 26px !important; }
    .login-spacer-header-form { height: 10px !important; }
    .login-spacer-form-btn    { height: 10px !important; }
    .login-header-logo { width: 50px !important; height: 50px !important; }
    .login-title  { font-size: 20px !important; }
    .login-input  { height: 48px !important; }
    .login-btn    { height: 44px !important; }
}

/* ── Bottom bar ───────────────────────────────────────── */
@media (max-width: 767px) {
    .bottom-bar-logo { display: none; }
    #bottom-bar > div:first-child { gap: 18px; }
    #bottom-bar span { font-size: 11px !important; }
}
@media (max-width: 399px) {
    #bottom-bar span:not(.copyright-text) { display: none; }
}
</style>
@endpush

@endsection
