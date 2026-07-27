{{--
    Admin Drawer — owns the mobile navigation overlay.
    Props: items (navigation contracts).
    Phase: 0.5 — Admin Foundation.
    Slots: none.
--}}
@props([
    'items' => [],
])

<div
    class="admin-shell__drawer-backdrop"
    x-cloak
    x-show="mobileOpen && accessibilityReady"
    @click="closeMobile()"
    aria-hidden="true"
></div>

<aside
    id="admin-mobile-drawer"
    class="admin-shell__drawer"
    x-cloak
    x-show="mobileOpen && accessibilityReady"
    x-trap.noscroll="mobileOpen && accessibilityReady"
    @keydown.escape.window="closeMobile()"
    role="dialog"
    aria-modal="true"
    aria-label="منوی پنل مدیریت"
>
    <div class="admin-shell__drawer-header">
        <span class="admin-shell__brand-label">ناوبری مدیریت</span>
        <button type="button" class="admin-shell__icon-button" aria-label="بستن منو" @click="closeMobile()">
            <span aria-hidden="true">×</span>
        </button>
    </div>

    <nav class="admin-shell__drawer-navigation" aria-label="بخش‌های مدیریت">
        <x-admin.navigation :items="$items" />
    </nav>
</aside>
