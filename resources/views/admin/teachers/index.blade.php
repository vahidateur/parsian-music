@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.teachers') }}@endsection

@section('content')
@php
    $btnPrimary   = "inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition duration-200 hover:from-amber-500 hover:to-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900";
    $statusBadge  = ['active' => 'bg-emerald-500/10 text-emerald-400', 'inactive' => 'bg-gray-700/50 text-gray-400'];
    $sortParams   = $list->sortParameters();
@endphp

{{-- Header --}}
<x-dashboard.section-header headingLevel="h1"
    :title="__('admin.teachers')"
    :subtitle="__('admin.manage_teachers')"
    :badge="$list->total . ' ' . __('admin.total')"
>
    <x-slot:actions>
        @if ($list->allows('create'))
            <a href="{{ route('admin.teachers.create') }}" class="{{ $btnPrimary }}" aria-label="{{ __('admin.create_teacher') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                {{ __('admin.create_teacher') }}
            </a>
        @endif
    </x-slot:actions>
</x-dashboard.section-header>

{{-- Feedback_Channel + Error_State recovery path --}}
<x-admin.feedback :returnUrl="route('admin.teachers.index')" />

{{-- Search + filters --}}
<x-dashboard.chart-container class="mb-5" title="{{ __('admin.search') }}" aria-label="{{ __('admin.filters_label') }}">
    @include('admin.partials.list-toolbar', [
        'list' => $list,
        'route' => 'admin.teachers.index',
        'searchPlaceholder' => __('admin.search_name'),
        'filters' => [
            'status' => ['label' => __('admin.status'), 'all' => __('admin.all_statuses')],
        ],
    ])
</x-dashboard.chart-container>

<x-admin.bulk-selection.toolbar
    entity="teacher"
    :entityLabel="__('admin.teacher')"
    :rows="$list->bulk_rows"
    :selectionContext="$list->selection_context"
    :previewEndpoint="route('admin.teachers.bulk.preview')"
    :executionEndpoint="route('admin.teachers.bulk')"
/>

{{-- Table --}}
<x-dashboard.chart-container
    :title="__('admin.teachers')"
    :badge="count($list->rows) . ' / ' . $list->total"
>
    @if (count($list->rows))
        <div class="-mx-4 -my-4 overflow-x-auto sm:-mx-6 sm:-my-6">
            <table class="w-full text-start text-sm" role="table">
                <thead>
                    <tr class="border-b border-gray-800/60 bg-gray-800/30">
                        <th scope="col" class="px-3 py-3.5">
                            <input
                                type="checkbox"
                                data-bulk-select-all
                                data-bulk-entity="teacher"
                                class="admin-bulk__checkbox"
                                @if (!collect($list->bulk_rows)->contains(fn ($bulkRow) => $bulkRow->selectable)) disabled @endif
                                aria-label="{{ __('admin.bulk_selection.select_all_visible') }}"
                            >
                        </th>
                        @include('admin.partials.sort-th', ['col'=>'full_name', 'label'=>__('admin.full_name'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.teachers.index', 'params'=>$sortParams])
                        @include('admin.partials.sort-th', ['col'=>'phone',     'label'=>__('admin.phone'),     'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.teachers.index', 'params'=>$sortParams])
                        @include('admin.partials.sort-th', ['col'=>'status',    'label'=>__('admin.status'),    'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.teachers.index', 'params'=>$sortParams])
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.instruments') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($list->rows as $row)
                        @php($bulkRow = $list->bulk_rows[$loop->index] ?? null)
                        <tr class="transition duration-150 hover:bg-gray-800/25">
                            <td class="px-3 py-3.5">
                                @if ($bulkRow?->selectable)
                                    <input
                                        type="checkbox"
                                        value="{{ $bulkRow->id }}"
                                        data-bulk-row
                                        data-bulk-entity="teacher"
                                        class="admin-bulk__checkbox"
                                        data-bulk-allowed-actions="{{ implode(',', $bulkRow->allowed_actions) }}"
                                        aria-label="{{ __('admin.bulk_selection.select_row', ['entity' => __('admin.teacher'), 'label' => $row->label]) }}"
                                    >
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-medium text-gray-100">{{ $row->label }}</td>
                            <td class="px-5 py-3.5 text-gray-400 tabular-nums" dir="ltr">{{ $row->field('phone') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge[$row->status] ?? 'bg-gray-700/50 text-gray-400' }}">
                                    {{ __('admin.statuses.'.$row->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-400">
                                {{ $row->relation('instruments') ?? '—' }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-4">
                                    @if ($row->allows('view'))
                                        <a href="{{ route('admin.teachers.show', $row->id) }}"
                                           class="text-xs font-medium text-gray-300 transition duration-150 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber-500/60">
                                            {{ __('admin.view') }}
                                        </a>
                                    @endif
                                    @if ($row->allows('manageInstruments'))
                                        <a href="{{ route('admin.teachers.instruments', $row->id) }}"
                                           class="text-xs font-medium text-sky-400 transition duration-150 hover:text-sky-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-sky-500/60">
                                            {{ __('admin.instruments') }}
                                        </a>
                                    @endif
                                    @if ($row->allows('update'))
                                        <a href="{{ route('admin.teachers.edit', $row->id) }}"
                                           class="text-xs font-medium text-amber-400 transition duration-150 hover:text-amber-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber-500/60">
                                            {{ __('admin.edit') }}
                                        </a>
                                    @endif
                                    @if ($row->allows('delete'))
                                        <button type="button"
                                                x-on:click="$dispatch('open-modal', 'confirm-teacher-delete-{{ $row->id }}')"
                                                class="text-xs font-medium text-red-400 transition duration-150 hover:text-red-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-red-500/60">
                                            {{ __('admin.delete') }}
                                        </button>
                                        <x-modal name="confirm-teacher-delete-{{ $row->id }}" variant="confirmation"
                                                 :entity="$row->label"
                                                 :action="__('admin.delete')"
                                                 :consequence="__('admin.confirmation_consequence_irreversible')">
                                            <x-admin.form-state>
                                                <form method="POST" action="{{ route('admin.teachers.destroy', $row->id) }}">
                                                    @csrf @method('DELETE')
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" x-on:click="$dispatch('close')"
                                                                class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300">
                                                            {{ __('admin.cancel') }}
                                                        </button>
                                                        <button type="submit" x-bind:disabled="pending" x-bind:aria-busy="pending"
                                                                class="rounded-lg bg-red-500/15 px-4 py-2 text-sm font-medium text-red-300 disabled:cursor-not-allowed disabled:opacity-60">
                                                            {{ __('admin.confirm') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </x-admin.form-state>
                                        </x-modal>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        {{-- Shared Empty_State: mode comes from the server-side list contract --}}
        <x-admin.list-empty
            :list="$list"
            route="admin.teachers.index"
            createRoute="admin.teachers.create"
            :createLabel="__('admin.create_teacher')"
            :message="__('admin.no_teachers_found')">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                </svg>
            </x-slot:icon>
        </x-admin.list-empty>
    @endif
</x-dashboard.chart-container>

@if (collect($list->bulk_rows)->contains(fn ($bulkRow) => $bulkRow->selectable && $bulkRow->allows('delete')))
    <x-admin.bulk-selection.confirmation-dialog
        entity="teacher"
        entityLabel="{{ __('admin.teacher') }}"
        :selectionContext="$list->selection_context"
        :executionEndpoint="route('admin.teachers.bulk')"
    />
@endif
<x-admin.bulk-selection.result-summary
    entity="teacher"
    entityLabel="{{ __('admin.teacher') }}"
    :resultEndpoint="route('admin.teachers.index', $list->queryParameters())"
/>

{{-- Total count + context-preserving pagination --}}
@include('admin.partials.list-footer', ['list' => $list])

@endsection
