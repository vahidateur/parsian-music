@props([
    'title' => '',
    'subtitle' => null,
    'badge' => null,
])

<div {{ $attributes->merge([
    'class' => 'mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between',
]) }}>
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-3">
            @if ($title)
                <h2 class="text-lg font-semibold text-amber-100">{{ $title }}</h2>
            @endif
            @if ($badge)
                <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-300">
                    {{ $badge }}
                </span>
            @endif
        </div>
        @if ($subtitle)
            <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-3">
            {{ $actions }}
        </div>
    @endisset

    {{ $slot }}
</div>
