@extends('layouts.dashboard')

@section('content')

{{-- Page Heading --}}
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.weekly_calendar') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ \App\Helpers\Jalalian::fromCarbon($weekStart) }} — {{ \App\Helpers\Jalalian::fromCarbon($weekEnd) }}
        </p>
    </div>

    {{-- Week Navigation --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.calendar.index', ['week' => $prevWeek]) }}"
           class="rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2 text-sm text-gray-300 transition hover:border-gray-600 hover:text-gray-100">{{ __('admin.prev_week') }}</a>
        <a href="{{ route('admin.calendar.index') }}"
           class="rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2 text-sm text-gray-300 transition hover:border-gray-600 hover:text-gray-100">{{ __('admin.today_nav') }}</a>
        <a href="{{ route('admin.calendar.index', ['week' => $nextWeek]) }}"
           class="rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2 text-sm text-gray-300 transition hover:border-gray-600 hover:text-gray-100">{{ __('admin.next_week') }}</a>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.calendar.index') }}" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3 lg:items-end">
    <input type="hidden" name="week" value="{{ request('week') }}">

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
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.student') }}</label>
        <select name="student_id" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.all_students') }}</option>
            @foreach ($students ?? [] as $student)
                <option value="{{ $student->id }}" {{ (string) request('student_id') === (string) $student->id ? 'selected' : '' }}>{{ $student->full_name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.room') }}</label>
        <select name="room" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            <option value="">{{ __('admin.all_rooms') }}</option>
            @foreach (['Room 1', 'Room 2', 'Room 3'] as $r)
                <option value="{{ $r }}" {{ request('room') === $r ? 'selected' : '' }}>{{ __('admin.rooms.' . $r) }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm font-medium text-gray-300 transition hover:border-gray-600 hover:text-gray-100">{{ __('admin.filter') }}</button>
        <a href="{{ route('admin.calendar.index') }}" class="rounded-lg px-4 py-2.5 text-sm text-gray-500 transition hover:text-gray-300">{{ __('admin.clear') }}</a>
    </div>
</form>

{{-- Calendar Grid --}}
<div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            {{-- Day Headers --}}
            <thead>
                <tr>
                    <th class="sticky left-0 z-10 w-16 border-b border-r border-gray-800/60 bg-gray-800/80 px-2 py-3 text-xs font-medium text-gray-500">{{ __('admin.time') }}</th>
                    @foreach ($days as $day)
                        @php
                            $isToday = $day->isToday();
                            $jalaliDate = \App\Helpers\Jalalian::fromCarbon($day);
                            $persianDayName = \App\Helpers\Jalalian::dayOfWeek($day);
                        @endphp
                        <th class="border-b border-r border-gray-800/60 px-3 py-3 text-center {{ $isToday ? 'bg-amber-500/5' : '' }}">
                            <div class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ $persianDayName }}</div>
                            <div class="mt-0.5 text-sm font-semibold {{ $isToday ? 'text-amber-300' : 'text-gray-300' }}">{{ $jalaliDate }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>

            {{-- Time Slot Rows --}}
            <tbody>
                @foreach ($hours as $hour)
                    @php
                        $label = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                    @endphp
                    <tr class="group">
                        <td class="sticky left-0 z-10 border-b border-r border-gray-800/60 bg-gray-800/80 px-2 py-2 text-xs text-gray-500 align-top">{{ $label }}</td>
                        @foreach ($days as $day)
                            @php
                                $dateKey = $day->toDateString();
                                $daySessions = $grid[$dateKey][$hour] ?? [];
                            @endphp
                            <td class="border-b border-r border-gray-800/60 p-1 align-top">
                                @foreach ($daySessions as $session)
                                    @php
                                        $statusValue = $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status;
                                        $statusColors = [
                                            'scheduled' => 'border-sky-500/40 bg-sky-500/10 text-sky-300',
                                            'completed' => 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300',
                                            'cancelled' => 'border-red-500/40 bg-red-500/10 text-red-300',
                                            'makeup' => 'border-amber-500/40 bg-amber-500/10 text-amber-300',
                                        ];
                                        $cardColor = $statusColors[$statusValue] ?? 'border-gray-600 bg-gray-700/40 text-gray-300';
                                    @endphp
                                    <div class="mb-1 rounded-md border px-2 py-1.5 text-xs {{ $cardColor }}">
                                        <div class="font-semibold leading-tight">{{ $session->enrollment?->student?->full_name ?? '—' }}</div>
                                        <div class="mt-0.5 opacity-80">{{ $session->enrollment?->instrument?->name ?? '—' }}</div>
                                        @php
                                            $startTime = is_string($session->start_time) ? $session->start_time : $session->start_time->format('H:i');
                                        @endphp
                                        <div class="mt-0.5 opacity-70">{{ $startTime }} · {{ __('admin.rooms.' . $session->room) }}</div>
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
