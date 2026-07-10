@extends('layouts.teacher')

@section('title', 'برنامه هفتگی')

@section('content')
@php
    use App\Enums\SessionStatusEnum;

    $statusColor = [
        'completed' => 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30',
        'scheduled' => 'bg-blue-500/15 text-blue-300 ring-1 ring-blue-500/30',
        'cancelled' => 'bg-red-500/15 text-red-300 ring-1 ring-red-500/30',
        'missed'    => 'bg-gray-500/15 text-gray-400 ring-1 ring-gray-500/30',
    ];
    $jalaliDays = ['Saturday'=>'شنبه','Sunday'=>'یکشنبه','Monday'=>'دوشنبه','Tuesday'=>'سه‌شنبه','Wednesday'=>'چهارشنبه','Thursday'=>'پنجشنبه','Friday'=>'جمعه'];

    $rangeFilter = request('range', 'week');
    $search      = request('search', '');
    $statusFlt   = request('status', '');
    $today       = \Carbon\CarbonImmutable::today();

    $dateStart = match($rangeFilter) {
        'today'    => $today,
        'tomorrow' => $today->addDay(),
        default    => $today->startOfWeek(),
    };
    $dateEnd = match($rangeFilter) {
        'today'    => $today,
        'tomorrow' => $today->addDay(),
        default    => $today->endOfWeek(),
    };

    $query = \App\Models\ClassSession::withEnrollmentDetails()
        ->forTeacher($teacher->id)
        ->forDateRange($dateStart->toDateString(), $dateEnd->toDateString())
        ->orderBySchedule();

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->whereHas('student', fn ($s) => $s->where('full_name', 'like', "%{$search}%"))
              ->orWhereHas('enrollment.student', fn ($s) => $s->where('full_name', 'like', "%{$search}%"));
        });
    }

    if ($statusFlt) {
        $query->where('status', $statusFlt);
    }

    $sessions = $query->get()->groupBy(fn ($s) => $s->session_date->toDateString());
@endphp

<x-dashboard.section-header
    title="برنامه کلاس‌ها"
    subtitle="{{ $teacher->full_name }}">
</x-dashboard.section-header>

{{-- Filters --}}
<x-dashboard.chart-container title="فیلتر" class="mt-5">
    <form method="GET" action="{{ route('teacher.schedule') }}" class="flex flex-wrap gap-3">
        {{-- Range --}}
        <div class="flex overflow-hidden rounded-xl border border-gray-700/60">
            @foreach(['today' => 'امروز', 'tomorrow' => 'فردا', 'week' => 'این هفته'] as $val => $label)
            <a href="{{ route('teacher.schedule', array_merge(request()->query(), ['range' => $val])) }}"
               class="px-4 py-2.5 text-sm font-medium transition {{ $rangeFilter === $val ? 'bg-blue-500/20 text-blue-300' : 'bg-gray-800/40 text-gray-400 hover:bg-gray-800/60 hover:text-gray-200' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        {{-- Search --}}
        <input type="text" name="search" value="{{ $search }}"
               placeholder="جستجوی هنرجو..."
               class="flex-1 min-w-[180px] rounded-xl border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-blue-500/50 focus:outline-none focus:ring-1 focus:ring-blue-500/30">

        {{-- Status --}}
        <select name="status"
                class="rounded-xl border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm text-gray-100 focus:border-blue-500/50 focus:outline-none focus:ring-1 focus:ring-blue-500/30">
            <option value="">همه وضعیت‌ها</option>
            @foreach(\App\Enums\SessionStatusEnum::cases() as $status)
            <option value="{{ $status->value }}" @selected($statusFlt === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <button type="submit"
                class="rounded-xl bg-gray-700/60 px-5 py-2.5 text-sm font-medium text-gray-200 transition hover:bg-gray-700">
            اعمال
        </button>
        <a href="{{ route('teacher.schedule') }}"
           class="rounded-xl px-4 py-2.5 text-sm text-gray-400 transition hover:text-gray-200">
            پاک‌سازی
        </a>
    </form>
</x-dashboard.chart-container>

{{-- Results --}}
<div class="mt-4 space-y-4">
    @forelse($sessions as $dateStr => $daySessions)
    @php
        $date      = \Carbon\Carbon::parse($dateStr);
        $dayName   = $jalaliDays[$date->englishDayOfWeek] ?? '';
        $jalaliDt  = \App\Helpers\Jalalian::fromCarbon($date);
        $isToday   = $date->isToday();
    @endphp

    <x-dashboard.chart-container
        :title="$dayName . ' ' . $jalaliDt"
        :badge="$daySessions->count() . ' کلاس'"
        :class="$isToday ? 'ring-1 ring-blue-500/30' : ''">

        <ul class="divide-y divide-gray-800/40" role="list">
            @foreach($daySessions as $session)
            @php
                $student    = $session->student ?? $session->enrollment?->student;
                $instrument = $session->instrument ?? $session->enrollment?->instrument;
                $hasAttend  = $session->attendances->isNotEmpty();
                $needsAttend = $session->status === SessionStatusEnum::Completed && !$hasAttend;
            @endphp
            <li class="group flex items-center justify-between gap-4 py-3 transition hover:bg-gray-800/20">
                <div class="flex items-center gap-3">
                    {{-- Time block --}}
                    <div class="w-14 text-center">
                        <p class="font-mono text-base font-semibold tabular-nums text-gray-100">
                            {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}
                        </p>
                        @if($session->duration_minutes)
                        <p class="text-[10px] text-gray-600">{{ $session->duration_minutes }} دقیقه</p>
                        @endif
                    </div>

                    {{-- Avatar --}}
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-blue-500/15 text-sm font-bold text-blue-300">
                        {{ mb_substr($student?->full_name ?? '?', 0, 1) }}
                    </div>

                    {{-- Info --}}
                    <div>
                        <p class="text-sm font-medium text-gray-100">{{ $student?->full_name ?? 'هنرجو نامشخص' }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $instrument?->display_name ?? '—' }}
                            @if($session->room) · اتاق {{ $session->room }} @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold {{ $statusColor[$session->status->value] ?? '' }}">
                        {{ $session->status->label() }}
                    </span>

                    @if($hasAttend)
                    <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400 ring-1 ring-emerald-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        ثبت شده
                    </span>
                    @elseif($needsAttend)
                    <a href="{{ route('teacher.attendance', $session) }}"
                       class="inline-flex items-center gap-1 rounded-lg bg-amber-500/15 px-2.5 py-1 text-xs font-semibold text-amber-300 ring-1 ring-amber-500/20 transition hover:bg-amber-500/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/50">
                        ثبت حضور
                    </a>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
    </x-dashboard.chart-container>
    @empty
    <x-dashboard.empty-state
        title="کلاسی یافت نشد"
        description="با فیلترهای انتخابی نتیجه‌ای وجود ندارد." />
    @endforelse
</div>
@endsection
