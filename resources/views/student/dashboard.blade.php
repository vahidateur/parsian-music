@extends('layouts.student')

@section('title', 'داشبورد هنرجو')

@section('content')
@php
    $statusColor = [
        'completed' => 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30',
        'scheduled' => 'bg-blue-500/15 text-blue-300 ring-1 ring-blue-500/30',
        'cancelled' => 'bg-red-500/15 text-red-300 ring-1 ring-red-500/30',
        'missed'    => 'bg-gray-500/15 text-gray-400 ring-1 ring-gray-500/30',
    ];
    $jalaliDays = ['Saturday'=>'شنبه','Sunday'=>'یکشنبه','Monday'=>'دوشنبه','Tuesday'=>'سه‌شنبه','Wednesday'=>'چهارشنبه','Thursday'=>'پنجشنبه','Friday'=>'جمعه'];
@endphp

{{-- Header --}}
<x-dashboard.section-header
    title="خوش آمدید، {{ $student->full_name }}"
    :subtitle="\App\Helpers\Jalalian::fromCarbon($today)">
    <x-slot name="actions">
        <a href="{{ route('student.classes') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-emerald-500/20 px-4 py-2 text-sm font-semibold text-emerald-300 ring-1 ring-emerald-500/30 transition hover:bg-emerald-500/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
            همه کلاس‌ها
        </a>
    </x-slot>
</x-dashboard.section-header>

{{-- KPI Row --}}
<div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5" aria-label="آمار هنرجو">
    <x-dashboard.kpi-card
        title="کلاس امروز"
        :value="$todaySessions->count()"
        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>'
        tone="green" />

    <x-dashboard.kpi-card
        title="درصد حضور"
        :value="$attendanceRate . '%'"
        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
        :tone="$attendanceRate >= 80 ? 'green' : ($attendanceRate >= 60 ? 'yellow' : 'red')" />

    <x-dashboard.kpi-card
        title="جلسات باقیمانده"
        :value="$remainingSessions"
        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>'
        :tone="$remainingSessions > 0 ? 'blue' : 'red'" />

    <x-dashboard.kpi-card
        title="بدهی معوق"
        :value="number_format($outstandingBalance) . ' تومان'"
        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>'
        :tone="$outstandingBalance > 0 ? 'red' : 'green'" />

    <x-dashboard.kpi-card
        title="اعلان‌های جدید"
        :value="$unreadCount"
        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>'
        :tone="$unreadCount > 0 ? 'yellow' : 'default'" />
</div>

<div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">

    {{-- ── Today's Classes ─────────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="کلاس‌های امروز"
        :badge="$todaySessions->count() . ' کلاس'"
        :actions='\'<a href="' . route("student.classes") . '" class="text-xs text-emerald-400 hover:text-emerald-300 transition">همه →</a>\''>
        @if($todaySessions->isEmpty())
            <x-dashboard.empty-state
                title="کلاسی برای امروز ندارید"
                description="از تقویم برای مشاهده برنامه هفتگی استفاده کنید." />
        @else
        <ul class="divide-y divide-gray-800/40" role="list">
            @foreach($todaySessions as $session)
            @php
                $teacher    = $session->teacher ?? $session->enrollment?->teacher;
                $instrument = $session->instrument ?? $session->enrollment?->instrument;
            @endphp
            <li class="flex items-center justify-between gap-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-sm font-bold text-emerald-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-100">
                            {{ $instrument?->display_name ?? 'کلاس موسیقی' }}
                        </p>
                        <p class="text-xs text-gray-500">
                            استاد: {{ $teacher?->full_name ?? '—' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-mono tabular-nums text-xs text-gray-400">{{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}</span>
                    <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-semibold {{ $statusColor[$session->status->value] ?? '' }}">
                        {{ $session->status->label() }}
                    </span>
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </x-dashboard.chart-container>

    {{-- ── Upcoming Classes ─────────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="کلاس‌های پیش رو"
        :badge="$upcomingSessions->count() . ' کلاس'"
        :actions='\'<a href="' . route("student.classes") . '" class="text-xs text-emerald-400 hover:text-emerald-300 transition">همه →</a>\''>
        @if($upcomingSessions->isEmpty())
            <x-dashboard.empty-state
                title="کلاسی در ۷ روز آینده ندارید" />
        @else
        <ul class="divide-y divide-gray-800/40" role="list">
            @foreach($upcomingSessions as $session)
            @php
                $teacher    = $session->teacher ?? $session->enrollment?->teacher;
                $instrument = $session->instrument ?? $session->enrollment?->instrument;
                $dayName    = $jalaliDays[$session->session_date->englishDayOfWeek] ?? '';
            @endphp
            <li class="flex items-center justify-between gap-3 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-16 text-center">
                        <p class="text-xs font-semibold text-emerald-300">{{ $dayName }}</p>
                        <p class="text-xs tabular-nums text-gray-500">{{ \App\Helpers\Jalalian::fromCarbon($session->session_date) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-100">{{ $instrument?->display_name ?? 'کلاس موسیقی' }}</p>
                        <p class="text-xs text-gray-500">{{ $teacher?->full_name ?? '—' }} · {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}</p>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </x-dashboard.chart-container>

    {{-- ── Attendance Summary ───────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="خلاصه حضور و غیاب"
        :actions='\'<a href="' . route("student.attendance") . '" class="text-xs text-emerald-400 hover:text-emerald-300 transition">جزئیات →</a>\''>
        <div class="space-y-3">
            {{-- Overall rate --}}
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-400">نرخ کلی حضور</span>
                <span class="text-lg font-bold tabular-nums {{ $attendanceRate >= 80 ? 'text-emerald-300' : ($attendanceRate >= 60 ? 'text-amber-300' : 'text-red-300') }}">{{ $attendanceRate }}%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-800">
                <div class="h-2 rounded-full transition-all duration-700
                    {{ $attendanceRate >= 80 ? 'bg-emerald-500' : ($attendanceRate >= 60 ? 'bg-amber-500' : 'bg-red-500') }}"
                     style="width: {{ $attendanceRate }}%"></div>
            </div>

            {{-- Breakdown --}}
            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach([
                    ['label'=>'حاضر',  'count'=>$presentCount,                          'color'=>'text-emerald-300'],
                    ['label'=>'غایب',  'count'=>$totalAttendances - $presentCount,       'color'=>'text-red-300'],
                ] as $item)
                <div class="rounded-xl border border-gray-800/40 bg-gray-800/20 p-3 text-center">
                    <p class="text-xl font-bold tabular-nums {{ $item['color'] }}">{{ $item['count'] }}</p>
                    <p class="text-xs text-gray-500">{{ $item['label'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </x-dashboard.chart-container>

    {{-- ── Subscriptions / Remaining Sessions ─────────────────────────────── --}}
    <x-dashboard.chart-container title="اشتراک‌های فعال"
        :badge="$subscriptions->count() . ' اشتراک'">
        @if($subscriptions->isEmpty())
            <x-dashboard.empty-state
                title="اشتراک فعالی ندارید"
                description="برای ثبت‌نام با مدیریت آموزشگاه تماس بگیرید." />
        @else
        <ul class="divide-y divide-gray-800/40" role="list">
            @foreach($subscriptions as $sub)
            @php
                $remaining = $sub->remaining_sessions;
                $pct = $sub->sessions_allocated > 0
                    ? max(0, min(100, round(($remaining / $sub->sessions_allocated) * 100)))
                    : 0;
                $barColor = $pct > 40 ? 'bg-emerald-500' : ($pct > 15 ? 'bg-amber-500' : 'bg-red-500');
            @endphp
            <li class="py-3">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-medium text-gray-100">{{ $sub->instrument?->display_name ?? 'موسیقی' }}</p>
                        @if($sub->teacher)
                        <p class="text-xs text-gray-500">استاد: {{ $sub->teacher->full_name }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold tabular-nums {{ $remaining > 0 ? 'text-emerald-300' : 'text-red-400' }}">{{ $remaining }}</span>
                        <span class="text-xs text-gray-500">/ {{ $sub->sessions_allocated }}</span>
                        <p class="text-[10px] text-gray-600">جلسه باقیمانده</p>
                    </div>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-800">
                    <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </x-dashboard.chart-container>

    {{-- ── Outstanding Balance / Recent Payments ───────────────────────────── --}}
    <x-dashboard.chart-container title="وضعیت مالی"
        :actions='\'<a href="' . route("student.invoices") . '" class="text-xs text-emerald-400 hover:text-emerald-300 transition">فاکتورها →</a>\''>
        {{-- Balance hero --}}
        <div class="mb-4 flex items-center justify-between rounded-xl border {{ $outstandingBalance > 0 ? 'border-red-500/20 bg-red-500/5' : 'border-emerald-500/20 bg-emerald-500/5' }} p-4">
            <div>
                <p class="text-xs text-gray-500">مانده بدهی</p>
                <p class="mt-1 text-2xl font-bold tabular-nums {{ $outstandingBalance > 0 ? 'text-red-300' : 'text-emerald-300' }}">
                    {{ number_format($outstandingBalance) }}
                    <span class="text-sm font-normal text-gray-500">تومان</span>
                </p>
            </div>
            @if($outstandingBalance > 0)
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-400/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            @else
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-emerald-400/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @endif
        </div>

        {{-- Recent payments --}}
        @if($recentPayments->isNotEmpty())
        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-600">آخرین پرداخت‌ها</p>
        <ul class="space-y-2" role="list">
            @foreach($recentPayments->take(3) as $payment)
            <li class="flex items-center justify-between text-sm">
                <span class="text-gray-400">
                    {{ $payment->invoice?->invoice_number ?? '—' }}
                </span>
                <div class="flex items-center gap-2">
                    <span class="font-mono tabular-nums text-xs text-gray-500">
                        {{ $payment->paid_at?->diffForHumans() }}
                    </span>
                    <span class="font-semibold text-emerald-300">
                        {{ number_format($payment->amount) }} تومان
                    </span>
                </div>
            </li>
            @endforeach
        </ul>
        <div class="mt-3 text-left">
            <a href="{{ route('student.payments') }}" class="text-xs text-emerald-400 transition hover:text-emerald-300">همه پرداخت‌ها →</a>
        </div>
        @else
        <x-dashboard.empty-state title="پرداختی ثبت نشده است" />
        @endif
    </x-dashboard.chart-container>

    {{-- ── Notifications ────────────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="اعلان‌های اخیر"
        :badge="$unreadCount > 0 ? $unreadCount . ' جدید' : null"
        :actions='\'<a href="' . route("student.notifications") . '" class="text-xs text-emerald-400 hover:text-emerald-300 transition">همه →</a>\''>
        @if($notifications->isEmpty())
            <x-dashboard.empty-state title="اعلانی ندارید" />
        @else
        <ul class="divide-y divide-gray-800/40" role="list">
            @foreach($notifications as $notif)
            @php
                $isUnread = is_null($notif->read_at);
                $msg = $notif->data['message'] ?? $notif->data['body'] ?? $notif->data['title'] ?? 'اعلان جدید';
            @endphp
            <li class="flex items-start gap-3 py-3">
                <span class="mt-1.5 block h-2 w-2 flex-shrink-0 rounded-full {{ $isUnread ? 'bg-emerald-400' : 'bg-gray-700' }}" aria-hidden="true"></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm {{ $isUnread ? 'font-medium text-gray-100' : 'text-gray-400' }}">{{ $msg }}</p>
                    <p class="mt-0.5 text-xs text-gray-600">{{ $notif->created_at->diffForHumans() }}</p>
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </x-dashboard.chart-container>

</div>

{{-- ── Quick Actions ────────────────────────────────────────────────────────── --}}
<div class="mt-5">
    <x-dashboard.section-header title="دسترسی سریع" />
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach([
            ['href' => route('student.classes'),    'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5', 'label' => 'کلاس‌های من'],
            ['href' => route('student.attendance'), 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',          'label' => 'حضور و غیاب'],
            ['href' => route('student.invoices'),   'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z', 'label' => 'فاکتورها'],
            ['href' => route('student.teachers'),   'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z', 'label' => 'استادان من'],
        ] as $action)
        <a href="{{ $action['href'] }}"
           class="group flex flex-col items-center gap-3 rounded-2xl border border-gray-800/50 bg-gray-800/20 p-5 text-center transition duration-200 hover:-translate-y-1 hover:border-emerald-500/30 hover:bg-emerald-500/5 hover:shadow-lg hover:shadow-emerald-500/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 ring-1 ring-emerald-500/20 transition group-hover:bg-emerald-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['icon'] }}"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-gray-300 group-hover:text-emerald-300">{{ $action['label'] }}</span>
        </a>
        @endforeach
    </div>
</div>
@endsection
