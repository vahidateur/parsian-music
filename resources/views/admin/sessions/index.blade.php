@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.class_sessions') }}@endsection

@section('content')
@php
    $btnPrimary   = "inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition duration-200 hover:from-amber-500 hover:to-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900";
    $btnSecondary = "inline-flex items-center gap-1.5 rounded-lg border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm font-medium text-gray-300 transition duration-200 hover:border-gray-600 hover:bg-gray-800/60 hover:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/30";
    $btnClear     = "rounded-lg px-4 py-2.5 text-sm text-gray-500 transition duration-200 hover:text-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/20";
    $inputClass   = "block w-full rounded-lg border border-gray-700/60 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition duration-200 focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20";
    $statusBadge  = [
        'scheduled' => 'bg-sky-500/10 text-sky-400',
        'completed' => 'bg-emerald-500/10 text-emerald-400',
        'cancelled' => 'bg-red-500/10 text-red-400',
        'missed'    => 'bg-red-500/10 text-red-400',
        'makeup'    => 'bg-amber-500/10 text-amber-300',
    ];
@endphp

{{-- Header --}}
<x-dashboard.section-header headingLevel="h1"
    :title="__('admin.class_sessions')"
    :subtitle="__('admin.view_filter_sessions')"
>
    <x-slot:actions>
        <a href="{{ route('admin.sessions.create') }}" class="{{ $btnPrimary }}" aria-label="{{ __('admin.create_session') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            {{ __('admin.create_session') }}
        </a>
    </x-slot:actions>
</x-dashboard.section-header>

{{-- Feedback_Channel: shared success / failure / validation --}}
<x-admin.feedback />

{{-- Filters --}}
<x-dashboard.chart-container class="mb-5" title="{{ __('admin.search') }}" aria-label="فیلتر جلسات">
    <form method="GET" action="{{ route('admin.sessions.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 xl:items-end" role="search">
        <div>
            <label for="f-student" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.student') }}</label>
            <select id="f-student" name="student_id" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_students') }}</option>
                @foreach ($students ?? [] as $s)
                    <option value="{{ $s->id }}" {{ (string) request('student_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-teacher" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.teacher') }}</label>
            <select id="f-teacher" name="teacher_id" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_teachers') }}</option>
                @foreach ($teachers ?? [] as $t)
                    <option value="{{ $t->id }}" {{ (string) request('teacher_id') === (string) $t->id ? 'selected' : '' }}>{{ $t->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-instrument" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.instrument') }}</label>
            <select id="f-instrument" name="instrument_id" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_instruments') }}</option>
                @foreach ($instruments ?? [] as $i)
                    <option value="{{ $i->id }}" {{ (string) request('instrument_id') === (string) $i->id ? 'selected' : '' }}>{{ $i->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-status" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.status') }}</label>
            <select id="f-status" name="status" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_statuses') }}</option>
                @foreach (\App\Enums\SessionStatusEnum::values() as $sv)
                    <option value="{{ $sv }}" {{ request('status') === $sv ? 'selected' : '' }}>{{ __('admin.session_statuses.'.$sv) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-room" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.room') }}</label>
            <select id="f-room" name="room" class="{{ $inputClass }}">
                <option value="">{{ __('admin.all_rooms') }}</option>
                @foreach (['A101', 'A102', 'A103'] as $r)
                    <option value="{{ $r }}" {{ request('room') === $r ? 'selected' : '' }}>{{ $r }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-date" class="mb-1.5 block text-xs font-medium text-gray-400">{{ __('admin.date') }}</label>
            <input id="f-date" type="date" name="date" value="{{ request('date', now()->format('Y-m-d')) }}" class="{{ $inputClass }}">
            @php $jalaliDate = \App\Helpers\Jalalian::fromCarbon(request('date') ? \Carbon\Carbon::parse(request('date')) : now()); @endphp
            <p class="mt-1 text-xs text-gray-500">{{ $jalaliDate }}</p>
        </div>
        <div class="flex items-end gap-3">
            <button type="submit" class="{{ $btnSecondary }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 0z"/></svg>
                {{ __('admin.filter') }}
            </button>
            <a href="{{ route('admin.sessions.index') }}" class="{{ $btnClear }}">{{ __('admin.clear') }}</a>
        </div>
    </form>
</x-dashboard.chart-container>

{{-- Table --}}
<x-dashboard.chart-container :title="__('admin.class_sessions')">
    @if ($sessions->count())
        <div class="-mx-4 -my-4 overflow-x-auto sm:-mx-6 sm:-my-6">
            <table class="w-full text-start text-sm" role="table">
                <thead>
                    <tr class="border-b border-gray-800/60 bg-gray-800/30">
                        @include('admin.partials.sort-th', ['col'=>'student_name',      'label'=>__('admin.student'),   'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                        @include('admin.partials.sort-th', ['col'=>'teacher_name',      'label'=>__('admin.teacher'),   'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                        @include('admin.partials.sort-th', ['col'=>'instrument_name',   'label'=>__('admin.instrument'),'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                        @include('admin.partials.sort-th', ['col'=>'session_date',      'label'=>__('admin.date'),      'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                        @include('admin.partials.sort-th', ['col'=>'start_time',        'label'=>__('admin.time'),      'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                        @include('admin.partials.sort-th', ['col'=>'duration_minutes',  'label'=>__('admin.duration'),  'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                        @include('admin.partials.sort-th', ['col'=>'room',              'label'=>__('admin.room'),      'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                        @include('admin.partials.sort-th', ['col'=>'status',            'label'=>__('admin.status'),    'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                        <th scope="col" class="px-5 py-3.5 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($sessions as $session)
                        @php
                            $sv     = $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status;
                            $badge  = $statusBadge[$sv] ?? 'bg-gray-700/50 text-gray-400';
                            $sName  = $session->enrollment?->student?->full_name  ?? $session->student?->full_name  ?? '—';
                            $tName  = $session->enrollment?->teacher?->full_name  ?? $session->teacher?->full_name  ?? '—';
                            $iName  = $session->enrollment?->instrument?->display_name ?? $session->instrument?->display_name ?? '—';
                            $sTime  = is_string($session->start_time) ? $session->start_time : $session->start_time?->format('H:i');
                        @endphp
                        <tr class="transition duration-150 hover:bg-gray-800/25">
                            <td class="px-5 py-3.5 font-medium text-gray-100">{{ $sName }}</td>
                            <td class="px-5 py-3.5 text-gray-400">{{ $tName }}</td>
                            <td class="px-5 py-3.5 text-gray-400">{{ $iName }}</td>
                            <td class="px-5 py-3.5 text-gray-400 tabular-nums">{{ \App\Helpers\Jalalian::fromCarbon($session->session_date) ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-mono text-gray-400 tabular-nums">{{ $sTime ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-gray-400 tabular-nums">{{ $session->duration_minutes }}{{ __('admin.minutes') }}</td>
                            <td class="px-5 py-3.5 text-gray-400">{{ $session->room ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge }}">
                                    {{ __('admin.session_statuses.'.$sv) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <button type="button"
                                        x-on:click="$dispatch('open-modal', 'confirm-session-delete-{{ $session->id }}')"
                                        class="rounded p-1 text-red-400 transition duration-150 hover:bg-red-500/10 hover:text-red-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-red-500/60"
                                        aria-label="{{ __('admin.delete') }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                <x-modal name="confirm-session-delete-{{ $session->id }}" variant="confirmation"
                                         :entity="$sName"
                                         :action="__('admin.delete')"
                                         :consequence="__('admin.confirmation_consequence_irreversible')">
                                    <x-admin.form-state>
                                        <form method="POST" action="{{ route('admin.sessions.destroy', $session) }}">
                                            @csrf @method('DELETE')
                                            <div class="flex justify-end gap-2">
                                                <button type="button" x-on:click="$dispatch('close')" class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300">{{ __('admin.cancel') }}</button>
                                                <button type="submit" x-bind:disabled="pending" x-bind:aria-busy="pending" class="rounded-lg bg-red-500/15 px-4 py-2 text-sm font-medium text-red-300 disabled:cursor-not-allowed disabled:opacity-60">{{ __('admin.confirm') }}</button>
                                            </div>
                                        </form>
                                    </x-admin.form-state>
                                </x-modal>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-dashboard.empty-state :message="__('admin.no_sessions_found')" compact>
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
            </x-slot:icon>
        </x-dashboard.empty-state>
    @endif
</x-dashboard.chart-container>

{{-- Pagination --}}
@if ($sessions->hasPages())
    <div class="mt-5 flex justify-center">
        {{ $sessions->withQueryString()->links() }}
    </div>
@endif

@endsection
