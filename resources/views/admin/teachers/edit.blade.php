@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.teachers') }} / {{ __('admin.edit_teacher') }}@endsection
@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.edit_teacher') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.update_teacher_desc') }}</p>
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

<form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" class="max-w-2xl space-y-5"
      x-data="dateForm('hire_date', '{{ old('hire_date', $teacher->hire_date?->format('Y-m-d') ?? '') }}')"
      x-init="init()">
    @csrf
    @method('PUT')

    {{-- full_name --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.full_name') }}</label>
        <input type="text" name="full_name" value="{{ old('full_name', $teacher->full_name) }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="{{ __('admin.teacher_full_name_placeholder') }}">
        @error('full_name')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- phone --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.phone') }}</label>
        <input type="tel" name="phone" value="{{ old('phone', $teacher->phone) }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="09123456789">
        @error('phone')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- status --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.status') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <select name="status"
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            @php $current = $teacher->status instanceof \BackedEnum ? $teacher->status->value : (string) $teacher->status; @endphp
            @foreach (['active', 'inactive'] as $s)
                <option value="{{ $s }}" {{ old('status', $current) === $s ? 'selected' : '' }}>
                    {{ __('admin.statuses.'.$s) }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- hire_date --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.hire_date') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <input type="hidden" name="hire_date" :value="isoValue">
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
        @error('hire_date')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- bio --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.bio') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <textarea name="bio" rows="3"
                  class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                  placeholder="{{ __('admin.optional_notes') }}">{{ old('bio', $teacher->bio) }}</textarea>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
            {{ __('admin.update_teacher') }}
        </button>
        <a href="{{ route('admin.teachers.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            {{ __('admin.cancel') }}
        </a>
    </div>
</form>

@include('admin.partials.date-form-script')

@endsection
