{{--
    Global Navbar — Parsian Music Academy
    Reusable across all public pages (Home, Teachers, Courses, Blog, Contact, About, Instruments).
    Premium luxury + cinematic. Glass surface. Gold accents.
    All styling via design tokens in teacher-theme.css (→ site-theme.css later).

    Props:
    - active: current page key for aria-current + active underline
              (home | about | teachers | courses | blog | contact)
--}}
@props([
    'active' => null,
])

@php
    $items = [
        ['key' => 'home',     'label' => 'خانه',        'href' => '/'],
        ['key' => 'about',    'label' => 'درباره ما',   'href' => '/about'],
        ['key' => 'teachers', 'label' => 'اساتید',      'href' => '/teachers'],
        ['key' => 'courses',  'label' => 'دوره‌ها',      'href' => '/courses'],
        ['key' => 'blog',     'label' => 'وبلاگ',        'href' => '/blog'],
        ['key' => 'contact',  'label' => 'تماس با ما',   'href' => '/contact'],
    ];
@endphp

<div x-data="{ drawer: false }" class="site-navbar-root">

    {{-- ══ NAVBAR ══ --}}
    <nav class="site-navbar" aria-label="ناوبری اصلی">
        <div class="site-navbar__inner">

            {{-- Logo (right side in RTL) --}}
            <a href="/" class="site-navbar__logo" aria-label="پارسیان موزیک آکادمی">
                <span class="site-navbar__logo-mark" aria-hidden="true">
                    <svg viewBox="0 0 48 48" width="48" height="48" fill="none">
                        <circle cx="24" cy="24" r="22" stroke="var(--gold-300)" stroke-width="1.5" opacity="0.6"/>
                        <path d="M24 12v16.5a4 4 0 1 1-2-3.46V16l8-2v10.5a4 4 0 1 1-2-3.46V12l-4 1z" fill="var(--gold-300)"/>
                    </svg>
                </span>
                <span class="site-navbar__logo-text">
                    <span class="site-navbar__logo-title">PARSIAN</span>
                    <span class="site-navbar__logo-sub">MUSIC ACADEMY</span>
                </span>
            </a>

            {{-- Menu (center) --}}
            <ul class="site-navbar__menu">
                @foreach($items as $item)
                    <li>
                        <a
                            href="{{ $item['href'] }}"
                            class="site-navbar__link {{ $active === $item['key'] ? 'is-active' : '' }}"
                            @if($active === $item['key']) aria-current="page" @endif
                        >
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- CTA (left side in RTL) --}}
            <div class="site-navbar__actions">
                <a href="/contact" class="site-navbar__cta">درخواست مشاوره</a>

                {{-- Hamburger (mobile only) --}}
                <button
                    type="button"
                    class="site-navbar__hamburger"
                    aria-label="باز کردن منو"
                    :aria-expanded="drawer.toString()"
                    @click="drawer = true"
                >
                    <span class="site-navbar__hamburger-lines" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
            </div>

        </div>
    </nav>

    {{-- ══ MOBILE DRAWER ══ --}}
    <div
        class="site-drawer-overlay"
        x-cloak
        x-show="drawer"
        x-transition.opacity
        @click="drawer = false"
        aria-hidden="true"
    ></div>

    <aside
        class="site-drawer"
        x-cloak
        x-show="drawer"
        x-trap.noscroll="drawer"
        x-transition:enter-start="site-drawer--closed"
        x-transition:leave-end="site-drawer--closed"
        @keydown.escape.window="drawer = false"
        aria-label="منوی موبایل"
        aria-modal="true"
        role="dialog"
    >
        <div class="site-drawer__head">
            {{-- Close button first in DOM → receives initial focus via x-trap --}}
            <button
                type="button"
                class="site-drawer__close"
                aria-label="بستن منو"
                @click="drawer = false"
            >
                <svg viewBox="0 0 22 22" width="22" height="22" fill="none" aria-hidden="true">
                    <path d="M4 4l14 14M18 4L4 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>
            <a href="/" class="site-navbar__logo">
                <span class="site-navbar__logo-mark" aria-hidden="true">
                    <svg viewBox="0 0 48 48" width="40" height="40" fill="none">
                        <circle cx="24" cy="24" r="22" stroke="var(--gold-300)" stroke-width="1.5" opacity="0.6"/>
                        <path d="M24 12v16.5a4 4 0 1 1-2-3.46V16l8-2v10.5a4 4 0 1 1-2-3.46V12l-4 1z" fill="var(--gold-300)"/>
                    </svg>
                </span>
                <span class="site-navbar__logo-text">
                    <span class="site-navbar__logo-title">PARSIAN</span>
                    <span class="site-navbar__logo-sub">MUSIC ACADEMY</span>
                </span>
            </a>
        </div>

        <ul class="site-drawer__menu">
            @foreach($items as $item)
                <li>
                    <a
                        href="{{ $item['href'] }}"
                        class="site-drawer__link {{ $active === $item['key'] ? 'is-active' : '' }}"
                        @if($active === $item['key']) aria-current="page" @endif
                    >
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        <a href="/contact" class="site-navbar__cta site-drawer__cta">درخواست مشاوره</a>
    </aside>

</div>
