@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.leads') }}@endsection

@section('content')
@php
    $statusBadge  = ['new' => 'bg-sky-500/10 text-sky-400', 'contacted' => 'bg-blue-500/10 text-blue-400', 'interested' => 'bg-violet-500/10 text-violet-400', 'trial_scheduled' => 'bg-amber-500/10 text-amber-400', 'registered' => 'bg-emerald-500/10 text-emerald-400', 'lost' => 'bg-gray-700/50 text-gray-400'];
    $priorityBadge = ['high' => 'bg-red-500/10 text-red-400', 'medium' => 'bg-amber-500/10 text-amber-400', 'low' => 'bg-gray-700/50 text-gray-400'];
    $inputClass   = "block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20";
@endphp

{{-- Back + Actions --}}
<header class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <a href="{{ route('admin.leads.index') }}" class="text-sm text-gray-400 transition hover:text-gray-200">{{ __('admin.back_to_leads') }}</a>
        <h1 class="mt-3 text-2xl font-semibold text-amber-100">{{ $lead->full_name }}</h1>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.leads.edit', $lead) }}" class="rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">
            {{ __('admin.edit_lead') }}
        </a>
        <button type="button" x-on:click="$dispatch('open-modal', 'confirm-lead-delete-{{ $lead->id }}')" class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-2.5 text-sm font-medium text-red-400 transition hover:bg-red-500/20">
            {{ __('admin.delete') }}
        </button>
        <x-modal name="confirm-lead-delete-{{ $lead->id }}" variant="confirmation"
                 :entity="$lead->full_name"
                 :action="__('admin.delete')"
                 :consequence="__('admin.confirmation_consequence_irreversible')">
            <x-admin.form-state>
                <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}">
                    @csrf @method('DELETE')
                    <div class="flex justify-end gap-2">
                        <button type="button" x-on:click="$dispatch('close')" class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300">{{ __('admin.cancel') }}</button>
                        <button type="submit" x-bind:disabled="pending" x-bind:aria-busy="pending" class="rounded-lg bg-red-500/15 px-4 py-2 text-sm font-medium text-red-300 disabled:cursor-not-allowed disabled:opacity-60">{{ __('admin.confirm') }}</button>
                    </div>
                </form>
            </x-admin.form-state>
        </x-modal>
    </div>
</header>

{{-- Feedback_Channel: shared success / failure / validation --}}
<x-admin.feedback />

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Main info --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Lead information --}}
        <div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
            <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
                <h2 class="text-lg font-semibold text-amber-100">{{ __('admin.lead_information') }}</h2>
                <div class="flex gap-2">
                    <span class="rounded-full {{ $statusBadge[$lead->status->value] ?? 'bg-gray-700/50 text-gray-400' }} px-2.5 py-0.5 text-xs font-medium">{{ $lead->status->label() }}</span>
                    <span class="rounded-full {{ $priorityBadge[$lead->priority->value] ?? 'bg-gray-700/50 text-gray-400' }} px-2.5 py-0.5 text-xs font-medium">{{ $lead->priority->label() }}</span>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-6 px-6 py-6 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.full_name') }}</p>
                    <p class="mt-1 text-sm text-gray-100">{{ $lead->full_name }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.phone') }}</p>
                    <p class="mt-1 text-sm text-gray-100" dir="ltr">{{ $lead->phone }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.email') }}</p>
                    <p class="mt-1 text-sm text-gray-100">{{ $lead->email ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.age') }}</p>
                    <p class="mt-1 text-sm text-gray-100">{{ $lead->age ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.source') }}</p>
                    <p class="mt-1 text-sm text-gray-100">{{ $lead->source->label() }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.assigned_admin') }}</p>
                    <p class="mt-1 text-sm text-gray-100">{{ $lead->assignedUser?->full_name ?? __('admin.unassigned') }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.preferred_instrument') }}</p>
                    <p class="mt-1 text-sm text-gray-100">{{ $lead->preferredInstrument?->display_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.preferred_teacher') }}</p>
                    <p class="mt-1 text-sm text-gray-100">{{ $lead->preferredTeacher?->full_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.next_follow_up') }}</p>
                    <p class="mt-1 text-sm {{ $lead->isOverdue() ? 'text-rose-400 font-medium' : 'text-gray-100' }}">
                        {{ $lead->next_follow_up_at ? \App\Helpers\Jalalian::fromCarbon($lead->next_follow_up_at, 'Y/m/d H:i') : '—' }}
                        @if ($lead->isOverdue())
                            <span class="mr-1 text-xs">({{ __('admin.overdue') }})</span>
                        @endif
                    </p>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.notes') }}</p>
                    <p class="mt-1 text-sm text-gray-400 whitespace-pre-line">{{ $lead->notes ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
            <div class="border-b border-gray-800/60 px-6 py-4">
                <h2 class="text-lg font-semibold text-amber-100">{{ __('admin.lead_timeline') }}</h2>
            </div>
            <div class="px-6 py-6">
                <ol class="relative border-r border-gray-800/60 pr-6 space-y-6">
                    <li class="relative">
                        <span class="absolute -right-[1.3rem] flex h-6 w-6 items-center justify-center rounded-full border border-gray-800 bg-sky-400 shadow-lg">
                            <svg class="h-3 w-3 text-gray-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </span>
                        <div class="rounded-xl border border-sky-500/30 bg-gray-800/30 px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="inline-flex items-center rounded-full bg-sky-500/10 px-2.5 py-0.5 text-xs font-medium text-sky-300">{{ __('admin.lead_created') }}</span>
                                <time class="text-xs text-gray-500 tabular-nums" dir="ltr">{{ \App\Helpers\Jalalian::fromCarbon($lead->created_at, 'Y/m/d H:i') }}</time>
                            </div>
                            <p class="mt-1.5 text-sm text-gray-300">{{ __('admin.history_lead_created_desc', ['source' => $lead->source->label()]) }}</p>
                        </div>
                    </li>
                    @if ($lead->updated_at && $lead->updated_at->ne($lead->created_at))
                        <li class="relative">
                            <span class="absolute -right-[1.3rem] flex h-6 w-6 items-center justify-center rounded-full border border-gray-800 bg-amber-400 shadow-lg">
                                <svg class="h-3 w-3 text-gray-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                            </span>
                            <div class="rounded-xl border border-amber-500/30 bg-gray-800/30 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="inline-flex items-center rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-medium text-amber-300">{{ __('admin.lead_last_updated') }}</span>
                                    <time class="text-xs text-gray-500 tabular-nums" dir="ltr">{{ \App\Helpers\Jalalian::fromCarbon($lead->updated_at, 'Y/m/d H:i') }}</time>
                                </div>
                                <p class="mt-1.5 text-sm text-gray-300">{{ __('admin.history_lead_status_desc', ['status' => $lead->status->label()]) }}</p>
                            </div>
                        </li>
                    @endif
                    @if ($lead->isConverted())
                        <li class="relative">
                            <span class="absolute -right-[1.3rem] flex h-6 w-6 items-center justify-center rounded-full border border-gray-800 bg-emerald-400 shadow-lg">
                                <svg class="h-3 w-3 text-gray-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                            <div class="rounded-xl border border-emerald-500/30 bg-gray-800/30 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-300">{{ __('admin.lead_converted') }}</span>
                                    <time class="text-xs text-gray-500 tabular-nums" dir="ltr">{{ \App\Helpers\Jalalian::fromCarbon($lead->converted_at, 'Y/m/d H:i') }}</time>
                                </div>
                                <p class="mt-1.5 text-sm text-gray-300">
                                    <a href="{{ route('admin.students.show', $lead->converted_student_id) }}" class="text-amber-400 hover:text-amber-300">{{ __('admin.view_student') }}</a>
                                </p>
                            </div>
                        </li>
                    @endif
                </ol>
            </div>
        </div>
    </div>

    {{-- Side actions --}}
    <div class="space-y-6">
        {{-- Update status --}}
        @if (!$lead->status->isTerminal())
            <div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
                <div class="border-b border-gray-800/60 px-6 py-4">
                    <h2 class="text-sm font-semibold text-amber-100">{{ __('admin.update_status') }}</h2>
                </div>
                <div class="px-6 py-5 space-y-3">
                    <form method="POST" action="{{ route('admin.leads.updateStatus', $lead) }}" class="space-y-3">
                        @csrf @method('PATCH')
                        <select name="status" class="{{ $inputClass }}">
                            @foreach (\App\Enums\LeadStatusEnum::cases() as $s)
                                @if ($lead->status->canTransitionTo($s))
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
                            {{ __('admin.update_status') }}
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Assign lead --}}
        <div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
            <div class="border-b border-gray-800/60 px-6 py-4">
                <h2 class="text-sm font-semibold text-amber-100">{{ __('admin.assign_lead') }}</h2>
            </div>
            <div class="px-6 py-5">
                <form method="POST" action="{{ route('admin.leads.assign', $lead) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <select name="assigned_to" class="{{ $inputClass }}">
                        <option value="">{{ __('admin.unassigned') }}</option>
                        @foreach ($assignees as $admin)
                            <option value="{{ $admin->id }}" {{ $lead->assigned_to === $admin->id ? 'selected' : '' }}>{{ $admin->full_name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">
                        {{ __('admin.save') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Follow-up --}}
        <div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
            <div class="border-b border-gray-800/60 px-6 py-4">
                <h2 class="text-sm font-semibold text-amber-100">{{ __('admin.schedule_follow_up') }}</h2>
            </div>
            <div class="px-6 py-5">
                <form method="POST" action="{{ route('admin.leads.followUp', $lead) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <input type="datetime-local" name="next_follow_up_at"
                           value="{{ $lead->next_follow_up_at?->format('Y-m-d\TH:i') }}"
                           class="{{ $inputClass }}" required>
                    <button type="submit" class="w-full rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">
                        {{ __('admin.save') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Convert --}}
        @if (!$lead->isConverted() && $lead->status->canTransitionTo(\App\Enums\LeadStatusEnum::Registered))
            <div class="overflow-hidden rounded-2xl border border-emerald-500/30 bg-emerald-500/[0.04] shadow-xl backdrop-blur-sm">
                <div class="border-b border-emerald-500/20 px-6 py-4">
                    <h2 class="text-sm font-semibold text-emerald-300">{{ __('admin.convert_lead') }}</h2>
                </div>
                <div class="px-6 py-5">
                    <form method="POST" action="{{ route('admin.leads.convert', $lead) }}" class="space-y-3">
                        @csrf
                        <label class="block text-xs font-medium text-gray-400">{{ __('admin.skill_level') }} ({{ __('admin.optional') }})</label>
                        <select name="skill_level" class="{{ $inputClass }}">
                            <option value="">{{ __('admin.select_level') }}</option>
                            @foreach (\App\Enums\SkillLevelEnum::cases() as $level)
                                <option value="{{ $level->value }}">{{ __('admin.skill_levels.' . $level->value) }}</option>
                            @endforeach
                        </select>
                        <label class="block text-xs font-medium text-gray-400">{{ __('admin.start_date') }} ({{ __('admin.optional') }})</label>
                        <input type="date" name="start_date" class="{{ $inputClass }}">
                        <p class="text-xs text-gray-500">{{ __('admin.convert_enrollment_hint') }}</p>
                        <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-emerald-500 hover:to-emerald-400">
                            {{ __('admin.convert_lead') }}
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
