@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.edit_student') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.update_student_desc') }}</p>
</div>

@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
        <ul class="list-disc pr-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.students.update', $student) }}" class="max-w-2xl space-y-5"
      x-data="dateForm('join_date', '{{ old('join_date', $student->join_date?->format('Y-m-d') ?? '') }}')"
      x-init="init()">
    @csrf
    @method('PUT')

    {{-- full_name --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.full_name') }}</label>
        <input type="text" name="full_name" value="{{ old('full_name', $student->full_name) }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="{{ __('admin.student_full_name_placeholder') }}">
        @error('full_name')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- phone --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.phone') }}</label>
        <input type="tel" name="phone" value="{{ old('phone', $student->phone) }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="09123456789">
        @error('phone')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- parent_phone --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.parent_phone') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <input type="tel" name="parent_phone" value="{{ old('parent_phone', $student->parent_phone) }}"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="{{ __('admin.optional') }}">
    </div>

    {{-- status --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.status') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <select name="status"
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            @php
                $currentStatus = old('status') ?: ($student->status instanceof \BackedEnum ? $student->status->value : (string) $student->status);
            @endphp
            @foreach (['active', 'paused', 'inactive', 'graduated'] as $statusOpt)
                <option value="{{ $statusOpt }}" {{ $currentStatus === $statusOpt ? 'selected' : '' }}>
                    {{ __('admin.statuses.' . $statusOpt) }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- join_date --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.join_date') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <input type="hidden" name="join_date" :value="isoValue">
        <div class="grid grid-cols-3 gap-2">
            <div>
                <label class="mb-1 block text-xs text-gray-500">سال</label>
                <input type="number" x-model="year" @input="onDateChange()" @blur="padYear()"
                       min="2010" max="2099"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="2024">
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-500">ماه</label>
                <input type="number" x-model="month" @input="onDateChange()"
                       min="1" max="12"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="1">
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-500">روز</label>
                <input type="number" x-model="day" @input="onDateChange()"
                       min="1" max="31"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="1">
            </div>
        </div>
        <p class="mt-1.5 text-xs text-gray-500">
            {{ __('admin.jalali_equivalent') }}:
            <span class="text-amber-400" x-text="jalali || '—'"></span>
        </p>
        @error('join_date')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- notes --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.notes') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <textarea name="notes" rows="3"
                  class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                  placeholder="{{ __('admin.optional_notes') }}">{{ old('notes', $student->notes) }}</textarea>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
            {{ __('admin.update_student') }}
        </button>
        <a href="{{ route('admin.students.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            {{ __('admin.cancel') }}
        </a>
    </div>
</form>

@include('admin.partials.date-form-script')

@endsection
