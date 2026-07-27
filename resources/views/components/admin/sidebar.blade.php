{{--
    Admin Sidebar — macOS Finder-style navigation.
    Props: items (navigation contracts).
    Phase: 1 — Dashboard Polish.
    Slots: none.
--}}
@props([
    'items' => [],
])

<aside class="admin-shell__sidebar" aria-label="ناوبری پنل مدیریت">

    {{-- Brand header --}}
    <div class="admin-shell__sidebar-header">
        <a class="admin-shell__brand" href="{{ route('admin.dashboard') }}" aria-label="پارسیان موزیک آکادمی">
            <span class="admin-shell__brand-mark" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M10 2a6 6 0 0 0-6 6c0 2.08 1.06 3.9 2.67 5H13.33A7.001 7.001 0 0 0 16 8a6 6 0 0 0-6-6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M7 17h6M8.5 17v1.5a1.5 1.5 0 0 0 3 0V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="admin-shell__brand-label" x-show="!collapsed" x-cloak>پارسیان موزیک</span>
        </a>
        <button
            type="button"
            class="admin-shell__collapse-button"
            aria-controls="admin-shell__sidebar-navigation"
            :aria-expanded="(!collapsed).toString()"
            :aria-label="collapsed ? 'باز کردن نوار کناری' : 'جمع کردن نوار کناری'"
            @click="toggleCollapsed()"
        >
            <span class="admin-shell__collapse-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path d="M10 12L6 8l4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </button>
    </div>

    {{-- Navigation --}}
    <nav id="admin-shell__sidebar-navigation" class="admin-shell__sidebar-navigation" aria-label="بخش‌های مدیریت">
        <x-admin.navigation :items="$items" />
    </nav>

    {{-- Footer --}}
    <div class="admin-shell__sidebar-footer" x-show="!collapsed" x-cloak>
        <div class="admin-shell__brand admin-shell__sidebar-brand-footer">
            <span class="admin-shell__brand-mark" aria-hidden="true">♫</span>
            <span class="admin-shell__brand-label">
                پارسیان موزیک
                <small class="admin-shell__version">نسخه ۱.۰.۰</small>
            </span>
        </div>

        {{-- Account area — visible in the Glass theme only (CSS-controlled). --}}
        <div class="admin-shell__account">
            <a class="admin-shell__account-card" href="{{ route('profile.edit') }}">
                <span class="admin-shell__account-avatar" aria-hidden="true">{{ mb_substr(auth()->user()->full_name ?? 'م', 0, 1) }}</span>
                <span class="admin-shell__account-body">
                    <strong class="admin-shell__account-name">{{ auth()->user()->full_name ?? 'حساب کاربری' }}</strong>
                    <small class="admin-shell__account-role">{{ __('admin.panel_title') }}</small>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="admin-shell__account-logout">
                    <span aria-hidden="true">⎋</span>
                    <span>خروج از سیستم</span>
                </button>
            </form>
        </div>
    </div>

</aside>
