@extends('layouts.dashboard')

@section('content')

{{-- Page Heading --}}
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.class_sessions') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('admin.view_filter_sessions') }}</p>
        </div>
        <a href="{{ route('admin.sessions.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition hover:from-amber-500 hover:to-amber-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('admin.create_session') }}
            </a>
    </div>
</div>

{{-- Success Message --}}
@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('admin.sessions.index') }}" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.student') }}</label>
        <select name="student_id" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.all_students') }}</option>
            @foreach ($students ?? [] as $student)
                <option value="{{ $student->id }}" {{ (string) request('student_id') === (string) $student->id ? 'selected' : '' }}>{{ $student->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.teacher') }}</label>
        <select name="teacher_id" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.all_teachers') }}</option>
            @foreach ($teachers ?? [] as $teacher)
                <option value="{{ $teacher->id }}" {{ (string) request('teacher_id') === (string) $teacher->id ? 'selected' : '' }}>{{ $teacher->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.instrument') }}</label>
        <select name="instrument_id" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.all_instruments') }}</option>
            @foreach ($instruments ?? [] as $instrument)
                <option value="{{ $instrument->id }}" {{ (string) request('instrument_id') === (string) $instrument->id ? 'selected' : '' }}>{{ $instrument->display_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.room') }}</label>
        <select name="room" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.all_rooms') }}</option>
            @foreach (['A101', 'A102', 'A103'] as $r)
                <option value="{{ $r }}" {{ request('room') === $r ? 'selected' : '' }}>{{ $r }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.status') }}</label>
        <select name="status" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.all_statuses') }}</option>
            @foreach (\App\Enums\SessionStatusEnum::values() as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ __('admin.session_statuses.' . $status) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.date') }}</label>
        <input type="date" name="date" value="{{ request('date', now()->format('Y-m-d')) }}"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
        @php
            $selectedDate = request('date') ? \Carbon\Carbon::parse(request('date')) : now();
            $jalaliDate = \App\Helpers\Jalalian::fromCarbon($selectedDate);
        @endphp
        <p class="mt-1 text-xs text-gray-500">{{ __('admin.jalali_equivalent') }}: <span class="text-amber-400">{{ $jalaliDate }}</span></p>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">{{ __('admin.filter') }}</button>
        <a href="{{ route('admin.sessions.index') }}" class="rounded-lg px-4 py-2.5 text-sm text-gray-500 transition hover:text-gray-300">{{ __('admin.clear') }}</a>
    </div>
</form>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-800/60 bg-gray-800/30">
                    @include('admin.partials.sort-th', ['col'=>'student_name',       'label'=>__('admin.student'),   'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                    @include('admin.partials.sort-th', ['col'=>'teacher_name',       'label'=>__('admin.teacher'),   'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                    @include('admin.partials.sort-th', ['col'=>'instrument_name',    'label'=>__('admin.instrument'),'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                    @include('admin.partials.sort-th', ['col'=>'session_date',      'label'=>__('admin.date'),     'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                    @include('admin.partials.sort-th', ['col'=>'start_time',        'label'=>__('admin.time'),     'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                    @include('admin.partials.sort-th', ['col'=>'duration_minutes',  'label'=>__('admin.duration'), 'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                    @include('admin.partials.sort-th', ['col'=>'room',              'label'=>__('admin.room'),     'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                    @include('admin.partials.sort-th', ['col'=>'status',            'label'=>__('admin.status'),   'currentSort'=>$sortCol, 'currentDir'=>$sortDir, 'route'=>'admin.sessions.index'])
                    <th class="px-5 py-4 text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/60">
                @forelse ($sessions as $session)
                    @php
                        $statusValue = $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status;
                        $statusStyles = [
                            'scheduled' => 'bg-sky-500/10 text-sky-400',
                            'completed' => 'bg-emerald-500/10 text-emerald-400',
                            'cancelled' => 'bg-red-500/10 text-red-400',
                            'missed' => 'bg-red-500/10 text-red-400',
                            'makeup' => 'bg-amber-500/10 text-amber-300',
                        ];
                        $badgeStyle = $statusStyles[$statusValue] ?? 'bg-gray-700/50 text-gray-400';
                    @endphp
                    <tr class="transition hover:bg-gray-800/20">
                        <td class="px-5 py-3.5 font-medium text-gray-100">{{ $session->enrollment?->student?->full_name ?? $session->student?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ $session->enrollment?->teacher?->full_name ?? $session->teacher?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ $session->enrollment?->instrument?->display_name ?? $session->instrument?->display_name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ \App\Helpers\Jalalian::fromCarbon($session->session_date) ?? '—' }}</td>
                        @php
                            $startTime = is_string($session->start_time) ? $session->start_time : $session->start_time?->format('H:i');
                        @endphp
                        <td class="px-5 py-3.5 text-gray-400">{{ $startTime ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ $session->duration_minutes }}{{ __('admin.minutes') }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ $session->room ?? '—' }}</td>
                        <td class="px-5 py-3.5">
                            <span class="rounded-full {{ $badgeStyle }} px-2.5 py-0.5 text-xs font-medium">
                                {{ __('admin.session_statuses.' . $statusValue) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <form method="POST" action="{{ route('admin.sessions.destroy', $session) }}" style="display: inline;"
                                  onsubmit="return confirm('{{ __('admin.delete_session_confirm') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 transition hover:text-red-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-12 text-center text-gray-500">{{ __('admin.no_sessions_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
@if ($sessions->hasPages())
    <div class="mt-6 flex justify-center">
        {{ $sessions->links() }}
    </div>
@endif

@endsection
