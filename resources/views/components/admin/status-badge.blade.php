{{--
    Admin Status Badge — pill badge driven by an enum colour token.
    Props: label (string), color (gray|blue|amber|emerald|red|rose|sky|violet).
    Phase: Billing.
    Slots: none.
--}}
@props([
    'label' => '',
    'color' => 'gray',
])

@php
    // Explicit map: Tailwind cannot resolve interpolated class names.
    $variants = [
        'gray'    => 'bg-gray-700/50 text-gray-300',
        'blue'    => 'bg-blue-500/10 text-blue-300',
        'sky'     => 'bg-sky-500/10 text-sky-400',
        'amber'   => 'bg-amber-500/10 text-amber-300',
        'emerald' => 'bg-emerald-500/10 text-emerald-400',
        'red'     => 'bg-red-500/10 text-red-400',
        'rose'    => 'bg-rose-500/10 text-rose-400',
        'violet'  => 'bg-violet-500/10 text-violet-400',
    ];
    $variant = $variants[$color] ?? $variants['gray'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . $variant]) }}>
    {{ $label }}
</span>
