{{--
    Week Sidebar — keyboard-reachable Saturday-to-Friday day navigation hooks.
    Props: none. Phase: Admin Calendar Module.
    Slots: none.
--}}
@props([])

<aside {{ $attributes->merge(['class' => 'calendar-week-sidebar']) }} id="calendar-week-sidebar" data-calendar-week-sidebar aria-labelledby="calendar-week-sidebar-title">
    <header class="calendar-week-sidebar__header">
        <h2 id="calendar-week-sidebar-title" class="calendar-week-sidebar__title">{{ __('admin.calendar_labels.week_days') }}</h2>
    </header>

    <nav class="calendar-week-sidebar__navigation" aria-label="{{ __('admin.calendar_navigation.week_days') }}" data-calendar-week-navigation>
        <ol id="calendar-week-days" class="calendar-week-sidebar__days" data-calendar-week-days data-calendar-selected-day>
            <li class="calendar-week-sidebar__day" data-calendar-week-day-item>
                <button class="calendar-week-sidebar__day-button" type="button" data-calendar-week-day data-calendar-day-index="0" aria-label="{{ __('admin.calendar_weekdays.saturday') }}">
                    <span data-calendar-week-day-name>{{ __('admin.calendar_weekdays.saturday') }}</span><span data-calendar-week-day-number aria-hidden="true">{{ __('admin.calendar_states.missing_value') }}</span>
                </button>
            </li>
            <li class="calendar-week-sidebar__day" data-calendar-week-day-item>
                <button class="calendar-week-sidebar__day-button" type="button" data-calendar-week-day data-calendar-day-index="1" aria-label="{{ __('admin.calendar_weekdays.sunday') }}">
                    <span data-calendar-week-day-name>{{ __('admin.calendar_weekdays.sunday') }}</span><span data-calendar-week-day-number aria-hidden="true">{{ __('admin.calendar_states.missing_value') }}</span>
                </button>
            </li>
            <li class="calendar-week-sidebar__day" data-calendar-week-day-item>
                <button class="calendar-week-sidebar__day-button" type="button" data-calendar-week-day data-calendar-day-index="2" aria-label="{{ __('admin.calendar_weekdays.monday') }}">
                    <span data-calendar-week-day-name>{{ __('admin.calendar_weekdays.monday') }}</span><span data-calendar-week-day-number aria-hidden="true">{{ __('admin.calendar_states.missing_value') }}</span>
                </button>
            </li>
            <li class="calendar-week-sidebar__day" data-calendar-week-day-item>
                <button class="calendar-week-sidebar__day-button" type="button" data-calendar-week-day data-calendar-day-index="3" aria-label="{{ __('admin.calendar_weekdays.tuesday') }}">
                    <span data-calendar-week-day-name>{{ __('admin.calendar_weekdays.tuesday') }}</span><span data-calendar-week-day-number aria-hidden="true">{{ __('admin.calendar_states.missing_value') }}</span>
                </button>
            </li>
            <li class="calendar-week-sidebar__day" data-calendar-week-day-item>
                <button class="calendar-week-sidebar__day-button" type="button" data-calendar-week-day data-calendar-day-index="4" aria-label="{{ __('admin.calendar_weekdays.wednesday') }}">
                    <span data-calendar-week-day-name>{{ __('admin.calendar_weekdays.wednesday') }}</span><span data-calendar-week-day-number aria-hidden="true">{{ __('admin.calendar_states.missing_value') }}</span>
                </button>
            </li>
            <li class="calendar-week-sidebar__day" data-calendar-week-day-item>
                <button class="calendar-week-sidebar__day-button" type="button" data-calendar-week-day data-calendar-day-index="5" aria-label="{{ __('admin.calendar_weekdays.thursday') }}">
                    <span data-calendar-week-day-name>{{ __('admin.calendar_weekdays.thursday') }}</span><span data-calendar-week-day-number aria-hidden="true">{{ __('admin.calendar_states.missing_value') }}</span>
                </button>
            </li>
            <li class="calendar-week-sidebar__day" data-calendar-week-day-item>
                <button class="calendar-week-sidebar__day-button" type="button" data-calendar-week-day data-calendar-day-index="6" aria-label="{{ __('admin.calendar_weekdays.friday') }}">
                    <span data-calendar-week-day-name>{{ __('admin.calendar_weekdays.friday') }}</span><span data-calendar-week-day-number aria-hidden="true">{{ __('admin.calendar_states.missing_value') }}</span>
                </button>
            </li>
        </ol>
    </nav>
</aside>
