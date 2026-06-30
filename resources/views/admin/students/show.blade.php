@extends('layouts.dashboard')

@section('content')

{{-- Back + Actions --}}
<div class="mb-8 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.students.index') }}" class="text-sm text-gray-400 transition hover:text-gray-200">← Back to Students</a>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.enrollments.create', ['student_id' => $student->id]) }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Add Enrollment
        </a>
        <a href="{{ route('admin.students.edit', $student) }}" class="rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">
            Edit Student
        </a>
    </div>
</div>

{{-- Section 1: Student Info --}}
<div class="mb-8 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="border-b border-gray-800/60 px-6 py-4">
        <h2 class="text-lg font-semibold text-amber-100">Student Information</h2>
    </div>
    <div class="grid grid-cols-1 gap-6 px-6 py-6 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Full Name</p>
            <p class="mt-1 text-sm text-gray-100">{{ $student->full_name }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Phone</p>
            <p class="mt-1 text-sm text-gray-100">{{ $student->phone }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Parent Phone</p>
            <p class="mt-1 text-sm text-gray-100">{{ $student->parent_phone ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Status</p>
            <span class="mt-1 inline-block rounded-full {{ (string) $student->status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-700/50 text-gray-400' }} px-2.5 py-0.5 text-xs font-medium">
                {{ ucfirst((string) $student->status) }}
            </span>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Join Date</p>
            <p class="mt-1 text-sm text-gray-100">{{ $student->join_date->format('Y/m/d') }}</p>
        </div>
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Notes</p>
            <p class="mt-1 text-sm text-gray-400">{{ $student->notes ?? '—' }}</p>
        </div>
    </div>
</div>

{{-- Section 2: Enrollments --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
        <h2 class="text-lg font-semibold text-amber-100">Enrollments</h2>
        <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">{{ $student->enrollments->count() }} total</span>
    </div>

    @if ($student->enrollments->count())
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-800/60 bg-gray-800/30">
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Instrument</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Teacher</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Skill</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">Started</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($student->enrollments as $enrollment)
                        <tr class="transition hover:bg-gray-800/20">
                            <td class="px-6 py-4 font-medium text-gray-100">{{ $enrollment->instrument?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-400">{{ $enrollment->teacher?->full_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-400">{{ ucfirst((string) $enrollment->skill_level) }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusStyles = [
                                        'active' => 'bg-emerald-500/10 text-emerald-400',
                                        'paused' => 'bg-amber-500/10 text-amber-300',
                                        'completed' => 'bg-sky-500/10 text-sky-400',
                                        'cancelled' => 'bg-red-500/10 text-red-400',
                                    ];
                                    $style = $statusStyles[$enrollment->status] ?? 'bg-gray-700/50 text-gray-400';
                                @endphp
                                <span class="rounded-full {{ $style }} px-2.5 py-0.5 text-xs font-medium">
                                    {{ ucfirst((string) $enrollment->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-400">{{ $enrollment->started_at?->format('Y/m/d') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-12 text-center text-gray-500">
            No enrollments yet.
        </div>
    @endif
</div>

@endsection
