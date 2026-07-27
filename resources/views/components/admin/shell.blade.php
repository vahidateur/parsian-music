{{--
    Admin Shell — owns the admin page frame and content regions.
    Props: navigation (array), title (string).
    Phase: 0.5 — Admin Foundation.
    Slots: breadcrumb, default content.
--}}
@props([
    'navigation' => [],
    'title' => null,
])

<div
    class="admin-shell"
    x-data="adminShell"
    :class="{ 'admin-shell--collapsed': collapsed }"
>
    <x-admin.sidebar :items="$navigation" />
    <x-admin.drawer :items="$navigation" />

    <div class="admin-shell__main">
        <x-admin.topbar :title="$title" />

        <main id="main-content" class="admin-shell__content" tabindex="-1">
            @if ($breadcrumb->isNotEmpty())
                {{ $breadcrumb }}
            @endif

            <div class="admin-shell__content-inner">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>
