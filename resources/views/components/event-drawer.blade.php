{{--
    Calendar Event Drawer — owns accessible session details and responsive drawer hooks.
    Props: none; event state is populated by the calendar drawer module.
    Phase: Admin Calendar Module.
    Slots: none.
--}}
<div
    class="calendar-drawer"
    x-data="{ open: false, status: '', statusLabel: '', statusClass: '', studentName: '{{ __('admin.calendar_states.missing_value') }}', teacherName: '{{ __('admin.calendar_states.missing_value') }}', instrumentName: '{{ __('admin.calendar_states.missing_value') }}', sessionDate: '{{ __('admin.calendar_states.missing_value') }}', startTime: '{{ __('admin.calendar_states.missing_value') }}', duration: '{{ __('admin.calendar_states.missing_value') }}', room: '{{ __('admin.calendar_states.missing_value') }}', notes: '{{ __('admin.calendar_states.no_notes') }}' }"
    x-cloak
    data-calendar-drawer
>
    <div
        class="calendar-drawer__overlay"
        x-show="open"
        x-transition:enter="calendar-drawer__overlay--enter"
        x-transition:leave="calendar-drawer__overlay--leave"
        x-on:click="close()"
        aria-hidden="true"
        data-calendar-drawer-overlay
    ></div>

    <aside
        id="calendar-event-drawer"
        class="calendar-drawer__panel"
        x-show="open"
        x-transition:enter="calendar-drawer__panel--enter"
        x-transition:leave="calendar-drawer__panel--leave"
        x-trap.noscroll="open"
        x-on:keydown.escape.window="close()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="calendar-event-drawer-title"
        data-calendar-drawer-panel
    >
        <div class="calendar-drawer__handle" aria-hidden="true"></div>

        <header class="calendar-drawer__header">
            <div class="calendar-drawer__heading-group">
                <span class="calendar-drawer__eyebrow">{{ __('admin.calendar_labels.session_details') }}</span>
                <h2 id="calendar-event-drawer-title" class="calendar-drawer__title" x-text="studentName">{{ __('admin.calendar_states.missing_value') }}</h2>
            </div>
            <button
                type="button"
                class="calendar-drawer__close"
                x-on:click="close()"
                aria-label="{{ __('admin.calendar_aria.close_details') }}"
                data-calendar-drawer-close
            >
                <span aria-hidden="true">×</span>
            </button>
        </header>

        <div class="calendar-drawer__body" aria-live="polite">
            <div class="calendar-drawer__status-row">
                <span class="calendar-drawer__label">{{ __('admin.calendar_labels.status') }}</span>
                <span
                    class="calendar-drawer__status-badge"
                    x-bind:data-status="status"
                    x-bind:class="statusClass"
                    x-text="statusLabel || '{{ __('admin.calendar_states.missing_value') }}'"
                    data-calendar-status-hook
                >{{ __('admin.calendar_states.missing_value') }}</span>
            </div>

            <dl class="calendar-drawer__details">
                <div class="calendar-drawer__detail">
                    <dt>{{ __('admin.calendar_labels.student') }}</dt>
                    <dd x-text="studentName">{{ __('admin.calendar_states.missing_value') }}</dd>
                </div>
                <div class="calendar-drawer__detail">
                    <dt>{{ __('admin.calendar_labels.teacher') }}</dt>
                    <dd x-text="teacherName">{{ __('admin.calendar_states.missing_value') }}</dd>
                </div>
                <div class="calendar-drawer__detail">
                    <dt>{{ __('admin.calendar_labels.instrument') }}</dt>
                    <dd x-text="instrumentName">{{ __('admin.calendar_states.missing_value') }}</dd>
                </div>
                <div class="calendar-drawer__detail">
                    <dt>{{ __('admin.calendar_labels.session_date') }}</dt>
                    <dd x-text="sessionDate">{{ __('admin.calendar_states.missing_value') }}</dd>
                </div>
                <div class="calendar-drawer__detail">
                    <dt>{{ __('admin.calendar_labels.start_time') }}</dt>
                    <dd x-text="startTime">{{ __('admin.calendar_states.missing_value') }}</dd>
                </div>
                <div class="calendar-drawer__detail">
                    <dt>{{ __('admin.calendar_labels.duration') }}</dt>
                    <dd><span x-text="duration">{{ __('admin.calendar_states.missing_value') }}</span> {{ __('admin.minutes') }}</dd>
                </div>
                <div class="calendar-drawer__detail">
                    <dt>{{ __('admin.calendar_labels.room') }}</dt>
                    <dd x-text="room">{{ __('admin.calendar_states.missing_value') }}</dd>
                </div>
            </dl>

            <section class="calendar-drawer__notes" aria-labelledby="calendar-event-drawer-notes-title">
                <h3 id="calendar-event-drawer-notes-title" class="calendar-drawer__section-title">{{ __('admin.calendar_labels.session_notes') }}</h3>
                <p class="calendar-drawer__notes-content" x-text="notes || '{{ __('admin.calendar_states.no_notes') }}'">{{ __('admin.calendar_states.no_notes') }}</p>
            </section>
        </div>
    </aside>
</div>
