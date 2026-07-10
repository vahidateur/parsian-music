<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $isRtl  = $locale === 'fa';
    $user   = auth()->user();
    $teacher = $user?->teacher;

    $navActive   = 'flex items-center gap-3 rounded-lg bg-blue-500/10 px-3 py-2.5 text-sm font-medium text-blue-300 ring-1 ring-blue-500/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50';
    $navInactive = 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-300 transition duration-150 hover:bg-gray-800/50 hover:text-blue-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/40';
    $iconActive  = 'h-5 w-5 text-blue-400';
    $iconInactive= 'h-5 w-5 text-blue-400/70';

    $isDash      = request()->routeIs('teacher.dashboard');
    $isSchedule  = request()->routeIs('teacher.schedule');
    $isStudents  = request()->routeIs('teacher.students*');
    $isAttendance= request()->routeIs('teacher.attendance*');
    $isCalendar  = request()->routeIs('teacher.calendar');
    $isNotifs    = request()->routeIs('teacher.notifications');
    $isProfile   = request()->routeIs('profile.*');
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($title ?? __('admin.panel_title')) . ' — پورتال استاد' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative min-h-screen overflow-x-hidden bg-gray-950 text-gray-100 antialiased">

    {{-- Background --}}
    <div class="pointer-events-none fixed inset-0 z-0 bg-gradient-to-br from-gray-950 via-gray-900 to-gray-950"></div>
    <div class="pointer-events-none fixed inset-0 z-0 opacity-[0.025]" style="background-image: radial-gradient(circle at 1px 1px, #ffffff 1px, transparent 0); background-size: 32px 32px;"></div>
    <div class="pointer-events-none fixed top-1/4 {{ $isRtl ? 'right-1/4' : 'left-1/4' }} z-0 h-80 w-80 rounded-full bg-blue-500/[0.04] blur-[120px]"></div>

    <div class="relative z-10 flex min-h-screen"
         x-data="{ collapsed: localStorage.getItem('teacherSidebarCollapsed') === 'true', toggleCollapse() { this.collapsed = !this.collapsed; localStorage.setItem('teacherSidebarCollapsed', this.collapsed); } }"
         :style="'--sidebar-width: ' + (collapsed ? '80px' : '280px')">

        {{-- Sidebar --}}
        <aside class="sidebar-transition sidebar-fixed-width fixed inset-y-0 z-30 hidden flex-col bg-gray-950/80 backdrop-blur-md lg:flex {{ $isRtl ? 'right-0 border-l border-gray-800/40' : 'left-0 border-r border-gray-800/40' }}">

            {{-- Brand --}}
            <div class="relative flex h-20 items-center justify-between overflow-hidden border-b border-gray-800/60 px-4">
                <div class="relative flex flex-1 items-center gap-2.5" x-show="!collapsed">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-blue-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                        </svg>
                    </div>
                    <div x-show="!collapsed">
                        <p class="truncate text-xs font-semibold text-blue-100">پورتال استاد</p>
                        <p class="truncate text-[10px] text-gray-500">{{ $teacher?->full_name ?? $user?->full_name }}</p>
                    </div>
                </div>
                <button @click="toggleCollapse()"
                        class="flex-shrink-0 rounded-lg p-1.5 text-gray-400 transition duration-150 hover:bg-gray-800/50 hover:text-blue-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/40">
                    <svg x-show="!collapsed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <svg x-show="collapsed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-3 py-5">
                <ul class="space-y-1" role="list">

                    <li>
                        <a href="{{ route('teacher.dashboard') }}" class="{{ $isDash ? $navActive : $navInactive }}" :title="collapsed ? 'داشبورد' : ''" aria-current="{{ $isDash ? 'page' : 'false' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isDash ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                            <span x-show="!collapsed">داشبورد</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('teacher.schedule') }}" class="{{ $isSchedule ? $navActive : $navInactive }}" :title="collapsed ? 'برنامه هفتگی' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isSchedule ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            <span x-show="!collapsed">برنامه هفتگی</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('teacher.students') }}" class="{{ $isStudents ? $navActive : $navInactive }}" :title="collapsed ? 'هنرجویان' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isStudents ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                            <span x-show="!collapsed">هنرجویان</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('teacher.calendar') }}" class="{{ $isCalendar ? $navActive : $navInactive }}" :title="collapsed ? 'تقویم' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isCalendar ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H18v-.008zm0 2.25h.008v.008H18V15z"/></svg>
                            <span x-show="!collapsed">تقویم</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('teacher.notifications') }}" class="{{ $isNotifs ? $navActive : $navInactive }}" :title="collapsed ? 'اعلان‌ها' : ''" aria-current="{{ $isNotifs ? 'page' : 'false' }}">
                            @php $unread = auth()->user()?->unreadNotifications->count() ?? 0; @endphp
                            <span class="relative">
                                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isNotifs ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                                @if($unread > 0)
                                <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white">{{ min($unread, 9) }}</span>
                                @endif
                            </span>
                            <span x-show="!collapsed">اعلان‌ها</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('profile.edit') }}" class="{{ $isProfile ? $navActive : $navInactive }}" :title="collapsed ? 'پروفایل' : ''" aria-current="{{ $isProfile ? 'page' : 'false' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isProfile ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            <span x-show="!collapsed">پروفایل</span>
                        </a>
                    </li>

                </ul>
            </nav>

            {{-- Footer --}}
            <div class="border-t border-gray-800/60 px-4 py-4" x-show="!collapsed">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-400 transition hover:bg-gray-800/50 hover:text-red-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                        خروج
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main --}}
        <div class="main-content-offset flex flex-1 flex-col min-w-0">

            {{-- Top navbar --}}
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-800/60 bg-gray-950/80 px-6 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    {{-- Mobile toggle (placeholder) --}}
                    <button type="button" class="lg:hidden rounded-lg p-2 text-gray-400 hover:bg-gray-800/50 hover:text-blue-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                    @isset($breadcrumb)
                        <nav class="text-sm text-gray-500" aria-label="breadcrumb">{{ $breadcrumb }}</nav>
                    @endisset
                </div>

                <div class="flex items-center gap-3">
                    {{-- Teacher identity chip --}}
                    <div class="hidden sm:flex items-center gap-2 rounded-xl border border-gray-800/60 bg-gray-900/60 px-3 py-1.5">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-500/20 text-xs font-bold text-blue-300">
                            {{ mb_substr($teacher?->full_name ?? $user?->full_name ?? '?', 0, 1) }}
                        </div>
                        <span class="text-xs font-medium text-gray-300">{{ $teacher?->full_name ?? $user?->full_name }}</span>
                    </div>

                    <a href="{{ route('profile.edit') }}"
                       class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-800/50 hover:text-blue-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/50"
                       title="پروفایل">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    </a>
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
