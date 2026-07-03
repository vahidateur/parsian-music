@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.new_enrollment') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.new_enrollment_desc') }}</p>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('admin.enrollments.store') }}" class="max-w-2xl space-y-5">
    @csrf

    {{-- student_id --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.student') }}</label>
        <select name="student_id" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_student') }}</option>
            @foreach ($students ?? [] as $student)
                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>{{ $student->full_name }}</option>
            @endforeach
        </select>
        @error('student_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- instrument_id --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.instrument') }}</label>
        <select name="instrument_id" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_instrument') }}</option>
            @foreach ($instruments ?? [] as $instrument)
                <option value="{{ $instrument->id }}" {{ old('instrument_id') == $instrument->id ? 'selected' : '' }}>{{ $instrument->display_name }}</option>
            @endforeach
        </select>
        @error('instrument_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- teacher_id --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.teacher') }}</label>
        <select name="teacher_id" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_teacher') }}</option>
            @foreach ($teachers ?? [] as $teacher)
                <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->full_name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">{{ __('admin.teacher_must_teach_instrument') }}</p>
        @error('teacher_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- skill_level --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.skill_level') }}</label>
        <select name="skill_level" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_level') }}</option>
            @foreach (\App\Enums\SkillLevelEnum::values() as $level)
                <option value="{{ $level }}" {{ old('skill_level') === $level ? 'selected' : '' }}>{{ __('admin.skill_levels.' . $level) }}</option>
            @endforeach
        </select>
        @error('skill_level')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- status --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.status') }}</label>
        <select name="status" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            @foreach (\App\Enums\EnrollmentStatusEnum::values() as $status)
                <option value="{{ $status }}" {{ old('status', 'active') === $status ? 'selected' : '' }}>{{ __('admin.statuses.' . $status) }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- started_at --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.started_at') }}</label>
        <input type="date" name="started_at" value="{{ old('started_at') }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        <p class="mt-1 text-xs text-gray-500">{{ __('admin.jalali_equivalent') }}: <span class="text-amber-400" id="startedAtJalali">—</span></p>
        @error('started_at')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- notes --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.notes') }}</label>
        <textarea name="notes" rows="4"
                  class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                  placeholder="{{ __('admin.optional_notes') }}">{{ old('notes') }}</textarea>
        @error('notes')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
            {{ __('admin.create_enrollment') }}
        </button>
        <a href="{{ route('admin.enrollments.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            {{ __('admin.cancel') }}
        </a>
    </div>
</form>

@endsection
