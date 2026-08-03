@extends('layouts.dashboard')

@section('title', 'ویرایش اتاق')

@section('content')
<div class="bg-gradient-to-b from-slate-900 to-slate-950 p-8">
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

        {{-- Feedback_Channel + Error_State recovery path; field errors render per field --}}
        <x-admin.feedback :validation="false" :returnUrl="route('admin.rooms.index')" />

        <!-- Form -->
        <x-admin.form-state>
        <form action="{{ route('admin.rooms.update', $room) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-lg border border-slate-700 bg-slate-800/50 backdrop-blur-sm p-6">
                <div class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-300 mb-2">نام اتاق</label>
                        <input type="text" name="name" {{ feedback_field_attributes('name') }} id="name" value="{{ old('name', $room->name) }}"
                               class="w-full rounded-lg border border-slate-600 bg-slate-700/50 px-4 py-2.5 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none transition"
                               placeholder="مثلاً: اتاق ۱" required>
                        <x-admin.feedback field="name" />
                    </div>

                    <!-- Capacity -->
                    <div>
                        <label for="capacity" class="block text-sm font-medium text-slate-300 mb-2">ظرفیت</label>
                        <input type="number" name="capacity" {{ feedback_field_attributes('capacity') }} id="capacity" value="{{ old('capacity', $room->capacity) }}"
                               class="w-full rounded-lg border border-slate-600 bg-slate-700/50 px-4 py-2.5 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none transition"
                               placeholder="مثلاً: 30">
                        <x-admin.feedback field="capacity" />
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3 pt-4">
                        <x-admin.submit-button
                            :label="__('admin.save')"
                            wrapper="flex-1"
                            class="w-full justify-center px-4 py-2.5" />
                        <a href="{{ route('admin.rooms.index') }}"
                           class="flex-1 rounded-lg border border-slate-600 bg-slate-700/50 px-4 py-2.5 font-semibold text-slate-300 text-center transition hover:bg-slate-700">
                            لغو
                        </a>
                    </div>
                </div>
            </div>
        </form>
        </x-admin.form-state>
    </div>
</div>
@endsection
