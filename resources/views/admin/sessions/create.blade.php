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

<form method="POST" action="{{ route('admin.sessions.store') }}" class="max-w-2xl space-y-6">
    @csrf

    {{-- 1. Student (Searchable Autocomplete) --}}
    <div x-data="studentAutocomplete()" class="relative">
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.student') }}</label>
        <input type="hidden" name="student_id" x-model="selectedId" required>
        <div class="relative">
            <input type="text" x-model="query" @input="search()" @keydown.down="next()" @keydown.up="prev()" @keydown.enter="select()"
                   placeholder="جستجو هنرجو..."
                   class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                   autocomplete="off">
            
            {{-- Dropdown Results --}}
            <ul x-show="results.length > 0" class="absolute z-10 mt-1 w-full rounded-lg border border-gray-700 bg-gray-900 shadow-lg max-h-48 overflow-y-auto">
                <template x-for="(result, index) in results" :key="index">
                    <li @click="selectResult(result)" :class="index === highlighted ? 'bg-amber-500/20' : 'bg-gray-900 hover:bg-gray-800'" 
                        class="px-4 py-2.5 cursor-pointer text-sm text-gray-200 transition">
                        <span x-text="`${result.full_name} (${result.id})`"></span>
                    </li>
                </template>
            </ul>

            {{-- No Results Message --}}
            <div x-show="query.length >= 2 && results.length === 0 && !loading" class="absolute z-10 mt-1 w-full rounded-lg border border-gray-700 bg-gray-900 px-4 py-3 text-sm text-gray-400">
                هنرجویی یافت نشد
            </div>

            {{-- Selected Value Display --}}
            <div x-show="selectedId" class="absolute left-4 top-3 text-sm text-amber-400 font-medium">
                <span x-text="selectedStudent"></span>
            </div>
        </div>
        @error('student_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 2. Teacher — always enabled, server-rendered options --}}
    <div x-data="teacherDropdown()">
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.teacher') }}</label>
        <select name="teacher_id" x-ref="teacherSelect" @change="onTeacherChange()" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_teacher') }}</option>
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

    {{-- 3. Instrument — filtered by teacher via teacher_instruments pivot --}}
    <div x-data="instrumentDropdown()" @teacher-selected.window="onTeacherSelected($event.detail)">
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.instrument') }}</label>
        <select name="instrument_id" x-ref="instrumentSelect" @change="onInstrumentChange()" :disabled="!hasTeacher" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
            <option value="">ابتدا معلم را انتخاب کنید</option>
        </select>
        @error('instrument_id')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 4. Session Date — split Y/M/D to prevent 5-digit year --}}
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

    {{-- 5. Start Time --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.start_time') }} (۱۵:۰۰ – ۲۱:۳۰)</label>
        <input type="time" name="start_time" required value="{{ old('start_time') }}"
               min="15:00" max="21:30"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        @error('start_time')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 6. Duration --}}
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

    {{-- 7. Room — temporary hardcoded list (A101/A102/A103) --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.room') }}</label>
        <select name="room" required
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.select_room_placeholder') }}</option>
            @foreach ($rooms as $room)
                <option value="{{ $room }}" {{ old('room') === $room ? 'selected' : '' }}>{{ $room }}</option>
            @endforeach
        </select>
        @error('room')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- 8. Over-quota Warning --}}
    <div x-data="overageWarning()"
         @student-selected.window="onStudentSelected($event.detail)"
         @teacher-selected.window="onTeacherChanged($event.detail)"
         @instrument-selected.window="onInstrumentChanged($event.detail)">
        {{-- Overage Warning Box --}}
        <div x-show="subscription && subscription.sessions_used >= subscription.sessions_allocated"
             x-cloak
             class="rounded-lg border border-amber-400 bg-amber-500/10 p-4 space-y-3">
            <p class="text-sm font-medium text-amber-400">⚠️ Sessions exceeded. Session will be marked as overage.</p>
            <div>
                <p class="mb-1.5 text-sm text-amber-300/80">Optional reason for overage:</p>
                <input type="text" name="notes"
                       placeholder="Reason..."
                       maxlength="255"
                       value="{{ old('notes') }}"
                       class="block w-full rounded-lg border border-amber-400/40 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            </div>
        </div>

        {{-- 8. Notes (hidden when overage warning is shown) --}}
        <div x-show="!(subscription && subscription.sessions_used >= subscription.sessions_allocated)">
            <label class="mb-1.5 block text-sm font-medium text-gray-300">
                {{ __('admin.notes') }}
                <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
            </label>
            <textarea name="notes" rows="3"
                      class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                      placeholder="{{ __('admin.optional_notes') }}">{{ old('notes') }}</textarea>
        </div>
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
@php
// Build teacher_id → [{id, name}] map from teacher_instruments pivot.
// Single query; used by Alpine to filter instruments when a teacher is chosen.
$teacherInstrumentMap = \Illuminate\Support\Facades\DB::table('teacher_instruments')
    ->join('instruments', 'teacher_instruments.instrument_id', '=', 'instruments.id')
    ->where('instruments.is_active', true)
    ->select('teacher_instruments.teacher_id',
             'instruments.id',
             'instruments.name_fa',
             'instruments.name')
    ->get()
    ->groupBy('teacher_id')
    ->map(fn ($rows) => $rows->map(fn ($r) => [
        'id'   => $r->id,
        'name' => $r->name_fa ?: $r->name,
    ])->values());
@endphp

const teacherInstrumentMap = @json($teacherInstrumentMap);

function rebuildSelectOptions(selectEl, items, placeholder) {
    if (!selectEl) return;
    selectEl.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = placeholder;
    selectEl.appendChild(ph);
    items.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.name;
        selectEl.appendChild(opt);
    });
}

// Teacher dropdown: server-rendered static options, always enabled.
// Dispatches 'teacher-selected' with the raw DOM value to avoid x-model timing race.
function teacherDropdown() {
    return {
        onTeacherChange() {
            const teacherId = this.$refs.teacherSelect.value;
            this.$dispatch('teacher-selected', { teacherId });
        },
    };
}

// Instrument dropdown: disabled until a teacher is chosen.
// Filters options from teacherInstrumentMap (teacher_instruments pivot).
function instrumentDropdown() {
    return {
        hasTeacher: false,

        onTeacherSelected(detail) {
            const teacherId = detail.teacherId;
            this.hasTeacher = !!teacherId;

            if (!teacherId) {
                rebuildSelectOptions(this.$refs.instrumentSelect, [], 'ابتدا معلم را انتخاب کنید');
                this.$dispatch('instrument-selected', { instrumentId: '' });
                return;
            }

            const instruments = teacherInstrumentMap[teacherId] || [];
            rebuildSelectOptions(
                this.$refs.instrumentSelect,
                instruments,
                instruments.length === 0 ? 'ساز تعریف‌نشده' : '{{ __('admin.select_instrument') }}'
            );
        },

        onInstrumentChange() {
            const instrumentId = this.$refs.instrumentSelect.value;
            this.$dispatch('instrument-selected', { instrumentId });
        },
    };
}

// Overage warning: checks student subscriptions when teacher + instrument are both chosen.
function overageWarning() {
    return {
        subscription: null,
        studentSubscriptions: [],
        teacherId: '',
        instrumentId: '',

        onStudentSelected(detail) {
            this.studentSubscriptions = detail.subscriptions || [];
            this.checkSubscription();
        },

        onTeacherChanged(detail) {
            this.teacherId = detail.teacherId || '';
            this.checkSubscription();
        },

        onInstrumentChanged(detail) {
            this.instrumentId = String(detail.instrumentId || '');
            this.checkSubscription();
        },

        checkSubscription() {
            if (!this.teacherId || !this.instrumentId) {
                this.subscription = null;
                return;
            }
            this.subscription = this.studentSubscriptions.find(sub =>
                String(sub.teacher_id) === String(this.teacherId) &&
                String(sub.instrument_id) === this.instrumentId
            ) || null;
        },
    };
}

function studentAutocomplete() {
    return {
        query: '',
        results: [],
        highlighted: -1,
        selectedId: '',
        selectedStudent: '',
        selectedSubscriptions: [],
        loading: false,
        allStudents: @json($students),

        search() {
            this.highlighted = -1;
            
            if (this.query.length < 2) {
                this.results = [];
                return;
            }

            const q = this.query.toLowerCase();
            this.results = this.allStudents
                .filter(s => 
                    s.full_name.toLowerCase().includes(q) || 
                    s.id.toString().includes(q)
                )
                .slice(0, 10);
        },

        selectResult(result) {
            this.selectedId = result.id;
            this.selectedStudent = `${result.full_name}`;
            this.selectedSubscriptions = result.subscriptions || [];
            this.query = '';
            this.results = [];
            // Notify teacher/instrument dropdowns that a student was selected
            this.$dispatch('student-selected', {
                studentId: result.id,
                subscriptions: result.subscriptions || [],
            });
        },

        select() {
            if (this.highlighted >= 0 && this.results[this.highlighted]) {
                this.selectResult(this.results[this.highlighted]);
            }
        },

        next() {
            if (this.highlighted < this.results.length - 1) {
                this.highlighted++;
            }
        },

        prev() {
            if (this.highlighted > 0) {
                this.highlighted--;
            }
        }
    };
}
</script>

@endsection
