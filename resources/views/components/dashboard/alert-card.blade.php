@props([
    'title' => null,
    'message' => null,
    'priority' => 'mid',
    'meta' => null,
])

@php
    $priorities = [
        'high' => [
            'shell' => 'border-red-500/20 bg-gradient-to-b from-red-500/[0.06] to-gray-900/60',
            'dot'   => 'bg-red-400',
            'text'  => 'text-red-300',
            'badge' => 'bg-red-500/10 text-red-400',
        ],
        'mid' => [
            'shell' => 'border-amber-500/20 bg-gradient-to-b from-amber-500/[0.05] to-gray-900/60',
            'dot'   => 'bg-amber-400',
            'text'  => 'text-amber-300',
            'badge' => 'bg-amber-500/10 text-amber-400',
        ],
        'low' => [
            'shell' => 'border-yellow-500/15 bg-gradient-to-b from-yellow-500/[0.04] to-gray-900/60',
            'dot'   => 'bg-yellow-300',
            'text'  => 'text-yellow-200',
            'badge' => 'bg-yellow-500/10 text-yellow-300',
        ],
        'info' => [
            'shell' => 'border-sky-500/20 bg-gradient-to-b from-sky-500/[0.05] to-gray-900/60',
            'dot'   => 'bg-sky-400',
            'text'  => 'text-sky-300',
            'badge' => 'bg-sky-500/10 text-sky-400',
        ],
        'success' => [
            'shell' => 'border-emerald-500/20 bg-gradient-to-b from-emerald-500/[0.05] to-gray-900/60',
            'dot'   => 'bg-emerald-400',
            'text'  => 'text-emerald-300',
            'badge' => 'bg-emerald-500/10 text-emerald-400',
        ],
    ];

    $palette = $priorities[$priority] ?? $priorities['mid'];
@endphp

<div {{ $attributes->merge([
    'class' => "rounded-xl border px-4 py-3 shadow-lg shadow-black/10 backdrop-blur-sm {$palette['shell']}",
]) }}>
    <div class="flex items-start gap-3">
        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $palette['dot'] }}"></span>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center justify-between gap-2">
                @if ($title)
                    <p class="text-sm font-medium {{ $palette['text'] }}">{{ $title }}</p>
                @endif

                @if ($meta)
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $palette['badge'] }}">
                        {{ $meta }}
                    </span>
                @endif
            </div>

            @if ($message)
                <p class="mt-1 text-sm text-gray-400">{{ $message }}</p>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>
