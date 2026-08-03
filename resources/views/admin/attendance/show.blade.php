@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.attendance') }}@endsection

@section('content')
@php
    $statusConfig = [
        'present' => ['label' => __('admin.present'), 'active' => 'bg-emerald-500 text-white border-emerald-500 shadow-emerald-500/30', 'badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30', 'chart' => '#10b981'],
        'absent'  => ['label' => __('admin.absent'),  'active' => 'bg-red-500 text-white border-red-500 shadow-red-500/30',             'badge' => 'bg-red-500/10 text-red-400 border-red-500/30',         'chart' => '#ef4444'],
        'late'    => ['label' => __('admin.late'),    'active' => 'bg-amber-500 text-white border-amber-500 shadow-amber-500/30',       'badge' => 'bg-amber-500/10 text-amber-300 border-amber-500/30',   'chart' => '#f59e0b'],
        'excused' => ['label' => __('admin.excused'), 'active' => 'bg-sky-500 text-white border-sky-500 shadow-sky-500/30',             'badge' => 'bg-sky-500/10 text-sky-400 border-sky-500/30',         'chart' => '#0ea5e9'],
    ];
    $chartData   = ['present' => $summary['present'], 'absent' => $summary['absent'], 'late' => $summary['late'], 'excused' => $summary['excused']];
    $totalMarked = array_sum($chartData);
    $startTime   = is_string($session->start_time) ? $session->start_time : $session->start_time?->format('H:i');
@endphp

{{-- Back link --}}
<a href="{{ route('admin.sessions.index') }}"
   class="mb-5 inline-flex items-center gap-1.5 text-sm text-gray-400 transition duration-150 hover:text-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/40">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
    {{ __('admin.back_to_sessions') }}
</a>

{{-- Page Header --}}
<x-dashboard.section-header headingLevel="h1"
    :title="__('admin.attendance')"
    :subtitle="__('admin.attendance_desc')"
>
    <x-slot:actions>
        {{-- Session Info Pills --}}
        <div class="flex flex-wrap gap-2" role="list" aria-label="اطلاعات جلسه">
            <span role="listitem" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-1.5 text-xs font-medium text-gray-300">
                <svg class="h-3.5 w-3.5 text-amber-400/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                {{ \App\Helpers\Jalalian::fromCarbon($session->session_date) ?? '—' }}
            </span>
            <span role="listitem" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-1.5 text-xs font-medium text-gray-300">
                <svg class="h-3.5 w-3.5 text-amber-400/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="tabular-nums" dir="ltr">{{ $startTime ?? '—' }}</span>
            </span>
            <span role="listitem" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-1.5 text-xs font-medium text-gray-300">
                <svg class="h-3.5 w-3.5 text-amber-400/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5A7.5 7.5 0 1119.5 10.5z"/></svg>
                {{ $session->room ?? '—' }}
            </span>
            <span role="listitem" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-3 py-1.5 text-xs font-medium text-gray-300">
                <svg class="h-3.5 w-3.5 text-amber-400/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                {{ $session->enrollment?->teacher?->full_name ?? '—' }}
            </span>
        </div>
    </x-slot:actions>
</x-dashboard.section-header>

{{-- Completion Progress --}}
<div class="mb-5 overflow-hidden rounded-xl border border-gray-800/60 bg-gray-900/50 px-5 py-4 shadow-lg backdrop-blur-sm">
    <div class="mb-2 flex items-center justify-between">
        <span class="text-xs font-medium text-gray-400">{{ __('admin.attendance_completion') }}</span>
        <span class="text-xs font-semibold text-amber-300 tabular-nums">{{ $completion }}%</span>
    </div>
    <div class="admin-progress-track" role="progressbar" aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100">
        <progress class="admin-progress admin-progress--accent" max="100" value="{{ $completion }}">{{ $completion }}%</progress>
    </div>
</div>

{{-- Feedback_Channel: shared success / failure / validation --}}
<x-admin.feedback />

<div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

    {{-- Student Cards --}}
    <div class="xl:col-span-2">
        @if ($students->isEmpty())
            <x-dashboard.empty-state
                :title="__('admin.no_students_enrolled')"
                message="این جلسه هیچ هنرجویی ندارد."
            >
                <x-slot:icon>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </x-slot:icon>
            </x-dashboard.empty-state>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" role="list" aria-label="لیست هنرجویان">
                @foreach ($students as $student)
                    @php $current = $student->attendance_status; @endphp
                    <div role="listitem"
                         class="group relative overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl shadow-black/10 backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-500/30 hover:shadow-amber-500/5">
                        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-500/[0.06] opacity-0 blur-2xl transition-opacity duration-500 group-hover:opacity-100" aria-hidden="true"></div>

                        {{-- Student Identity --}}
                        <div class="relative flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-500/20 to-amber-600/10 text-sm font-bold text-amber-300" aria-hidden="true">
                                    {{ mb_substr($student->full_name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-gray-100">{{ $student->full_name }}</p>
                                    <p class="text-xs text-gray-500 tabular-nums" dir="ltr">{{ $student->phone }}</p>
                                </div>
                            </div>

                            {{-- Current Status Badge --}}
                            @if ($current && isset($statusConfig[$current]))
                                <span class="rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusConfig[$current]['badge'] }}">
                                    {{ $statusConfig[$current]['label'] }}
                                </span>
                            @else
                                <span class="rounded-full border border-gray-700/60 bg-gray-800/40 px-2.5 py-0.5 text-xs font-medium text-gray-500">
                                    {{ __('admin.unmarked') }}
                                </span>
                            @endif
                        </div>

                        {{-- Attendance Action Buttons --}}
                        <form method="POST" action="{{ route('admin.sessions.attendance.store', $session) }}"
                              class="relative mt-4 grid grid-cols-4 gap-2">
                            @csrf
                            <input type="hidden" name="student_id" value="{{ $student->id }}">

                            @foreach ($statusConfig as $key => $cfg)
                                <button type="submit" name="status" value="{{ $key }}"
                                        aria-label="{{ $cfg['label'] }} — {{ $student->full_name }}"
                                        aria-pressed="{{ $current === $key ? 'true' : 'false' }}"
                                        class="rounded-lg border py-2 text-xs font-semibold transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900 {{
                                            $current === $key
                                                ? $cfg['active'] . ' shadow-lg focus-visible:ring-white/30'
                                                : 'border-gray-700/60 bg-gray-800/30 text-gray-400 hover:bg-gray-800/60 hover:text-gray-200 focus-visible:ring-gray-500/40'
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

    {{-- Summary Sidebar --}}
    <div class="xl:col-span-1">
        <div class="admin-attendance__summary">
            <x-dashboard.chart-container :title="__('admin.summary')" :badge="$totalMarked . '/' . $students->count()">

                {{-- CSS Conic-Gradient Pie Chart --}}
                <div class="flex justify-center">
                    @php
                        $chartSegments = [];
                        $offset = 0;
                        foreach ($chartData as $status => $count) {
                            $percentage = $totalMarked > 0 ? ($count / $totalMarked) * 100 : 0;
                            if ($percentage > 0) {
                                $chartSegments[] = [
                                    'status' => $status,
                                    'percentage' => $percentage,
                                    'offset' => $offset,
                                ];
                                $offset += $percentage;
                            }
                        }
                        if ($chartSegments === []) {
                            $chartSegments[] = ['status' => 'empty', 'percentage' => 100, 'offset' => 0];
                        }
                    @endphp
                    <div class="admin-attendance__chart" role="img" aria-label="نمودار دایره‌ای حضور و غیاب">
                        <svg class="admin-attendance__chart-svg" viewBox="0 0 42 42" aria-hidden="true">
                            <circle class="admin-attendance__chart-track" cx="21" cy="21" r="15.9155" pathLength="100" />
                            @foreach ($chartSegments as $segment)
                                <circle
                                    class="admin-attendance__chart-segment admin-attendance__chart-segment--{{ $segment['status'] }}"
                                    cx="21"
                                    cy="21"
                                    r="15.9155"
                                    pathLength="100"
                                    stroke-dasharray="{{ $segment['percentage'] }} {{ 100 - $segment['percentage'] }}"
                                    stroke-dashoffset="{{ 100 - $segment['offset'] }}"
                                />
                            @endforeach
                        </svg>
                        <div class="admin-attendance__chart-center">
                            <span class="text-2xl font-bold tabular-nums text-amber-100">{{ $totalMarked }}</span>
                            <span class="text-xs text-gray-500">{{ __('admin.marked') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Legend --}}
                <dl class="mt-6 space-y-2.5">
                    @foreach ($chartData as $status => $count)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="admin-attendance__legend-dot admin-attendance__legend-dot--{{ $status }}" aria-hidden="true"></span>
                                <dt class="text-sm text-gray-300">{{ $statusConfig[$status]['label'] }}</dt>
                            </div>
                            <dd class="text-sm font-semibold tabular-nums text-gray-100">{{ $count }}</dd>
                        </div>
                    @endforeach
                </dl>

                {{-- Totals --}}
                <div class="mt-5 space-y-1.5 border-t border-gray-800/60 pt-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-400">{{ __('admin.total_students_count') }}</span>
                        <span class="font-semibold tabular-nums text-gray-100">{{ $students->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-400">{{ __('admin.unmarked_count_label') }}</span>
                        <span class="font-semibold tabular-nums {{ ($students->count() - $totalMarked) > 0 ? 'text-amber-300' : 'text-gray-100' }}">
                            {{ $students->count() - $totalMarked }}
                        </span>
                    </div>
                </div>
            </x-dashboard.chart-container>
        </div>
    </div>

</div>


@endsection
