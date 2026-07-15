{{--
    CTA Button — Site-wide reusable component.
    NOT teacher-specific. Usable on any public page.
    Rule 12: No inline styles.
    Rule 13: No hardcoded values.
    Rule 9:  Site-wide, not page-specific.
--}}
@props([
    'label' => 'درخواست کلاس',
])

<button
    type="button"
    aria-label="{{ $label }}"
    class="
        w-full
        px-[var(--space-4)]
        py-[var(--space-2)]
        rounded-[var(--radius-sm)]
        bg-[var(--gold-300)]
        text-[var(--neutral-950)]
        text-[var(--text-md)]
        font-bold
        focus:outline-none
        focus-visible:ring-2
        focus-visible:ring-[var(--gold-300)]
        focus-visible:ring-offset-2
        cursor-pointer
    "
>
    {{ $label }}
</button>
