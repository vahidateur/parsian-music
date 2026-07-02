@extends('layouts.dashboard')

@section('content')

{{-- Page Heading --}}
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-amber-100">Enrollments</h1>
            <p class="mt-1 text-sm text-gray-500">Manage student instrument enrollments.</p>
        </div>
        <a href="{{ route('admin.enrollments.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            New Enrollment
        </a>
    </div>
</div>

{{-- Success Message --}}
@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('admin.enrollments.index') }}" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
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
        <label class="mb-1 block text-xs font-medium text-gray-400">Status</label>
        <select name="status" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">All Statuses</option>
            @foreach (['active', 'paused', 'completed', 'cancelled'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">Filter</button>
        <a href="{{ route('admin.enrollments.index') }}" class="rounded-lg px-4 py-2.5 text-sm text-gray-500 transition hover:text-gray-300">Clear</a>
    </div>
</form>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-800/60 bg-gray-800/30">
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Student</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Teacher</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Instrument</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Skill</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Started</th>
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($enrollments as $enrollment)
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-6 py-4 font-medium text-gray-100">{{ $enrollment->student?->full_name ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ $enrollment->teacher?->full_name ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ $enrollment->instrument?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-400">
                            @php
                                $enrollmentSkillValue = $enrollment->skill_level instanceof \BackedEnum ? $enrollment->skill_level->value : (string) $enrollment->skill_level;
                            @endphp
                            {{ ucfirst($enrollmentSkillValue) }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusValue = $enrollment->status instanceof \BackedEnum ? $enrollment->status->value : (string) $enrollment->status;
                                $statusStyles = [
                                    'active' => 'bg-emerald-500/10 text-emerald-400',
                                    'paused' => 'bg-amber-500/10 text-amber-300',
                                    'completed' => 'bg-sky-500/10 text-sky-400',
                                    'cancelled' => 'bg-red-500/10 text-red-400',
                                ];
                                $style = $statusStyles[$statusValue] ?? 'bg-gray-700/50 text-gray-400';
                            @endphp
                            <span class="rounded-full {{ $style }} px-2.5 py-0.5 text-xs font-medium">
                                {{ ucfirst($statusValue) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-400">{{ $enrollment->started_at?->format('Y/m/d') ?? '—' }}</td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="text-amber-400 transition hover:text-amber-300">Edit</a>
                            <form method="POST" action="{{ route('admin.enrollments.destroy', $enrollment) }}" class="inline ml-3" onsubmit="return confirm('Delete this enrollment?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 transition hover:text-red-300">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">No enrollments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
@if ($enrollments->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $enrollments->withQueryString()->links() }}
    </div>
@endif

@endsection
