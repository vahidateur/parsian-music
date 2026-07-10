@extends('layouts.dashboard')

@section('title', 'ویرایش کاربر')

@section('content')
@php
    $actor = auth()->user();
    $assignableRoles = collect(\App\Enums\RoleEnum::cases())
        ->filter(fn ($r) => $actor->role->canManage($r));
@endphp

<x-dashboard.section-header title="ویرایش کاربر" subtitle="{{ $user->full_name }}">
    <x-slot name="actions">
        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-700/60 px-4 py-2 text-sm text-gray-300 transition hover:border-gray-600 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
            بازگشت
        </a>
    </x-slot>
</x-dashboard.section-header>

@if(session('success'))
    <x-dashboard.alert-card priority="success" :message="session('success')" class="mt-4" />
@endif

<x-dashboard.chart-container title="اطلاعات کاربر" class="mt-5">
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
        @csrf @method('PUT')

        @php
            $inputClass = 'w-full rounded-xl border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-500/50 focus:outline-none focus:ring-1 focus:ring-amber-500/30';
            $labelClass = 'block text-xs font-semibold text-gray-400 mb-1.5';
            $errorClass = 'mt-1 text-xs text-red-400';
        @endphp

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label class="{{ $labelClass }}">نام کامل <span class="text-red-400">*</span></label>
                <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}"
                       class="{{ $inputClass }} @error('full_name') border-red-500/50 @enderror" required>
                @error('full_name')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">تلفن <span class="text-red-400">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                       class="{{ $inputClass }} @error('phone') border-red-500/50 @enderror" required>
                @error('phone')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">ایمیل</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                       class="{{ $inputClass }} @error('email') border-red-500/50 @enderror">
                @error('email')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">نقش <span class="text-red-400">*</span></label>
                <select name="role" class="{{ $inputClass }} @error('role') border-red-500/50 @enderror" required>
                    @foreach($assignableRoles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $user->role->value) === $role->value)>
                            {{ $role->label() }}
                        </option>
                    @endforeach
                </select>
                @error('role')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Status indicator --}}
        <div class="flex items-center gap-3 rounded-xl border border-gray-700/40 bg-gray-800/20 px-4 py-3">
            <span class="h-2 w-2 rounded-full {{ $user->is_active ? 'bg-emerald-400' : 'bg-red-400' }}"></span>
            <span class="text-sm text-gray-300">وضعیت: {{ $user->is_active ? 'فعال' : 'غیرفعال' }}</span>
            @if($actor->role->canManage($user->role) && $user->id !== $actor->id)
            <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="mr-auto">
                @csrf @method('PATCH')
                <button type="submit"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ $user->is_active ? 'bg-red-500/20 text-red-300 hover:bg-red-500/30' : 'bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/40">
                    {{ $user->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                </button>
            </form>
            @endif
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-800/40 pt-5">
            <a href="{{ route('admin.users.index') }}"
               class="rounded-xl px-5 py-2.5 text-sm text-gray-400 transition hover:text-gray-200">انصراف</a>
            <button type="submit"
                    class="rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                ذخیره تغییرات
            </button>
        </div>
    </form>
</x-dashboard.chart-container>
@endsection
