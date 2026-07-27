{{--
    Admin Topbar — macOS-style top bar.
    Props: title (string).
    Phase: 1 — Dashboard Polish.
    Slots: none.
    State: uses parent adminShell scope (notifOpen, userMenuOpen, closeAllPanels).
--}}
@props([
    'title' => null,
])

<header class="admin-shell__topbar">

    {{-- Mobile hamburger --}}
    <button
        type="button"
        class="admin-shell__mobile-toggle"
        aria-controls="admin-mobile-drawer"
        :aria-expanded="mobileOpen.toString()"
        :disabled="!accessibilityReady"
        aria-label="باز کردن منوی مدیریت"
        @click="openMobile()"
    >
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <rect x="2" y="4"  width="14" height="1.5" rx=".75" fill="currentColor"/>
            <rect x="2" y="8.25" width="14" height="1.5" rx=".75" fill="currentColor"/>
            <rect x="2" y="12.5" width="14" height="1.5" rx=".75" fill="currentColor"/>
        </svg>
    </button>

    {{-- Search --}}
    <div class="admin-shell__search" role="search">
        <svg class="admin-shell__search-icon" width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <circle cx="6.5" cy="6.5" r="5" stroke="currentColor" stroke-width="1.5"/>
            <path d="M10.5 10.5L14 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <input
            type="search"
            class="admin-shell__search-input"
            placeholder="جستجو… (⌘K)"
            aria-label="جستجو در پنل"
        >
    </div>

    {{-- Brand center (mobile only) --}}
    <span class="admin-shell__brand-mobile" aria-hidden="true">پارسیان موزیک</span>

    {{-- Actions --}}
    <div class="admin-shell__topbar-actions">

        {{-- Day/Night theme toggle: sun → Glass theme, moon → Dark theme --}}
        <button
            type="button"
            class="admin-shell__icon-button admin-shell__theme-toggle"
            aria-label="تغییر پوسته پنل"
            :aria-label="theme === 'glass' ? 'تغییر به پوسته شب' : 'تغییر به پوسته روز (شیشه‌ای)'"
            x-on:click="toggleTheme()"
        >
            <span class="admin-shell__theme-icon admin-shell__theme-icon--sun" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <circle cx="8" cy="8" r="3.25" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M8 1v1.75M8 13.25V15M1 8h1.75M13.25 8H15M3.05 3.05l1.24 1.24M11.71 11.71l1.24 1.24M3.05 12.95l1.24-1.24M11.71 4.29l1.24-1.24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="admin-shell__theme-icon admin-shell__theme-icon--moon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M13.5 9.8A5.6 5.6 0 0 1 6.2 2.5a5.75 5.75 0 1 0 7.3 7.3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                </svg>
            </span>
        </button>

        {{-- Notification bell + panel --}}
        <div class="admin-shell__notif-wrapper" x-ref="notifWrapper">
            <button
                type="button"
                class="admin-shell__icon-button"
                aria-label="اعلان‌ها"
                aria-haspopup="dialog"
                aria-controls="admin-notif-panel"
                :aria-expanded="notifOpen.toString()"
                x-on:click="toggleNotif()"
                x-ref="notifTrigger"
            >
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path d="M8 1.5A4.5 4.5 0 0 0 3.5 6v3L2 11h12l-1.5-2V6A4.5 4.5 0 0 0 8 1.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                    <path d="M6.5 13a1.5 1.5 0 0 0 3 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <span class="admin-shell__notif-badge" aria-label="۳ اعلان جدید">۳</span>
            </button>

            <div
                id="admin-notif-panel"
                class="admin-shell__notif-panel"
                x-show="notifOpen"
                x-cloak
                x-transition:enter="admin-shell__panel-enter"
                x-transition:enter-start="admin-shell__panel-enter-start"
                x-transition:enter-end="admin-shell__panel-enter-end"
                x-transition:leave="admin-shell__panel-leave"
                x-transition:leave-start="admin-shell__panel-leave-start"
                x-transition:leave-end="admin-shell__panel-leave-end"
                @click.outside="closeNotif()"
                @keydown.escape.window="closeNotif()"
                role="dialog"
                aria-label="مرکز اعلان‌ها"
                aria-modal="false"
            >
                <div class="admin-shell__notif-panel-header">
                    <span class="admin-shell__notif-panel-title">اعلان‌ها</span>
                    <button type="button" class="admin-shell__notif-panel-clear" aria-label="پاک کردن همه">پاک کردن</button>
                </div>
                <div class="admin-shell__notif-panel-list" role="list">
                    <div class="admin-shell__notif-item admin-shell__notif-item--unread" role="listitem">
                        <span class="admin-shell__notif-item-dot" aria-hidden="true"></span>
                        <div>
                            <p class="admin-shell__notif-item-title">هنرجوی جدید ثبت‌نام کرد</p>
                            <p class="admin-shell__notif-item-meta">مریم رضایی · پیانو · ۵ دقیقه پیش</p>
                        </div>
                    </div>
                    <div class="admin-shell__notif-item admin-shell__notif-item--unread" role="listitem">
                        <span class="admin-shell__notif-item-dot" aria-hidden="true"></span>
                        <div>
                            <p class="admin-shell__notif-item-title">پرداخت جدید دریافت شد</p>
                            <p class="admin-shell__notif-item-meta">علی محمدی · ۱۲٬۴۵۰٬۰۰۰ ریال · ۲۰ دقیقه پیش</p>
                        </div>
                    </div>
                    <div class="admin-shell__notif-item admin-shell__notif-item--unread" role="listitem">
                        <span class="admin-shell__notif-item-dot" aria-hidden="true"></span>
                        <div>
                            <p class="admin-shell__notif-item-title">جلسه لغو شد</p>
                            <p class="admin-shell__notif-item-meta">سارا احمدی · گیتار سطح ۲ · ۱ ساعت پیش</p>
                        </div>
                    </div>
                    <div class="admin-shell__notif-item" role="listitem">
                        <span class="admin-shell__notif-item-dot admin-shell__notif-item-dot--read" aria-hidden="true"></span>
                        <div>
                            <p class="admin-shell__notif-item-title">گزارش هفتگی آماده شد</p>
                            <p class="admin-shell__notif-item-meta">سیستم · دیروز</p>
                        </div>
                    </div>
                </div>
                <div class="admin-shell__notif-panel-footer">
                    <a href="#" class="admin-shell__notif-panel-link">مشاهده همه اعلان‌ها</a>
                </div>
            </div>
        </div>

        {{-- User menu --}}
        <div class="admin-shell__user-menu" x-ref="userMenuWrapper">
            <button
                type="button"
                class="admin-shell__user-placeholder"
                :aria-expanded="userMenuOpen.toString()"
                aria-haspopup="menu"
                aria-controls="admin-user-panel"
                aria-label="منوی حساب کاربری"
                x-on:click="toggleUserMenu()"
                x-ref="userMenuTrigger"
            >
                <span class="admin-shell__user-avatar" aria-hidden="true">م</span>
                <span class="admin-shell__user-label">حساب کاربری</span>
                <svg class="admin-shell__user-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" :class="{ 'admin-shell__user-chevron--open': userMenuOpen }">
                    <path d="M2.5 4.5L6 8l3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div
                id="admin-user-panel"
                class="admin-shell__user-panel"
                x-show="userMenuOpen"
                x-cloak
                x-transition:enter="admin-shell__panel-enter"
                x-transition:enter-start="admin-shell__panel-enter-start"
                x-transition:enter-end="admin-shell__panel-enter-end"
                x-transition:leave="admin-shell__panel-leave"
                x-transition:leave-start="admin-shell__panel-leave-start"
                x-transition:leave-end="admin-shell__panel-leave-end"
                @click.outside="closeUserMenu()"
                @keydown.escape.window="closeUserMenu()"
                role="menu"
                aria-label="گزینه‌های حساب کاربری"
            >
                <div class="admin-shell__user-panel-header">
                    <span class="admin-shell__user-panel-avatar" aria-hidden="true">م</span>
                    <div>
                        <p class="admin-shell__user-panel-name">مدیر سیستم</p>
                        <p class="admin-shell__user-panel-role">مدیر ارشد</p>
                    </div>
                </div>
                <div class="admin-shell__user-panel-divider" role="separator"></div>
                <a href="{{ route('profile.edit') }}" class="admin-shell__user-panel-item" role="menuitem">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><circle cx="7" cy="5" r="3" stroke="currentColor" stroke-width="1.4"/><path d="M1.5 13c0-2.76 2.46-5 5.5-5s5.5 2.24 5.5 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                    <span>پروفایل</span>
                </a>
                <a href="{{ route('admin.settings.index') }}" class="admin-shell__user-panel-item" role="menuitem">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><circle cx="7" cy="7" r="2" stroke="currentColor" stroke-width="1.4"/><path d="M7 1v1.5M7 11.5V13M1 7h1.5M11.5 7H13M2.93 2.93l1.06 1.06M10.01 10.01l1.06 1.06M2.93 11.07l1.06-1.06M10.01 3.99l1.06-1.06" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                    <span>تنظیمات</span>
                </a>
                <div class="admin-shell__user-panel-divider" role="separator"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="admin-shell__user-panel-item admin-shell__user-panel-item--danger" role="menuitem">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M9 1H3a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M11 5l2 2-2 2M6 7h7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>خروج از سیستم</span>
                    </button>
                </form>
            </div>
        </div>

    </div>
</header>
