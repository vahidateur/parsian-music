@extends('layouts.teacher')

@section('title', 'داشبورد استاد')

@section('content')
@php
    $statusColor = [
        'completed' => 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30',
        'scheduled' => 'bg-blue-500/15 text-blue-300 ring-blue-500/30',
        'cancelled' => 'bg-red-500/15 text-red-300 ring-red-500/30',
        'missed'    => 'bg-gray-500/15 text-gray-400 ring-gray-500/30',
    ];
    $jalaliDays = ['Saturday'=>'شنبه','Sunday'=>'یکشنبه','Monday'=>'دوشنبه','Tuesday'=>'سه‌شنبه','Wednesday'=>'چهارشنبه','Thursday'=>'پنجشنبه','Friday'=>'جمعه'];
@endphp

{{-- Header --}}
<x-dashboard.section-header
    title="داشبورد استاد"
    :subtitle="$teacher->full_name . ' — ' . ($jalaliDays[$today->englishDayOfWeek] ?? '') . ' ' . \App\Helpers\Jalalian::fromCarbon($today)">
</x-dashboard.section-header>

{{-- KPI Row --}}
<div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4" role="list" aria-label="آمار کلی">
    <x-dashboard.kpi-card
        title="کلاس‌های این هفته"
        :value="$panel['weeklySessions']"
        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>'
        tone="blue" />

    <x-dashboard.kpi-card
        title="تکمیل‌شده"
        :value="$panel['completedSessions']"
        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
        tone="green" />

    <x-dashboard.kpi-card
        title="هنرجویان"
        :value="$panel['totalStudents']"
        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>'
        tone="purple" />

    <x-dashboard.kpi-card
        title="انتظار ثبت حضور"
        :value="$waitingAttendance->count()"
        icon='<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>'
        :tone="$waitingAttendance->count() > 0 ? 'red' : 'default'" />
</div>

<div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">

    {{-- ── Today's Classes ─────────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="کلاس‌های امروز"
        :badge="$todaySessions->count() . ' کلاس'">
        @if($todaySessions->isEmpty())
            <x-dashboard.empty-state
                title="کلاسی برای امروز ندارید"
                description="روز آرامی داشته باشید!" />
        @else
        <ul class="divide-y divide-gray-800/40" role="list">
            @foreach($todaySessions as $session)
            @php
                $student = $session->student ?? $session->enrollment?->student;
                $instrument = $session->instrument ?? $session->enrollment?->instrument;
            @endphp
            <li class="flex items-center justify-between gap-4 py-3 transition hover:bg-gray-800/20">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-blue-500/15 text-sm font-bold text-blue-300">
                        {{ mb_substr($student?->full_name ?? '?', 0, 1) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-100">{{ $student?->full_name ?? 'هنرجو نامشخص' }}</p>
                        <p class="text-xs text-gray-500">{{ $instrument?->display_name ?? '—' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-mono tabular-nums text-xs text-gray-400">{{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}</span>
                    <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-semibold ring-1 {{ $statusColor[$session->status->value] ?? 'bg-gray-500/15 text-gray-400 ring-gray-500/30' }}">
                        {{ $session->status->label() }}
                    </span>
                    @if(in_array($session->status->value, ['completed']) && !$session->attendances->count())
                    <a href="{{ route('teacher.attendance', $session) }}"
                       class="rounded-lg bg-amber-500/20 px-2 py-0.5 text-xs font-medium text-amber-300 transition hover:bg-amber-500/30">
                        ثبت حضور
                    </a>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </x-dashboard.chart-container>

    {{-- ── Upcoming Classes ─────────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="کلاس‌های پیش رو"
        :badge="$upcomingSessions->count() . ' کلاس'">
        @if($upcomingSessions->isEmpty())
            <x-dashboard.empty-state
                title="کلاسی در ۷ روز آینده ندارید" />
        @else
        <ul class="divide-y divide-gray-800/40" role="list">
            @foreach($upcomingSessions as $session)
            @php
                $student    = $session->student ?? $session->enrollment?->student;
                $instrument = $session->instrument ?? $session->enrollment?->instrument;
                $dayName    = $jalaliDays[$session->session_date->englishDayOfWeek] ?? '';
                $jalaliDate = \App\Helpers\Jalalian::fromCarbon($session->session_date);
            @endphp
            <li class="flex items-center justify-between gap-3 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-14 text-center">
                        <p class="text-xs font-semibold text-blue-300">{{ $dayName }}</p>
                        <p class="text-xs text-gray-500 tabular-nums">{{ $jalaliDate }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-100">{{ $student?->full_name ?? '—' }}</p>
                        <p class="text-xs text-gray-500">{{ $instrument?->display_name ?? '—' }} · {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}</p>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </x-dashboard.chart-container>

    {{-- ── Attendance Waiting ───────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="منتظر ثبت حضور"
        :badge="$waitingAttendance->count()"
        :class="$waitingAttendance->count() > 0 ? 'ring-1 ring-amber-500/20' : ''">
        @if($waitingAttendance->isEmpty())
            <x-dashboard.empty-state
                title="همه حضور و غیاب‌ها ثبت شده‌اند"
                description="کارت تمیز است!" />
        @else
        <ul class="divide-y divide-gray-800/40" role="list">
            @foreach($waitingAttendance as $session)
            @php
                $student = $session->student ?? $session->enrollment?->student;
            @endphp
            <li class="flex items-center justify-between gap-3 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-100">{{ $student?->full_name ?? '—' }}</p>
                    <p class="text-xs text-gray-500">{{ \App\Helpers\Jalalian::fromCarbon($session->session_date) }}</p>
                </div>
                <a href="{{ route('teacher.attendance', $session) }}"
                   class="rounded-xl bg-amber-500/20 px-3 py-1.5 text-xs font-semibold text-amber-300 transition hover:bg-amber-500/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/50">
                    ثبت حضور
                </a>
            </li>
            @endforeach
        </ul>
        @endif
    </x-dashboard.chart-container>

    {{-- ── Assigned Students ────────────────────────────────────────────────── --}}
    <x-dashboard.chart-container title="هنرجویان من"
        :badge="$students->count() . ' هنرجو'"
        :actions='\'<a href="' . route("teacher.students") . '" class="text-xs text-blue-400 hover:text-blue-300">همه →</a>\''>
        @if($students->isEmpty())
            <x-dashboard.empty-state
                title="هنرجویی تخصیص نیافته است" />
        @else
        <ul class="divide-y divide-gray-800/40" role="list">
            @foreach($students->take(6) as $student)
            <li class="flex items-center gap-3 py-2.5 transition hover:bg-gray-800/20">
                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-700/60 text-xs font-bold text-blue-300">
                    {{ mb_substr($student->full_name, 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-100">{{ $student->full_name }}</p>
                    <p class="text-xs text-gray-500">{{ $student->phone }}</p>
                </div>
                <span class="text-xs text-gray-600">{{ $student->enrollments->count() }} ثبت‌نام</span>
            </li>
            @endforeach
        </ul>
        @if($students->count() > 6)
        <div class="mt-3 border-t border-gray-800/40 pt-3 text-center">
            <a href="{{ route('teacher.students') }}" class="text-xs text-blue-400 transition hover:text-blue-300">
                +{{ $students->count() - 6 }} هنرجوی دیگر
            </a>
        </div>
        @endif
        @endif
    </x-dashboard.chart-container>

</div>

{{-- ── Weekly Stats Bar ─────────────────────────────────────────────────────── --}}
<x-dashboard.chart-container title="آمار هفتگی" class="mt-5">
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @php
            $total = max($panel['weeklySessions'], 1);
        @endphp
        @foreach([
            ['label'=>'برگزارشده',    'value'=>$panel['completedSessions'], 'color'=>'bg-emerald-500'],
            ['label'=>'از دست‌رفته',  'value'=>$panel['missedSessions'],    'color'=>'bg-red-500'],
            ['label'=>'حاضر',         'value'=>$panel['presentCount'],       'color'=>'bg-blue-500'],
            ['label'=>'غایب',         'value'=>$panel['absentCount'],        'color'=>'bg-amber-500'],
        ] as $stat)
        <div class="rounded-xl border border-gray-800/40 bg-gray-800/20 p-4">
            <p class="text-2xl font-bold text-gray-100 tabular-nums">{{ $stat['value'] }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $stat['label'] }}</p>
            <div class="mt-2 h-1 w-full overflow-hidden rounded-full bg-gray-800">
                <div class="{{ $stat['color'] }} h-1 rounded-full transition-all duration-500"
                     style="width: {{ $total > 0 ? min(100, round(($stat['value'] / $total) * 100)) : 0 }}%"></div>
            </div>
        </div>
        @endforeach
    </div>
</x-dashboard.chart-container>
@endsection
