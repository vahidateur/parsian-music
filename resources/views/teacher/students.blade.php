@extends('layouts.teacher')

@section('title', 'هنرجویان من')

@section('content')

<x-dashboard.section-header
    title="هنرجویان من"
    :subtitle="$enrollments->total() . ' ثبت‌نام فعال'">
</x-dashboard.section-header>

{{-- Search --}}
<x-dashboard.chart-container title="جستجو" class="mt-5">
    <form method="GET" action="{{ route('teacher.students') }}" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="جستجوی نام یا تلفن هنرجو..."
               class="flex-1 min-w-[200px] rounded-xl border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-blue-500/50 focus:outline-none focus:ring-1 focus:ring-blue-500/30">
        <button type="submit"
                class="rounded-xl bg-gray-700/60 px-5 py-2.5 text-sm font-medium text-gray-200 transition hover:bg-gray-700">
            جستجو
        </button>
        @if(request('search'))
        <a href="{{ route('teacher.students') }}"
           class="rounded-xl px-4 py-2.5 text-sm text-gray-400 transition hover:text-gray-200">
            پاک‌سازی
        </a>
        @endif
    </form>
</x-dashboard.chart-container>

{{-- Students list --}}
<x-dashboard.chart-container title="لیست هنرجویان"
    :badge="$enrollments->total() . ' مورد'" class="mt-4">
    @if($enrollments->isEmpty())
        <x-dashboard.empty-state
            title="هنرجویی یافت نشد"
            description="هیچ هنرجویی با این جستجو پیدا نشد." />
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm" role="table" aria-label="لیست هنرجویان">
            <thead>
                <tr class="border-b border-gray-800/60 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="px-4 py-3" scope="col">هنرجو</th>
                    <th class="px-4 py-3" scope="col">ساز</th>
                    <th class="px-4 py-3" scope="col">تاریخ شروع</th>
                    <th class="px-4 py-3" scope="col">وضعیت</th>
                    <th class="px-4 py-3 text-center" scope="col">حضور و غیاب</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/40">
                @foreach($enrollments as $enrollment)
                @php
                    $student    = $enrollment->student;
                    $instrument = $enrollment->instrument;

                    // Attendance summary for this enrollment
                    $sessionIds = \App\Models\ClassSession::where('enrollment_id', $enrollment->id)->pluck('id');
                    $attendanceSummary = \App\Models\ClassAttendance::whereIn('class_session_id', $sessionIds)
                        ->selectRaw('status, count(*) as total')
                        ->groupBy('status')
                        ->pluck('total', 'status');
                    $totalSessions  = $sessionIds->count();
                    $presentCount   = $attendanceSummary[\App\Enums\AttendanceStatusEnum::Present->value] ?? 0;
                    $attendanceRate = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100) : 0;

                    // Upcoming sessions for this enrollment
                    $upcoming = \App\Models\ClassSession::where('enrollment_id', $enrollment->id)
                        ->where('status', \App\Enums\SessionStatusEnum::Scheduled->value)
                        ->whereDate('session_date', '>=', now())
                        ->orderBy('session_date')->orderBy('start_time')
                        ->first();
                @endphp
                <tr class="transition hover:bg-gray-800/20">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-blue-500/15 text-sm font-bold text-blue-300">
                                {{ mb_substr($student->full_name, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-100">{{ $student->full_name }}</p>
                                <p class="text-xs text-gray-500">{{ $student->phone }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-3">
                        <span class="text-sm text-gray-300">{{ $instrument?->display_name ?? '—' }}</span>
                    </td>

                    <td class="px-4 py-3 text-xs text-gray-500 tabular-nums">
                        {{ $enrollment->start_date ? \App\Helpers\Jalalian::fromCarbon($enrollment->start_date) : '—' }}
                    </td>

                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold ring-1
                            {{ $enrollment->status?->value === 'active' ? 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30' : 'bg-gray-500/15 text-gray-400 ring-gray-500/30' }}">
                            {{ $enrollment->status?->label() ?? '—' }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex flex-col items-center gap-1">
                            {{-- Progress bar --}}
                            <div class="flex w-full max-w-[100px] items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-800">
                                    <div class="h-1.5 rounded-full transition-all duration-500
                                        {{ $attendanceRate >= 80 ? 'bg-emerald-500' : ($attendanceRate >= 60 ? 'bg-amber-500' : 'bg-red-500') }}"
                                         style="width: {{ $attendanceRate }}%"></div>
                                </div>
                                <span class="text-xs font-mono tabular-nums text-gray-400">{{ $attendanceRate }}%</span>
                            </div>
                            @if($upcoming)
                            <p class="text-[10px] text-gray-600 tabular-nums">
                                بعدی: {{ \App\Helpers\Jalalian::fromCarbon($upcoming->session_date) }}
                                {{ \Carbon\Carbon::parse($upcoming->start_time)->format('H:i') }}
                            </p>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($enrollments->hasPages())
    <div class="mt-4 border-t border-gray-800/40 px-4 pt-4">
        {{ $enrollments->withQueryString()->links() }}
    </div>
    @endif
    @endif
</x-dashboard.chart-container>
@endsection
