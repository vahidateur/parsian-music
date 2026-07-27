<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'fa';
    /* Admin theme marker. Read from the raw cookie jar (written client-side by
       the topbar toggle) so the correct theme paints on the first frame. */
    $adminTheme = (($_COOKIE['pm_admin_theme'] ?? null) === 'glass') ? 'glass' : 'dark';
    $pageTitle = $__env->yieldContent('title') ?: __('admin.panel_title');
    $navigation = [
        ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'label' => __('admin.dashboard'), 'icon' => '⌂'],
        ['route' => 'admin.students.index', 'active' => 'admin.students.*', 'label' => __('admin.students'), 'icon' => '♙', 'children' => [
            ['route' => 'admin.students.index', 'active' => 'admin.students.index', 'label' => 'لیست هنرجویان', 'icon' => '◇'],
            ['route' => 'admin.leads.index', 'active' => 'admin.leads.*', 'label' => __('admin.leads'), 'icon' => '☷'],
        ]],
        ['route' => 'admin.teachers.index', 'active' => 'admin.teachers.*', 'label' => __('admin.teachers'), 'icon' => '♬'],
        ['route' => 'admin.sessions.index', 'active' => 'admin.sessions.*', 'label' => __('admin.sessions'), 'icon' => '◫', 'children' => [
            ['route' => 'admin.sessions.index', 'active' => 'admin.sessions.index', 'label' => 'لیست جلسات', 'icon' => '▫'],
            ['route' => 'admin.calendar.index', 'active' => 'admin.calendar.*', 'label' => __('admin.calendar'), 'icon' => '◷'],
        ]],
        ['route' => 'admin.reports.attendance', 'active' => 'admin.reports.*', 'label' => __('admin.reports'), 'icon' => '▤'],
        ['route' => 'admin.instruments.index', 'active' => 'admin.instruments.*', 'label' => __('admin.instruments'), 'icon' => '◉'],
        ['route' => 'admin.rooms.index', 'active' => 'admin.rooms.*', 'label' => 'اتاق‌ها', 'icon' => '▣'],
        ['route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'label' => 'تنظیمات', 'icon' => '⚙', 'children' => [
            ['route' => 'admin.settings.index', 'active' => 'admin.settings.index', 'label' => 'تنظیمات عمومی', 'icon' => '◎'],
            ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'label' => 'مدیریت کاربران', 'icon' => '♟'],
        ]],
    ];
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" data-admin-theme="{{ $adminTheme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-page">
    <x-admin.shell :navigation="$navigation" :title="$pageTitle">
        <x-slot:breadcrumb>
            @hasSection('breadcrumb')
                <x-admin.breadcrumb-area>@yield('breadcrumb')</x-admin.breadcrumb-area>
            @endif
        </x-slot:breadcrumb>

        @yield('content')
    </x-admin.shell>
</body>
</html>
