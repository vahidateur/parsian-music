@extends('layouts.teacher')

@section('title', 'تقویم')

@section('content')
@php
    use App\Enums\SessionStatusEnum;

    $statusColor = [
        'completed' => 'bg-emerald-500/20 border-emerald-500/30 text-emerald-300',
        'scheduled' => 'bg-blue-500/20 border-blue-500/30 text-blue-300',
        'cancelled' => 'bg-red-500/20 border-red-500/30 text-red-400 line-through',
        'missed'    => 'bg-gray-600/20 border-gray-600/30 text-gray-500',
    ];
    $jalaliDays = ['Saturday'=>'شنبه','Sunday'=>'یکشنبه','Monday'=>'دوشنبه','Tuesday'=>'سه‌شنبه','Wednesday'=>'چهارشنبه','Thursday'=>'پنجشنبه','Friday'=>'جمعه'];
    $jalaliDaysShort = ['Saturday'=>'ش','Sunday'=>'ی','Monday'=>'د','Tuesday'=>'س','Wednesday'=>'چ','Thursday'=>'پ','Friday'=>'ج'];

    $prevWeek = $weekStart->subWeek()->toDateString();
    $nextWeek = $weekStart->addWeek()->toDateString();
@endphp

{{-- Header with week navigation --}}
<div class="mb-5 flex items-center justify-between">
    <x-dashboard.section-header
        title="تقویم هفتگی"
        :subtitle="'هفته ' . \App\Helpers\Jalalian::fromCarbon($weekStart) . ' تا ' . \App\Helpers\Jalalian::fromCarbon($weekEnd)">
    </x-dashboard.section-header>

    <div class="flex items-center gap-2">
        <a href="{{ route('teacher.calendar', ['week' => $prevWeek]) }}"
           class="inline-flex items-center gap-1.5 rounded-xl border border-gray-700/60 bg-gray-800/40 px-3 py-2 text-sm text-gray-300 transition hover:bg-gray-800/70 hover:text-blue-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            هفته قبل
        </a>
        <a href="{{ route('teacher.calendar') }}"
           class="rounded-xl border border-blue-500/30 bg-blue-500/10 px-3 py-2 text-sm font-medium text-blue-300 transition hover:bg-blue-500/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/50">
            این هفته
        </a>
        <a href="{{ route('teacher.calendar', ['week' => $nextWeek]) }}"
           class="inline-flex items-center gap-1.5 rounded-xl border border-gray-700/60 bg-gray-800/40 px-3 py-2 text-sm text-gray-300 transition hover:bg-gray-800/70 hover:text-blue-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/50">
            هفته بعد
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
    </div>
</div>

{{-- Weekly grid --}}
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7" role="grid" aria-label="تقویم هفتگی">
    @foreach($days as $day)
    @php
        $dateStr   = $day->toDateString();
        $isToday   = $day->isToday();
        $daySessions = $sessions[$dateStr] ?? collect();
        $dayName   = $jalaliDays[$day->englishDayOfWeek] ?? '';
        $dayShort  = $jalaliDaysShort[$day->englishDayOfWeek] ?? '';
        $jalaliDay = \App\Helpers\Jalalian::fromCarbon($day, 'j');
        $jalaliMon = \App\Helpers\Jalalian::fromCarbon($day, 'F');
    @endphp

    <div class="flex flex-col rounded-2xl border transition duration-150
                {{ $isToday ? 'border-blue-500/40 bg-blue-500/5 shadow-lg shadow-blue-500/10' : 'border-gray-800/50 bg-gray-800/10 hover:border-gray-700/60' }}"
         role="gridcell"
         aria-label="{{ $dayName }} {{ $jalaliDay }} {{ $jalaliMon }}">

        {{-- Day header --}}
        <div class="flex items-center justify-between px-3 py-3 {{ $isToday ? 'border-b border-blue-500/20' : 'border-b border-gray-800/40' }}">
            <div>
                <span class="text-xs font-semibold {{ $isToday ? 'text-blue-300' : 'text-gray-400' }}">{{ $dayName }}</span>
            </div>
            <div class="flex h-8 w-8 items-center justify-center rounded-full
                        {{ $isToday ? 'bg-blue-500 font-bold text-white' : 'text-gray-400' }}">
                <span class="text-sm tabular-nums">{{ $jalaliDay }}</span>
            </div>
        </div>

        {{-- Sessions --}}
        <div class="flex flex-1 flex-col gap-1.5 p-2">
            @forelse($daySessions as $session)
            @php
                $student    = $session->student ?? $session->enrollment?->student;
                $instrument = $session->instrument ?? $session->enrollment?->instrument;
                $hasAttend  = $session->attendances->isNotEmpty();
                $needsAttend= $session->status === SessionStatusEnum::Completed && !$hasAttend;
                $cardColor  = $statusColor[$session->status->value] ?? 'bg-gray-700/20 border-gray-700/30 text-gray-400';
            @endphp

            <a href="{{ route('teacher.attendance', $session) }}"
               class="group relative flex flex-col rounded-lg border p-2 transition duration-150 hover:scale-[1.02] hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/50 {{ $cardColor }}"
               title="{{ $student?->full_name }} — {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}">

                <div class="flex items-center justify-between gap-1">
                    <span class="truncate text-xs font-semibold leading-tight">
                        {{ $student?->full_name ?? 'نامشخص' }}
                    </span>
                    <span class="shrink-0 font-mono text-[10px] tabular-nums opacity-75">
                        {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}
                    </span>
                </div>

                @if($instrument)
                <span class="mt-0.5 truncate text-[10px] opacity-60">{{ $instrument->display_name }}</span>
                @endif

                @if($needsAttend)
                <span class="mt-1 inline-flex items-center gap-1 rounded bg-amber-500/30 px-1.5 py-0.5 text-[9px] font-bold text-amber-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    ثبت نشده
                </span>
                @elseif($hasAttend)
                <span class="mt-1 inline-flex items-center gap-1 text-[9px] opacity-60">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    ثبت شده
                </span>
                @endif
            </a>
            @empty
            <div class="flex flex-1 items-center justify-center py-4">
                <span class="text-xs text-gray-700">—</span>
            </div>
            @endforelse
        </div>

        {{-- Day total --}}
        @if($daySessions->count() > 0)
        <div class="border-t {{ $isToday ? 'border-blue-500/20' : 'border-gray-800/40' }} px-3 py-1.5">
            <span class="text-[10px] text-gray-600">{{ $daySessions->count() }} کلاس</span>
        </div>
        @endif
    </div>
    @endforeach
</div>

{{-- Legend --}}
<div class="mt-5 flex flex-wrap items-center gap-4 rounded-xl border border-gray-800/40 bg-gray-900/40 px-5 py-3">
    <span class="text-xs font-medium text-gray-500">راهنما:</span>
    @foreach([
        ['color' => 'bg-blue-400',    'label' => 'برنامه‌ریزی‌شده'],
        ['color' => 'bg-emerald-400', 'label' => 'برگزارشده'],
        ['color' => 'bg-red-400',     'label' => 'لغوشده'],
        ['color' => 'bg-gray-500',    'label' => 'از دست‌رفته'],
        ['color' => 'bg-amber-400 animate-pulse', 'label' => 'نیاز به ثبت حضور'],
    ] as $item)
    <span class="flex items-center gap-1.5 text-xs text-gray-400">
        <span class="h-2.5 w-2.5 rounded-full {{ $item['color'] }}"></span>
        {{ $item['label'] }}
    </span>
    @endforeach
</div>
@endsection
