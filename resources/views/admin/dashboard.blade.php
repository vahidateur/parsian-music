@extends('layouts.dashboard')

@section('content')

{{-- Page Heading --}}
<div class="mb-8 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-amber-100">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Welcome back. Here is what's happening at the academy today.</p>
    </div>
    <div class="flex flex-col gap-4 sm:items-end">
        <p class="text-sm text-gray-500">{{ now()->format('Y/m/d') }}</p>

        {{-- Quick Actions --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.students.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400 hover:shadow-amber-500/20 focus:outline-none focus:ring-2 focus:ring-amber-500/40">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New Student
            </a>
            <a href="{{ route('admin.teachers.create') }}" class="inline-flex items-center gap-2 rounded-lg border border-amber-500/30 bg-amber-500/[0.04] px-4 py-2.5 text-sm font-semibold text-amber-200 shadow-lg transition hover:border-amber-500/50 hover:bg-amber-500/[0.08] focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New Teacher
            </a>
            <a href="{{ route('admin.sessions.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm font-semibold text-gray-200 shadow-lg transition hover:border-gray-600 hover:bg-gray-800/70 focus:outline-none focus:ring-2 focus:ring-gray-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Sessions
            </a>
        </div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">

    {{-- Total Students --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 p-6 shadow-xl backdrop-blur-sm transition hover:border-amber-500/30">
        <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-500/10 blur-2xl"></div>
        <div class="relative">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Students</p>
            <p class="mt-3 text-4xl font-bold text-amber-100">{{ $totalStudents }}</p>
            <p class="mt-2 text-xs text-gray-500">Total registered</p>
        </div>
    </div>

    {{-- Active Teachers --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 p-6 shadow-xl backdrop-blur-sm transition hover:border-amber-500/30">
        <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-500/10 blur-2xl"></div>
        <div class="relative">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Active Teachers</p>
            <p class="mt-3 text-4xl font-bold text-amber-100">{{ $activeTeachers }}</p>
            <p class="mt-2 text-xs text-gray-500">Currently teaching</p>
        </div>
    </div>

    {{-- Today Sessions --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 p-6 shadow-xl backdrop-blur-sm transition hover:border-amber-500/30">
        <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-500/10 blur-2xl"></div>
        <div class="relative">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Today Sessions</p>
            <p class="mt-3 text-4xl font-bold text-amber-100">{{ $todaySessions }}</p>
            <p class="mt-2 text-xs text-gray-500">Scheduled for today</p>
        </div>
    </div>

    {{-- Monthly Revenue (placeholder) --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 p-6 shadow-xl backdrop-blur-sm transition hover:border-amber-500/30">
        <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-500/10 blur-2xl"></div>
        <div class="relative">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Monthly Revenue</p>
            <p class="mt-3 text-4xl font-bold text-amber-100">—</p>
            <p class="mt-2 text-xs text-amber-400/80">Coming soon</p>
        </div>
    </div>
</div>

{{-- Main Grid --}}
<div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- Today Schedule (left, large) --}}
    <section class="xl:col-span-2 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
        <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
            <h2 class="text-lg font-semibold text-amber-100">Today's Schedule</h2>
            <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">{{ $recentSessions->count() }} sessions</span>
        </div>

        @if ($recentSessions->count())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-800/60 bg-gray-800/30">
                            <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                            <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                            <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500">Teacher</th>
                            <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500">Room</th>
                            <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60">
                        @foreach ($recentSessions as $session)
                            @php
                                $statusStyles = [
                                    'scheduled' => 'bg-sky-500/10 text-sky-400',
                                    'completed' => 'bg-emerald-500/10 text-emerald-400',
                                    'cancelled' => 'bg-red-500/10 text-red-400',
                                    'missed' => 'bg-red-500/10 text-red-400',
                                    'makeup' => 'bg-amber-500/10 text-amber-300',
                                ];
                                $statusValue = $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status;
                                $badgeStyle = $statusStyles[$statusValue] ?? 'bg-gray-700/50 text-gray-400';
                            @endphp
                            <tr class="transition hover:bg-gray-800/20">
                                <td class="px-6 py-3.5 font-mono text-sm font-semibold text-amber-400">{{ $session->start_time?->format('H:i') ?? '—' }}</td>
                                <td class="px-6 py-3.5 font-medium text-gray-100">{{ $session->enrollment?->student?->full_name ?? '—' }}</td>
                                <td class="px-6 py-3.5 text-gray-400">{{ $session->enrollment?->teacher?->full_name ?? '—' }}</td>
                                <td class="px-6 py-3.5 text-gray-400">{{ $session->room }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="rounded-full {{ $badgeStyle }} px-2.5 py-0.5 text-xs font-medium">
                                        {{ ucfirst($statusValue) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center text-gray-500">
                No sessions scheduled for today.
            </div>
        @endif
    </section>

    {{-- Alerts Panel --}}
    <div class="space-y-6">

        {{-- Cancelled Sessions --}}
        <section class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
            <div class="border-b border-gray-800/60 px-6 py-4">
                <h2 class="text-base font-semibold text-amber-100">Cancelled Sessions</h2>
            </div>
            <div class="px-6 py-6 text-center">
                <p class="text-4xl font-bold text-red-400">{{ $cancelledSessions }}</p>
                <p class="mt-2 text-xs text-gray-500">cancelled (last 7 days)</p>
            </div>
        </section>

        {{-- Missed Sessions --}}
        <section class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
            <div class="border-b border-gray-800/60 px-6 py-4">
                <h2 class="text-base font-semibold text-amber-100">Missed Sessions</h2>
            </div>
            <div class="px-6 py-6 text-center">
                <p class="text-4xl font-bold text-red-400">{{ $missedSessions }}</p>
                <p class="mt-2 text-xs text-gray-500">missed (last 7 days)</p>
            </div>
        </section>
    </div>
</div>

@endsection
