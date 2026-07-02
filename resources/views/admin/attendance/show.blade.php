@extends('layouts.dashboard')

@section('content')

@php
    $statusConfig = [
        'present' => ['label' => __('admin.present'), 'active' => 'bg-emerald-500 text-white border-emerald-500 shadow-emerald-500/30', 'badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30', 'chart' => '#10b981'],
        'absent'  => ['label' => __('admin.absent'),  'active' => 'bg-red-500 text-white border-red-500 shadow-red-500/30',       'badge' => 'bg-red-500/10 text-red-400 border-red-500/30',       'chart' => '#ef4444'],
        'late'    => ['label' => __('admin.late'),    'active' => 'bg-amber-500 text-white border-amber-500 shadow-amber-500/30', 'badge' => 'bg-amber-500/10 text-amber-300 border-amber-500/30', 'chart' => '#f59e0b'],
        'excused' => ['label' => __('admin.excused'), 'active' => 'bg-sky-500 text-white border-sky-500 shadow-sky-500/30',       'badge' => 'bg-sky-500/10 text-sky-400 border-sky-500/30',       'chart' => '#0ea5e9'],
    ];

    // Chart data
    $chartData = [
        'present' => $summary['present'],
        'absent'  => $summary['absent'],
        'late'    => $summary['late'],
        'excused' => $summary['excused'],
    ];
    $totalMarked = array_sum($chartData);
@endphp

{{-- Header --}}
<div class="mb-8">
    <a href="{{ route('admin.sessions.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm text-gray-400 transition hover:text-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
        {{ __('admin.back_to_sessions') }}
    </a>

    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.attendance') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('admin.attendance_desc') }}</p>
        </div>

        {{-- Session Info Pills --}}
        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-1.5 text-xs font-medium text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-400/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                {{ \App\Helpers\Jalalian::fromCarbon($session->session_date) ?? '—' }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-1.5 text-xs font-medium text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-400/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                @php
                    $startTime = is_string($session->start_time) ? $session->start_time : $session->start_time?->format('H:i');
                @endphp
                {{ $startTime ?? '—' }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-1.5 text-xs font-medium text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-400/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5A7.5 7.5 0 1119.5 10.5z" /></svg>
                {{ __('admin.rooms.' . $session->room) ?? $session->room }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-1.5 text-xs font-medium text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-400/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                {{ $session->enrollment?->teacher?->full_name ?? '—' }}
            </span>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="mt-6">
        <div class="mb-1.5 flex items-center justify-between">
            <span class="text-xs font-medium text-gray-400">{{ __('admin.attendance_completion') }}</span>
            <span class="text-xs font-semibold text-amber-300">{{ $completion }}%</span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-800">
            <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-400 transition-all duration-700 ease-out" style="width: {{ $completion }}%"></div>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- Student Cards --}}
    <div class="xl:col-span-2">
        @if ($students->isEmpty())
            <div class="rounded-2xl border border-gray-800/60 bg-gray-900/50 px-6 py-16 text-center text-gray-500 shadow-xl backdrop-blur-sm">
                {{ __('admin.no_students_enrolled') }}
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($students as $student)
                    @php
                        $current = $student->attendance_status;
                    @endphp
                    <div class="group relative overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm transition-all duration-300 hover:border-amber-500/30 hover:shadow-amber-500/5">
                        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-500/[0.06] opacity-0 blur-2xl transition-opacity duration-500 group-hover:opacity-100"></div>

                        <div class="relative flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-amber-500/20 to-amber-600/10 text-sm font-bold text-amber-300">
                                    {{ strtoupper(mb_substr($student->full_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-100">{{ $student->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $student->phone }}</p>
                                </div>
                            </div>

                            {{-- Status Badge --}}
                            @if ($current && isset($statusConfig[$current]))
                                <span class="rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusConfig[$current]['badge'] }} animate-pulse-once">
                                    {{ $statusConfig[$current]['label'] }}
                                </span>
                            @else
                                <span class="rounded-full border border-gray-700/60 bg-gray-800/40 px-2.5 py-0.5 text-xs font-medium text-gray-500">
                                    {{ __('admin.unmarked') }}
                                </span>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <form method="POST" action="{{ route('admin.sessions.attendance.store', $session) }}" class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @csrf
                            <input type="hidden" name="student_id" value="{{ $student->id }}">

                            @foreach ($statusConfig as $key => $cfg)
                                <button type="submit" name="status" value="{{ $key }}"
                                        class="rounded-lg border px-2 py-2 text-xs font-semibold transition-all duration-200 {{
                                            $current === $key
                                                ? $cfg['active'] . ' shadow-lg'
                                                : 'border-gray-700/60 bg-gray-800/30 text-gray-400 hover:bg-gray-800/60 hover:text-gray-200'
                                        }}">
                                    {{ $cfg['label'] }}
                                </button>
                            @endforeach
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Summary Chart --}}
    <div class="xl:col-span-1">
        <div class="sticky top-24 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
            <div class="border-b border-gray-800/60 px-6 py-4">
                <h2 class="text-base font-semibold text-amber-100">{{ __('admin.summary') }}</h2>
            </div>

            <div class="px-6 py-6">

                {{-- CSS Conic-Gradient Pie Chart --}}
                <div class="flex justify-center">
                    @php
                        $segments = [];
                        $offset = 0;
                        foreach ($chartData as $status => $count) {
                            if ($totalMarked > 0) {
                                $pct = ($count / $totalMarked) * 100;
                                $segments[] = $statusConfig[$status]['chart'] . ' ' . $offset . '% ' . ($offset + $pct) . '%';
                                $offset += $pct;
                            }
                        }
                        $conicGradient = $totalMarked > 0
                            ? 'conic-gradient(' . implode(', ', $segments) . ')'
                            : 'conic-gradient(#374151 0% 100%)';
                    @endphp
                    <div class="relative h-40 w-40">
                        <div class="h-40 w-40 rounded-full shadow-inner transition-all duration-700" style="background: {{ $conicGradient }}"></div>
                        <div class="absolute inset-4 flex flex-col items-center justify-center rounded-full bg-gray-950">
                            <span class="text-2xl font-bold text-amber-100">{{ $totalMarked }}</span>
                            <span class="text-xs text-gray-500">{{ __('admin.marked') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="mt-6 space-y-2.5">
                    @foreach ($chartData as $status => $count)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="h-3 w-3 rounded-full" style="background: {{ $statusConfig[$status]['chart'] }}"></span>
                                <span class="text-sm text-gray-300">{{ $statusConfig[$status]['label'] }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-100">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Total --}}
                <div class="mt-5 border-t border-gray-800/60 pt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-400">{{ __('admin.total_students_count') }}</span>
                        <span class="text-sm font-semibold text-gray-100">{{ $students->count() }}</span>
                    </div>
                    <div class="mt-1 flex items-center justify-between">
                        <span class="text-sm text-gray-400">{{ __('admin.unmarked_count_label') }}</span>
                        <span class="text-sm font-semibold text-gray-100">{{ $students->count() - $totalMarked }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes pulse-once {
        0% { transform: scale(0.9); opacity: 0; }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); opacity: 1; }
    }
    .animate-pulse-once { animation: pulse-once 0.4s ease-out; }
</style>

@endsection
