---
inclusion: always
---

# 03 — BLADE (Structure Only)

1. Blade فقط ساختار معنایی: `section`, `article`, `header`, `footer`, `nav`, `figure`, `button`, `x-components`.
2. **ممنوع:** `style="..."` (به‌جز تزریق CSS variable در موارد نادر استثنایی).
3. **ممنوع:** `onmouseover`, `onmouseout`, `onclick`, `onfocus`, `onblur` و هر inline event handler.
4. کلاس‌های Layout صفحه (`grid`, `flex` روی container, `col-span`, `row-span`, `justify-*`, `items-*` روی container, `gap-*`, `padding`/`margin` صفحه) فقط CSS.
5. مجاز (layout داخلی خود کامپوننت): `inline-flex`, `items-center` روی خود دکمه/بج، `w-full` برای دکمه، `relative`, `absolute`, `overflow-hidden`, `rounded-*`, `sr-only`.
6. `@props` با مقدار پیش‌فرض معنادار.
7. `{{ }}` برای escape خودکار؛ `{!! !!}` فقط با محتوای مطمئن.
8. هر کامپوننت کامنت هدر: مسئولیت، props، فاز.
9. برای flash اولیه از `x-cloak` استفاده شود، نه `style="display:none"`.
10. حلقه‌ها و شرط‌های ساده مجاز؛ منطق پیچیده در ViewModel.
