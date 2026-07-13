@extends('layouts.dashboard')
@section('breadcrumb')تنظیمات@endsection

@section('content')
@php
    $colorMap = [
        'amber'   => ['card' => 'border-amber-500/20 hover:border-amber-500/40 hover:shadow-amber-500/10',  'icon' => 'bg-amber-500/10 text-amber-400 ring-amber-500/20',   'badge' => 'bg-amber-500/10 text-amber-400', 'glow' => 'bg-amber-500/10'],
        'sky'     => ['card' => 'border-sky-500/20 hover:border-sky-500/40 hover:shadow-sky-500/10',        'icon' => 'bg-sky-500/10 text-sky-400 ring-sky-500/20',         'badge' => 'bg-sky-500/10 text-sky-400', 'glow' => 'bg-sky-500/10'],
        'violet'  => ['card' => 'border-violet-500/20 hover:border-violet-500/40 hover:shadow-violet-500/10','icon' => 'bg-violet-500/10 text-violet-400 ring-violet-500/20','badge' => 'bg-violet-500/10 text-violet-400', 'glow' => 'bg-violet-500/10'],
        'emerald' => ['card' => 'border-emerald-500/20 hover:border-emerald-500/40 hover:shadow-emerald-500/10','icon' => 'bg-emerald-500/10 text-emerald-400 ring-emerald-500/20','badge' => 'bg-emerald-500/10 text-emerald-400', 'glow' => 'bg-emerald-500/10'],
        'orange'  => ['card' => 'border-orange-500/20 hover:border-orange-500/40 hover:shadow-orange-500/10','icon' => 'bg-orange-500/10 text-orange-400 ring-orange-500/20','badge' => 'bg-orange-500/10 text-orange-400', 'glow' => 'bg-orange-500/10'],
        'rose'    => ['card' => 'border-rose-500/20 hover:border-rose-500/40 hover:shadow-rose-500/10',     'icon' => 'bg-rose-500/10 text-rose-400 ring-rose-500/20',       'badge' => 'bg-rose-500/10 text-rose-400', 'glow' => 'bg-rose-500/10'],
        'gray'    => ['card' => 'border-gray-600/30 hover:border-gray-500/50 hover:shadow-gray-500/10',     'icon' => 'bg-gray-700/40 text-gray-400 ring-gray-600/30',       'badge' => 'bg-gray-700/40 text-gray-400', 'glow' => 'bg-gray-500/10'],
    ];

    $groupLabels = ['core' => 'هسته سیستم', 'infra' => 'زیرساخت', 'comms' => 'ارتباطات', 'finance' => 'مالی', 'system' => 'سیستم'];

    $groups = [];
    foreach ($catalogue as $sectionKey => $item) {
        $groups[$item['group']][$sectionKey] = $item;
    }
@endphp

<x-dashboard.section-header
    title="تنظیمات"
    subtitle="پیکربندی و مدیریت تمام جنبه‌های آموزشگاه"
    :badge="count($catalogue) . ' بخش'"
/>

<x-settings.shell :catalogue="$catalogue">
    @foreach ($groups as $groupKey => $items)
        <div class="mb-3 flex items-center gap-3">
            <span class="text-xs font-semibold uppercase tracking-widest text-gray-600">{{ $groupLabels[$groupKey] ?? $groupKey }}</span>
            <div class="h-px flex-1 bg-gray-800/60"></div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($items as $key => $meta)
                @php $palette = $colorMap[$meta['color']] ?? $colorMap['gray']; @endphp
                <a href="{{ route('admin.settings.show', $key) }}"
                   class="group relative flex flex-col overflow-hidden rounded-2xl border bg-gray-900/50 p-5 shadow-lg shadow-black/10 backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl {{ $palette['card'] }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40"
                   aria-label="{{ $meta['title'] }}: {{ $meta['desc'] }}">

                    <div class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full opacity-0 blur-2xl transition-opacity duration-500 group-hover:opacity-100 {{ $palette['glow'] }}" aria-hidden="true"></div>

                    <div class="relative flex items-start justify-between">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl ring-1 {{ $palette['icon'] }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}"/>
                            </svg>
                        </div>
                        @if ($meta['coming_soon'])
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $palette['badge'] }}">به‌زودی</span>
                        @else
                            <svg class="h-4 w-4 text-gray-700 transition duration-200 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        @endif
                    </div>

                    <div class="relative mt-4">
                        <p class="font-semibold text-gray-100">{{ $meta['title'] }}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-gray-500">{{ $meta['desc'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endforeach
</x-settings.shell>

@endsection
