@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.students') }}@endsection

@section('content')
@php
    $btnPrimary   = "inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition duration-200 hover:from-amber-500 hover:to-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900";
    $statusBadge  = ['active' => 'bg-emerald-500/10 text-emerald-400', 'inactive' => 'bg-gray-700/50 text-gray-400', 'suspended' => 'bg-rose-500/10 text-rose-400'];
    $sortParams   = $list->sortParameters();
@endphp

{{-- Header --}}
<x-dashboard.section-header headingLevel="h1"
    :title="__('admin.students')"
    subtitle="مدیریت هنرجویان آموزشگاه"
    :badge="$list->total . ' ' . __('admin.total')"
>
    <x-slot:actions>
        @if ($list->allows('create'))
            <a href="{{ route('admin.students.create') }}" class="{{ $btnPrimary }}" aria-label="{{ __('admin.new_student') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                {{ __('admin.new_student') }}
            </a>
        @endif
    </x-slot:actions>
</x-dashboard.section-header>

{{-- Feedback_Channel + Error_State recovery path --}}
<x-admin.feedback :returnUrl="route('admin.students.index')" />

{{-- Search + filters --}}
<x-dashboard.chart-container class="mb-5" title="{{ __('admin.search') }}" aria-label="{{ __('admin.filters_label') }}">
    @include('admin.partials.list-toolbar', [
        'list' => $list,
        'route' => 'admin.students.index',
        'searchPlaceholder' => __('admin.search_name'),
        'filters' => [
            'status' => ['label' => __('admin.status'), 'all' => __('admin.all_statuses')],
        ],
    ])
</x-dashboard.chart-container>

<x-admin.bulk-selection.toolbar
    entity="student"
    :entityLabel="__('admin.student')"
    :rows="$list->bulk_rows"
    :selectionContext="$list->selection_context"
    :previewEndpoint="route('admin.students.bulk.preview')"
    :executionEndpoint="route('admin.students.bulk')"
/>

{{-- Table --}}
<x-dashboard.chart-container
    :title="__('admin.students')"
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
                                data-bulk-entity="student"
                                class="admin-bulk__checkbox"
                                @if (!collect($list->bulk_rows)->contains(fn ($bulkRow) => $bulkRow->selectable)) disabled @endif
                                aria-label="{{ __('admin.bulk_selection.select_all_visible') }}"
                            >
                        </th>
                        @include('admin.partials.sort-th', ['col'=>'full_name', 'label'=>__('admin.full_name'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.students.index', 'params'=>$sortParams])
                        @include('admin.partials.sort-th', ['col'=>'phone',     'label'=>__('admin.phone'),     'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.students.index', 'params'=>$sortParams])
                        @include('admin.partials.sort-th', ['col'=>'status',    'label'=>__('admin.status'),    'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.students.index', 'params'=>$sortParams])
                        @include('admin.partials.sort-th', ['col'=>'join_date', 'label'=>__('admin.join_date'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.students.index', 'params'=>$sortParams])
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
                                        data-bulk-entity="student"
                                        class="admin-bulk__checkbox"
                                        data-bulk-allowed-actions="{{ implode(',', $bulkRow->allowed_actions) }}"
                                        aria-label="{{ __('admin.bulk_selection.select_row', ['entity' => __('admin.student'), 'label' => $row->label]) }}"
                                    >
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-medium text-gray-100">
                                @if ($row->allows('view'))
                                    <a href="{{ route('admin.students.show', $row->id) }}" class="hover:text-amber-300 transition duration-150">
                                        {{ $row->label }}
                                    </a>
                                @else
                                    {{ $row->label }}
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-400 tabular-nums" dir="ltr">{{ $row->field('phone') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge[$row->status] ?? 'bg-gray-700/50 text-gray-400' }}">
                                    {{ __('admin.statuses.'.$row->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-400 tabular-nums">{{ \App\Helpers\Jalalian::fromCarbon($row->field('join_date')) }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-4">
                                    @if ($row->allows('update'))
                                        <a href="{{ route('admin.students.edit', $row->id) }}"
                                           class="text-xs font-medium text-amber-400 transition duration-150 hover:text-amber-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber-500/60">
                                            {{ __('admin.edit') }}
                                        </a>
                                    @endif
                                    @if ($row->allows('delete'))
                                        <button type="button"
                                                x-on:click="$dispatch('open-modal', 'confirm-student-delete-{{ $row->id }}')"
                                                class="text-xs font-medium text-red-400 transition duration-150 hover:text-red-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-red-500/60">
                                            {{ __('admin.delete') }}
                                        </button>
                                        <x-modal name="confirm-student-delete-{{ $row->id }}" variant="confirmation"
                                                 :entity="$row->label"
                                                 :action="__('admin.delete')"
                                                 :consequence="__('admin.confirmation_consequence_irreversible')">
                                            <x-admin.form-state>
                                                <form method="POST" action="{{ route('admin.students.destroy', $row->id) }}">
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
            route="admin.students.index"
            createRoute="admin.students.create"
            :createLabel="__('admin.new_student')"
            :message="__('admin.no_students_found')">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </x-slot:icon>
        </x-admin.list-empty>
    @endif
</x-dashboard.chart-container>

@if (collect($list->bulk_rows)->contains(fn ($bulkRow) => $bulkRow->selectable && $bulkRow->allows('delete')))
    <x-admin.bulk-selection.confirmation-dialog
        entity="student"
        entityLabel="{{ __('admin.student') }}"
        :selectionContext="$list->selection_context"
        :executionEndpoint="route('admin.students.bulk')"
    />
@endif
<x-admin.bulk-selection.result-summary
    entity="student"
    entityLabel="{{ __('admin.student') }}"
    :resultEndpoint="route('admin.students.index', $list->queryParameters())"
/>

{{-- Total count + context-preserving pagination --}}
@include('admin.partials.list-footer', ['list' => $list])

@endsection
