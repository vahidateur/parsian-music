@extends('layouts.dashboard')
@section('breadcrumb'){{ __('admin.teachers') }} / {{ __('admin.create_teacher') }}@endsection
@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.create_teacher') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.register_teacher_desc') }}</p>
</div>

{{-- Feedback_Channel + Error_State recovery path --}}
<x-admin.feedback :returnUrl="route('admin.teachers.index')" />

{{-- Loading_State owner of this Record_Form --}}
<x-admin.form-state>
<form method="POST" action="{{ route('admin.teachers.store') }}" class="max-w-2xl space-y-5"
      x-data="adminDateForm('hire_date', '{{ old('hire_date', '') }}')">
    @csrf

    {{-- full_name --}}
    <div>
        <label for="full_name" class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.full_name') }}</label>
        <input id="full_name" type="text" name="full_name" {{ feedback_field_attributes('full_name') }} value="{{ old('full_name') }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="{{ __('admin.teacher_full_name_placeholder') }}">
        <x-admin.feedback field="full_name" />
    </div>

    {{-- phone --}}
    <div>
        <label for="phone" class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.phone') }}</label>
        <input id="phone" type="tel" name="phone" {{ feedback_field_attributes('phone') }} value="{{ old('phone') }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="09123456789">
        <x-admin.feedback field="phone" />
    </div>

    {{-- status --}}
    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-300">
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <select id="status" name="status"
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            @foreach (['active', 'inactive'] as $s)
                <option value="{{ $s }}" {{ old('status', 'active') === $s ? 'selected' : '' }}>
                    {{ __('admin.statuses.'.$s) }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- hire_date --}}
    <div>
        <label for="hire_date" class="mb-1.5 block text-sm font-medium text-gray-300">
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <input id="hire_date" type="hidden" name="hire_date" {{ feedback_field_attributes('hire_date') }} :value="isoValue">
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
                <input type="number" x-model="day" @input="onDateChange()" :max="daysInMonth()"
                       min="1" max="31"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="1">
            </div>
        </div>
        <p class="mt-1.5 text-xs text-gray-500">
            {{ __('admin.jalali_equivalent') }}:
            <span class="text-amber-400" x-text="jalali || '—'"></span>
        </p>
        <x-admin.feedback field="hire_date" />
    </div>

    {{-- bio --}}
    <div>
        <label for="bio" class="mb-1.5 block text-sm font-medium text-gray-300">
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <textarea id="bio" name="bio" rows="3"
                  class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                  placeholder="{{ __('admin.optional_notes') }}">{{ old('bio') }}</textarea>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-4 pt-2">
        <x-admin.submit-button :label="__('admin.create_teacher')" />
        <a href="{{ route('admin.teachers.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            {{ __('admin.cancel') }}
        </a>
    </div>
</form>
</x-admin.form-state>

@endsection
