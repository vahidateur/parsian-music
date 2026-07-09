@props([
    'title'    => null,
    'subtitle' => null,
    'badge'    => null,
    'span'     => null,
])

@php
    $spanClass = match ((string) $span) {
        '2' => 'xl:col-span-2',
        '3' => 'xl:col-span-3',
        default => '',
    };
@endphp

<section
    role="region"
    aria-label="{{ $title ?? 'Dashboard section' }}"
    {{ $attributes->merge([
        'class' => "overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl shadow-black/10 backdrop-blur-sm transition duration-200 hover:shadow-2xl hover:shadow-black/15 {$spanClass}",
    ]) }}
>
    @if ($title || $subtitle || $badge || isset($actions) || isset($header))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-800/60 px-4 py-3.5 sm:px-6 sm:py-4">
            @isset($header)
                {{ $header }}
            @else
                <div class="min-w-0">
                    @if ($title)
                        <h2 class="text-sm font-semibold text-amber-100 sm:text-base">{{ $title }}</h2>
                    @endif
                    @if ($subtitle)
                        <p class="mt-0.5 text-xs text-gray-500">{{ $subtitle }}</p>
                    @endif
                </div>
            @endisset

            <div class="flex items-center gap-3">
                @if ($badge)
                    <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">
                        {{ $badge }}
                    </span>
                @endif
                @isset($actions)
                    {{ $actions }}
                @endisset
            </div>
        </div>
    @endif

    <div class="p-4 sm:p-6">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-gray-800/60 px-4 py-4 sm:px-6">
            {{ $footer }}
        </div>
    @endisset
</section>
