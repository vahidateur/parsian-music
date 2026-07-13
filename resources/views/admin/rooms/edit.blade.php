@extends('layouts.admin')

@section('title', 'ویرایش اتاق')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-900 to-slate-950 p-8">
    <div class="mx-auto max-w-2xl">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('admin.rooms.index') }}" class="text-amber-400 hover:text-amber-300 transition flex items-center gap-1 mb-4">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                بازگشت
            </a>
            <h1 class="text-3xl font-bold text-white">ویرایش اتاق</h1>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.rooms.update', $room) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-lg border border-slate-700 bg-slate-800/50 backdrop-blur-sm p-6">
                <div class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-300 mb-2">نام اتاق</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $room->name) }}"
                               class="w-full rounded-lg border border-slate-600 bg-slate-700/50 px-4 py-2.5 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none transition"
                               placeholder="مثلاً: اتاق ۱" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Capacity -->
                    <div>
                        <label for="capacity" class="block text-sm font-medium text-slate-300 mb-2">ظرفیت</label>
                        <input type="number" name="capacity" id="capacity" value="{{ old('capacity', $room->capacity) }}"
                               class="w-full rounded-lg border border-slate-600 bg-slate-700/50 px-4 py-2.5 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none transition"
                               placeholder="مثلاً: 30">
                        @error('capacity')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3 pt-4">
                        <button type="submit" 
                                class="flex-1 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-4 py-2.5 font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
                            ذخیره تغییرات
                        </button>
                        <a href="{{ route('admin.rooms.index') }}"
                           class="flex-1 rounded-lg border border-slate-600 bg-slate-700/50 px-4 py-2.5 font-semibold text-slate-300 text-center transition hover:bg-slate-700">
                            لغو
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
