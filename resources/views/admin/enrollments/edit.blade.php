@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.edit_enrollment') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.update_enrollment_desc') }}</p>
</div>

{{-- Feedback_Channel + Error_State recovery path; field errors render per field --}}
<x-admin.feedback :validation="false" :returnUrl="route('admin.enrollments.index')" />

{{-- Loading_State owner of this Record_Form --}}
<x-admin.form-state>
<form method="POST" action="{{ route('admin.enrollments.update', $enrollment) }}" class="max-w-2xl space-y-5">
    @csrf
    @method('PUT')

    {{-- student_id --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.student') }}</label>
        <select name="student_id" {{ feedback_field_attributes('student_id') }} required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_student') }}</option>
            @foreach ($students ?? [] as $student)
                <option value="{{ $student->id }}" {{ old('student_id', $enrollment->student_id) == $student->id ? 'selected' : '' }}>{{ $student->full_name }}</option>
            @endforeach
        </select>
        <x-admin.feedback field="student_id" />
    </div>

    {{-- instrument_id --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.instrument') }}</label>
        <select name="instrument_id" {{ feedback_field_attributes('instrument_id') }} required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_instrument') }}</option>
            @foreach ($instruments ?? [] as $instrument)
                <option value="{{ $instrument->id }}" {{ old('instrument_id', $enrollment->instrument_id) == $instrument->id ? 'selected' : '' }}>{{ $instrument->display_name }}</option>
            @endforeach
        </select>
        <x-admin.feedback field="instrument_id" />
    </div>

    {{-- teacher_id --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.teacher') }}</label>
        <select name="teacher_id" {{ feedback_field_attributes('teacher_id') }} required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_teacher') }}</option>
            @foreach ($teachers ?? [] as $teacher)
                <option value="{{ $teacher->id }}" {{ old('teacher_id', $enrollment->teacher_id) == $teacher->id ? 'selected' : '' }}>{{ $teacher->full_name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">{{ __('admin.teacher_must_teach_instrument') }}</p>
        <x-admin.feedback field="teacher_id" />
    </div>

    {{-- skill_level --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.skill_level') }}</label>
        <select name="skill_level" {{ feedback_field_attributes('skill_level') }} required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_level') }}</option>
            @foreach (\App\Enums\SkillLevelEnum::values() as $level)
                <option value="{{ $level }}" {{ (string) old('skill_level', $enrollment->skill_level) === $level ? 'selected' : '' }}>{{ __('admin.skill_levels.' . $level) }}</option>
            @endforeach
        </select>
        <x-admin.feedback field="skill_level" />
    </div>

    {{-- status --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.status') }}</label>
        <select name="status" {{ feedback_field_attributes('status') }} required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            @foreach (\App\Enums\EnrollmentStatusEnum::values() as $status)
                <option value="{{ $status }}" {{ (string) old('status', $enrollment->status) === $status ? 'selected' : '' }}>{{ __('admin.statuses.' . $status) }}</option>
            @endforeach
        </select>
        <x-admin.feedback field="status" />
    </div>

    {{-- started_at --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.started_at') }}</label>
        <input type="date" name="started_at" {{ feedback_field_attributes('started_at') }} value="{{ old('started_at', $enrollment->started_at?->format('Y-m-d')) }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        @if ($enrollment->started_at)
            <p class="mt-1 text-xs text-gray-500">{{ __('admin.jalali_equivalent') }}: <span class="text-amber-400">{{ \App\Helpers\Jalalian::fromCarbon($enrollment->started_at) }}</span></p>
        @endif
        <x-admin.feedback field="started_at" />
    </div>

    {{-- notes --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.notes') }}</label>
        <textarea name="notes" {{ feedback_field_attributes('notes') }} rows="4"
                  class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                  placeholder="{{ __('admin.optional_notes') }}">{{ old('notes', $enrollment->notes) }}</textarea>
        <x-admin.feedback field="notes" />
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-4 pt-2">
        <x-admin.submit-button :label="__('admin.update_enrollment_btn')" />
        <a href="{{ route('admin.enrollments.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            {{ __('admin.cancel') }}
        </a>
    </div>
</form>
</x-admin.form-state>

@endsection
