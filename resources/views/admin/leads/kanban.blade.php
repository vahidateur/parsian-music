@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.leads') }}@endsection

@section('content')
@php
    $btnSecondary = "inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm font-medium text-gray-300 transition duration-200 hover:border-gray-600 hover:bg-gray-800/60 hover:text-gray-100";
    $priorityBadge = ['high' => 'bg-red-500/10 text-red-400', 'medium' => 'bg-amber-500/10 text-amber-400', 'low' => 'bg-gray-700/50 text-gray-400'];
    $colHeaderTone = [
        'new' => 'border-sky-500/30 text-sky-300',
        'contacted' => 'border-blue-500/30 text-blue-300',
        'interested' => 'border-violet-500/30 text-violet-300',
        'trial_scheduled' => 'border-amber-500/30 text-amber-300',
        'registered' => 'border-emerald-500/30 text-emerald-300',
        'lost' => 'border-gray-600/30 text-gray-400',
    ];
@endphp

<x-dashboard.section-header headingLevel="h1" :title="__('admin.leads_kanban')" :subtitle="__('admin.kanban_subtitle')">
    <x-slot:actions>
        <a href="{{ route('admin.leads.index') }}" class="{{ $btnSecondary }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            {{ __('admin.list_view') }}
        </a>
        <a href="{{ route('admin.leads.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            {{ __('admin.new_lead') }}
        </a>
    </x-slot:actions>
</x-dashboard.section-header>

{{-- Note: drag & drop interaction is not wired yet — columns render current
     status buckets only. Each card links through to the lead's detail page
     where status can be advanced via the status-update form. --}}

<div class="grid grid-cols-1 gap-4 overflow-x-auto pb-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6" role="list" aria-label="{{ __('admin.leads_kanban') }}">
    @foreach ($columns as $col)
        @php $colLeads = $leads->get($col->value, collect()); @endphp
        <div class="flex min-w-[260px] flex-col rounded-2xl border {{ $colHeaderTone[$col->value] ?? 'border-gray-800/60 text-gray-300' }} bg-gray-900/50 shadow-xl backdrop-blur-sm" role="listitem" aria-label="{{ $col->label() }}">
            <div class="flex items-center justify-between border-b border-gray-800/60 px-4 py-3">
                <h2 class="text-sm font-semibold">{{ $col->label() }}</h2>
                <span class="rounded-full bg-gray-800/60 px-2 py-0.5 text-xs font-medium text-gray-400">{{ $colLeads->count() }}</span>
            </div>
            <p class="text-[11px] text-gray-600 px-4 py-2 border-b border-gray-800/40">تغییر وضعیت را از صفحه جزئیات سرنخ انجام دهید</p>

            <div class="flex-1 space-y-3 p-3" data-kanban-column="{{ $col->value }}">
                @forelse ($colLeads as $lead)
                    <a href="{{ route('admin.leads.show', $lead) }}"
                       class="block rounded-xl border border-gray-800/60 bg-gray-800/30 p-3 transition duration-150 hover:border-gray-700 hover:bg-gray-800/50"
                       data-lead-id="{{ $lead->id }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-medium text-gray-100">{{ $lead->full_name }}</p>
                            <span class="shrink-0 rounded-full {{ $priorityBadge[$lead->priority->value] ?? 'bg-gray-700/50 text-gray-400' }} px-2 py-0.5 text-[11px] font-medium">
                                {{ $lead->priority->label() }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500" dir="ltr">{{ $lead->phone }}</p>
                        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                            <span>{{ $lead->preferredInstrument?->display_name ?? '—' }}</span>
                            <span>{{ $lead->assignedUser?->full_name ?? __('admin.unassigned') }}</span>
                        </div>
                        @if ($lead->isOverdue())
                            <p class="mt-2 text-[11px] font-medium text-rose-400">{{ __('admin.overdue') }}</p>
                        @endif
                    </a>
                @empty
                    <p class="px-2 py-6 text-center text-xs text-gray-600">{{ __('admin.no_leads_in_column') }}</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

@endsection
