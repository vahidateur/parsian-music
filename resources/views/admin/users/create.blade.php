@extends('layouts.dashboard')

@section('title', 'ایجاد کاربر جدید')

@section('content')
{{-- $assignableRoles comes from the controller: no role comparison in the view. --}}

<x-dashboard.section-header headingLevel="h1" title="ایجاد کاربر جدید" subtitle="حساب کاربری جدید در سیستم">
    <x-slot name="actions">
        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-700/60 px-4 py-2 text-sm text-gray-300 transition hover:border-gray-600 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
            بازگشت
        </a>
    </x-slot>
</x-dashboard.section-header>

{{-- Feedback_Channel: shared success / failure feedback; field errors render per field --}}
<x-admin.feedback :validation="false" />

<x-dashboard.chart-container title="اطلاعات کاربر" class="mt-5">
    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
        @csrf

        @php
            $inputClass = 'w-full rounded-xl border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-500/50 focus:outline-none focus:ring-1 focus:ring-amber-500/30';
            $labelClass = 'block text-xs font-semibold text-gray-400 mb-1.5';
        @endphp

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="full_name" class="{{ $labelClass }}">نام کامل <span class="text-red-400">*</span></label>
                <input id="full_name" type="text" name="full_name" {{ feedback_field_attributes('full_name') }} value="{{ old('full_name') }}"
                       class="{{ $inputClass }} @error('full_name') border-red-500/50 @enderror"
                       placeholder="نام و نام خانوادگی" required>
                <x-admin.feedback field="full_name" />
            </div>

            <div>
                <label for="phone" class="{{ $labelClass }}">تلفن <span class="text-red-400">*</span></label>
                <input id="phone" type="text" name="phone" {{ feedback_field_attributes('phone') }} value="{{ old('phone') }}"
                       class="{{ $inputClass }} @error('phone') border-red-500/50 @enderror"
                       placeholder="09xxxxxxxxx" required>
                <x-admin.feedback field="phone" />
            </div>

            <div>
                <label for="email" class="{{ $labelClass }}">ایمیل</label>
                <input id="email" type="email" name="email" {{ feedback_field_attributes('email') }} value="{{ old('email') }}"
                       class="{{ $inputClass }} @error('email') border-red-500/50 @enderror"
                       placeholder="example@email.com">
                <x-admin.feedback field="email" />
            </div>

            <div>
                <label for="role" class="{{ $labelClass }}">نقش <span class="text-red-400">*</span></label>
                <select id="role" name="role" {{ feedback_field_attributes('role') }} class="{{ $inputClass }} @error('role') border-red-500/50 @enderror" required>
                    <option value="">انتخاب نقش...</option>
                    @foreach($assignableRoles as $role)
                        <option value="{{ $role->value }}" @selected(old('role') === $role->value)>
                            {{ $role->label() }}
                        </option>
                    @endforeach
                </select>
                <x-admin.feedback field="role" />
            </div>

            <div>
                <label for="password" class="{{ $labelClass }}">رمز عبور <span class="text-red-400">*</span></label>
                <input id="password" type="password" name="password" {{ feedback_field_attributes('password') }}
                       class="{{ $inputClass }} @error('password') border-red-500/50 @enderror"
                       placeholder="حداقل ۸ کاراکتر" required>
                <x-admin.feedback field="password" />
            </div>

            <div>
                <label for="password_confirmation" class="{{ $labelClass }}">تکرار رمز عبور <span class="text-red-400">*</span></label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="{{ $inputClass }}"
                       placeholder="تکرار رمز عبور" required>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-800/40 pt-5">
            <a href="{{ route('admin.users.index') }}"
               class="rounded-xl px-5 py-2.5 text-sm text-gray-400 transition hover:text-gray-200">انصراف</a>
            <button type="submit"
                    class="rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                ایجاد کاربر
            </button>
        </div>
    </form>
</x-dashboard.chart-container>
@endsection
