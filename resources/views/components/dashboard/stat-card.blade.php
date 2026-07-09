@props([
    'label' => '',
    'value' => '—',
    'description' => null,
    'trend' => null,
    'trendDirection' => null,
    'tone' => 'neutral',
])

@php
    $tones = [
        'neutral' => 'border-gray-800/60 bg-gray-900/50',
        'amber'   => 'border-amber-500/20 bg-amber-500/[0.04]',
        'emerald' => 'border-emerald-500/20 bg-emerald-500/[0.04]',
        'sky'     => 'border-sky-500/20 bg-sky-500/[0.04]',
        'rose'    => 'border-rose-500/20 bg-rose-500/[0.04]',
        'violet'  => 'border-violet-500/20 bg-violet-500/[0.04]',
    ];

    $trendClass = match ($trendDirection) {
        'up'   => 'text-emerald-400 bg-emerald-500/10',
        'down' => 'text-rose-400 bg-rose-500/10',
        'flat' => 'text-gray-400 bg-gray-700/40',
        default => 'text-amber-300 bg-amber-500/10',
    };

    $surface = $tones[$tone] ?? $tones['neutral'];
@endphp

<div {{ $attributes->merge([
    'class' => "rounded-2xl border {$surface} p-5 shadow-xl shadow-black/10 backdrop-blur-sm",
]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            @if ($label)
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ $label }}</p>
            @endif
            <p class="mt-2 text-2xl font-semibold text-white">{{ $value }}</p>
            @if ($description)
                <p class="mt-1 text-sm text-gray-400">{{ $description }}</p>
            @endif
        </div>

        @if ($trend)
            <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $trendClass }}">
                {{ $trend }}
            </span>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="mt-4 border-t border-gray-800/60 pt-4">
            {{ $slot }}
        </div>
    @endif
</div>
