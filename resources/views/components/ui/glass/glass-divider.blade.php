{{--
    Glass Divider Component
    
    Decorative divider with optional text.
    Used in: Forms, Sections throughout the system.
    
    Props:
    - text: optional text in center
    - class: additional CSS classes
--}}

@props([
    'text' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 text-[13px] text-[var(--color-gold)]/60']) }}>
    <span class="flex-1 h-px bg-[var(--color-gold)]/20"></span>
    
    @if($text)
        <span>{{ $text }}</span>
    @endif
    
    <span class="flex-1 h-px bg-[var(--color-gold)]/20"></span>
</div>
