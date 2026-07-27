{{--
    Calendar Header — semantic day navigation and live Jalali date mount.
    Props: dateLabel (string|null). Phase: Admin Calendar Module.
    Slots: none.
--}}
@props(['dateLabel' => null])

<header {{ $attributes->merge(['class' => 'calendar-header']) }} data-calendar-header>
    <div class="calendar-header__content">
        <p class="calendar-header__eyebrow">{{ __('admin.calendar_labels.title') }}</p>
        <p id="calendar-current-date" class="calendar-header__date" data-calendar-current-date aria-live="polite">
            {{ $dateLabel ?? __('admin.calendar_labels.date_loading') }}
        </p>
    </div>

    <nav class="calendar-header__navigation" aria-label="{{ __('admin.calendar_navigation.day') }}">
        <button id="calendar-prev-day" class="calendar-header__control" type="button" data-calendar-action="previous" data-calendar-prev-day aria-label="{{ __('admin.calendar_navigation.previous_day') }}">
            <span aria-hidden="true">←</span>
            <span>{{ __('admin.calendar_navigation.previous_day') }}</span>
        </button>
        <button id="calendar-today" class="calendar-header__control calendar-header__control--today" type="button" data-calendar-action="today" data-calendar-today aria-label="{{ __('admin.calendar_navigation.go_to_today') }}">
            {{ __('admin.today') }}
        </button>
        <button id="calendar-next-day" class="calendar-header__control" type="button" data-calendar-action="next" data-calendar-next-day aria-label="{{ __('admin.calendar_navigation.next_day') }}">
            <span>{{ __('admin.calendar_navigation.next_day') }}</span>
            <span aria-hidden="true">→</span>
        </button>
    </nav>
</header>
