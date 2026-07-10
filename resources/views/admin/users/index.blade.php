@extends('layouts.dashboard')

@section('title', 'مدیریت کاربران')

@section('content')
@php
    $roles = \App\Enums\RoleEnum::cases();
    $actor = auth()->user();

    $roleColor = [
        'super_admin' => 'bg-purple-500/15 text-purple-300 ring-purple-500/30',
        'admin'       => 'bg-amber-500/15 text-amber-300 ring-amber-500/30',
        'teacher'     => 'bg-blue-500/15 text-blue-300 ring-blue-500/30',
        'student'     => 'bg-green-500/15 text-green-300 ring-green-500/30',
    ];
    $statusColor = [
        true  => 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30',
        false => 'bg-red-500/15 text-red-300 ring-red-500/30',
    ];
@endphp

<x-dashboard.section-header
    title="مدیریت کاربران"
    subtitle="{{ $users->total() }} کاربر در سیستم">
    <x-slot name="actions">
        @if($actor->role->canManage(\App\Enums\RoleEnum::ADMIN))
        <a href="{{ route('admin.users.create') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-gray-900 transition hover:bg-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            کاربر جدید
        </a>
        @endif
    </x-slot>
</x-dashboard.section-header>

@if(session('success'))
    <x-dashboard.alert-card priority="success" :message="session('success')" class="mt-4" />
@endif

@if(session('error'))
    <x-dashboard.alert-card priority="high" :message="session('error')" class="mt-4" />
@endif

{{-- Temp password flash --}}
@if(session('temp_password'))
<div class="mt-4 flex items-start gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 px-5 py-4">
    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
    <div>
        <p class="text-sm font-semibold text-amber-300">رمز موقت تنظیم شد</p>
        <p class="mt-1 text-xs text-gray-400">این رمز را به کاربر اطلاع دهید. پس از بستن صفحه قابل بازیابی نیست.</p>
        <code class="mt-2 inline-block rounded-lg bg-gray-900 px-4 py-2 text-sm font-mono font-bold tracking-widest text-amber-300">{{ session('temp_password') }}</code>
    </div>
</div>
@endif

{{-- Filters --}}
<x-dashboard.chart-container title="فیلتر" class="mt-5">
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="جستجوی نام، تلفن یا ایمیل..."
               class="flex-1 min-w-[200px] rounded-xl border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-500/50 focus:outline-none focus:ring-1 focus:ring-amber-500/30">

        <select name="role" class="rounded-xl border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm text-gray-100 focus:border-amber-500/50 focus:outline-none focus:ring-1 focus:ring-amber-500/30">
            <option value="">همه نقش‌ها</option>
            @foreach($roles as $role)
                <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>

        <select name="status" class="rounded-xl border border-gray-700/60 bg-gray-800/40 px-4 py-2.5 text-sm text-gray-100 focus:border-amber-500/50 focus:outline-none focus:ring-1 focus:ring-amber-500/30">
            <option value="">همه وضعیت‌ها</option>
            <option value="active" @selected(request('status') === 'active')>فعال</option>
            <option value="inactive" @selected(request('status') === 'inactive')>غیرفعال</option>
        </select>

        <button type="submit"
                class="rounded-xl bg-gray-700/60 px-5 py-2.5 text-sm font-medium text-gray-200 transition hover:bg-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500">
            اعمال
        </button>
        <a href="{{ route('admin.users.index') }}"
           class="rounded-xl px-4 py-2.5 text-sm text-gray-400 transition hover:text-gray-200">
            پاک‌سازی
        </a>
    </form>
</x-dashboard.chart-container>

{{-- Table --}}
<x-dashboard.chart-container title="لیست کاربران" :badge="$users->total() . ' کاربر'" class="mt-5">
    @if($users->isEmpty())
        <x-dashboard.empty-state
            title="کاربری یافت نشد"
            description="هیچ کاربری با فیلترهای انتخابی وجود ندارد." />
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm" role="table" aria-label="لیست کاربران">
            <thead>
                <tr class="border-b border-gray-800/60 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="px-4 py-3" scope="col">کاربر</th>
                    <th class="px-4 py-3" scope="col">نقش</th>
                    <th class="px-4 py-3" scope="col">وضعیت</th>
                    <th class="px-4 py-3" scope="col">آخرین ورود</th>
                    <th class="px-4 py-3" scope="col">ایجادکننده</th>
                    <th class="px-4 py-3 text-center" scope="col">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/40">
                @foreach($users as $user)
                <tr class="group transition duration-150 hover:bg-gray-800/25">
                    {{-- User --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gray-700/60 text-sm font-bold text-amber-300">
                                {{ mb_substr($user->full_name, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-100">{{ $user->full_name }}</p>
                                <p class="text-xs text-gray-500">{{ $user->phone }}</p>
                                @if($user->email)
                                    <p class="text-xs text-gray-600">{{ $user->email }}</p>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Role --}}
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 {{ $roleColor[$user->role->value] ?? 'bg-gray-500/15 text-gray-300 ring-gray-500/30' }}">
                            {{ $user->role->label() }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusColor[$user->is_active] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $user->is_active ? 'bg-emerald-400' : 'bg-red-400' }}"></span>
                            {{ $user->is_active ? 'فعال' : 'غیرفعال' }}
                        </span>
                    </td>

                    {{-- Last login --}}
                    <td class="px-4 py-3 text-xs text-gray-500 tabular-nums">
                        {{ $user->last_login_at ? \App\Helpers\Jalalian::fromCarbon($user->last_login_at, 'Y/m/d') : '—' }}
                    </td>

                    {{-- Created by --}}
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ $user->createdBy?->full_name ?? '—' }}
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3">
                        @if($actor->role->canManage($user->role))
                        <div class="flex items-center justify-center gap-1">
                            {{-- Edit --}}
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-700/50 hover:text-amber-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/50"
                               title="ویرایش">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                            </a>

                            {{-- Toggle active --}}
                            @if($user->id !== $actor->id)
                            <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="rounded-lg p-1.5 transition hover:bg-gray-700/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/40 {{ $user->is_active ? 'text-emerald-400 hover:text-red-400' : 'text-red-400 hover:text-emerald-400' }}"
                                        title="{{ $user->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $user->is_active ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' : 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }}"/></svg>
                                </button>
                            </form>

                            {{-- Reset password --}}
                            <form method="POST" action="{{ route('admin.users.resetPassword', $user) }}" class="inline"
                                  onsubmit="return confirm('رمز کاربر {{ $user->full_name }} ریست می‌شود. ادامه می‌دهید؟')">
                                @csrf
                                <button type="submit"
                                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-700/50 hover:text-amber-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/40"
                                        title="ریست رمز">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                                </button>
                            </form>

                            {{-- Delete --}}
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
                                  onsubmit="return confirm('کاربر {{ $user->full_name }} حذف خواهد شد. مطمئنید؟')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-700/50 hover:text-red-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40"
                                        title="حذف">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                        @else
                        <span class="block text-center text-xs text-gray-700">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="mt-4 border-t border-gray-800/40 px-4 pt-4">
        {{ $users->withQueryString()->links() }}
    </div>
    @endif
    @endif
</x-dashboard.chart-container>
@endsection
