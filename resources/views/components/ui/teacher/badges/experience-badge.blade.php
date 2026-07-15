{{--
    Experience Badge — Reusable for any badge context, not teacher-specific.
    Rule 12: No inline styles.
    Rule 13: No hardcoded values — tokens only.
    Rule 16: Dimensions match final component exactly.
--}}
@props([
    'experience',
])

<div
    role="text"
    aria-label="{{ $experience }}"
    class="
        inline-flex
        items-center
        px-[var(--space-2)]
        py-[var(--space-1)]
        rounded-[var(--radius-xs)]
        border
        border-[var(--gold-300)]
        text-[var(--gold-300)]
        text-[var(--text-sm)]
        font-medium
    "
>
    {{ $experience }}
</div>
