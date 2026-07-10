@extends('layouts.dashboard')

@section('title', 'پروفایل من')

@section('content')
@php
    $user = auth()->user();
    $inputClass  = 'w-full rounded-xl border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-500/50 focus:outline-none focus:ring-1 focus:ring-amber-500/30';
    $labelClass  = 'block text-xs font-semibold text-gray-400 mb-1.5';
    $errorClass  = 'mt-1 text-xs text-red-400';
    $locales     = ['fa' => 'فارسی', 'en' => 'English'];
    $timezones   = ['Asia/Tehran' => 'تهران (UTC+3:30)', 'UTC' => 'UTC', 'Asia/Dubai' => 'دبی (UTC+4)'];
@endphp

<x-dashboard.section-header title="پروفایل من" subtitle="اطلاعات حساب کاربری شما">
    <x-slot name="actions">
        <span class="inline-flex items-center rounded-xl bg-{{ $user->role->color() }}-500/15 px-3 py-1 text-xs font-semibold text-{{ $user->role->color() }}-300 ring-1 ring-{{ $user->role->color() }}-500/30">
            {{ $user->role->label() }}
        </span>
    </x-slot>
</x-dashboard.section-header>

@if(session('status') === 'profile-updated')
    <x-dashboard.alert-card priority="success" message="پروفایل با موفقیت به‌روزرسانی شد." class="mt-4" />
@endif
@if(session('status') === 'password-updated')
    <x-dashboard.alert-card priority="success" message="رمز عبور با موفقیت تغییر کرد." class="mt-4" />
@endif

<div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">

    {{-- Avatar card --}}
    <div class="flex flex-col items-center gap-4 rounded-2xl border border-gray-800/60 bg-gray-900/50 p-6">
        <div class="relative">
            @if($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}"
                     class="h-24 w-24 rounded-full object-cover ring-2 ring-amber-500/30">
            @else
                <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gray-700/60 text-3xl font-bold text-amber-300">
                    {{ mb_substr($user->full_name, 0, 1) }}
                </div>
            @endif
        </div>
        <div class="text-center">
            <p class="font-semibold text-gray-100">{{ $user->full_name }}</p>
            <p class="text-xs text-gray-500">{{ $user->phone }}</p>
            @if($user->email)
                <p class="text-xs text-gray-600">{{ $user->email }}</p>
            @endif
        </div>

        {{-- Avatar upload --}}
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="w-full">
            @csrf @method('PATCH')
            <input type="hidden" name="_section" value="avatar">
            <label class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed border-gray-700/60 p-4 transition hover:border-amber-500/40">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                <span class="text-xs text-gray-500">بارگذاری تصویر</span>
                <input type="file" name="avatar" accept="image/*" class="hidden"
                       onchange="this.closest('form').submit()">
            </label>
            @error('avatar')<p class="{{ $errorClass }} text-center">{{ $message }}</p>@enderror
        </form>
    </div>

    {{-- Profile info --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Personal info --}}
        <x-dashboard.chart-container title="اطلاعات شخصی">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf @method('PATCH')
                <input type="hidden" name="_section" value="info">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">نام کامل <span class="text-red-400">*</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}"
                               class="{{ $inputClass }} @error('full_name') border-red-500/50 @enderror" required>
                        @error('full_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">تلفن</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                               class="{{ $inputClass }} @error('phone') border-red-500/50 @enderror">
                        @error('phone')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">ایمیل</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="{{ $inputClass }} @error('email') border-red-500/50 @enderror">
                        @error('email')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">زبان رابط</label>
                        <select name="locale" class="{{ $inputClass }}">
                            @foreach($locales as $code => $label)
                                <option value="{{ $code }}" @selected(old('locale', $user->locale ?? 'fa') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">منطقه زمانی</label>
                        <select name="timezone" class="{{ $inputClass }}">
                            @foreach($timezones as $tz => $label)
                                <option value="{{ $tz }}" @selected(old('timezone', $user->timezone ?? 'Asia/Tehran') === $tz)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                        ذخیره اطلاعات
                    </button>
                </div>
            </form>
        </x-dashboard.chart-container>

        {{-- Change password --}}
        <x-dashboard.chart-container title="تغییر رمز عبور">
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">رمز عبور فعلی <span class="text-red-400">*</span></label>
                        <input type="password" name="current_password"
                               class="{{ $inputClass }} @error('current_password', 'updatePassword') border-red-500/50 @enderror"
                               autocomplete="current-password">
                        @error('current_password', 'updatePassword')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">رمز عبور جدید <span class="text-red-400">*</span></label>
                        <input type="password" name="password"
                               class="{{ $inputClass }} @error('password', 'updatePassword') border-red-500/50 @enderror"
                               autocomplete="new-password">
                        @error('password', 'updatePassword')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">تکرار رمز جدید <span class="text-red-400">*</span></label>
                        <input type="password" name="password_confirmation"
                               class="{{ $inputClass }}"
                               autocomplete="new-password">
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="rounded-xl bg-gray-700/60 px-6 py-2.5 text-sm font-semibold text-gray-200 transition hover:bg-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500">
                        تغییر رمز
                    </button>
                </div>
            </form>
        </x-dashboard.chart-container>

        {{-- Danger zone --}}
        <x-dashboard.chart-container title="منطقه خطر">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-200">حذف حساب کاربری</p>
                    <p class="text-xs text-gray-500 mt-1">این عمل غیرقابل بازگشت است.</p>
                </div>
                <button type="button"
                        x-data
                        @click="$dispatch('open-modal', 'confirm-user-deletion')"
                        class="rounded-xl border border-red-500/30 px-4 py-2 text-sm text-red-400 transition hover:bg-red-500/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40">
                    حذف حساب
                </button>
            </div>
        </x-dashboard.chart-container>

    </div>
</div>
@endsection
