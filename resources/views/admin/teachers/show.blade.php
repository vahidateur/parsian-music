{{--
    Teacher Record_Detail screen (route: admin.teachers.show).
    Expects: $detail (App\DTOs\RecordDetailData) fully resolved by TeacherDetailQuery.
    Only persisted values are rendered; an absent value falls back to the
    localized placeholder carried by the DTO. No query runs in this template.
--}}
@extends('layouts.dashboard')

@section('title', __('admin.teacher_details'))
@section('breadcrumb'){{ __('admin.teachers') }} / {{ $detail->label }}@endsection

@section('content')
@php
    $profile = $detail->section(\App\Services\Details\TeacherDetailQuery::SECTION_PROFILE);
    $instruments = $detail->section(\App\Services\Details\TeacherDetailQuery::SECTION_INSTRUMENTS);
    $enrollments = $detail->section(\App\Services\Details\TeacherDetailQuery::SECTION_ENROLLMENTS);

    // Explicit maps: Tailwind cannot resolve interpolated class names.
    $statusColors = ['active' => 'emerald', 'inactive' => 'gray'];
    $enrollmentStatusColors = [
        'active' => 'emerald',
        'paused' => 'amber',
        'completed' => 'sky',
        'cancelled' => 'red',
    ];
    $secondaryLink = 'rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-300 transition duration-200 hover:border-gray-600 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40';
    $columnHead = 'px-5 py-3.5 text-start text-xs font-medium uppercase tracking-wider text-gray-500';
@endphp

{{-- Back + record level actions --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('admin.teachers.index') }}" class="text-sm text-gray-400 transition duration-150 hover:text-gray-200 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber-500/60">
        {{ __('admin.back_to_teachers') }}
    </a>
    <div class="flex flex-wrap items-center gap-3">
        @if ($detail->allows('manageInstruments'))
            <a href="{{ route('admin.teachers.instruments', $detail->id) }}" class="{{ $secondaryLink }}">
                {{ __('admin.instruments') }}
            </a>
        @endif
        @if ($detail->allows('update'))
            <a href="{{ route('admin.teachers.edit', $detail->id) }}" class="{{ $secondaryLink }}">
                {{ __('admin.edit_teacher') }}
            </a>
        @endif
    </div>
</div>

{{-- Page heading: the single h1 of this screen --}}
<header class="mb-6">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold text-amber-100">{{ $detail->label }}</h1>
        @if ($detail->status_label)
            <x-admin.status-badge :label="$detail->status_label" :color="$statusColors[$detail->status] ?? 'gray'" />
        @endif
    </div>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.teacher_details_desc') }}</p>
</header>

@include('admin.partials.flash')

{{-- Section: persisted profile values --}}
<section id="{{ $profile->id }}" data-section="{{ $profile->id }}" aria-labelledby="{{ $profile->id }}_heading"
         class="mb-6 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="border-b border-gray-800/60 px-5 py-4 sm:px-6">
        <h2 id="{{ $profile->id }}_heading" class="text-base font-semibold text-amber-100">{{ $profile->title }}</h2>
    </div>
    <dl class="grid grid-cols-1 gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6 lg:grid-cols-3">
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

{{-- Section: assigned instruments --}}
<section id="{{ $instruments->id }}" data-section="{{ $instruments->id }}" aria-labelledby="{{ $instruments->id }}_heading"
         class="mb-6 overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-800/60 px-5 py-4 sm:px-6">
        <h2 id="{{ $instruments->id }}_heading" class="text-base font-semibold text-amber-100">{{ $instruments->title }}</h2>
        <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">
            {{ __('admin.total_count', ['count' => $instruments->rowCount()]) }}
        </span>
    </div>

    @if ($instruments->rowCount())
        <div class="overflow-x-auto">
            <table class="w-full text-start text-sm">
                <thead>
                    <tr class="border-b border-gray-800/60 bg-gray-800/30">
                        <th scope="col" class="{{ $columnHead }}">{{ __('admin.instrument') }}</th>
                        <th scope="col" class="{{ $columnHead }}">{{ __('admin.skill_level') }}</th>
                        <th scope="col" class="{{ $columnHead }}">{{ __('admin.primary') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($instruments->rows as $row)
                        <tr class="transition duration-150 hover:bg-gray-800/25">
                            <td class="px-5 py-3.5 font-medium text-gray-100">{{ $row->label }}</td>
                            <td class="px-5 py-3.5 text-gray-400">{{ $detail->display($row->field('skill_level')) }}</td>
                            <td class="px-5 py-3.5">
                                @if ($row->field('is_primary'))
                                    <x-admin.status-badge :label="$row->field('is_primary')" color="amber" />
                                @else
                                    <span class="text-gray-400">{{ $detail->placeholder }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-dashboard.empty-state :message="$instruments->empty_message" compact />
    @endif
</section>

{{-- Section: related operational data (enrollments) --}}
<section id="{{ $enrollments->id }}" data-section="{{ $enrollments->id }}" aria-labelledby="{{ $enrollments->id }}_heading"
         class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-800/60 px-5 py-4 sm:px-6">
        <h2 id="{{ $enrollments->id }}_heading" class="text-base font-semibold text-amber-100">{{ $enrollments->title }}</h2>
        <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">
            {{ __('admin.total_count', ['count' => $enrollments->rowCount()]) }}
        </span>
    </div>

    @if ($enrollments->rowCount())
        <div class="overflow-x-auto">
            <table class="w-full text-start text-sm">
                <thead>
                    <tr class="border-b border-gray-800/60 bg-gray-800/30">
                        <th scope="col" class="{{ $columnHead }}">{{ __('admin.student') }}</th>
                        <th scope="col" class="{{ $columnHead }}">{{ __('admin.instrument') }}</th>
                        <th scope="col" class="{{ $columnHead }}">{{ __('admin.skill') }}</th>
                        <th scope="col" class="{{ $columnHead }}">{{ __('admin.status') }}</th>
                        <th scope="col" class="{{ $columnHead }}">{{ __('admin.started') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($enrollments->rows as $row)
                        <tr class="transition duration-150 hover:bg-gray-800/25">
                            <td class="px-5 py-3.5 font-medium text-gray-100">{{ $detail->display($row->label) }}</td>
                            <td class="px-5 py-3.5 text-gray-400">{{ $detail->display($row->relation('instrument')) }}</td>
                            <td class="px-5 py-3.5 text-gray-400">{{ $detail->display($row->field('skill_level')) }}</td>
                            <td class="px-5 py-3.5">
                                @if ($row->relation('status_label'))
                                    <x-admin.status-badge
                                        :label="$row->relation('status_label')"
                                        :color="$enrollmentStatusColors[$row->status] ?? 'gray'" />
                                @else
                                    <span class="text-gray-400">{{ $detail->placeholder }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-400 tabular-nums">{{ $detail->display($row->field('started_at')) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-dashboard.empty-state :message="$enrollments->empty_message" compact />
    @endif
</section>

@endsection
