@extends('layouts.dashboard')
@section('breadcrumb')تنظیمات@endsection

@section('content')
@php
    $inputClass = "block w-full rounded-lg border border-gray-700/60 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 placeholder-gray-500 transition duration-200 focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 disabled:cursor-not-allowed disabled:opacity-50";
    $labelClass = "mb-1.5 block text-sm font-medium text-gray-300";
    $hintClass  = "mt-1 text-xs text-gray-500";
    $divider    = "border-t border-gray-800/60 pt-5 mt-5";

    $sectionView  = 'admin.settings.sections.' . $section;
    $hasPartial   = view()->exists($sectionView);

    $selfContainedSections = ['institute', 'rooms'];
    $isSelfContained       = in_array($section, $selfContainedSections);
    $isActionable          = ! $meta['coming_soon'] && ! $isSelfContained;
@endphp

<x-dashboard.section-header headingLevel="h1" :title="$meta['title']" :subtitle="$meta['desc']">
    <x-slot:actions>
        @if ($meta['coming_soon'])
            <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-400 ring-1 ring-amber-500/20">به‌زودی</span>
        @endif
    </x-slot:actions>
</x-dashboard.section-header>

@if (session('success'))
    <x-dashboard.alert-card priority="success" :message="session('success')" class="mb-4" />
@endif
@if ($errors->any())
    <x-dashboard.alert-card priority="high" :message="$errors->first()" class="mb-4" />
@endif

<x-settings.shell :catalogue="$catalogue" :section="$section">
    @if ($meta['coming_soon'])
        <x-dashboard.alert-card
            title="این بخش هنوز پیاده‌سازی نشده"
            message="قابلیت‌های این بخش در نسخه‌های بعدی ارائه می‌شود. دکمه ذخیره‌ای برای این بخش وجود ندارد."
            priority="mid" />
    @endif

    @if ($isActionable)
    <form method="POST"
          action="{{ route('admin.settings.update', $section) }}"
          enctype="multipart/form-data"
          novalidate>
        @csrf
        @method('PUT')
    @endif

        @if ($hasPartial)
            @include($sectionView, compact('inputClass', 'labelClass', 'hintClass', 'divider', 'meta', 'section', 'settings'))
        @else
            @include('admin.settings.sections._coming_soon', compact('meta', 'divider'))
        @endif

        @if ($isActionable)
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.settings.show', $section) }}"
               class="rounded-lg px-4 py-2.5 text-sm text-gray-500 transition duration-150 hover:text-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500/30">
                بازنشانی
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-500 px-5 py-2.5 text-sm font-semibold text-gray-950 shadow-lg shadow-amber-500/10 transition duration-200 hover:from-amber-500 hover:to-amber-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-950">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                ذخیره تغییرات
            </button>
        </div>
        @endif

    @if ($isActionable)
    </form>
    @endif
</x-settings.shell>

@endsection
