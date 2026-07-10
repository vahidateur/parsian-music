@extends('layouts.dashboard')
@section('breadcrumb')تنظیمات@endsection

@section('content')
@php
    $inputClass = "block w-full rounded-lg border border-gray-700/60 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition duration-200 focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 disabled:cursor-not-allowed disabled:opacity-50";
    $labelClass = "mb-1.5 block text-sm font-medium text-gray-300";
    $hintClass  = "mt-1 text-xs text-gray-500";
    $divider    = "border-t border-gray-800/60 pt-5 mt-5";

    $groupLabels = ['core' => 'هسته سیستم', 'infra' => 'زیرساخت', 'comms' => 'ارتباطات', 'finance' => 'مالی', 'system' => 'سیستم'];

    $sectionView  = 'admin.settings.sections.' . $section;
    $hasPartial   = view()->exists($sectionView);
@endphp

{{-- Back link --}}
<a href="{{ route('admin.settings.index') }}"
   class="mb-5 inline-flex items-center gap-1.5 text-sm text-gray-400 transition duration-150 hover:text-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/40">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
    </svg>
    بازگشت به تنظیمات
</a>

{{-- Page Header --}}
<x-dashboard.section-header :title="$meta['title']" :subtitle="$meta['desc']">
    <x-slot:actions>
        @if ($meta['coming_soon'])
            <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-400">به‌زودی</span>
        @endif
    </x-slot:actions>
</x-dashboard.section-header>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-4">

    {{-- ── Sidebar Nav ──────────────────────────────────────────────────── --}}
    <aside class="lg:col-span-1">
        <x-dashboard.chart-container title="بخش‌های تنظیمات">
            @foreach (collect($catalogue)->groupBy('group') as $groupKey => $items)
                <p class="mb-1 mt-3 px-1 text-[10px] font-semibold uppercase tracking-widest text-gray-600 first:mt-0">
                    {{ $groupLabels[$groupKey] ?? $groupKey }}
                </p>
                <nav class="space-y-0.5">
                    @foreach ($items as $key => $item)
                        <a href="{{ route('admin.settings.show', $key) }}"
                           class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40
                               {{ $key === $section
                                    ? 'bg-amber-500/10 font-medium text-amber-300'
                                    : 'text-gray-400 hover:bg-gray-800/50 hover:text-gray-200' }}"
                           aria-current="{{ $key === $section ? 'page' : 'false' }}">
                            <svg class="h-4 w-4 shrink-0 {{ $key === $section ? 'text-amber-400' : 'text-gray-600' }}"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                            </svg>
                            <span class="truncate">{{ $item['title'] }}</span>
                            @if ($item['coming_soon'])
                                <span class="ms-auto rounded-full bg-gray-800 px-1.5 py-0.5 text-[9px] font-medium text-gray-500">soon</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            @endforeach
        </x-dashboard.chart-container>
    </aside>

    {{-- ── Main Content ─────────────────────────────────────────────────── --}}
    <div class="space-y-5 lg:col-span-3">

        @if ($meta['coming_soon'])
            <x-dashboard.alert-card
                title="این بخش در حال توسعه است"
                message="قابلیت‌های این بخش به‌زودی در نسخه‌های بعدی ارائه می‌شود."
                priority="mid"
            />
        @endif

        {{-- Render the section partial, or fall back to the generic coming-soon view --}}
        @if ($hasPartial)
            @include($sectionView, compact('inputClass', 'labelClass', 'hintClass', 'divider', 'meta', 'section'))
        @else
            @include('admin.settings.sections._coming_soon', compact('meta', 'divider'))
        @endif

        {{-- Save button — only for implemented sections that don't embed their own form --}}
        @php $selfContainedSections = ['institute', 'rooms']; @endphp
        @if (!$meta['coming_soon'] && !in_array($section, $selfContainedSections))
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button"
                        class="rounded-lg px-4 py-2.5 text-sm text-gray-500 transition duration-150 hover:text-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/30">
                    بازنشانی
                </button>
                <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-5 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition duration-200 hover:from-amber-500 hover:to-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-950">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    ذخیره تغییرات
                </button>
            </div>
        @endif

    </div>
</div>

@endsection
