@extends('layouts.teacher')

@section('title', 'اعلان‌ها')

@section('content')

<x-dashboard.section-header
    title="اعلان‌ها"
    :subtitle="auth()->user()->unreadNotifications->count() . ' اعلان خوانده‌نشده'">
    <x-slot name="actions">
        @if(auth()->user()->unreadNotifications->count() > 0)
        <a href="{{ route('teacher.notifications', ['mark_read' => 'all']) }}"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-700/60 bg-gray-800/40 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800/70 hover:text-blue-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            علامت‌گذاری همه به عنوان خوانده‌شده
        </a>
        @endif
    </x-slot>
</x-dashboard.section-header>

<x-dashboard.chart-container title="اعلان‌های من" class="mt-5">
    @if($notifications->isEmpty())
        <x-dashboard.empty-state
            title="اعلانی وجود ندارد"
            description="هیچ اعلانی برای شما ثبت نشده است." />
    @else
    <ul class="divide-y divide-gray-800/40" role="list" aria-label="اعلان‌ها">
        @foreach($notifications as $notif)
        @php
            $isUnread = is_null($notif->read_at);
            $data     = $notif->data;
            $message  = $data['message'] ?? $data['body'] ?? $data['title'] ?? 'اعلان جدید';
            $title    = $data['title'] ?? null;
            $event    = $data['event'] ?? null;
        @endphp
        <li class="group flex items-start gap-4 py-4 transition hover:bg-gray-800/20"
            x-data>
            {{-- Unread dot --}}
            <div class="mt-1.5 flex-shrink-0">
                @if($isUnread)
                <span class="block h-2.5 w-2.5 rounded-full bg-blue-400 shadow-sm shadow-blue-500/50" aria-label="خوانده‌نشده"></span>
                @else
                <span class="block h-2.5 w-2.5 rounded-full bg-gray-700" aria-label="خوانده‌شده"></span>
                @endif
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                @if($title)
                <p class="text-sm font-semibold {{ $isUnread ? 'text-gray-100' : 'text-gray-400' }}">
                    {{ $title }}
                </p>
                @endif
                <p class="mt-0.5 text-sm {{ $isUnread ? 'text-gray-300' : 'text-gray-500' }}">
                    {{ $message }}
                </p>
                <p class="mt-1 text-xs text-gray-600 tabular-nums">
                    {{ $notif->created_at->diffForHumans() }}
                    @if($notif->read_at)
                    · خوانده شد {{ $notif->read_at->diffForHumans() }}
                    @endif
                </p>
            </div>

            {{-- Badge --}}
            <div class="flex-shrink-0">
                <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-semibold ring-1
                    {{ $isUnread ? 'bg-blue-500/15 text-blue-300 ring-blue-500/30' : 'bg-gray-700/30 text-gray-500 ring-gray-700/50' }}">
                    {{ $isUnread ? 'جدید' : 'خوانده‌شده' }}
                </span>
            </div>
        </li>
        @endforeach
    </ul>

    @if($notifications->hasPages())
    <div class="mt-4 border-t border-gray-800/40 px-4 pt-4">
        {{ $notifications->withQueryString()->links() }}
    </div>
    @endif
    @endif
</x-dashboard.chart-container>
@endsection
