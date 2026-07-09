@props([
    'label' => '',
    'value' => '—',
    'hint'  => null,
    'badge' => null,
    'tone'  => 'amber',
])

@php
    $tones = [
        'amber'   => ['hover' => 'hover:border-amber-500/30 hover:shadow-amber-500/10',  'icon' => 'bg-amber-500/10 ring-amber-500/20 text-amber-400',   'badge' => 'bg-amber-500/10 text-amber-400'],
        'emerald' => ['hover' => 'hover:border-emerald-500/30 hover:shadow-emerald-500/10','icon' => 'bg-emerald-500/10 ring-emerald-500/20 text-emerald-400','badge' => 'bg-emerald-500/10 text-emerald-400'],
        'sky'     => ['hover' => 'hover:border-sky-500/30 hover:shadow-sky-500/10',      'icon' => 'bg-sky-500/10 ring-sky-500/20 text-sky-400',         'badge' => 'bg-sky-500/10 text-sky-400'],
        'violet'  => ['hover' => 'hover:border-violet-500/30 hover:shadow-violet-500/10','icon' => 'bg-violet-500/10 ring-violet-500/20 text-violet-400', 'badge' => 'bg-violet-500/10 text-violet-400'],
        'rose'    => ['hover' => 'hover:border-rose-500/30 hover:shadow-rose-500/10',    'icon' => 'bg-rose-500/10 ring-rose-500/20 text-rose-400',       'badge' => 'bg-rose-500/10 text-rose-400'],
        'orange'  => ['hover' => 'hover:border-orange-500/30 hover:shadow-orange-500/10','icon' => 'bg-orange-500/10 ring-orange-500/20 text-orange-400', 'badge' => 'bg-orange-500/10 text-orange-400'],
    ];
    $palette = $tones[$tone] ?? $tones['amber'];
@endphp

<div
    role="figure"
    aria-label="{{ $label }}: {{ $value }}"
    {{ $attributes->merge([
        'class' => "group relative overflow-hidden rounded-xl border border-gray-800/40 bg-gray-900/50 p-5 sm:p-6 shadow-lg shadow-black/10 backdrop-blur-sm transition-all duration-200 hover:-translate-y-1 hover:bg-gray-900/80 hover:shadow-xl hover:shadow-black/20 {$palette['hover']}",
    ]) }}
>
    <div class="relative flex items-start justify-between gap-3">
        @isset($icon)
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ring-1 {{ $palette['icon'] }}" aria-hidden="true">
                {{ $icon }}
            </div>
        @endisset

        @if ($badge)
            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $palette['badge'] }}">
                {{ $badge }}
            </span>
        @endif
    </div>

    <div class="relative mt-4">
        <p class="text-3xl font-bold tabular-nums text-white" aria-live="polite">{{ $value }}</p>
        @if ($label)
            <p class="mt-1 text-sm font-medium text-gray-300">{{ $label }}</p>
        @endif
        @if ($hint)
            <p class="mt-0.5 text-xs text-gray-500">{{ $hint }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
