@props([
    'title' => null,
    'description' => null,
    'time' => null,
    'badge' => null,
    'tone' => 'amber',
    'last' => false,
])

@php
    $tones = [
        'amber'   => ['dot' => 'bg-amber-400',   'badge' => 'bg-amber-500/10 text-amber-300',   'border' => 'border-amber-500/20'],
        'emerald' => ['dot' => 'bg-emerald-400', 'badge' => 'bg-emerald-500/10 text-emerald-300', 'border' => 'border-emerald-500/20'],
        'sky'     => ['dot' => 'bg-sky-400',     'badge' => 'bg-sky-500/10 text-sky-300',       'border' => 'border-sky-500/20'],
        'violet'  => ['dot' => 'bg-violet-400',  'badge' => 'bg-violet-500/10 text-violet-300',  'border' => 'border-violet-500/20'],
        'rose'    => ['dot' => 'bg-rose-400',    'badge' => 'bg-rose-500/10 text-rose-300',      'border' => 'border-rose-500/20'],
        'orange'  => ['dot' => 'bg-orange-400',  'badge' => 'bg-orange-500/10 text-orange-300',  'border' => 'border-orange-500/20'],
        'gray'    => ['dot' => 'bg-gray-400',    'badge' => 'bg-gray-700/50 text-gray-300',      'border' => 'border-gray-600/30'],
    ];

    $palette = $tones[$tone] ?? $tones['amber'];
@endphp

<li {{ $attributes->merge([
    'class' => ($last ? '' : 'pb-6 ') . 'relative',
]) }}>
    <span class="absolute -right-[1.3rem] flex h-6 w-6 items-center justify-center rounded-full border border-gray-800 {{ $palette['dot'] }} shadow-lg">
        @isset($icon)
            <span class="flex h-3 w-3 items-center justify-center text-gray-950">
                {{ $icon }}
            </span>
        @else
            <span class="h-2 w-2 rounded-full bg-gray-950"></span>
        @endisset
    </span>

    <div class="rounded-xl border {{ $palette['border'] }} bg-gray-800/30 px-4 py-3 transition hover:bg-gray-800/50">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                @if ($badge)
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $palette['badge'] }}">
                        {{ $badge }}
                    </span>
                @endif
                @if ($title)
                    <p class="text-sm font-medium text-gray-200">{{ $title }}</p>
                @endif
            </div>

            @if ($time)
                <time class="text-xs text-gray-500 tabular-nums" dir="ltr">{{ $time }}</time>
            @endif
        </div>

        @if ($description)
            <p class="mt-1.5 text-sm text-gray-300">{{ $description }}</p>
        @endif

        @if ($slot->isNotEmpty())
            <div class="mt-2">
                {{ $slot }}
            </div>
        @endif
    </div>
</li>
