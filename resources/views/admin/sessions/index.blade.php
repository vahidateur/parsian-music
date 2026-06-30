@extends('layouts.dashboard')

@section('content')

{{-- Page Heading --}}
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-amber-100">Class Sessions</h1>
            <p class="mt-1 text-sm text-gray-500">View and filter all scheduled sessions.</p>
        </div>
        <form method="POST" action="{{ route('admin.sessions.generate') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Generate Sessions
            </button>
        </form>
    </div>
</div>

{{-- Success Message --}}
@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('admin.sessions.index') }}" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">Student</label>
        <select name="student_id" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">All Students</option>
            @foreach ($students ?? [] as $student)
                <option value="{{ $student->id }}" {{ (string) request('student_id') === (string) $student->id ? 'selected' : '' }}>{{ $student->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">Teacher</label>
        <select name="teacher_id" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">All Teachers</option>
            @foreach ($teachers ?? [] as $teacher)
                <option value="{{ $teacher->id }}" {{ (string) request('teacher_id') === (string) $teacher->id ? 'selected' : '' }}>{{ $teacher->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">Instrument</label>
        <select name="instrument_id" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">All Instruments</option>
            @foreach ($instruments ?? [] as $instrument)
                <option value="{{ $instrument->id }}" {{ (string) request('instrument_id') === (string) $instrument->id ? 'selected' : '' }}>{{ $instrument->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">Room</label>
        <select name="room" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">All Rooms</option>
            @foreach (['Room 1', 'Room 2', 'Room 3'] as $r)
                <option value="{{ $r }}" {{ request('room') === $r ? 'selected' : '' }}>{{ $r }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">Status</label>
        <select name="status" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">All Statuses</option>
            @foreach (['scheduled', 'completed', 'cancelled', 'missed', 'makeup'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">Date</label>
        <input type="date" name="date" value="{{ request('date') }}"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">Filter</button>
        <a href="{{ route('admin.sessions.index') }}" class="rounded-lg px-4 py-2.5 text-sm text-gray-500 transition hover:text-gray-300">Clear</a>
    </div>
</form>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-800/60 bg-gray-800/30">
                    <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                    <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Teacher</th>
                    <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Instrument</th>
                    <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                    <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                    <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Duration</th>
                    <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Room</th>
                    <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($sessions as $session)
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
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-5 py-3.5 font-medium text-gray-100">{{ $session->enrollment?->student?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ $session->enrollment?->teacher?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ $session->enrollment?->instrument?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ $session->session_date?->format('Y/m/d') ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ $session->start_time?->format('H:i') ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ $session->duration_minutes }}m</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ $session->room }}</td>
                        <td class="px-5 py-3.5">
                            <span class="rounded-full {{ $badgeStyle }} px-2.5 py-0.5 text-xs font-medium">
                                {{ ucfirst((string) $session->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-gray-500">No sessions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
@if ($sessions->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $sessions->links() }}
    </div>
@endif

@endsection
