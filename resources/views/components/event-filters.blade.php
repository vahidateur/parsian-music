{{--
    Calendar Event Filters — owns the four server-populated calendar filters and mobile controls.
    Props: teachers, students, rooms, instruments, selectedFilters.
    Phase: Admin Calendar Module.
    Slots: none.
--}}
@props([
    'teachers' => [],
    'students' => [],
    'rooms' => [],
    'instruments' => [],
    'selectedFilters' => [],
])

<section
    class="calendar-filters"
    aria-labelledby="calendar-filters-title"
    data-calendar-filters
>
    <div class="calendar-filters__header">
        <h2 id="calendar-filters-title" class="calendar-filters__title">{{ __('admin.calendar_filters.title') }}</h2>
        <div class="calendar-filters__actions">
            <span
                class="calendar-filters__count"
                data-calendar-filters-count
                aria-label="{{ __('admin.calendar_aria.active_filters') }}"
                hidden
            ></span>
            <button
                type="button"
                class="calendar-filters__toggle"
                data-calendar-filters-toggle
                aria-controls="calendar-filter-fields"
                aria-expanded="false"
            >
                <span>{{ __('admin.calendar_filters.toggle') }}</span>
                <span aria-hidden="true">⌄</span>
            </button>
            <button
                type="button"
                class="calendar-filters__clear"
                data-calendar-filters-clear
            >
                {{ __('admin.calendar_filters.clear_all') }}
            </button>
        </div>
    </div>

    <div
        id="calendar-filter-fields"
        class="calendar-filters__fields"
        data-calendar-filter-fields
    >
        <div class="calendar-filters__field">
            <label class="calendar-filters__label" for="calendar-filter-teacher">{{ __('admin.calendar_filters.teacher') }}</label>
            <select
                id="calendar-filter-teacher"
                name="teacher_id"
                class="calendar-filters__select"
                data-calendar-filter="teacher_id"
                aria-describedby="calendar-filter-teacher-hint"
            >
                <option value="">{{ __('admin.calendar_filters.all_teachers') }}</option>
                @foreach ($teachers as $teacher)
                    <option
                        value="{{ $teacher->id }}"
                        @selected((string) ($selectedFilters['teacher_id'] ?? '') === (string) $teacher->id)
                    >{{ $teacher->full_name ?? $teacher->name ?? __('admin.calendar_states.missing_value') }}</option>
                @endforeach
            </select>
            <span id="calendar-filter-teacher-hint" class="calendar-filters__hint">{{ __('admin.calendar_filters.by_teacher') }}</span>
        </div>

        <div class="calendar-filters__field">
            <label class="calendar-filters__label" for="calendar-filter-student">{{ __('admin.calendar_filters.student') }}</label>
            <select
                id="calendar-filter-student"
                name="student_id"
                class="calendar-filters__select"
                data-calendar-filter="student_id"
                aria-describedby="calendar-filter-student-hint"
            >
                <option value="">{{ __('admin.calendar_filters.all_students') }}</option>
                @foreach ($students as $student)
                    <option
                        value="{{ $student->id }}"
                        @selected((string) ($selectedFilters['student_id'] ?? '') === (string) $student->id)
                    >{{ $student->full_name ?? $student->name ?? __('admin.calendar_states.missing_value') }}</option>
                @endforeach
            </select>
            <span id="calendar-filter-student-hint" class="calendar-filters__hint">{{ __('admin.calendar_filters.by_student') }}</span>
        </div>

        <div class="calendar-filters__field">
            <label class="calendar-filters__label" for="calendar-filter-room">{{ __('admin.calendar_filters.room') }}</label>
            <select
                id="calendar-filter-room"
                name="room"
                class="calendar-filters__select"
                data-calendar-filter="room"
                aria-describedby="calendar-filter-room-hint"
            >
                <option value="">{{ __('admin.calendar_filters.all_rooms') }}</option>
                @foreach ($rooms as $room)
                    <option
                        value="{{ $room }}"
                        @selected((string) ($selectedFilters['room'] ?? '') === (string) $room)
                    >{{ $room }}</option>
                @endforeach
            </select>
            <span id="calendar-filter-room-hint" class="calendar-filters__hint">{{ __('admin.calendar_filters.by_room') }}</span>
        </div>

        <div class="calendar-filters__field">
            <label class="calendar-filters__label" for="calendar-filter-instrument">{{ __('admin.calendar_filters.instrument') }}</label>
            <select
                id="calendar-filter-instrument"
                name="instrument_id"
                class="calendar-filters__select"
                data-calendar-filter="instrument_id"
                aria-describedby="calendar-filter-instrument-hint"
            >
                <option value="">{{ __('admin.calendar_filters.all_instruments') }}</option>
                @foreach ($instruments as $instrument)
                    <option
                        value="{{ $instrument->id }}"
                        @selected((string) ($selectedFilters['instrument_id'] ?? '') === (string) $instrument->id)
                    >{{ $instrument->display_name ?? $instrument->name ?? __('admin.calendar_states.missing_value') }}</option>
                @endforeach
            </select>
            <span id="calendar-filter-instrument-hint" class="calendar-filters__hint">{{ __('admin.calendar_filters.by_instrument') }}</span>
        </div>
    </div>
</section>
