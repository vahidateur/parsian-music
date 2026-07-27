@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.weekly_calendar') }}@endsection

@section('content')
@php
    $btnNav     = "inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-2 text-sm text-gray-300 transition duration-200 hover:border-gray-600 hover:bg-gray-800/60 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/30";
    $btnToday   = "inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-3 py-2 text-sm font-semibold text-gray-950 shadow-sm transition duration-200 hover:from-amber-500 hover:to-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900";
    $btnSecondary = "inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm font-medium text-gray-300 transition duration-200 hover:border-gray-600 hover:bg-gray-800/60 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/30";
    $btnClear   = "rounded-lg px-4 py-2.5 text-sm text-gray-500 transition duration-200 hover:text-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/20";
    $inputClass = "block w-full rounded-lg border border-gray-700/60 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition duration-200 focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20";

    $statusColors = [
        'scheduled' => 'border-sky-500/40 bg-sky-500/10 text-sky-300 hover:bg-sky-500/15',
        'completed' => 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/15',
        'cancelled' => 'border-red-500/40 bg-red-500/10 text-red-300 hover:bg-red-500/15',
        'missed'    => 'border-orange-500/40 bg-orange-500/10 text-orange-300 hover:bg-orange-500/15',
        'makeup'    => 'border-violet-500/40 bg-violet-500/10 text-violet-300 hover:bg-violet-500/15',
    ];
@endphp

{{-- Header --}}
<x-dashboard.section-header
    :title="__('admin.weekly_calendar')"
    :subtitle="\App\Helpers\Jalalian::fromCarbon($weekStart) . ' — ' . \App\Helpers\Jalalian::fromCarbon($weekEnd)"
>
    <x-slot:actions>
        <nav class="flex items-center gap-2" aria-label="ناوبری هفته">
            <a href="{{ route('admin.calendar.index', ['week' => $prevWeek]) }}" class="{{ $btnNav }}" aria-label="{{ __('admin.prev_week') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                {{ __('admin.prev_week') }}
            </a>
            <a href="{{ route('admin.calendar.index') }}" class="{{ $btnToday }}">
                {{ __('admin.today_nav') }}
            </a>
            <a href="{{ route('admin.calendar.index', ['week' => $nextWeek]) }}" class="{{ $btnNav }}" aria-label="{{ __('admin.next_week') }}">
                {{ __('admin.next_week') }}
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
        </nav>
    </x-slot:actions>
</x-dashboard.section-header>

{{-- Filters --}}
<x-dashboard.chart-container class="mb-5" title="{{ __('admin.filter') }}" aria-label="فیلتر تقویم">
    <form method="GET" action="{{ route('admin.calendar.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end" role="search">
        <input type="hidden" name="week" value="{{ request('week') }}">

        <div>
            <label for="cal-teacher" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.teacher') }}</label>
            <select id="cal-teacher" name="teacher_id" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_teachers') }}</option>
                @foreach ($teachers ?? [] as $teacher)
                    <option value="{{ $teacher->id }}" {{ (string) request('teacher_id') === (string) $teacher->id ? 'selected' : '' }}>{{ $teacher->full_name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="cal-student" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.student') }}</label>
            <select id="cal-student" name="student_id" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_students') }}</option>
                @foreach ($students ?? [] as $student)
                    <option value="{{ $student->id }}" {{ (string) request('student_id') === (string) $student->id ? 'selected' : '' }}>{{ $student->full_name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="cal-room" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.room') }}</label>
            <select id="cal-room" name="room" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_rooms') }}</option>
                @foreach (['A101', 'A102', 'A103'] as $r)
                    <option value="{{ $r }}" {{ request('room') === $r ? 'selected' : '' }}>{{ $r }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="{{ $btnSecondary }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 0z"/></svg>
                {{ __('admin.filter') }}
            </button>
            <a href="{{ route('admin.calendar.index') }}" class="{{ $btnClear }}">{{ __('admin.clear') }}</a>
        </div>
    </form>
</x-dashboard.chart-container>

{{-- Status Legend --}}
<div class="mb-4 flex flex-wrap items-center gap-3 px-1" aria-label="راهنمای رنگ وضعیت" role="list">
    @foreach ([
        'scheduled' => ['label' => __('admin.session_statuses.scheduled'), 'dot' => 'bg-sky-400'],
        'completed' => ['label' => __('admin.session_statuses.completed'), 'dot' => 'bg-emerald-400'],
        'cancelled' => ['label' => __('admin.session_statuses.cancelled'), 'dot' => 'bg-red-400'],
        'missed'    => ['label' => __('admin.session_statuses.missed'),    'dot' => 'bg-orange-400'],
    ] as $legendItem)
        <div role="listitem" class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-full {{ $legendItem['dot'] }}" aria-hidden="true"></span>
            <span class="text-xs text-gray-500">{{ $legendItem['label'] }}</span>
        </div>
    @endforeach
</div>

{{-- Calendar Grid --}}
<x-dashboard.chart-container aria-label="تقویم هفتگی">
    <div class="-mx-4 -my-4 overflow-x-auto sm:-mx-6 sm:-my-6">
        <table class="w-full min-w-[700px] border-collapse" dir="rtl" role="grid" aria-label="جدول زمان‌بندی هفتگی">

            {{-- Day Headers --}}
            <thead>
                <tr role="row">
                    <th scope="col" class="calendar__time-column w-16 border-b border-s border-gray-800/60 bg-gray-800/80 px-2 py-3 text-xs font-medium text-gray-500">
                        {{ __('admin.time') }}
                    </th>
                    @foreach ($days as $day)
                        @php
                            $isToday = $day->isToday();
                            $jalaliDate = \App\Helpers\Jalalian::fromCarbon($day);
                            $persianDayName = \App\Helpers\Jalalian::dayOfWeek($day);
                        @endphp
                        <th scope="col" role="columnheader"
                            class="border-b border-s border-gray-800/60 px-2 py-3 text-center transition duration-200 {{ $isToday ? 'bg-amber-500/5' : '' }}">
                            <div class="text-xs font-medium uppercase tracking-wider {{ $isToday ? 'text-amber-400' : 'text-gray-500' }}">
                                {{ $persianDayName }}
                            </div>
                            <div class="mt-0.5 text-sm font-semibold {{ $isToday ? 'text-amber-300' : 'text-gray-300' }}">
                                {{ $jalaliDate }}
                            </div>
                            @if ($isToday)
                                <div class="mx-auto mt-1 h-1 w-6 rounded-full bg-amber-500/60"></div>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>

            {{-- Time Slot Rows --}}
            <tbody class="divide-y divide-gray-800/40">
                @foreach ($hours as $hour)
                    @php
                        $label      = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                        $isBookable = $hour >= 15 && $hour <= 21;
                    @endphp
                    <tr role="row" class="group">
                        <td class="calendar__time-column border-s border-gray-800/60 bg-gray-800/80 px-2 py-2 align-top text-xs tabular-nums {{ $isBookable ? 'text-amber-400/80' : 'text-gray-600' }}">
                            {{ $label }}
                        </td>
                        @foreach ($days as $day)
                            @php
                                $dateKey     = $day->toDateString();
                                $daySessions = $grid[$dateKey][$hour] ?? [];
                                $isToday     = $day->isToday();
                            @endphp
                            <td role="gridcell"
                                class="calendar__grid-cell border-s border-gray-800/40 p-1 align-top transition duration-150 {{ $isToday ? 'bg-amber-500/[0.02]' : '' }} group-hover:bg-gray-800/10">
                                @foreach ($daySessions as $session)
                                    @php
                                        $sv = $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status;
                                        $cardColor = $statusColors[$sv] ?? 'border-gray-600/50 bg-gray-700/30 text-gray-300 hover:bg-gray-700/40';
                                        $sName = $session->enrollment?->student?->full_name ?? $session->student?->full_name ?? '—';
                                        $tName = $session->enrollment?->teacher?->full_name ?? $session->teacher?->full_name ?? null;
                                        $iName = $session->enrollment?->instrument?->display_name ?? $session->instrument?->display_name ?? null;
                                        $sTime = is_string($session->start_time) ? $session->start_time : $session->start_time?->format('H:i');
                                    @endphp
                                    <div class="mb-1 cursor-default rounded-lg border px-2 py-1.5 text-xs transition duration-150 {{ $cardColor }}"
                                         role="article" aria-label="{{ $sName }} — {{ $sv }}">
                                        <div class="truncate font-semibold leading-tight">{{ $sName }}</div>
                                        @if ($tName)
                                            <div class="mt-0.5 truncate opacity-75">{{ $tName }}</div>
                                        @endif
                                        @if ($iName)
                                            <div class="mt-0.5 truncate opacity-60">{{ $iName }}</div>
                                        @endif
                                        <div class="mt-0.5 font-mono tabular-nums opacity-60">{{ $sTime }}@if($session->room) · {{ $session->room }}@endif</div>
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-dashboard.chart-container>

@endsection
