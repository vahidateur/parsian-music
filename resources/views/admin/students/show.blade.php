{{--
    Student Record_Detail screen (route: admin.students.show).
    Expects: $detail (App\DTOs\RecordDetailData) resolved by StudentDetailQuery,
    plus $student and $financialSummary for the sections owned elsewhere.
    Absent persisted values fall back to the localized placeholder of the DTO.
--}}
@extends('layouts.dashboard')

@section('content')

@php
    $profile = $detail->section(\App\Services\Details\StudentDetailQuery::SECTION_PROFILE);
    $history = $detail->section(\App\Services\Details\StudentDetailQuery::SECTION_HISTORY);
    // Explicit map: Tailwind cannot resolve interpolated class names.
    $studentStatusColors = ['active' => 'emerald', 'paused' => 'amber', 'inactive' => 'gray', 'graduated' => 'sky'];
@endphp

{{-- Back + Actions --}}
<div class="mb-8 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.students.index') }}" class="text-sm text-gray-400 transition hover:text-gray-200">{{ __('admin.back_to_students') }}</a>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.enrollments.create', ['student_id' => $student->id]) }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('admin.add_enrollment') }}
        </a>
        <a href="{{ route('admin.students.edit', $student) }}" class="rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">
            {{ __('admin.edit_student') }}
        </a>
    </div>
</div>

{{-- Page heading: the single h1 of this screen --}}
<header class="mb-6">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold text-amber-100">{{ $detail->label }}</h1>
        @if ($detail->status_label)
            <x-admin.status-badge :label="$detail->status_label" :color="$studentStatusColors[$detail->status] ?? 'gray'" />
        @endif
    </div>
</header>

{{-- Section 1: persisted profile values --}}
<section id="{{ $profile->id }}" data-section="{{ $profile->id }}" aria-labelledby="{{ $profile->id }}_heading"
         class="mb-8 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="border-b border-gray-800/60 px-6 py-4">
        <h2 id="{{ $profile->id }}_heading" class="text-lg font-semibold text-amber-100">{{ $profile->title }}</h2>
    </div>
    <dl class="grid grid-cols-1 gap-6 px-6 py-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($profile->fields as $field)
            <div @class(['lg:col-span-3' => $field['multiline']])>
                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ $field['label'] }}</dt>
                <dd @class(['mt-1 text-sm text-gray-100', 'tabular-nums' => $field['dir'] === 'ltr', 'whitespace-pre-line' => $field['multiline']])
                    @if ($field['dir']) dir="{{ $field['dir'] }}" @endif>
                    {{ $detail->display($field['value']) }}
                </dd>
            </div>
        @endforeach
    </dl>
</section>

{{-- Section 2: Financial Summary --}}
@include('admin.partials.financial-summary', ['student' => $student, 'financialSummary' => $financialSummary])

{{-- Section 3: Enrollments --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
        <h2 class="text-lg font-semibold text-amber-100">{{ __('admin.enrollments') }}</h2>
        <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">{{ __('admin.total_count', ['count' => $student->enrollments->count()]) }}</span>
    </div>

    @if ($student->enrollments->count())
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-800/60 bg-gray-800/30">
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.instrument') }}</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.teacher') }}</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.skill') }}</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.status') }}</th>
                        <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.started') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($student->enrollments as $enrollment)
                        <tr class="transition hover:bg-gray-800/20">
                            <td class="px-6 py-4 font-medium text-gray-100">{{ $enrollment->instrument?->display_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-400">{{ $enrollment->teacher?->full_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-400">
                                @php
                                    $skillValue = $enrollment->skill_level instanceof \BackedEnum ? $enrollment->skill_level->value : (string) $enrollment->skill_level;
                                @endphp
                                {{ __('admin.skill_levels.' . $skillValue) }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusValue = $enrollment->status instanceof \BackedEnum ? $enrollment->status->value : (string) $enrollment->status;
                                    $statusStyles = [
                                        'active' => 'bg-emerald-500/10 text-emerald-400',
                                        'paused' => 'bg-amber-500/10 text-amber-300',
                                        'completed' => 'bg-sky-500/10 text-sky-400',
                                        'cancelled' => 'bg-red-500/10 text-red-400',
                                    ];
                                    $style = $statusStyles[$statusValue] ?? 'bg-gray-700/50 text-gray-400';
                                @endphp
                                <span class="rounded-full {{ $style }} px-2.5 py-0.5 text-xs font-medium">
                                    {{ __('admin.statuses.' . $statusValue) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-400">{{ $enrollment->started_at ? \App\Helpers\Jalalian::fromCarbon($enrollment->started_at) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-12 text-center text-gray-500">
            {{ __('admin.no_enrollments_yet') }}
        </div>
    @endif
</div>

@include('admin.partials.subscription-summary')

{{-- Section 4: history timeline (stable identifier `student_history`) --}}
@include('admin.partials.timeline', ['detail' => $detail, 'section' => $history])

@endsection
