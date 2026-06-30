@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">Edit Enrollment</h1>
    <p class="mt-1 text-sm text-gray-500">Update enrollment information.</p>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('admin.enrollments.update', $enrollment) }}" class="max-w-2xl space-y-5">
    @csrf
    @method('PUT')

    {{-- student_id --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Student</label>
        <select name="student_id" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">Select student...</option>
            @foreach ($students ?? [] as $student)
                <option value="{{ $student->id }}" {{ old('student_id', $enrollment->student_id) == $student->id ? 'selected' : '' }}>{{ $student->full_name }}</option>
            @endforeach
        </select>
        @error('student_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- instrument_id --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Instrument</label>
        <select name="instrument_id" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">Select instrument...</option>
            @foreach ($instruments ?? [] as $instrument)
                <option value="{{ $instrument->id }}" {{ old('instrument_id', $enrollment->instrument_id) == $instrument->id ? 'selected' : '' }}>{{ $instrument->name }}</option>
            @endforeach
        </select>
        @error('instrument_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- teacher_id --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Teacher</label>
        <select name="teacher_id" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">Select teacher...</option>
            @foreach ($teachers ?? [] as $teacher)
                <option value="{{ $teacher->id }}" {{ old('teacher_id', $enrollment->teacher_id) == $teacher->id ? 'selected' : '' }}>{{ $teacher->full_name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">The teacher must teach the selected instrument.</p>
        @error('teacher_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- skill_level --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Skill Level</label>
        <select name="skill_level" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">Select level...</option>
            @foreach (['beginner', 'intermediate', 'advanced'] as $level)
                <option value="{{ $level }}" {{ (string) old('skill_level', $enrollment->skill_level) === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
            @endforeach
        </select>
        @error('skill_level')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- status --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Status</label>
        <select name="status" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            @foreach (['active', 'paused', 'completed', 'cancelled'] as $status)
                <option value="{{ $status }}" {{ (string) old('status', $enrollment->status) === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- started_at --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Started At</label>
        <input type="date" name="started_at" value="{{ old('started_at', $enrollment->started_at?->format('Y-m-d')) }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        @error('started_at')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- notes --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Notes</label>
        <textarea name="notes" rows="4"
                  class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                  placeholder="Optional notes">{{ old('notes', $enrollment->notes) }}</textarea>
        @error('notes')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
            Update Enrollment
        </button>
        <a href="{{ route('admin.enrollments.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            Cancel
        </a>
    </div>
</form>

@endsection
