@extends('layouts.dashboard')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.sessions.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm text-gray-400 transition hover:text-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
        {{ __('admin.back_to_sessions') }}
    </a>
    <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.create_session') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.schedule_session_manually_desc') }}</p>
</div>

<x-admin.feedback />

<form method="POST" action="{{ route('admin.sessions.store') }}" class="max-w-2xl space-y-6"
      x-data="sessionCreate"
      data-session-create
      data-students="{{ e(json_encode($students, JSON_UNESCAPED_UNICODE)) }}"
      data-teacher-instruments="{{ e(json_encode($teacher_instrument_map, JSON_UNESCAPED_UNICODE)) }}"
      data-initial-student-id="{{ old('student_id') }}"
      data-initial-teacher-id="{{ old('teacher_id') }}"
      data-initial-instrument-id="{{ old('instrument_id') }}">
    @csrf

    <div class="relative">
        <label for="student-search" class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.student') }}</label>
        <input id="student_id" type="hidden" name="student_id" value="{{ old('student_id') }}" {{ feedback_field_attributes('student_id') }} data-session-student-id required>
        <div class="relative">
            <input id="student-search" type="text" placeholder="جستجو هنرجو..." autocomplete="off"
                   class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                   data-session-student-search aria-controls="session-student-results">
            <ul id="session-student-results" class="absolute z-10 mt-1 hidden max-h-48 w-full overflow-y-auto rounded-lg border border-gray-700 bg-gray-900 shadow-lg" role="listbox" aria-label="نتایج جستجوی هنرجو" data-session-student-results></ul>
            <div class="absolute left-4 top-3 hidden text-sm font-medium text-amber-400" data-session-selected-student>
                <span data-session-selected-student-label></span>
            </div>
        </div>
        <p class="mt-1 hidden text-sm text-gray-400" data-session-student-empty>هنرجویی یافت نشد</p>
        <x-admin.feedback field="student_id" />
    </div>

    <div>
        <label for="session-teacher" class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.teacher') }}</label>
        <select id="session-teacher" name="teacher_id" {{ feedback_field_attributes('teacher_id') }} required data-session-teacher
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_teacher') }}</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}">{{ $teacher->full_name }}</option>
            @endforeach
        </select>
        <x-admin.feedback field="teacher_id" />
    </div>

    <div>
        <label for="session-instrument" class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.instrument') }}</label>
        <select id="session-instrument" name="instrument_id" {{ feedback_field_attributes('instrument_id') }} required data-session-instrument disabled
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 disabled:cursor-not-allowed disabled:opacity-50">
            <option value="">ابتدا معلم را انتخاب کنید</option>
        </select>
        <x-admin.feedback field="instrument_id" />
    </div>

    <div x-data="adminDateForm('session_date', '{{ old('session_date', '') }}')">
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.date') }}</label>
        <input type="hidden" name="session_date" {{ feedback_field_attributes('session_date') }} :value="isoValue">
        <div class="grid grid-cols-3 gap-2">
            <div><label class="mb-1 block text-xs text-gray-500">سال</label><input type="number" x-model="year" @input="onDateChange()" @blur="padYear()" min="2010" max="2099" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100" placeholder="2024"></div>
            <div><label class="mb-1 block text-xs text-gray-500">ماه</label><input type="number" x-model="month" @input="onDateChange()" min="1" max="12" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100" placeholder="1"></div>
            <div><label class="mb-1 block text-xs text-gray-500">روز</label><input type="number" x-model="day" @input="onDateChange()" :max="daysInMonth()" min="1" max="31" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100" placeholder="1"></div>
        </div>
        <p class="mt-1.5 text-xs text-gray-500">{{ __('admin.jalali_equivalent') }}: <span class="text-amber-400" x-text="jalali || '—'"></span></p>
        <x-admin.feedback field="session_date" />
    </div>

    <div>
        <label for="session-start-time" class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.start_time') }} (۱۵:۰۰ – ۲۱:۳۰)</label>
        <input id="session-start-time" type="time" name="start_time" {{ feedback_field_attributes('start_time') }} required value="{{ old('start_time') }}" min="15:00" max="21:30"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        <x-admin.feedback field="start_time" />
    </div>

    <div>
        <label for="session-duration" class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.duration_minutes_label') }}</label>
        <select id="session-duration" name="duration_minutes" {{ feedback_field_attributes('duration_minutes') }} required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            @foreach ([30, 45, 60, 90, 120] as $duration)
                <option value="{{ $duration }}" {{ old('duration_minutes', 60) == $duration ? 'selected' : '' }}>{{ $duration }} {{ __('admin.minutes') }}</option>
            @endforeach
        </select>
        <x-admin.feedback field="duration_minutes" />
    </div>

    <div>
        <label for="session-room" class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.room') }}</label>
        <select id="session-room" name="room" {{ feedback_field_attributes('room') }} required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_room_placeholder') }}</option>
            @foreach ($rooms as $room)
                <option value="{{ $room->name }}" {{ old('room') === $room->name ? 'selected' : '' }}>{{ $room->name }}</option>
            @endforeach
        </select>
        <x-admin.feedback field="room" />
    </div>

    <div data-session-quota>
        <div class="hidden space-y-3 rounded-lg border border-amber-400 bg-amber-500/10 p-4" data-session-overage role="status">
            <p class="text-sm font-medium text-amber-400">⚠️ Sessions exceeded. Session will be marked as overage.</p>
            <div>
                <label for="session-overage-notes" class="mb-1.5 block text-sm text-amber-300/80">Optional reason for overage:</label>
                <input id="session-overage-notes" type="text" name="notes" placeholder="Reason..." maxlength="255" value="{{ old('notes') }}"
                       class="block w-full rounded-lg border border-amber-400/40 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500">
            </div>
        </div>
        <div data-session-standard-notes>
            <label for="session-notes" class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.notes') }} <span class="text-xs text-gray-500">({{ __('admin.optional') }})</span></label>
            <textarea id="session-notes" name="notes" rows="3" placeholder="{{ __('admin.optional_notes') }}"
                      class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">{{ __('admin.create_session') }}</button>
        <a href="{{ route('admin.sessions.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">{{ __('admin.cancel') }}</a>
    </div>
</form>

@endsection
