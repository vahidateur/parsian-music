@extends('layouts.dashboard')

@section('content')

{{-- Page Heading --}}
<div class="mb-8 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.dashboard') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('admin.welcome_message') }}</p>
    </div>
    <div class="flex flex-col gap-4 sm:items-end">
        <p class="text-sm text-gray-500">{{ \App\Helpers\Jalalian::fromCarbon(now()) }}</p>

        {{-- Quick Actions --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.students.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400 hover:shadow-amber-500/20 focus:outline-none focus:ring-2 focus:ring-amber-500/40">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('admin.new_student') }}
            </a>
            <a href="{{ route('admin.teachers.create') }}" class="inline-flex items-center gap-2 rounded-lg border border-amber-500/30 bg-amber-500/[0.04] px-4 py-2.5 text-sm font-semibold text-amber-200 shadow-lg transition hover:border-amber-500/50 hover:bg-amber-500/[0.08] focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('admin.new_teacher') }}
            </a>
            <a href="{{ route('admin.sessions.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm font-semibold text-gray-200 shadow-lg transition hover:border-gray-600 hover:bg-gray-800/70 focus:outline-none focus:ring-2 focus:ring-gray-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('admin.sessions') }}
            </a>
        </div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">

    {{-- Total Students --}}
    <div class="group relative overflow-hidden rounded-xl border border-gray-800/40 bg-gray-900/50 p-6 shadow-lg shadow-black/10 transition-all duration-300 hover:-translate-y-0.5 hover:border-amber-500/20 hover:bg-gray-900/70 hover:shadow-amber-500/10">
        <div class="relative flex items-start justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10 ring-1 ring-amber-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
            <span class="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-400">+0%</span>
        </div>
        <div class="relative mt-4">
            <p class="text-3xl font-bold text-white">{{ $totalStudents }}</p>
            <p class="mt-1 text-sm font-medium text-gray-300">{{ __('admin.total_students') }}</p>
            <p class="mt-0.5 text-xs text-gray-500">{{ __('admin.total_registered') }}</p>
        </div>
    </div>

    {{-- Active Teachers --}}
    <div class="group relative overflow-hidden rounded-xl border border-gray-800/40 bg-gray-900/50 p-6 shadow-lg shadow-black/10 transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-500/20 hover:bg-gray-900/70 hover:shadow-emerald-500/10">
        <div class="relative flex items-start justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/10 ring-1 ring-emerald-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                </svg>
            </div>
            <span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-400">فعال</span>
        </div>
        <div class="relative mt-4">
            <p class="text-3xl font-bold text-white">{{ $activeTeachers }}</p>
            <p class="mt-1 text-sm font-medium text-gray-300">{{ __('admin.active_teachers') }}</p>
            <p class="mt-0.5 text-xs text-gray-500">{{ __('admin.currently_teaching') }}</p>
        </div>
    </div>

    {{-- Today Sessions --}}
    <div class="group relative overflow-hidden rounded-xl border border-gray-800/40 bg-gray-900/50 p-6 shadow-lg shadow-black/10 transition-all duration-300 hover:-translate-y-0.5 hover:border-sky-500/20 hover:bg-gray-900/70 hover:shadow-sky-500/10">
        <div class="relative flex items-start justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-500/10 ring-1 ring-sky-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
            </div>
            <span class="rounded-full bg-sky-500/10 px-2 py-0.5 text-xs font-medium text-sky-400">امروز</span>
        </div>
        <div class="relative mt-4">
            <p class="text-3xl font-bold text-white">{{ $todaySessions }}</p>
            <p class="mt-1 text-sm font-medium text-gray-300">{{ __('admin.today_sessions') }}</p>
            <p class="mt-0.5 text-xs text-gray-500">{{ __('admin.scheduled_for_today') }}</p>
        </div>
    </div>

    {{-- Monthly Revenue (placeholder) --}}
    <div class="group relative overflow-hidden rounded-xl border border-gray-800/40 bg-gray-900/50 p-6 shadow-lg shadow-black/10 transition-all duration-300 hover:-translate-y-0.5 hover:border-violet-500/20 hover:bg-gray-900/70 hover:shadow-violet-500/10">
        <div class="relative flex items-start justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-500/10 ring-1 ring-violet-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span class="rounded-full bg-violet-500/10 px-2 py-0.5 text-xs font-medium text-violet-400">{{ __('admin.coming_soon') }}</span>
        </div>
        <div class="relative mt-4">
            <p class="text-3xl font-bold text-gray-600">—</p>
            <p class="mt-1 text-sm font-medium text-gray-300">{{ __('admin.monthly_revenue') }}</p>
            <p class="mt-0.5 text-xs text-gray-500">{{ __('admin.coming_soon') }}</p>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 1 — Charts                                                --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- 1A. Monthly Enrollment Line Chart --}}
    <section class="xl:col-span-2 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
        <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
            <div>
                <h2 class="text-base font-semibold text-amber-100">ثبت‌نام ماهانه هنرجویان</h2>
                <p class="mt-0.5 text-xs text-gray-500">۶ ماه اخیر</p>
            </div>
            <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">نمودار خطی</span>
        </div>
        <div class="px-6 py-6">
            {{-- Y-axis labels + chart area --}}
            <div class="flex gap-3">
                {{-- Y labels --}}
                <div class="flex w-8 shrink-0 flex-col justify-between pb-6 text-right">
                    @foreach ([20, 15, 10, 5, 0] as $y)
                        <span class="text-xs text-gray-600">{{ $y }}</span>
                    @endforeach
                </div>
                {{-- Chart --}}
                <div class="relative flex-1">
                    {{-- Grid lines --}}
                    <div class="absolute inset-0 flex flex-col justify-between pb-6">
                        @foreach (range(0, 4) as $i)
                            <div class="h-px w-full bg-gray-800/60"></div>
                        @endforeach
                    </div>
                    {{-- Bars (line chart simulated with CSS bars + connector) --}}
                    @php
                        $counts = array_column($enrollmentTrend, 'count');
                        $max = max($counts) ?: 1;
                    @endphp
                    <div class="relative flex h-40 items-end justify-around gap-1 pb-6">
                        @foreach ($enrollmentTrend as $trend)
                            @php $pct = $trend['count'] > 0 ? round(($trend['count'] / $max) * 100) : 0; @endphp
                            <div class="group/bar relative flex flex-1 flex-col items-center gap-1">
                                {{-- Tooltip --}}
                                <div class="pointer-events-none absolute -top-7 left-1/2 -translate-x-1/2 rounded-md bg-gray-800 px-2 py-1 text-xs font-semibold text-amber-300 opacity-0 shadow-lg transition-opacity group-hover/bar:opacity-100 whitespace-nowrap">
                                    {{ $trend['count'] }} {{ __('admin.student') }}
                                </div>
                                {{-- Bar --}}
                                <div class="w-full rounded-t-md bg-gradient-to-t from-amber-600/60 to-amber-400/80 transition-all duration-300 group-hover/bar:from-amber-500/80 group-hover/bar:to-amber-300"
                                     style="height: {{ $pct }}%">
                                </div>
                            </div>
                        @endforeach
                    </div>
                    {{-- X labels --}}
                    <div class="flex justify-around">
                        @foreach ($enrollmentTrend as $trend)
                            <span class="flex-1 text-center text-xs text-gray-500">{{ $trend['label'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            {{-- Legend --}}
            <div class="mt-4 flex items-center gap-2 border-t border-gray-800/60 pt-4">
                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                <span class="text-xs text-gray-400">{{ __('admin.total_students') }} ثبت‌نام شده در هر ماه</span>
            </div>
        </div>
    </section>    {{-- 1B. Attendance Donut Chart --}}
    <section class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
        <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
            <div>
                <h2 class="text-base font-semibold text-amber-100">تحلیل حضور و غیاب</h2>
                <p class="mt-0.5 text-xs text-gray-500">۳۰ روز اخیر</p>
            </div>
            <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-400">نمودار دایره‌ای</span>
        </div>
        <div class="px-6 py-6">
            {{-- Donut via conic-gradient --}}
            @php
                $total = $attendanceStats['total'] ?? 1;
                $data = [
                    ['label' => 'حاضر',   'value' => $attendanceStats['present'] ?? 0, 'color' => '#10b981', 'text' => 'text-emerald-400', 'bg' => 'bg-emerald-400'],
                    ['label' => 'غایب',   'value' => $attendanceStats['absent'] ?? 0, 'color' => '#ef4444', 'text' => 'text-red-400',     'bg' => 'bg-red-400'],
                    ['label' => 'تأخیر',  'value' => $attendanceStats['late'] ?? 0, 'color' => '#f59e0b', 'text' => 'text-amber-400',  'bg' => 'bg-amber-400'],
                    ['label' => 'موجه',   'value' => $attendanceStats['excused'] ?? 0, 'color' => '#0ea5e9', 'text' => 'text-sky-400',    'bg' => 'bg-sky-400'],
                ];
                
                $segs = []; $off = 0;
                foreach ($data as $d) {
                    $pct = $total > 0 ? ($d['value'] / $total) * 100 : 0;
                    $segs[] = $d['color'] . ' ' . $off . '% ' . ($off + $pct) . '%';
                    $off += $pct;
                }
                $conic = 'conic-gradient(' . implode(', ', $segs) . ')';
            @endphp
            <div class="flex justify-center">
                <div class="relative h-36 w-36">
                    <div class="h-36 w-36 rounded-full" style="background: {{ $conic }}"></div>
                    <div class="absolute inset-[18px] flex flex-col items-center justify-center rounded-full bg-gray-950 shadow-inner">
                        <span class="text-xl font-bold text-white">{{ $total }}</span>
                        <span class="text-xs text-gray-500">{{ __('admin.total') }}</span>
                    </div>
                </div>
            </div>
            {{-- Legend --}}
            <div class="mt-5 space-y-2.5">
                @foreach ($data as $d)
                    @php
                        $percentage = $total > 0 ? round(($d['value'] / $total) * 100) : 0;
                    @endphp
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ $d['bg'] }}"></span>
                            <span class="text-xs text-gray-300">{{ $d['label'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 w-16 overflow-hidden rounded-full bg-gray-800">
                                <div class="{{ $d['bg'] }} h-full rounded-full opacity-70" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="w-8 text-right text-xs font-semibold {{ $d['text'] }}">{{ $percentage }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>

{{-- ═════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 2 — Live Widgets                                            --}}
{{-- ═════════════════════════════════════════════════════════════════════ --}}
<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">    {{-- 2A. Today's Class Timeline --}}
    <section class="lg:col-span-1 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
        <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
            <h2 class="text-base font-semibold text-amber-100">تایم‌لاین کلاس‌های امروز</h2>
            <span class="flex h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_6px_2px_rgba(52,211,153,0.4)]"></span>
        </div>
        <div class="px-6 py-5">
            @php
                $timeline = [
                    ['time' => '08:00', 'student' => 'علی رضایی',    'instrument' => 'پیانو',  'room' => 'اتاق ۱', 'status' => 'completed', 'color' => 'bg-emerald-400'],
                    ['time' => '09:30', 'student' => 'سارا محمدی',   'instrument' => 'ویولن',  'room' => 'اتاق ۲', 'status' => 'completed', 'color' => 'bg-emerald-400'],
                    ['time' => '11:00', 'student' => 'نیما کریمی',   'instrument' => 'گیتار',  'room' => 'اتاق ۱', 'status' => 'scheduled', 'color' => 'bg-sky-400'],
                    ['time' => '13:00', 'student' => 'مریم احمدی',   'instrument' => 'تار',    'room' => 'اتاق ۳', 'status' => 'scheduled', 'color' => 'bg-sky-400'],
                    ['time' => '15:30', 'student' => 'رضا قاسمی',    'instrument' => 'سنتور',  'room' => 'اتاق ۲', 'status' => 'scheduled', 'color' => 'bg-sky-400'],
                ];
            @endphp
            <ol class="relative border-r border-gray-800 pr-6">
                @foreach ($timeline as $item)
                    <li class="group mb-5 last:mb-0">
                        {{-- dot --}}
                        <span class="absolute -right-[5px] mt-1.5 h-2.5 w-2.5 rounded-full border border-gray-900 {{ $item['color'] }} {{ $item['status'] === 'scheduled' ? 'opacity-50' : '' }}"></span>
                        <div class="rounded-xl border border-gray-800/60 bg-gray-800/30 px-4 py-3 transition-all duration-200 hover:border-amber-500/20 hover:bg-gray-800/50">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs font-bold text-amber-400">{{ $item['time'] }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $item['status'] === 'completed' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-sky-500/10 text-sky-400' }}">
                                    {{ $item['status'] === 'completed' ? 'برگزارشده' : 'برنامه‌ریزی‌شده' }}
                                </span>
                            </div>
                            <p class="mt-1.5 text-sm font-medium text-gray-100">{{ $item['student'] }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $item['instrument'] }} · {{ $item['room'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 2B. Recent Student Registrations --}}
    <section class="lg:col-span-1 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
        <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
            <h2 class="text-base font-semibold text-amber-100">آخرین ثبت‌نام‌ها</h2>
            <a href="{{ route('admin.students.index') }}" class="text-xs text-amber-500/70 transition hover:text-amber-400">مشاهده همه ←</a>
        </div>
        <div class="divide-y divide-gray-800/60">
            @forelse ($recentStudents as $student)
                @php
                    $enrollment = $student->enrollments->first();
                    $instrument = $enrollment?->instrument?->display_name ?? '—';
                    $level = $enrollment?->skill_level ? __('admin.skill_levels.'.$enrollment->skill_level->value) : '—';
                    $jalaliDate = \App\Helpers\Jalalian::fromCarbon($student->created_at);
                @endphp
                <div class="flex items-center gap-4 px-6 py-3.5 transition hover:bg-gray-800/30">
                    {{-- Avatar --}}
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-500/20 to-amber-600/10 text-sm font-bold text-amber-300">
                        {{ mb_substr($student->full_name, 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-100">{{ $student->full_name }}</p>
                        <p class="text-xs text-gray-500">{{ $instrument }} · {{ $level }}</p>
                    </div>
                    <span class="shrink-0 text-xs text-gray-600">{{ $jalaliDate }}</span>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <p class="text-xs text-gray-500">{{ __('admin.no_students_found') }}</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- 2C. Payment Alerts --}}
    <section class="lg:col-span-1 overflow-hidden rounded-2xl border border-red-500/10 bg-gradient-to-b from-red-500/[0.04] to-gray-900/60 shadow-xl backdrop-blur-sm">
        <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-red-400 shadow-[0_0_6px_2px_rgba(248,113,113,0.4)]"></span>
                <h2 class="text-base font-semibold text-amber-100">هشدار پرداخت</h2>
            </div>
            <span class="rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-medium text-red-400">۵ مورد</span>
        </div>
        <div class="divide-y divide-gray-800/60">
            @php
                $alerts = [
                    ['name' => 'علی رضایی',   'amount' => '۵۰۰,۰۰۰', 'days' => 12, 'severity' => 'high'],
                    ['name' => 'سارا محمدی',  'amount' => '۳۵۰,۰۰۰', 'days' => 8,  'severity' => 'high'],
                    ['name' => 'نیما کریمی',  'amount' => '۴۵۰,۰۰۰', 'days' => 5,  'severity' => 'mid'],
                    ['name' => 'مریم احمدی',  'amount' => '۲۵۰,۰۰۰', 'days' => 3,  'severity' => 'mid'],
                    ['name' => 'رضا قاسمی',   'amount' => '۶۰۰,۰۰۰', 'days' => 1,  'severity' => 'low'],
                ];
            @endphp
            @foreach ($alerts as $alert)
                @php
                    $dot  = $alert['severity'] === 'high' ? 'bg-red-400'    : ($alert['severity'] === 'mid' ? 'bg-amber-400' : 'bg-yellow-300');
                    $text = $alert['severity'] === 'high' ? 'text-red-400'  : ($alert['severity'] === 'mid' ? 'text-amber-400' : 'text-yellow-300');
                    $bg   = $alert['severity'] === 'high' ? 'bg-red-500/10' : ($alert['severity'] === 'mid' ? 'bg-amber-500/10' : 'bg-yellow-500/10');
                @endphp
                <div class="flex items-center gap-4 px-6 py-3.5 transition hover:bg-gray-800/30">
                    <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full {{ $dot }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-100">{{ $alert['name'] }}</p>
                        <p class="text-xs text-gray-500">{{ $alert['days'] }} روز تأخیر</p>
                    </div>
                    <span class="shrink-0 rounded-full {{ $bg }} px-2 py-0.5 text-xs font-semibold {{ $text }}">
                        {{ $alert['amount'] }} ت
                    </span>
                </div>
            @endforeach
        </div>
        <div class="border-t border-gray-800/60 px-6 py-3 text-center">
            <p class="text-xs text-gray-600">داده نمونه — ماژول پرداخت به زودی</p>
        </div>
    </section>
</div>

{{-- Main Grid --}}
<div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- Today Schedule (left, large) --}}
    <section class="xl:col-span-2 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
        <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
            <h2 class="text-lg font-semibold text-amber-100">{{ __('admin.todays_schedule') }}</h2>
            <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">{{ __('admin.sessions_count', ['count' => $recentSessions->count()]) }}</span>
        </div>

        @if ($recentSessions->count())
            <div class="overflow-x-auto">
                <table class="w-full text-start text-sm">
                    <thead>
                        <tr class="border-b border-gray-800/60 bg-gray-800/30">
                            <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.time') }}</th>
                            <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.student') }}</th>
                            <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.teacher') }}</th>
                            <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.room') }}</th>
                            <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60">
                        @foreach ($recentSessions as $session)
                            @php
                                $statusStyles = [
                                    'scheduled' => 'bg-sky-500/10 text-sky-400',
                                    'completed' => 'bg-emerald-500/10 text-emerald-400',
                                    'cancelled' => 'bg-red-500/10 text-red-400',
                                    'missed' => 'bg-red-500/10 text-red-400',
                                    'makeup' => 'bg-amber-500/10 text-amber-300',
                                ];
                                $statusValue = $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status;
                                $badgeStyle = $statusStyles[$statusValue] ?? 'bg-gray-700/50 text-gray-400';
                            @endphp
                            <tr class="transition hover:bg-gray-800/20">
                                <td class="px-6 py-3.5 font-mono text-sm font-semibold text-amber-400">{{ $session->start_time?->format('H:i') ?? '—' }}</td>
                                <td class="px-6 py-3.5 font-medium text-gray-100">{{ $session->enrollment?->student?->full_name ?? '—' }}</td>
                                <td class="px-6 py-3.5 text-gray-400">{{ $session->enrollment?->teacher?->full_name ?? '—' }}</td>
                                <td class="px-6 py-3.5 text-gray-400">{{ $session->room ?? '—' }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="rounded-full {{ $badgeStyle }} px-2.5 py-0.5 text-xs font-medium">
                                        {{ __('admin.session_statuses.'.$statusValue) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center text-gray-500">
                {{ __('admin.no_sessions_today') }}
            </div>
        @endif
    </section>

    {{-- Alerts Panel --}}
    <div class="space-y-6">

        {{-- Cancelled Sessions --}}
        <section class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
            <div class="border-b border-gray-800/60 px-6 py-4">
                <h2 class="text-base font-semibold text-amber-100">{{ __('admin.cancelled_sessions') }}</h2>
            </div>
            <div class="px-6 py-6 text-center">
                <p class="text-4xl font-bold text-red-400">{{ $cancelledSessions }}</p>
                <p class="mt-2 text-xs text-gray-500">{{ __('admin.cancelled_last_7_days') }}</p>
            </div>
        </section>

        {{-- Missed Sessions --}}
        <section class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
            <div class="border-b border-gray-800/60 px-6 py-4">
                <h2 class="text-base font-semibold text-amber-100">{{ __('admin.missed_sessions') }}</h2>
            </div>
            <div class="px-6 py-6 text-center">
                <p class="text-4xl font-bold text-red-400">{{ $missedSessions }}</p>
                <p class="mt-2 text-xs text-gray-500">{{ __('admin.missed_last_7_days') }}</p>
            </div>
        </section>
    </div>
</div>

@endsection
