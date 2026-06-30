@extends('layouts.dashboard')

@section('content')

{{-- Back Link --}}
<div class="mb-6">
    <a href="{{ route('admin.teachers.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 transition hover:text-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
        Back to Teachers
    </a>
</div>

{{-- Section 1: Teacher Profile Card --}}
<div class="mb-8 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-center">
        {{-- Avatar --}}
        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-500/20 to-amber-600/10 text-2xl font-bold text-amber-300">
            {{ strtoupper(mb_substr($teacher->full_name, 0, 1)) }}
        </div>

        <div class="flex-1">
            <h1 class="text-2xl font-semibold text-amber-100">{{ $teacher->full_name }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-400">
                <span class="inline-flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-400/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                    {{ $teacher->phone }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-400/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                    Joined {{ $teacher->hire_date?->format('Y/m/d') ?? '—' }}
                </span>
                <span class="rounded-full {{ (string) $teacher->status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-700/50 text-gray-400' }} px-2.5 py-0.5 text-xs font-medium">
                    {{ ucfirst((string) $teacher->status) }}
                </span>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.teachers.instruments', $teacher) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2 text-xs font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a2.25 2.25 0 001.632-2.163z" /></svg>
                Instruments
            </a>
            <a href="{{ route('admin.teachers.edit', $teacher) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2 text-xs font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                Edit
            </a>
        </div>
    </div>

    {{-- Instruments --}}
    @if ($teacher->instruments->isNotEmpty())
        <div class="border-t border-gray-800/60 px-6 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Teaches:</span>
                @foreach ($teacher->instruments as $instrument)
                    <span class="rounded-full border border-amber-500/30 bg-amber-500/[0.04] px-2.5 py-0.5 text-xs font-medium text-amber-300">
                        {{ $instrument->name }}
                        @if ($instrument->pivot->is_primary)
                            <span class="ml-1 text-amber-400/60">★</span>
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- Section 2: KPI Cards --}}
<div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">

    {{-- Total Students --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 p-6 shadow-xl backdrop-blur-sm">
        <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-500/10 blur-2xl"></div>
        <div class="relative">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Students</p>
            <p class="mt-3 text-4xl font-bold text-amber-100">{{ $totalStudents }}</p>
            <p class="mt-2 text-xs text-gray-500">Currently enrolled</p>
        </div>
    </div>

    {{-- Weekly Sessions --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 p-6 shadow-xl backdrop-blur-sm">
        <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-500/10 blur-2xl"></div>
        <div class="relative">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Weekly Sessions</p>
            <p class="mt-3 text-4xl font-bold text-amber-100">{{ $weeklySessions }}</p>
            <p class="mt-2 text-xs text-gray-500">This week</p>
        </div>
    </div>

    {{-- Completed --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 p-6 shadow-xl backdrop-blur-sm">
        <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-500/10 blur-2xl"></div>
        <div class="relative">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Completed</p>
            <p class="mt-3 text-4xl font-bold text-emerald-400">{{ $completedSessions }}</p>
            <p class="mt-2 text-xs text-gray-500">Sessions done</p>
        </div>
    </div>

    {{-- Missed --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 p-6 shadow-xl backdrop-blur-sm">
        <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-amber-500/10 blur-2xl"></div>
        <div class="relative">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Missed</p>
            <p class="mt-3 text-4xl font-bold text-red-400">{{ $missedSessions }}</p>
            <p class="mt-2 text-xs text-gray-500">Needs attention</p>
        </div>
    </div>
</div>

{{-- Section 3: Weekly Schedule --}}
<div class="mb-8 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
        <h2 class="text-lg font-semibold text-amber-100">Weekly Schedule</h2>
        <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">{{ $weeklySessions }} sessions</span>
    </div>

    @if ($sessions->isNotEmpty())
        <ul class="divide-y divide-gray-800/60">
            @foreach ($sessions as $session)
                @php
                    $statusStyles = [
                        'scheduled' => 'bg-sky-500/10 text-sky-400',
                        'completed' => 'bg-emerald-500/10 text-emerald-400',
                        'cancelled' => 'bg-red-500/10 text-red-400',
                        'missed' => 'bg-red-500/10 text-red-400',
                        'makeup' => 'bg-amber-500/10 text-amber-300',
                    ];
                    $badgeStyle = $statusStyles[$session->status] ?? 'bg-gray-700/50 text-gray-400';
                @endphp
                <li class="flex flex-wrap items-center gap-4 px-6 py-4 transition hover:bg-gray-800/20">
                    <div class="flex w-28 shrink-0 flex-col">
                        <span class="font-mono text-sm font-semibold text-amber-400">{{ $session->session_date?->format('M d') ?? '—' }}</span>
                        <span class="font-mono text-xs text-gray-500">{{ $session->start_time?->format('H:i') ?? '—' }}</span>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-100">{{ $session->enrollment?->student?->full_name ?? '—' }}</p>
                        <p class="text-xs text-gray-500">{{ $session->enrollment?->instrument?->name ?? '—' }} · {{ $session->room }}</p>
                    </div>
                    <span class="rounded-full {{ $badgeStyle }} px-2.5 py-0.5 text-xs font-medium">
                        {{ ucfirst((string) $session->status) }}
                    </span>
                    <a href="{{ route('admin.sessions.attendance.show', $session) }}" class="text-amber-400 transition hover:text-amber-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <div class="px-6 py-12 text-center text-gray-500">
            No sessions scheduled this week.
        </div>
    @endif
</div>

{{-- Section 4: Quick Links --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <a href="{{ route('admin.sessions.index', ['teacher_id' => $teacher->id]) }}" class="group flex items-center justify-between rounded-2xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm transition hover:border-amber-500/30">
        <div class="flex items-center gap-4">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400 transition group-hover:bg-amber-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="font-medium text-gray-100">Attendance</p>
                <p class="text-xs text-gray-500">Present: {{ $presentCount }} · Absent: {{ $absentCount }}</p>
            </div>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 transition group-hover:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
    </a>

    <a href="{{ route('admin.students.index') }}" class="group flex items-center justify-between rounded-2xl border border-gray-800/60 bg-gray-900/50 p-5 shadow-xl backdrop-blur-sm transition hover:border-amber-500/30">
        <div class="flex items-center gap-4">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400 transition group-hover:bg-amber-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
            </div>
            <div>
                <p class="font-medium text-gray-100">Students</p>
                <p class="text-xs text-gray-500">{{ $totalStudents }} enrolled</p>
            </div>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 transition group-hover:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
    </a>
</div>

@endsection
