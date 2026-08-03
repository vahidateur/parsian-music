{{--
    Student history detail section (stable identifier `student_history`).
    Expects:
      $detail  App\DTOs\RecordDetailData  — carries the localized placeholder
      $section App\DTOs\RecordDetailSection — history rows in deterministic order
    Rows arrive already resolved from persisted data by StudentDetailQuery, so no
    query runs here. The shared Empty_State renders when no event exists.
--}}

@php
/**
 * Presentation config per event type: [dot, badge, border, icon path].
 * Tailwind cannot resolve interpolated class names, so the map stays explicit.
 */
if (!function_exists('timelineConfig')) {
function timelineConfig(string $type): array {
    return match ($type) {
        'student_created'    => ['bg-sky-400',     'bg-sky-500/10 text-sky-300',     'border-sky-500/30',
            'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        'enrollment_created' => ['bg-emerald-400', 'bg-emerald-500/10 text-emerald-300', 'border-emerald-500/30',
            'M12 4.5v15m7.5-7.5h-15'],
        'teacher_changed'    => ['bg-violet-400',  'bg-violet-500/10 text-violet-300',  'border-violet-500/30',
            'M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342'],
        'instrument_changed' => ['bg-amber-400',   'bg-amber-500/10 text-amber-300',    'border-amber-500/30',
            'M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a2.25 2.25 0 001.632-2.163zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 01-.99-3.467l2.31-.66A2.25 2.25 0 009 15.553z'],
        'session_completed'  => ['bg-green-400',   'bg-green-500/10 text-green-300',    'border-green-500/30',
            'M4.5 12.75l6 6 9-13.5'],
        'session_cancelled'  => ['bg-red-400',     'bg-red-500/10 text-red-300',        'border-red-500/30',
            'M6 18L18 6M6 6l12 12'],
        'attendance_marked'  => ['bg-orange-400',  'bg-orange-500/10 text-orange-300',  'border-orange-500/30',
            'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
        'discount_assigned'  => ['bg-indigo-400',  'bg-indigo-500/10 text-indigo-300',  'border-indigo-500/30',
            'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'admin_note'         => ['bg-gray-400',    'bg-gray-700/50 text-gray-300',      'border-gray-600/30',
            'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z'],
        default              => ['bg-gray-500',    'bg-gray-700/50 text-gray-400',      'border-gray-600/30',
            'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z'],
    };
}
}
@endphp

<section id="{{ $section->id }}" data-section="{{ $section->id }}" aria-labelledby="{{ $section->id }}_heading"
         class="mt-8 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-800/60 px-6 py-4">
        <h2 id="{{ $section->id }}_heading" class="text-lg font-semibold text-amber-100">{{ $section->title }}</h2>
        @if ($section->rowCount())
            <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">
                {{ __('admin.total_count', ['count' => $section->rowCount()]) }}
            </span>
        @endif
    </div>

    @if ($section->rowCount() === 0)
        <x-dashboard.empty-state :message="$section->empty_message" compact>
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-dashboard.empty-state>
    @else
        <div class="px-6 py-6">
            <ol class="relative space-y-0 border-e border-gray-800/60 pe-6">
                @foreach ($section->rows as $i => $row)
                    @php
                        [$dot, $badgeBg, $badgeBorder, $iconPath] = timelineConfig((string) $row->field('event_type'));
                        $isLast = $i === $section->rowCount() - 1;
                    @endphp
                    <li data-history-key="{{ $row->id }}" class="{{ $isLast ? '' : 'pb-6' }} relative">
                        {{-- Connector dot --}}
                        <span class="absolute -end-[1.3rem] flex h-6 w-6 items-center justify-center rounded-full border border-gray-800 {{ $dot }} shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
                            </svg>
                        </span>

                        {{-- Event card --}}
                        <div class="rounded-xl border {{ $badgeBorder }} bg-gray-800/30 px-4 py-3 transition duration-200 hover:bg-gray-800/50">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="inline-flex items-center rounded-full {{ $badgeBg }} px-2.5 py-0.5 text-xs font-medium">
                                    {{ $row->label }}
                                </span>
                                <time class="text-xs text-gray-500 tabular-nums" dir="ltr">{{ $detail->display($row->field('timestamp')) }}</time>
                            </div>

                            <p class="mt-1.5 text-sm text-gray-300">{{ $detail->display($row->field('description')) }}</p>

                            @if ($row->relations !== [])
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($row->relations as $value)
                                        @if ($value !== null && $value !== '—')
                                            <span class="inline-flex items-center gap-1 rounded-md border border-gray-700/50 bg-gray-700/20 px-2 py-0.5 text-xs text-gray-400">
                                                {{ $value }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</section>
