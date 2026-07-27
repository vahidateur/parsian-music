{{--
    Calendar layout wrapper.
    Props: teachers, students, rooms, instruments, eventsUrl.
    Slots: none. Phase: Admin Calendar Module.
--}}
@props([
    'teachers' => [],
    'students' => [],
    'rooms' => [],
    'instruments' => [],
    'eventsUrl' => null,
])

<section
    id="admin-calendar"
    class="calendar"
    dir="rtl"
    data-calendar-root
    data-events-url="{{ $eventsUrl ?? '' }}"
    data-calendar-message-loading="{{ __('admin.calendar_states.loading') }}"
    data-calendar-message-empty="{{ __('admin.calendar_states.empty') }}"
    data-calendar-message-error="{{ __('admin.calendar_errors.loading_sessions') }}"
    data-calendar-message-no-notes="{{ __('admin.calendar_states.no_notes') }}"
    data-calendar-status-scheduled="{{ __('admin.session_statuses.scheduled') }}"
    data-calendar-status-completed="{{ __('admin.session_statuses.completed') }}"
    data-calendar-status-cancelled="{{ __('admin.session_statuses.cancelled') }}"
    data-calendar-status-missed="{{ __('admin.session_statuses.missed') }}"
    data-calendar-mount-id="calendar-mount"
    data-calendar-skeleton-id="calendar-skeleton"
    data-calendar-error-id="calendar-error"
    aria-labelledby="calendar-page-title"
>
    <x-calendar-header />

    <div class="calendar__workspace">
        <aside class="calendar__sidebar" aria-label="{{ __('admin.calendar_labels.week_days') }}">
            <x-week-sidebar />
        </aside>

        <div class="calendar__content">
            <x-event-filters
                :teachers="$teachers"
                :students="$students"
                :rooms="$rooms"
                :instruments="$instruments"
            />

            <section class="calendar__timeline" data-calendar-timeline aria-label="{{ __('admin.calendar_labels.daily_schedule') }}">
                <x-day-timeline
                    mount-id="calendar-mount"
                    skeleton-id="calendar-skeleton"
                    error-id="calendar-error"
                />
            </section>
        </div>
    </div>

    <x-event-drawer />
</section>
