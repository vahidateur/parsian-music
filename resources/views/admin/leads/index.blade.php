@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.leads') }}@endsection

@section('content')
@php
    $btnPrimary   = "inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition duration-200 hover:from-amber-500 hover:to-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900";
    $btnSecondary = "inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm font-medium text-gray-300 transition duration-200 hover:border-gray-600 hover:bg-gray-800/60 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/30";
    $btnClear     = "rounded-lg px-4 py-2.5 text-sm text-gray-500 transition duration-200 hover:text-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/20";
    $inputClass   = "block w-full rounded-lg border border-gray-700/60 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition duration-200 focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20";
    $statusBadge  = ['new' => 'bg-sky-500/10 text-sky-400', 'contacted' => 'bg-blue-500/10 text-blue-400', 'interested' => 'bg-violet-500/10 text-violet-400', 'trial_scheduled' => 'bg-amber-500/10 text-amber-400', 'registered' => 'bg-emerald-500/10 text-emerald-400', 'lost' => 'bg-gray-700/50 text-gray-400'];
    $priorityBadge = ['high' => 'bg-red-500/10 text-red-400', 'medium' => 'bg-amber-500/10 text-amber-400', 'low' => 'bg-gray-700/50 text-gray-400'];
@endphp

{{-- Header --}}
<x-dashboard.section-header
    :title="__('admin.leads')"
    :subtitle="__('admin.manage_leads')"
    :badge="$leads->total() . ' ' . __('admin.total')"
>
    <x-slot:actions>
        <a href="{{ route('admin.leads.kanban') }}" class="{{ $btnSecondary }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25A2.25 2.25 0 0113.5 8.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
            {{ __('admin.kanban_view') }}
        </a>
        <a href="{{ route('admin.leads.create') }}" class="{{ $btnPrimary }}" aria-label="{{ __('admin.new_lead') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            {{ __('admin.new_lead') }}
        </a>
    </x-slot:actions>
</x-dashboard.section-header>

{{-- Flash --}}
@if (session('success'))
    <x-dashboard.alert-card class="mb-5" :title="session('success')" priority="success" />
@endif
@if (session('error'))
    <x-dashboard.alert-card class="mb-5" :title="session('error')" priority="high" />
@endif

{{-- Filters --}}
<x-dashboard.chart-container class="mb-5" title="{{ __('admin.search') }}" aria-label="فیلتر سرنخ‌ها">
    <form method="GET" action="{{ route('admin.leads.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6 lg:items-end" role="search">
        <div>
            <label for="filter-name" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.full_name') }}</label>
            <input id="filter-name" type="text" name="full_name" value="{{ request('full_name') }}"
                   placeholder="{{ __('admin.search_name') }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label for="filter-phone" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.phone') }}</label>
            <input id="filter-phone" type="tel" name="phone" value="{{ request('phone') }}"
                   placeholder="{{ __('admin.search_phone') }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label for="filter-status" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.status') }}</label>
            <select id="filter-status" name="status" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_statuses') }}</option>
                @foreach (\App\Enums\LeadStatusEnum::cases() as $s)
                    <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-priority" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.priority') }}</label>
            <select id="filter-priority" name="priority" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_priorities') }}</option>
                @foreach (\App\Enums\LeadPriorityEnum::cases() as $p)
                    <option value="{{ $p->value }}" {{ request('priority') === $p->value ? 'selected' : '' }}>{{ $p->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-source" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.source') }}</label>
            <select id="filter-source" name="source" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_sources') }}</option>
                @foreach (\App\Enums\LeadSourceEnum::cases() as $src)
                    <option value="{{ $src->value }}" {{ request('source') === $src->value ? 'selected' : '' }}>{{ $src->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-assigned" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.assigned_admin') }}</label>
            <select id="filter-assigned" name="assigned_to" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_admins') }}</option>
                @foreach ($assignees as $admin)
                    <option value="{{ $admin->id }}" {{ (string) request('assigned_to') === (string) $admin->id ? 'selected' : '' }}>{{ $admin->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="{{ $btnSecondary }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 0z"/></svg>
                {{ __('admin.filter') }}
            </button>
            <a href="{{ route('admin.leads.index') }}" class="{{ $btnClear }}">{{ __('admin.clear') }}</a>
        </div>
    </form>
</x-dashboard.chart-container>

{{-- Table --}}
<x-dashboard.chart-container
    :title="__('admin.leads')"
    :badge="$leads->count() . ' / ' . $leads->total()"
>
    @if ($leads->count())
        <div class="-mx-4 -my-4 overflow-x-auto sm:-mx-6 sm:-my-6">
            <table class="w-full text-start text-sm" role="table">
                <thead>
                    <tr class="border-b border-gray-800/60 bg-gray-800/30">
                        @include('admin.partials.sort-th', ['col'=>'full_name', 'label'=>__('admin.full_name'), 'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.leads.index'])
                        @include('admin.partials.sort-th', ['col'=>'phone',     'label'=>__('admin.phone'),     'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.leads.index'])
                        @include('admin.partials.sort-th', ['col'=>'status',    'label'=>__('admin.status'),    'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.leads.index'])
                        @include('admin.partials.sort-th', ['col'=>'priority',  'label'=>__('admin.priority'),  'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.leads.index'])
                        @include('admin.partials.sort-th', ['col'=>'source',    'label'=>__('admin.source'),    'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.leads.index'])
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.assigned_admin') }}</th>
                        @include('admin.partials.sort-th', ['col'=>'next_follow_up_at', 'label'=>__('admin.next_follow_up'), 'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.leads.index'])
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($leads as $lead)
                        <tr class="transition duration-150 hover:bg-gray-800/25 {{ $lead->isOverdue() ? 'bg-rose-500/[0.03]' : '' }}">
                            <td class="px-5 py-3.5 font-medium text-gray-100">
                                <a href="{{ route('admin.leads.show', $lead) }}" class="hover:text-amber-300 transition duration-150">
                                    {{ $lead->full_name }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-gray-400 tabular-nums" dir="ltr">{{ $lead->phone }}</td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge[$lead->status->value] ?? 'bg-gray-700/50 text-gray-400' }}">
                                    {{ $lead->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $priorityBadge[$lead->priority->value] ?? 'bg-gray-700/50 text-gray-400' }}">
                                    {{ $lead->priority->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-400">{{ $lead->source->label() }}</td>
                            <td class="px-5 py-3.5 text-gray-400">{{ $lead->assignedUser?->full_name ?? '—' }}</td>
                            <td class="px-5 py-3.5 tabular-nums {{ $lead->isOverdue() ? 'text-rose-400 font-medium' : 'text-gray-400' }}">
                                {{ $lead->next_follow_up_at ? \App\Helpers\Jalalian::fromCarbon($lead->next_follow_up_at, 'Y/m/d H:i') : '—' }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-4">
                                    <a href="{{ route('admin.leads.show', $lead) }}"
                                       class="text-xs font-medium text-sky-400 transition duration-150 hover:text-sky-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-sky-500/60">
                                        {{ __('admin.view') }}
                                    </a>
                                    <a href="{{ route('admin.leads.edit', $lead) }}"
                                       class="text-xs font-medium text-amber-400 transition duration-150 hover:text-amber-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber-500/60">
                                        {{ __('admin.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" class="inline"
                                          onsubmit="return confirm('{{ __('admin.delete_lead_confirm') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="text-xs font-medium text-red-400 transition duration-150 hover:text-red-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-red-500/60">
                                            {{ __('admin.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-dashboard.empty-state :message="__('admin.no_leads_found')" compact>
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </x-slot:icon>
        </x-dashboard.empty-state>
    @endif
</x-dashboard.chart-container>

{{-- Pagination --}}
@if ($leads->hasPages())
    <div class="mt-5 flex justify-center">
        {{ $leads->withQueryString()->links() }}
    </div>
@endif

@endsection
