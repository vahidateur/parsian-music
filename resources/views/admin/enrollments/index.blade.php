@extends('layouts.dashboard')

@section('content')
@php
    $sortParams = $list->sortParameters();
    $statusStyles = [
        'active' => 'bg-emerald-500/10 text-emerald-400',
        'paused' => 'bg-amber-500/10 text-amber-300',
        'completed' => 'bg-sky-500/10 text-sky-400',
        'cancelled' => 'bg-red-500/10 text-red-400',
    ];
@endphp

{{-- Page Heading --}}
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.enrollments') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('admin.manage_enrollments') }}</p>
        </div>
        @if ($list->allows('create'))
        <a href="{{ route('admin.enrollments.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('admin.new_enrollment') }}
        </a>
        @endif
    </div>
</div>

{{-- Feedback_Channel + Error_State recovery path --}}
<x-admin.feedback :returnUrl="route('admin.enrollments.index')" />

{{-- Filters --}}
<div class="mb-6">
    @include('admin.partials.list-toolbar', [
        'list' => $list,
        'route' => 'admin.enrollments.index',
        'searchable' => false,
        'filters' => [
            'student_id' => ['label' => __('admin.student'), 'all' => __('admin.all_students')],
            'teacher_id' => ['label' => __('admin.teacher'), 'all' => __('admin.all_teachers')],
            'instrument_id' => ['label' => __('admin.instrument'), 'all' => __('admin.all_instruments')],
            'status' => ['label' => __('admin.status'), 'all' => __('admin.all_statuses')],
        ],
    ])
</div>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-800/60 bg-gray-800/30">
                    @include('admin.partials.sort-th', ['col'=>'student_name',    'label'=>__('admin.student'),   'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.enrollments.index', 'params'=>$sortParams])
                    @include('admin.partials.sort-th', ['col'=>'teacher_name',    'label'=>__('admin.teacher'),   'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.enrollments.index', 'params'=>$sortParams])
                    @include('admin.partials.sort-th', ['col'=>'instrument_name', 'label'=>__('admin.instrument'),'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.enrollments.index', 'params'=>$sortParams])
                    @include('admin.partials.sort-th', ['col'=>'skill_level', 'label'=>__('admin.skill'),     'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.enrollments.index', 'params'=>$sortParams])
                    @include('admin.partials.sort-th', ['col'=>'status',     'label'=>__('admin.status'),     'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.enrollments.index', 'params'=>$sortParams])
                    @include('admin.partials.sort-th', ['col'=>'started_at', 'label'=>__('admin.started'),    'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.enrollments.index', 'params'=>$sortParams])
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($list->rows as $row)
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-6 py-4 font-medium text-gray-100">{{ $row->relation('student') ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ $row->relation('teacher') ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ $row->relation('instrument') ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ __('admin.skill_levels.' . $row->field('skill_level')) }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full {{ $statusStyles[$row->status] ?? 'bg-gray-700/50 text-gray-400' }} px-2.5 py-0.5 text-xs font-medium">
                                {{ __('admin.statuses.' . $row->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-400">{{ \App\Helpers\Jalalian::fromCarbon($row->field('started_at')) }}</td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            @if ($row->allows('update'))
                                <a href="{{ route('admin.enrollments.edit', $row->id) }}" class="text-amber-400 transition hover:text-amber-300">{{ __('admin.edit') }}</a>
                            @endif
                            @if ($row->allows('delete'))
                            <button type="button" x-on:click="$dispatch('open-modal', 'confirm-enrollment-delete-{{ $row->id }}')" class="text-red-400 transition hover:text-red-300">{{ __('admin.delete') }}</button>
                            <x-modal name="confirm-enrollment-delete-{{ $row->id }}" variant="confirmation"
                                     :entity="$row->label"
                                     :action="__('admin.delete')"
                                     :consequence="__('admin.confirmation_consequence_irreversible')">
                                <x-admin.form-state>
                                    <form method="POST" action="{{ route('admin.enrollments.destroy', $row->id) }}">
                                        @csrf @method('DELETE')
                                        <div class="flex justify-end gap-2">
                                            <button type="button" x-on:click="$dispatch('close')" class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300">{{ __('admin.cancel') }}</button>
                                            <button type="submit" x-bind:disabled="pending" x-bind:aria-busy="pending" class="rounded-lg bg-red-500/15 px-4 py-2 text-sm font-medium text-red-300 disabled:cursor-not-allowed disabled:opacity-60">{{ __('admin.confirm') }}</button>
                                        </div>
                                    </form>
                                </x-admin.form-state>
                            </x-modal>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            {{-- Shared Empty_State: mode comes from the server-side list contract --}}
                            <x-admin.list-empty
                                :list="$list"
                                route="admin.enrollments.index"
                                createRoute="admin.enrollments.create"
                                :createLabel="__('admin.new_enrollment')"
                                :message="__('admin.no_enrollments_found')" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Total count + context-preserving pagination --}}
@include('admin.partials.list-footer', ['list' => $list])

@endsection
