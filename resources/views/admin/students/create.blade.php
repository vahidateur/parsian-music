@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-semibold text-amber-100">{{ __('admin.create_student') }}</h1>
    <p class="mt-1 text-sm text-gray-500">{{ __('admin.register_student_desc') }}</p>
</div>

{{-- Feedback_Channel + Error_State recovery path --}}
<x-admin.feedback :returnUrl="route('admin.students.index')" />

{{-- Loading_State owner of this Record_Form --}}
<x-admin.form-state>
<form method="POST" action="{{ route('admin.students.store') }}" class="max-w-2xl space-y-5"
      x-data="adminDateForm('join_date', '{{ old('join_date', '') }}')">
    @csrf

    {{-- full_name --}}
    <div>
        <label for="full_name" class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.full_name') }}</label>
        <input id="full_name" type="text" name="full_name" {{ feedback_field_attributes('full_name') }} value="{{ old('full_name') }}" required
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="{{ __('admin.student_full_name_placeholder') }}">
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

    {{-- parent_phone --}}
    <div>
        <label for="parent_phone" class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.parent_phone') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <input id="parent_phone" type="tel" name="parent_phone" {{ feedback_field_attributes('parent_phone') }} value="{{ old('parent_phone') }}"
               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
               placeholder="{{ __('admin.optional') }}">
        <x-admin.feedback field="parent_phone" />
    </div>

    {{-- status --}}
    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.status') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <select id="status" name="status"
                class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            @foreach (['active', 'paused', 'inactive', 'graduated'] as $statusOpt)
                <option value="{{ $statusOpt }}" {{ old('status', 'active') === $statusOpt ? 'selected' : '' }}>
                    {{ __('admin.statuses.' . $statusOpt) }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- join_date — split Y/M/D inputs to prevent 5-digit year --}}
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.join_date') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        {{-- Hidden input carries the final value to the server --}}
        <input type="hidden" name="join_date" {{ feedback_field_attributes('join_date') }} :value="isoValue">
        <div class="grid grid-cols-3 gap-2">
            <div>
                <label for="join_date_year" class="mb-1 block text-xs text-gray-500">سال</label>
                <input id="join_date_year" type="number" x-model="year" @input="onDateChange()" @blur="padYear()"
                       min="2010" max="2099" maxlength="4"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="2024">
            </div>
            <div>
                <label for="join_date_month" class="mb-1 block text-xs text-gray-500">ماه</label>
                <input id="join_date_month" type="number" x-model="month" @input="onDateChange()"
                       min="1" max="12" maxlength="2"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="1">
            </div>
            <div>
                <label for="join_date_day" class="mb-1 block text-xs text-gray-500">روز</label>
                <input id="join_date_day" type="number" x-model="day" @input="onDateChange()" :max="daysInMonth()"
                       min="1" max="31" maxlength="2"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                       placeholder="1">
            </div>
        </div>
        <p class="mt-1.5 text-xs text-gray-500">
            {{ __('admin.jalali_equivalent') }}:
            <span class="text-amber-400" x-text="jalali || '—'"></span>
        </p>
        <x-admin.feedback field="join_date" />
    </div>

    {{-- notes --}}
    <div>
        <label for="notes" class="mb-1.5 block text-sm font-medium text-gray-300">
            {{ __('admin.notes') }}
            <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
        </label>
        <textarea id="notes" name="notes" rows="3"
                  class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                  placeholder="{{ __('admin.optional_notes') }}">{{ old('notes') }}</textarea>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-4 pt-2">
        <x-admin.submit-button :label="__('admin.create_student')" />
        <a href="{{ route('admin.students.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            {{ __('admin.cancel') }}
        </a>
    </div>
</form>
</x-admin.form-state>

@endsection
