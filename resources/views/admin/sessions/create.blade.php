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
    // Lookup map keyed by enrollment id: teacher/instrument names for the
    // readonly auto-filled fields, plus the suggested base fee (nullable —
    // taken from that enrollment's most recent prior session, per audit).
    $enrollmentMeta = $enrollments->mapWithKeys(function ($e) use ($lastFees) {
        return [
            $e->id => [
                'teacher_name'    => $e->teacher?->full_name ?? '',
                'instrument_name' => $e->instrument?->display_name ?? '',
                'base_fee'        => $lastFees[$e->id] ?? null,
            ],
        ];
    });
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

    {{-- 2. Active Enrollment — all options rendered server-side (no template
         re-rendering), filtered client-side via the `hidden` attribute per
         student. This avoids the x-model/<template x-for> sync issue that
         made the enrollment dropdown unreliable. --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.active_enrollment') }}</label>
        <select name="enrollment_id" required x-ref="enrollmentSelect" @change="onEnrollmentChange($event.target.value)"
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_enrollment_placeholder') }}</option>
            @foreach ($enrollments as $e)
                @php
                    $skillLabel = $e->skill_level ? __('admin.skill_levels.' . $e->skill_level->value) : '';
                    $label = ($e->instrument?->display_name ?? '—') . ' — ' . ($e->teacher?->full_name ?? '—') . ' — ' . ($skillLabel ?: '—');
                @endphp
                <option value="{{ $e->id }}" data-student-id="{{ $e->student_id }}"
                        @if(old('enrollment_id') == $e->id) selected @endif>{{ $label }}</option>
            @endforeach
        </select>
        <p x-show="studentId && visibleEnrollmentCount === 0" class="mt-1 text-xs text-red-400">
            {{ __('admin.no_active_enrollment_for_student') }}
        </p>
        @error('enrollment_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 3. Teacher (auto-filled, readonly) --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.teacher') }}</label>
        <input type="text" readonly tabindex="-1"
               :value="teacherName || ''"
               :placeholder="enrollmentId ? '—' : '{{ __('admin.select_enrollment_placeholder') }}'"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/30 px-4 py-3 text-sm text-gray-300 cursor-not-allowed">
        <p class="mt-1 text-xs text-gray-500">{{ __('admin.auto_filled_hint') }}</p>
    </div>

    {{-- 4. Instrument (auto-filled, readonly) --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.instrument') }}</label>
        <input type="text" readonly tabindex="-1"
               :value="instrumentName || ''"
               :placeholder="enrollmentId ? '—' : '{{ __('admin.select_enrollment_placeholder') }}'"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/30 px-4 py-3 text-sm text-gray-300 cursor-not-allowed">
    </div>

    {{-- 5. Base Fee (auto-filled, readonly display — suggestion only) --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.base_fee') }}
            <span class="text-gray-500 text-xs">({{ __('admin.currency_toman') }})</span>
        </label>
        <input type="text" readonly tabindex="-1"
               :value="baseFee !== null ? Number(baseFee).toLocaleString('en-US') : ''"
               :placeholder="enrollmentId ? '—' : '{{ __('admin.select_enrollment_placeholder') }}'"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/30 px-4 py-3 text-sm text-gray-300 cursor-not-allowed">
        <p class="mt-1 text-xs text-gray-500">{{ __('admin.auto_filled_hint') }}</p>
    </div>

    {{-- 6. Session Date — split Y/M/D to prevent 5-digit year --}}
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

    {{-- 7. Start Time --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.start_time') }} (۱۵:۰۰ – ۲۱:۳۰)</label>
        <input type="time" name="start_time" required value="{{ old('start_time') }}"
               min="15:00" max="21:30"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        @error('start_time')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 8. Duration --}}
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

    {{-- 9. Room — sourced from the rooms table (active only), replacing the
         previous hardcoded list --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.room') }}</label>
        <select name="room" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_room_placeholder') }}</option>
            @foreach ($rooms as $room)
                <option value="{{ $room->name }}" {{ old('room') === $room->name ? 'selected' : '' }}>{{ $room->name }}</option>
            @endforeach
        </select>
        @error('room')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 10. Session Fee (manual, admin can override the base-fee suggestion above) --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.session_fee') }}
            <span class="text-gray-500 text-xs">({{ __('admin.currency_toman') }}) ({{ __('admin.optional') }})</span>
        </label>
        <input type="number" name="session_fee" x-model="sessionFee" value="{{ old('session_fee') }}"
               min="0" step="1000"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="0">
        @error('session_fee')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 11. Discount --}}
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

    {{-- 12. Notes --}}
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
    const enrollmentMeta  = @json($enrollmentMeta);
    const oldStudentId    = '{{ old("student_id", "") }}';
    const oldEnrollmentId = '{{ old("enrollment_id", "") }}';

    return {
        studentId:            oldStudentId,
        enrollmentId:         oldEnrollmentId,
        teacherName:          '',
        instrumentName:       '',
        baseFee:              null,
        sessionFee:           '{{ old('session_fee', '') }}',
        visibleEnrollmentCount: 0,

        init() {
            // Apply the initial student filter (handles old-input restore
            // after a validation error) and restore auto-filled fields.
            this.$nextTick(() => {
                this.filterEnrollmentOptions();
                if (this.enrollmentId) {
                    this._fillFromEnrollment(this.enrollmentId);
                }
            });
        },

        onStudentChange() {
            this.enrollmentId   = '';
            this.teacherName    = '';
            this.instrumentName = '';
            this.baseFee        = null;
            if (this.$refs.enrollmentSelect) {
                this.$refs.enrollmentSelect.value = '';
            }
            this.filterEnrollmentOptions();
        },

        onEnrollmentChange(id) {
            this.enrollmentId = id;
            this._fillFromEnrollment(id);
        },

        /**
         * Toggle the `hidden` attribute on each <option> based on the
         * selected student. All options are already rendered server-side,
         * so this only affects visibility — no re-render, no sync issues.
         */
        filterEnrollmentOptions() {
            const select = this.$refs.enrollmentSelect;
            if (!select) return;

            let visible = 0;
            Array.from(select.options).forEach((opt) => {
                if (!opt.value) return; // keep the placeholder always visible
                const matches = !this.studentId || opt.dataset.studentId === String(this.studentId);
                opt.hidden = !matches;
                if (matches) visible++;
            });
            this.visibleEnrollmentCount = visible;
        },

        _fillFromEnrollment(id) {
            const meta = enrollmentMeta[id];
            this.teacherName    = meta ? meta.teacher_name    : '';
            this.instrumentName = meta ? meta.instrument_name : '';
            this.baseFee        = meta ? meta.base_fee        : null;
            // Pre-fill the editable session fee with the suggested base fee
            // only if the admin hasn't already typed a value.
            if (meta && meta.base_fee !== null && !this.sessionFee) {
                this.sessionFee = meta.base_fee;
            }
        },
    };
}
</script>

@endsection
