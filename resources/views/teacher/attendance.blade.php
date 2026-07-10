@extends('layouts.teacher')

@section('title', 'ثبت حضور و غیاب')

@section('content')
@php
    use App\Enums\AttendanceStatusEnum;

    $statusConfig = [
        AttendanceStatusEnum::Present->value  => ['label' => 'حاضر',    'color' => 'bg-emerald-500/20 border-emerald-500/40 text-emerald-300 hover:bg-emerald-500/30', 'active' => 'bg-emerald-500 text-white border-emerald-500', 'dot' => 'bg-emerald-400'],
        AttendanceStatusEnum::Absent->value   => ['label' => 'غایب',    'color' => 'bg-red-500/20 border-red-500/40 text-red-300 hover:bg-red-500/30',           'active' => 'bg-red-500 text-white border-red-500',       'dot' => 'bg-red-400'],
        AttendanceStatusEnum::Late->value     => ['label' => 'تأخیر',   'color' => 'bg-amber-500/20 border-amber-500/40 text-amber-300 hover:bg-amber-500/30',     'active' => 'bg-amber-500 text-white border-amber-500',   'dot' => 'bg-amber-400'],
        AttendanceStatusEnum::Excused->value  => ['label' => 'موجه',    'color' => 'bg-blue-500/20 border-blue-500/40 text-blue-300 hover:bg-blue-500/30',         'active' => 'bg-blue-500 text-white border-blue-500',     'dot' => 'bg-blue-400'],
    ];

    $sessionDate  = \App\Helpers\Jalalian::fromCarbon($session->session_date);
    $sessionTime  = \Carbon\Carbon::parse($session->start_time)->format('H:i');
    $statusLabel  = $session->status->label();
@endphp

{{-- Back link --}}
<div class="mb-5">
    <a href="{{ route('teacher.schedule') }}"
       class="inline-flex items-center gap-2 text-sm text-gray-400 transition hover:text-blue-300">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        بازگشت به برنامه
    </a>
</div>

{{-- Session header --}}
<x-dashboard.chart-container
    title="مشخصات جلسه"
    class="mb-5">
    <div class="flex flex-wrap items-center gap-6 text-sm">
        <div>
            <p class="text-xs text-gray-500">تاریخ</p>
            <p class="mt-0.5 font-semibold tabular-nums text-gray-100">{{ $sessionDate }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500">ساعت</p>
            <p class="mt-0.5 font-semibold tabular-nums text-gray-100">{{ $sessionTime }}</p>
        </div>
        @if($session->duration_minutes)
        <div>
            <p class="text-xs text-gray-500">مدت</p>
            <p class="mt-0.5 font-semibold text-gray-100">{{ $session->duration_minutes }} دقیقه</p>
        </div>
        @endif
        @if($session->room)
        <div>
            <p class="text-xs text-gray-500">اتاق</p>
            <p class="mt-0.5 font-semibold text-gray-100">{{ $session->room }}</p>
        </div>
        @endif
        <div>
            <p class="text-xs text-gray-500">وضعیت</p>
            <span class="mt-0.5 inline-flex items-center rounded-lg bg-blue-500/15 px-2.5 py-1 text-xs font-semibold text-blue-300 ring-1 ring-blue-500/30">
                {{ $statusLabel }}
            </span>
        </div>
    </div>
</x-dashboard.chart-container>

{{-- Flash --}}
@if(session('success'))
    <x-dashboard.alert-card priority="success" :message="session('success')" class="mb-5" />
@endif
@if($session->attendances->isNotEmpty() && !session('success'))
    <x-dashboard.alert-card priority="info" message="حضور و غیاب این جلسه قبلاً ثبت شده است. می‌توانید ویرایش کنید." class="mb-5" />
@endif

{{-- Bulk form --}}
<form method="POST" action="{{ route('teacher.attendance.save', $session) }}"
      x-data="attendanceForm()"
      @submit.prevent="submitAll">

    @csrf

    <x-dashboard.chart-container title="ثبت حضور و غیاب" class="mb-5">

        @if($students->isEmpty())
            <x-dashboard.empty-state
                title="هنرجویی برای این جلسه ثبت نشده"
                description="این جلسه هنرجوی مشخصی ندارد." />
        @else
        <ul class="space-y-4" role="list">
            @foreach($students as $student)
            @php
                $existing = $attendanceMap[$student->id] ?? null;
                $defStatus = $existing?->status->value ?? AttendanceStatusEnum::Present->value;
                $defNote   = $existing?->note ?? '';
            @endphp
            <li class="rounded-xl border border-gray-800/40 bg-gray-800/20 p-4"
                x-data="{ status: '{{ $defStatus }}', note: '{{ addslashes($defNote) }}' }">

                <input type="hidden" name="attendance[{{ $loop->index }}][student_id]" value="{{ $student->id }}">
                <input type="hidden" name="attendance[{{ $loop->index }}][note]"       :value="note">
                <input type="hidden" name="attendance[{{ $loop->index }}][status]"     :value="status">

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    {{-- Student info --}}
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-sm font-bold text-blue-300">
                            {{ mb_substr($student->full_name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-100">{{ $student->full_name }}</p>
                            <p class="text-xs text-gray-500">{{ $student->phone }}</p>
                        </div>
                    </div>

                    {{-- Status radio buttons --}}
                    <div class="flex flex-wrap gap-2" role="group" :aria-label="'وضعیت ' + '{{ $student->full_name }}'">
                        @foreach($statusConfig as $val => $cfg)
                        <button type="button"
                                @click="status = '{{ $val }}'"
                                :class="status === '{{ $val }}' ? '{{ $cfg['active'] }}' : '{{ $cfg['color'] }}'"
                                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-semibold transition duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/50">
                            <span class="h-2 w-2 rounded-full {{ $cfg['dot'] }}" aria-hidden="true"></span>
                            {{ $cfg['label'] }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Note --}}
                <div class="mt-3">
                    <input type="text"
                           x-model="note"
                           placeholder="یادداشت (اختیاری)..."
                           class="w-full rounded-xl border border-gray-700/60 bg-gray-900/50 px-4 py-2.5 text-sm text-gray-200 placeholder-gray-600 focus:border-blue-500/40 focus:outline-none focus:ring-1 focus:ring-blue-500/20">
                </div>

                {{-- Status indicator --}}
                <div class="mt-2 text-right">
                    <span class="text-xs text-gray-600">
                        وضعیت انتخابی:
                        <span :class="{
                            'text-emerald-400': status === 'present',
                            'text-red-400':     status === 'absent',
                            'text-amber-400':   status === 'late',
                            'text-blue-400':    status === 'excused',
                        }" x-text="{
                            present: 'حاضر',
                            absent:  'غایب',
                            late:    'تأخیر',
                            excused: 'موجه',
                        }[status]" class="font-semibold"></span>
                    </span>
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </x-dashboard.chart-container>

    @if($students->isNotEmpty())
    {{-- Quick-fill bar --}}
    <div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-gray-800/40 bg-gray-900/40 px-5 py-3">
        <span class="text-sm text-gray-400">پر کردن سریع:</span>
        <button type="button"
                onclick="document.querySelectorAll('[x-data]').forEach(el => { if (el._x_dataStack) { el._x_dataStack[0].status = 'present'; } })"
                class="rounded-lg bg-emerald-500/15 px-3 py-1.5 text-xs font-semibold text-emerald-300 transition hover:bg-emerald-500/25">
            همه حاضر
        </button>
        <button type="button"
                onclick="document.querySelectorAll('[x-data]').forEach(el => { if (el._x_dataStack) { el._x_dataStack[0].status = 'absent'; } })"
                class="rounded-lg bg-red-500/15 px-3 py-1.5 text-xs font-semibold text-red-300 transition hover:bg-red-500/25">
            همه غایب
        </button>
        <div class="flex-1"></div>
        <button type="submit"
                class="rounded-xl bg-blue-500 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
            ثبت حضور و غیاب
        </button>
    </div>
    @endif
</form>

<script>
function attendanceForm() {
    return {
        submitAll() {
            this.$el.submit();
        }
    };
}
</script>
@endsection
