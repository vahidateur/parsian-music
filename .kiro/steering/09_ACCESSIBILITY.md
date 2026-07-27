---
inclusion: always
---

# 09 — ACCESSIBILITY (A11y)

1. Keyboard navigation کامل روی همه عناصر تعاملی.
2. Focus ring قابل مشاهده (هرگز `outline: none` بدون جایگزین).
3. `aria-current="page"` روی آیتم فعال ناوبری.
4. `aria-label` روی دکمه‌های آیکونی.
5. Modal/Drawer: `role="dialog"`, `aria-modal="true"`, Focus Trap، ESC، بازگشت focus.
6. `aria-hidden="true"` روی لایه‌های تزئینی.
7. Contrast حداقل WCAG AA.
8. `prefers-reduced-motion` رعایت شود.
9. تصویر معنادار `alt` دارد؛ تزئینی `alt=""`.
10. ساختار heading منطقی (یک h1، سپس h2, h3).
11. Focus Trap از `@alpinejs/focus` (`x-trap`).
12. لینک و دکمه معنایی (`<a>` برای ناوبری، `<button>` برای عمل).
