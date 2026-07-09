@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.attendance_report') }}@endsection

@section('content')
@php
    $btnSecondary = "inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm font-medium text-gray-300 transition duration-200 hover:border-gray-600 hover:bg-gray-800/60 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/30";
    $btnClear     = "rounded-lg px-4 py-2.5 text-sm text-gray-500 transition duration-200 hover:text-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/20";
    $inputClass   = "block w-full rounded-lg border border-gray-700/60 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition duration-200 focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20";
@endphp

{{-- Header --}}
<x-dashboard.section-header
    :title="__('admin.attendance_report')"
    :subtitle="__('admin.attendance_report_desc')"
/>

{{-- Date Filter --}}
<x-dashboard.chart-container class="mb-5" title="{{ __('admin.filter') }}" aria-label="بازه تاریخ گزارش">
    <form method="GET" action="{{ route('admin.reports.attendance') }}" class="flex flex-wrap items-end gap-4" role="search">
        <div>
            <label for="start-date" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.from') }}</label>
            <input id="start-date" type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="{{ $inputClass }} w-auto">
        </div>
        <div>
            <label for="end-date" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.to') }}</label>
            <input id="end-date" type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="{{ $inputClass }} w-auto">
        </div>
        <div class="flex gap-3">
            <button type="submit" class="{{ $btnSecondary }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('admin.apply') }}
            </button>
            <a href="{{ route('admin.reports.attendance') }}" class="{{ $btnClear }}">{{ __('admin.reset') }}</a>
        </div>
    </form>
</x-dashboard.chart-container>

{{-- KPI Summary --}}
<div class="mb-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <x-dashboard.kpi-card
        :label="__('admin.total_sessions')"
        :value="$totals['sessions']"
        tone="amber"
    >
        <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg></x-slot:icon>
    </x-dashboard.kpi-card>

    <x-dashboard.kpi-card
        :label="__('admin.present')"
        :value="$totals['present']"
        tone="emerald"
    >
        <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
    </x-dashboard.kpi-card>

    <x-dashboard.kpi-card
        :label="__('admin.absent')"
        :value="$totals['absent']"
        tone="rose"
    >
        <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
    </x-dashboard.kpi-card>

    <x-dashboard.kpi-card
        :label="__('admin.late')"
        :value="$totals['late']"
        tone="amber"
    >
        <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
    </x-dashboard.kpi-card>

    <x-dashboard.kpi-card
        :label="__('admin.excused')"
        :value="$totals['excused']"
        tone="sky"
    >
        <x-slot:icon><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg></x-slot:icon>
    </x-dashboard.kpi-card>
</div>

{{-- Per-Student Table --}}
<x-dashboard.chart-container :title="__('admin.by_student')">
    @if ($rows->count())
        <div class="-mx-4 -my-4 overflow-x-auto sm:-mx-6 sm:-my-6">
            <table class="w-full text-start text-sm" role="table">
                <thead>
                    <tr class="border-b border-gray-800/60 bg-gray-800/30">
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.student') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500 tabular-nums">{{ __('admin.total') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-emerald-600">{{ __('admin.present') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-red-600">{{ __('admin.absent') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-amber-600">{{ __('admin.late') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-sky-600">{{ __('admin.excused') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($rows as $row)
                        <tr class="transition duration-150 hover:bg-gray-800/25">
                            <td class="px-5 py-3.5 font-medium text-gray-100">{{ $row->student?->full_name ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-semibold tabular-nums text-gray-200">{{ $row->total }}</td>
                            <td class="px-5 py-3.5 tabular-nums text-emerald-400">{{ $row->present }}</td>
                            <td class="px-5 py-3.5 tabular-nums text-red-400">{{ $row->absent }}</td>
                            <td class="px-5 py-3.5 tabular-nums text-amber-400">{{ $row->late }}</td>
                            <td class="px-5 py-3.5 tabular-nums text-sky-400">{{ $row->excused }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-dashboard.empty-state :message="__('admin.no_attendance_records')" compact>
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-dashboard.empty-state>
    @endif
</x-dashboard.chart-container>

@endsection
