<x-dashboard.chart-container :title="$meta['title']">
    <div class="py-4 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-800/60 bg-gray-800/40 text-gray-500">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}"/>
            </svg>
        </div>
        <p class="text-base font-semibold text-gray-300">{{ $meta['title'] }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ $meta['desc'] }}</p>
    </div>

    @if (!empty($meta['features']))
        <div class="{{ $divider }}">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-600">قابلیت‌های برنامه‌ریزی‌شده</p>
            <ul class="space-y-2.5" role="list">
                @foreach ($meta['features'] as $feature)
                    <li class="flex items-start gap-2.5 text-sm text-gray-400">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-500/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-dashboard.chart-container>
