@extends('layouts.admin')

@section('title', 'اتاق‌های کلاس')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-900 to-slate-950 p-8">
    <div class="mx-auto max-w-6xl">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white">اتاق‌های کلاس</h1>
                <p class="mt-2 text-slate-400">مدیریت اتاق‌های آموزشی</p>
            </div>
            <a href="{{ route('admin.rooms.create') }}" 
               class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-2.5 font-semibold text-gray-950 shadow-lg transition hover:from-amber-500 hover:to-amber-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                اتاق جدید
            </a>
        </div>

        <!-- Messages -->
        @if($message = session('success'))
            <div class="mb-6 flex items-center gap-3 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="text-green-200">{{ $message }}</span>
            </div>
        @endif

        <!-- Table -->
        <div class="rounded-lg border border-slate-700 bg-slate-800/50 backdrop-blur-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-700 bg-slate-700/30">
                            <th class="px-6 py-3 text-right text-sm font-semibold text-slate-300">نام اتاق</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-slate-300">ظرفیت</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-slate-300">وضعیت</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-slate-300">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($rooms as $room)
                            <tr class="hover:bg-slate-700/20 transition">
                                <td class="px-6 py-4 text-sm text-white font-medium">{{ $room->name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-300">{{ $room->capacity ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    @if($room->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-500/20 px-2.5 py-0.5 text-xs font-medium text-green-300">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-400"></span>
                                            فعال
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-500/20 px-2.5 py-0.5 text-xs font-medium text-slate-300">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            غیرفعال
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.rooms.edit', $room) }}"
                                           class="text-amber-400 hover:text-amber-300 transition">
                                            ویرایش
                                        </a>
                                        <form action="{{ route('admin.rooms.destroy', $room) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    onclick="return confirm('آیا مطمئن هستید؟')"
                                                    class="text-red-400 hover:text-red-300 transition">
                                                حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                                    هیچ اتاقی ثبت نشده است
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($rooms->hasPages())
            <div class="mt-6">
                {{ $rooms->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
