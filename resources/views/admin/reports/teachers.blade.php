@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.teacher_performance_report') }}@endsection

@section('content')
@php
    $rateColor = fn ($r) => $r >= 80 ? 'text-emerald-400' : ($r >= 50 ? 'text-amber-300' : 'text-red-400');
    $rateTone  = fn ($r) => $r >= 80 ? 'admin-progress--success' : ($r >= 50 ? 'admin-progress--warning' : 'admin-progress--danger');
@endphp

{{-- Header --}}
<x-dashboard.section-header
    :title="__('admin.teacher_performance_report')"
    :subtitle="__('admin.teacher_performance_desc')"
    :badge="\App\Helpers\Jalalian::fromCarbon($startDate) . ' ← ' . \App\Helpers\Jalalian::fromCarbon($endDate)"
/>

{{-- Summary KPIs --}}
@if ($rows->count())
    @php
        $avgRate   = $rows->avg('rate');
        $topTeacher = $rows->sortByDesc('rate')->first();
        $totalSess  = $rows->sum('total');
    @endphp
    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-dashboard.kpi-card
            :label="__('admin.total_sessions')"
            :value="$totalSess"
            hint="در این بازه زمانی"
            tone="amber"
        >
            <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>

        <x-dashboard.kpi-card
            :label="__('admin.attendance_rate')"
            :value="round($avgRate) . '%'"
            hint="میانگین همه اساتید"
            tone="emerald"
        >
            <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>

        <x-dashboard.kpi-card
            label="بهترین استاد"
            :value="$topTeacher ? $topTeacher['teacher']->full_name : '—'"
            :hint="$topTeacher ? $topTeacher['rate'] . '% ' . __('admin.attendance_rate') : ''"
            tone="sky"
        >
            <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg></x-slot:icon>
        </x-dashboard.kpi-card>
    </div>
@endif

{{-- Teacher Table --}}
<x-dashboard.chart-container :title="__('admin.teacher_performance_report')" :badge="$rows->count() . ' استاد'">
    @if ($rows->count())
        <div class="-mx-4 -my-4 overflow-x-auto sm:-mx-6 sm:-my-6">
            <table class="w-full text-start text-sm" role="table">
                <thead>
                    <tr class="border-b border-gray-800/60 bg-gray-800/30">
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.teacher') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.total_sessions') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-emerald-600">{{ __('admin.completed') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-red-600">{{ __('admin.missed') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.attendance_rate') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($rows as $row)
                        <tr class="transition duration-150 hover:bg-gray-800/25">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-500/20 to-amber-600/10 text-xs font-bold text-amber-300" aria-hidden="true">
                                        {{ mb_substr($row['teacher']->full_name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-gray-100">{{ $row['teacher']->full_name }}</p>
                                        <p class="text-xs text-gray-500" dir="ltr">{{ $row['teacher']->phone }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-semibold tabular-nums text-gray-200">{{ $row['total'] }}</td>
                            <td class="px-5 py-3.5 tabular-nums text-emerald-400">{{ $row['completed'] }}</td>
                            <td class="px-5 py-3.5 tabular-nums text-red-400">{{ $row['missed'] }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="admin-progress-track admin-progress-track--compact hidden sm:block" role="progressbar" aria-valuenow="{{ $row['rate'] }}" aria-valuemin="0" aria-valuemax="100">
                                        <progress class="admin-progress {{ $rateTone($row['rate']) }}" max="100" value="{{ $row['rate'] }}">{{ $row['rate'] }}%</progress>
                                    </div>
                                    <span class="w-12 text-sm font-bold tabular-nums {{ $rateColor($row['rate']) }}">
                                        {{ $row['rate'] }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-dashboard.empty-state :message="__('admin.no_teacher_activity')" compact>
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                </svg>
            </x-slot:icon>
        </x-dashboard.empty-state>
    @endif
</x-dashboard.chart-container>

@endsection
