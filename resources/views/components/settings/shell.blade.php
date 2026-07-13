@props([
    'catalogue',
    'section' => null,
])

@php
    $groupLabels = ['core' => 'هسته سیستم', 'infra' => 'زیرساخت', 'comms' => 'ارتباطات', 'finance' => 'مالی', 'system' => 'سیستم'];

    // groupBy() re-indexes keys to 0,1,2… — preserve section slugs (general, email, …)
    $grouped = [];
    foreach ($catalogue as $sectionKey => $item) {
        $grouped[$item['group']][$sectionKey] = $item;
    }
@endphp

<div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-4">

    <aside class="lg:col-span-1">
        <x-dashboard.chart-container title="بخش‌های تنظیمات">
            <nav class="mb-2 space-y-0.5" role="navigation" aria-label="نمای کلی تنظیمات">
                <a href="{{ route('admin.settings.index') }}"
                   class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40
                       {{ $section === null ? 'bg-amber-500/10 font-medium text-amber-300' : 'text-gray-400 hover:bg-gray-800/50 hover:text-gray-200' }}"
                   aria-current="{{ $section === null ? 'page' : 'false' }}">
                    <svg class="h-4 w-4 shrink-0 {{ $section === null ? 'text-amber-400' : 'text-gray-600' }}"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                    </svg>
                    <span>نمای کلی</span>
                </a>
            </nav>

            @foreach ($grouped as $groupKey => $items)
                <p class="mb-1 mt-3 px-1 text-[10px] font-semibold uppercase tracking-widest text-gray-600 first:mt-0">
                    {{ $groupLabels[$groupKey] ?? $groupKey }}
                </p>
                <nav class="space-y-0.5" role="navigation" aria-label="{{ $groupLabels[$groupKey] ?? $groupKey }}">
                    @foreach ($items as $key => $item)
                        <a href="{{ route('admin.settings.show', $key) }}"
                           class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/40
                               {{ $key === $section ? 'bg-amber-500/10 font-medium text-amber-300' : 'text-gray-400 hover:bg-gray-800/50 hover:text-gray-200' }}"
                           aria-current="{{ $key === $section ? 'page' : 'false' }}">
                            <svg class="h-4 w-4 shrink-0 {{ $key === $section ? 'text-amber-400' : 'text-gray-600' }}"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                            </svg>
                            <span class="truncate">{{ $item['title'] }}</span>
                            @if ($item['coming_soon'])
                                <span class="ms-auto shrink-0 rounded-full bg-gray-800 px-1.5 py-0.5 text-[9px] font-medium text-gray-500">soon</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            @endforeach
        </x-dashboard.chart-container>
    </aside>

    <div class="space-y-5 lg:col-span-3">
        {{ $slot }}
    </div>
</div>
