{{--
    Admin Breadcrumb Area — provides the shell breadcrumb landmark.
    Props: none.
    Phase: 0.5 — Admin Foundation.
    Slots: breadcrumb content.
--}}
<nav class="admin-shell__breadcrumb" aria-label="مسیر صفحه">
    <a href="{{ route('admin.dashboard') }}" class="admin-shell__breadcrumb-link">{{ __('admin.dashboard') }}</a>
    <span class="admin-shell__breadcrumb-separator" aria-hidden="true">/</span>
    <span class="admin-shell__breadcrumb-current" aria-current="page">{{ $slot }}</span>
</nav>
