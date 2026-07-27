{{--
    Day Timeline — stable FullCalendar mount, loading, empty, and retry regions.
    Props: mountId, skeletonId, errorId. Phase: Admin Calendar Module.
    Slots: none.
--}}
@props([
    'mountId' => 'calendar-mount',
    'skeletonId' => 'calendar-skeleton',
    'errorId' => 'calendar-error',
])

<section {{ $attributes->merge(['class' => 'calendar-day-timeline']) }} id="calendar-day-timeline" data-calendar-timeline aria-labelledby="calendar-timeline-title" aria-describedby="calendar-timeline-status">
    <h2 id="calendar-timeline-title" class="sr-only">{{ __('admin.calendar_labels.daily_schedule') }}</h2>

    <div id="calendar-timeline-status" class="calendar-day-timeline__status" data-calendar-status aria-live="polite"></div>

    <div id="{{ $skeletonId }}" class="calendar-day-timeline__skeleton" data-calendar-loading role="status" aria-live="polite" aria-busy="true">
        <span class="calendar-day-timeline__skeleton-row" aria-hidden="true"></span>
        <span class="calendar-day-timeline__skeleton-row" aria-hidden="true"></span>
        <span class="calendar-day-timeline__skeleton-row" aria-hidden="true"></span>
        <span class="calendar-day-timeline__skeleton-row" aria-hidden="true"></span>
        <span class="calendar-day-timeline__skeleton-label">{{ __('admin.calendar_states.loading') }}</span>
    </div>

    <div id="calendar-empty-state" class="calendar-day-timeline__empty" data-calendar-empty role="status" aria-live="polite" hidden>
        <p>{{ __('admin.calendar_states.empty') }}</p>
    </div>

    <div id="{{ $errorId }}" class="calendar-day-timeline__error" data-calendar-error role="alert" aria-live="assertive" hidden>
        <p data-calendar-error-message>{{ __('admin.calendar_states.error') }}</p>
        <button id="calendar-retry" class="calendar-day-timeline__retry" type="button" data-calendar-retry>{{ __('admin.calendar_states.retry') }}</button>
    </div>

    <div id="{{ $mountId }}" class="calendar-day-timeline__mount" data-calendar-mount data-fullcalendar-mount tabindex="0" role="application" aria-label="{{ __('admin.calendar_aria.timeline') }}"></div>
</section>
