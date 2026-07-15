{{--
    Instrument Chip — Reusable for any chip/tag context, not teacher-specific.
    Rule 12: No inline styles.
    Rule 13: No hardcoded values — tokens only.
--}}
@props([
    'instrument',
])

<span
    class="
        inline-flex
        items-center
        px-[var(--space-2)]
        py-[var(--space-1)]
        rounded-[var(--radius-xs)]
        bg-[var(--glass-bg)]
        border
        border-[var(--glass-border)]
        text-[var(--text-secondary)]
        text-[var(--text-sm)]
    "
>
    {{ $instrument }}
</span>
