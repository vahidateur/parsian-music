@extends('layouts.dashboard')

@section('content')

{{-- Header --}}
<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">Attendance Report</h1>
    <p class="mt-1 text-sm text-gray-500">Aggregated attendance by student for the selected range.</p>
</div>

{{-- Date Range Filter --}}
<form method="GET" action="{{ route('admin.reports.attendance') }}" class="mb-6 flex flex-wrap items-end gap-4">
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">From</label>
        <input type="date" name="start_date" value="{{ $startDate->toDateString() }}"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">To</label>
        <input type="date" name="end_date" value="{{ $endDate->toDateString() }}"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
    </div>
    <button type="submit" class="rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">Apply</button>
    <a href="{{ route('admin.reports.attendance') }}" class="rounded-lg px-4 py-2.5 text-sm text-gray-500 transition hover:text-gray-300">Reset</a>
</form>

{{-- Totals Summary --}}
<div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <div class="rounded-xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Sessions</p>
        <p class="mt-2 text-2xl font-bold text-amber-100">{{ $totals['sessions'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Present</p>
        <p class="mt-2 text-2xl font-bold text-emerald-400">{{ $totals['present'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Absent</p>
        <p class="mt-2 text-2xl font-bold text-red-400">{{ $totals['absent'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Late</p>
        <p class="mt-2 text-2xl font-bold text-amber-400">{{ $totals['late'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Excused</p>
        <p class="mt-2 text-2xl font-bold text-sky-400">{{ $totals['excused'] }}</p>
    </div>
</div>

{{-- Per-Student Table --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="border-b border-gray-800/60 px-6 py-4">
        <h2 class="text-lg font-semibold text-amber-100">By Student</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-800/60 bg-gray-800/30">
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Total</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Present</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Absent</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Late</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Excused</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($rows as $row)
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-6 py-3.5 font-medium text-gray-100">{{ $row->student?->full_name ?? '—' }}</td>
                        <td class="px-6 py-3.5 text-right font-semibold text-gray-200">{{ $row->total }}</td>
                        <td class="px-6 py-3.5 text-right text-emerald-400">{{ $row->present }}</td>
                        <td class="px-6 py-3.5 text-right text-red-400">{{ $row->absent }}</td>
                        <td class="px-6 py-3.5 text-right text-amber-400">{{ $row->late }}</td>
                        <td class="px-6 py-3.5 text-right text-sky-400">{{ $row->excused }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">No attendance records in this range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
