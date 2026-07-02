@extends('layouts.dashboard')

@section('content')

{{-- Page Heading --}}
<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">Create Session</h1>
    <p class="mt-1 text-sm text-gray-500">Schedule a new class session manually.</p>
</div>

{{-- Form --}}
<form method="POST" action="{{ route('admin.sessions.store') }}" class="max-w-2xl space-y-6">
    @csrf

    {{-- Student --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Student</label>
        <select name="student_id" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">Select student...</option>
            @foreach ($students as $student)
                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                    {{ $student->full_name }}
                </option>
            @endforeach
        </select>
        @error('student_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Teacher --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Teacher</label>
        <select name="teacher_id" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">Select teacher...</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                    {{ $teacher->full_name }}
                </option>
            @endforeach
        </select>
        @error('teacher_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Session Date --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.date') }}</label>
        <input type="date" name="session_date" required value="{{ old('session_date') }}"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        <p class="mt-1 text-xs text-gray-500">{{ __('admin.jalali_equivalent') }}: <span class="text-amber-400" id="sessionDateJalali">—</span></p>
        @error('session_date')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Start Time --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.start_time') }} (15:00 - 21:30)</label>
        <input type="time" name="start_time" required value="{{ old('start_time') }}"
               min="15:00" max="21:30"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        @error('start_time')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <script>
        document.querySelector('input[name="session_date"]').addEventListener('change', function(e) {
            if (e.target.value) {
                const date = new Date(e.target.value + 'T00:00:00');
                let jalaliStr = 'محاسبه شد...';
                document.getElementById('sessionDateJalali').textContent = jalaliStr;
            }
        });
    </script>

    {{-- Duration --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Duration (minutes)</label>
        <input type="number" name="duration_minutes" required value="{{ old('duration_minutes', 60) }}"
               min="30" max="120" step="30"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        @error('duration_minutes')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Room --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">Room</label>
        <select name="room" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">Select room...</option>
            @foreach (['Room 1', 'Room 2', 'Room 3'] as $room)
                <option value="{{ $room }}" {{ old('room') === $room ? 'selected' : '' }}>
                    {{ $room }}
                </option>
            @endforeach
        </select>
        @error('room')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Buttons --}}
    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
            Create Session
        </button>
        <a href="{{ route('admin.sessions.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            Cancel
        </a>
    </div>
</form>

@endsection
