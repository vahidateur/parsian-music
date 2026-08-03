@extends('layouts.dashboard')

@section('title', __('admin.invoices'))

@section('content')
@php
    $sortParams = $list->sortParameters();
@endphp

{{-- Page Heading --}}
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.invoices') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('admin.manage_invoices') }}</p>
        </div>
        @if ($list->allows('create'))
        <a href="{{ route('admin.invoices.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('admin.new_invoice') }}
        </a>
        @endif
    </div>
</div>

{{-- Feedback_Channel + Error_State recovery path --}}
<x-admin.feedback :returnUrl="route('admin.invoices.index')" />

{{-- Filters --}}
<div class="mb-6">
    @include('admin.partials.list-toolbar', [
        'list' => $list,
        'route' => 'admin.invoices.index',
        'searchLabel' => __('admin.invoice_number'),
        'filters' => [
            'student_id' => ['label' => __('admin.student'), 'all' => __('admin.all_students')],
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
                    @include('admin.partials.sort-th', ['col'=>'invoice_number', 'label'=>__('admin.invoice_number'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.invoices.index', 'params'=>$sortParams])
                    @include('admin.partials.sort-th', ['col'=>'student_name', 'label'=>__('admin.student'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.invoices.index', 'params'=>$sortParams])
                    @include('admin.partials.sort-th', ['col'=>'issue_date', 'label'=>__('admin.issue_date'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.invoices.index', 'params'=>$sortParams])
                    @include('admin.partials.sort-th', ['col'=>'due_date', 'label'=>__('admin.due_date'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.invoices.index', 'params'=>$sortParams])
                    @include('admin.partials.sort-th', ['col'=>'total', 'label'=>__('admin.total_amount'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.invoices.index', 'params'=>$sortParams])
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.amount_due') }}</th>
                    @include('admin.partials.sort-th', ['col'=>'status', 'label'=>__('admin.status'), 'currentSort'=>$list->context->sort, 'currentDir'=>$list->context->direction, 'route'=>'admin.invoices.index', 'params'=>$sortParams])
                    <th class="px-6 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($list->rows as $row)
                    @php($status = \App\Enums\InvoiceStatusEnum::tryFrom((string) $row->status))
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-6 py-4 font-medium text-gray-100">{{ $row->label }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ $row->relation('student') ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ \App\Helpers\Jalalian::fromCarbon($row->field('issue_date')) }}</td>
                        <td class="px-6 py-4 text-gray-400">{{ \App\Helpers\Jalalian::fromCarbon($row->field('due_date')) }}</td>
                        <td class="px-6 py-4 text-gray-100">{{ number_format((int) $row->field('total')) }} {{ __('admin.currency_toman') }}</td>
                        <td class="px-6 py-4 {{ (int) $row->field('outstanding') > 0 ? 'text-amber-300' : 'text-emerald-400' }}">
                            {{ number_format((int) $row->field('outstanding')) }} {{ __('admin.currency_toman') }}
                        </td>
                        <td class="px-6 py-4">
                            <x-admin.status-badge :label="$status?->label() ?? (string) $row->status" :color="$status?->color() ?? 'gray'" />
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            @if ($row->allows('view'))
                                <a href="{{ route('admin.invoices.show', $row->id) }}" class="text-amber-400 transition hover:text-amber-300">{{ __('admin.view') }}</a>
                            @endif
                            @unless ($status?->isTerminal())
                                @if ($row->allows('update'))
                                    <a href="{{ route('admin.invoices.edit', $row->id) }}" class="ml-3 text-gray-400 transition hover:text-gray-200">{{ __('admin.edit') }}</a>
                                @endif
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            {{-- Shared Empty_State: mode comes from the server-side list contract --}}
                            <x-admin.list-empty
                                :list="$list"
                                route="admin.invoices.index"
                                createRoute="admin.invoices.create"
                                :createLabel="__('admin.new_invoice')"
                                :message="__('admin.no_invoices_found')" />
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
