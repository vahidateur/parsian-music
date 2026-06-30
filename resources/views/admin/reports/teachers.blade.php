@extends('layouts.dashboard')

@section('content')

{{-- Header --}}
<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">Teacher Performance Report</h1>
    <p class="mt-1 text-sm text-gray-500">
        Session delivery & attendance rate — last 30 days
        ({{ $startDate->format('Y/m/d') }} → {{ $endDate->format('Y/m/d') }}).
    </p>
</div>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-800/60 bg-gray-800/30">
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Teacher</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Total Sessions</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Completed</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Missed</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Attendance Rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($rows as $row)
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-6 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-amber-500/20 to-amber-600/10 text-xs font-bold text-amber-300">
                                    {{ strtoupper(mb_substr($row['teacher']->full_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-100">{{ $row['teacher']->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $row['teacher']->phone }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3.5 text-right font-semibold text-gray-200">{{ $row['total'] }}</td>
                        <td class="px-6 py-3.5 text-right text-emerald-400">{{ $row['completed'] }}</td>
                        <td class="px-6 py-3.5 text-right text-red-400">{{ $row['missed'] }}</td>
                        <td class="px-6 py-3.5">
                            <div class="flex items-center justify-end gap-3">
                                <div class="hidden h-2 w-24 overflow-hidden rounded-full bg-gray-800 sm:block">
                                    <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-400" style="width: {{ $row['rate'] }}%"></div>
                                </div>
                                <span class="w-10 text-right text-sm font-semibold {{ $row['rate'] >= 80 ? 'text-emerald-400' : ($row['rate'] >= 50 ? 'text-amber-300' : 'text-red-400') }}">
                                    {{ $row['rate'] }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">No teacher activity in the last 30 days.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
