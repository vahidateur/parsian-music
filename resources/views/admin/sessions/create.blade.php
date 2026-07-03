@extends('layouts.dashboard')

@section('content')

{{-- Page Heading --}}
<div class="mb-8">
    <a href="{{ route('admin.sessions.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm text-gray-400 transition hover:text-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
        {{ __('admin.back_to_sessions') }}
    </a>
    <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.create_session') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.schedule_session_manually_desc') }}</p>
</div>

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
        <ul class="list-disc pr-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $enrollmentsJson = $enrollments->map(function ($e) {
        return [
            'id'              => $e->id,
            'student_id'      => $e->student_id,
            'teacher_name'    => $e->teacher?->full_name ?? '',
            'instrument_name' => $e->instrument?->display_name ?? '',
            'label'           => ($e->instrument?->display_name ?? '—') . ' — ' . ($e->teacher?->full_name ?? '—'),
        ];
    })->values();
@endphp

<form method="POST" action="{{ route('admin.sessions.store') }}" class="max-w-2xl space-y-6"
      x-data="sessionCreateForm()" x-init="init()">
    @csrf

    {{-- 1. Student --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.student') }}</label>
        <select x-model="studentId" @change="onStudentChange()"
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_student') }}</option>
            @foreach ($students as $s)
                <option value="{{ $s->id }}" @if(old('student_id') == $s->id) selected @endif>{{ $s->full_name }}</option>
            @endforeach
        </select>
    </div>

    {{-- 2. Active Enrollment --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.active_enrollment') }}</label>
        <select name="enrollment_id" required x-model="enrollmentId" @change="onEnrollmentChange()"
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_enrollment_placeholder') }}</option>
            <template x-for="e in filteredEnrollments" :key="e.id">
                <option :value="e.id" :selected="e.id == enrollmentId" x-text="e.label"></option>
            </template>
        </select>
        <p x-show="studentId && filteredEnrollments.length === 0" class="mt-1 text-xs text-red-400">
            {{ __('admin.no_active_enrollment_for_student') }}
        </p>
        @error('enrollment_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 3. Teacher (auto-filled) --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.teacher') }}</label>
        <input type="text" readonly tabindex="-1"
               :value="teacherName || ''"
               :placeholder="enrollmentId ? '—' : '{{ __('admin.select_enrollment_placeholder') }}'"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/30 px-4 py-3 text-sm text-gray-300 cursor-not-allowed">
        <p class="mt-1 text-xs text-gray-500">{{ __('admin.auto_filled_hint') }}</p>
    </div>

    {{-- 4. Instrument (auto-filled) --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.instrument') }}</label>
        <input type="text" readonly tabindex="-1"
               :value="instrumentName || ''"
               :placeholder="enrollmentId ? '—' : '{{ __('admin.select_enrollment_placeholder') }}'"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/30 px-4 py-3 text-sm text-gray-300 cursor-not-allowed">
    </div>

    {{-- 5. Session Date — split Y/M/D to prevent 5-digit year --}}
    <div x-data="dateForm('session_date', '{{ old('session_date', '') }}')" x-init="init()">
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.date') }}</label>
        <input type="hidden" name="session_date" :value="isoValue">
        <div class="grid grid-cols-3 gap-2">
            <div>
                <label class="mb-1 block text-xs text-gray-500">سال</label>
                <input type="number" x-model="year" @input="onDateChange()" @blur="padYear()"
                       min="2010" max="2099"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="2024">
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-500">ماه</label>
                <input type="number" x-model="month" @input="onDateChange()"
                       min="1" max="12"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="1">
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-500">روز</label>
                <input type="number" x-model="day" @input="onDateChange()"
                       min="1" max="31"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="1">
            </div>
        </div>
        <p class="mt-1.5 text-xs text-gray-500">
            {{ __('admin.jalali_equivalent') }}:
            <span class="text-amber-400" x-text="jalali || '—'"></span>
        </p>
        @error('session_date')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 6. Start Time --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.start_time') }} (۱۵:۰۰ – ۲۱:۳۰)</label>
        <input type="time" name="start_time" required value="{{ old('start_time') }}"
               min="15:00" max="21:30"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        @error('start_time')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 7. Duration --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.duration_minutes_label') }}</label>
        <select name="duration_minutes" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            @foreach ([30, 45, 60, 90, 120] as $d)
                <option value="{{ $d }}" {{ old('duration_minutes', 60) == $d ? 'selected' : '' }}>
                    {{ $d }} {{ __('admin.minutes') }}
                </option>
            @endforeach
        </select>
        @error('duration_minutes')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 8. Room --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.room') }}</label>
        <select name="room" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_room_placeholder') }}</option>
            @foreach (['A101', 'A102', 'A103'] as $room)
                <option value="{{ $room }}" {{ old('room') === $room ? 'selected' : '' }}>{{ $room }}</option>
            @endforeach
        </select>
        @error('room')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 9. Session Fee --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.session_fee') }}
            <span class="text-gray-500 text-xs">({{ __('admin.currency_toman') }}) ({{ __('admin.optional') }})</span>
        </label>
        <input type="number" name="session_fee" value="{{ old('session_fee') }}"
               min="0" step="1000"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="0">
        @error('session_fee')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 10. Discount --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.discount') }}
            <span class="text-gray-500 text-xs">({{ __('admin.currency_toman') }}) ({{ __('admin.optional') }})</span>
        </label>
        <input type="number" name="discount" value="{{ old('discount') }}"
               min="0" step="1000"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="0">
        @error('discount')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 11. Notes --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.notes') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <textarea name="notes" rows="3"
                  class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                  placeholder="{{ __('admin.optional_notes') }}">{{ old('notes') }}</textarea>
    </div>

    {{-- Buttons --}}
    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
            {{ __('admin.create_session') }}
        </button>
        <a href="{{ route('admin.sessions.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            {{ __('admin.cancel') }}
        </a>
    </div>
</form>

@include('admin.partials.date-form-script')

<script>
function sessionCreateForm() {
    const allEnrollments = @json($enrollmentsJson);
    const oldStudentId   = '{{ old("student_id", "") }}';
    const oldEnrollmentId = '{{ old("enrollment_id", "") }}';

    return {
        studentId:      oldStudentId,
        enrollmentId:   oldEnrollmentId,
        teacherName:    '',
        instrumentName: '',
        enrollments:    allEnrollments,

        get filteredEnrollments() {
            if (!this.studentId) return [];
            return this.enrollments.filter(
                e => String(e.student_id) === String(this.studentId)
            );
        },

        init() {
            // If there is a previously selected enrollment (e.g. after validation error),
            // auto-restore teacher and instrument from the JSON data.
            if (this.enrollmentId) {
                this._fillFromEnrollment(this.enrollmentId);
            }
        },

        onStudentChange() {
            this.enrollmentId   = '';
            this.teacherName    = '';
            this.instrumentName = '';
        },

        onEnrollmentChange() {
            this._fillFromEnrollment(this.enrollmentId);
        },

        _fillFromEnrollment(id) {
            const found = this.enrollments.find(
                e => String(e.id) === String(id)
            );
            this.teacherName    = found ? found.teacher_name    : '';
            this.instrumentName = found ? found.instrument_name : '';
        },
    };
}
</script>

@endsection
