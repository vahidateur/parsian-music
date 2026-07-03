<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'fa';
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? __('admin.panel_title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative min-h-screen overflow-x-hidden bg-gray-950 text-gray-100 antialiased">

    {{-- Background layers --}}
    <div class="pointer-events-none fixed inset-0 z-0 bg-gradient-to-br from-gray-950 via-gray-900 to-gray-950"></div>
    <div class="pointer-events-none fixed inset-0 z-0 opacity-[0.025]" style="background-image: radial-gradient(circle at 1px 1px, #ffffff 1px, transparent 0); background-size: 32px 32px;"></div>
    <div class="pointer-events-none fixed inset-0 z-0 opacity-[0.05]" style="background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(245, 158, 11, 0.15), transparent 70%);"></div>
    <div class="pointer-events-none fixed top-1/4 {{ $isRtl ? 'right-1/4' : 'left-1/4' }} z-0 h-80 w-80 rounded-full bg-amber-500/[0.04] blur-[120px]"></div>
    <div class="pointer-events-none fixed bottom-0 {{ $isRtl ? 'left-1/4' : 'right-1/4' }} z-0 h-72 w-72 rounded-full bg-amber-400/[0.03] blur-[100px]"></div>

    <div class="relative z-10 flex min-h-screen">

        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 z-30 hidden w-64 flex-col bg-gray-900/70 backdrop-blur-xl lg:flex {{ $isRtl ? 'right-0 border-l border-gray-800/60' : 'left-0 border-r border-gray-800/60' }}">

            {{-- Brand --}}
            <div class="relative flex h-20 items-center gap-2.5 overflow-hidden border-b border-gray-800/60 px-6">
                <div class="pointer-events-none absolute {{ $isRtl ? '-right-4' : '-left-4' }} top-1/2 h-24 w-24 -translate-y-1/2 rounded-full bg-amber-500/[0.08] blur-2xl"></div>
                <span class="relative text-xl font-bold tracking-tight text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a2.25 2.25 0 001.632-2.163zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 01-.99-3.467l2.31-.66A2.25 2.25 0 009 15.553z" />
                    </svg>
                </span>
                <span class="relative text-lg font-semibold text-amber-100">{{ __('admin.brand_name') }}</span>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-3 py-5">
                @php
                    $navActive   = 'flex items-center gap-3 rounded-lg bg-amber-500/10 px-3 py-2.5 text-sm font-medium text-amber-300 ring-1 ring-amber-500/20';
                    $navInactive = 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-300 transition hover:bg-gray-800/50 hover:text-amber-300';
                    $iconActive  = 'h-5 w-5 text-amber-400';
                    $iconInactive= 'h-5 w-5 text-amber-400/80';
                    $isDash        = request()->routeIs('admin.dashboard');
                    $isStudents    = request()->routeIs('admin.students.*');
                    $isTeachers    = request()->routeIs('admin.teachers.*');
                    $isSessions    = request()->routeIs('admin.sessions.*');
                    $isCalendar    = request()->routeIs('admin.calendar.*');
                    $isReports     = request()->routeIs('admin.reports.*');
                    $isInstruments = request()->routeIs('admin.instruments.*');
                @endphp
                <ul class="space-y-1">

                    {{-- Dashboard --}}
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="{{ $isDash ? $navActive : $navInactive }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isDash ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25A2.25 2.25 0 0113.5 8.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                            {{ __('admin.dashboard') }}
                        </a>
                    </li>

                    {{-- Students --}}
                    <li>
                        <a href="{{ route('admin.students.index') }}" class="{{ $isStudents ? $navActive : $navInactive }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isStudents ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                            {{ __('admin.students') }}
                        </a>
                    </li>

                    {{-- Teachers --}}
                    <li>
                        <a href="{{ route('admin.teachers.index') }}" class="{{ $isTeachers ? $navActive : $navInactive }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isTeachers ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" /></svg>
                            {{ __('admin.teachers') }}
                        </a>
                    </li>

                    {{-- Sessions / Classes --}}
                    <li>
                        <a href="{{ route('admin.sessions.index') }}" class="{{ $isSessions ? $navActive : $navInactive }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isSessions ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                            {{ __('admin.sessions') }}
                        </a>
                    </li>

                    {{-- Calendar / Schedule --}}
                    <li>
                        <a href="{{ route('admin.calendar.index') }}" class="{{ $isCalendar ? $navActive : $navInactive }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isCalendar ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ __('admin.calendar') }}
                        </a>
                    </li>

                    {{-- Reports --}}
                    <li>
                        <a href="{{ route('admin.reports.attendance') }}" class="{{ $isReports ? $navActive : $navInactive }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isReports ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                            {{ __('admin.reports') }}
                        </a>
                    </li>

                    {{-- Instruments --}}
                    <li>
                        <a href="{{ route('admin.instruments.index') }}" class="{{ $isInstruments ? $navActive : $navInactive }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $isInstruments ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a2.25 2.25 0 001.632-2.163zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 01-.99-3.467l2.31-.66A2.25 2.25 0 009 15.553z" /></svg>
                            {{ __('admin.instruments') }}
                        </a>
                    </li>

                </ul>
            </nav>

            {{-- Footer --}}
            <div class="border-t border-gray-800/60 px-6 py-4">
                <p class="text-xs text-gray-500">{{ __('admin.academy_footer') }}</p>
            </div>
        </aside>

        {{-- Main area --}}
        <div class="flex flex-1 flex-col {{ $isRtl ? 'lg:pr-64' : 'lg:pl-64' }}">

            {{-- Top Navbar --}}
            <header class="relative sticky top-0 z-20 flex h-20 items-center justify-between overflow-hidden border-b border-gray-800/60 bg-gray-950/80 px-6 backdrop-blur-md">
                <div class="pointer-events-none absolute inset-0 opacity-20" style="background: radial-gradient(ellipse at 70% 50%, rgba(245, 158, 11, 0.10), transparent 70%);"></div>

                {{-- Mobile sidebar toggle --}}
                <button type="button" class="relative lg:hidden rounded-lg p-2 text-gray-400 transition hover:bg-gray-800/50 hover:text-amber-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="relative">
                    <h2 class="text-sm font-medium text-gray-400">{{ $pageTitle ?? __('admin.dashboard') }}</h2>
                </div>

                <div class="relative flex items-center gap-4">
                    <span class="text-sm text-gray-400">{{ auth()->user()?->full_name ?? '' }}</span>

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-2 text-sm font-medium text-gray-300 transition hover:border-red-500/50 hover:bg-red-500/10 hover:text-red-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                            {{ __('admin.logout') }}
                        </button>
                    </form>
                </div>
            </header>

            {{-- Content --}}
            <main class="flex-1 p-6">

                {{-- Breadcrumb bar (rendered if any child view yields 'breadcrumb') --}}
                @hasSection('breadcrumb')
                    <nav class="mb-6 flex items-center gap-2 text-xs text-gray-500">
                        <a href="{{ route('admin.dashboard') }}" class="transition hover:text-amber-400">{{ __('admin.dashboard') }}</a>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 {{ $isRtl ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                        <span class="text-amber-300/80">@yield('breadcrumb')</span>
                    </nav>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>
