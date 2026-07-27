---
inclusion: always
---

# 07 — COMPONENTS

1. هر کامپوننت مسئولیت واحد و مستقل قابل رندر (Storybook-style).
2. props با `@props` و مقدار پیش‌فرض معنادار.
3. کامپوننت پایه نباید به داده صفحه خاص وابسته باشد.
4. کامپوننت عمومی UI: `resources/views/components/ui/{domain}/`.
5. کامپوننت صفحه‌ای: `resources/views/components/ui/{page}/`.
6. کامپوننت Leaf (button, badge, chip) قابل استفاده در کل سایت.
7. نام فایل kebab-case.
8. کامنت هدر: مسئولیت، props، فاز، slotها.
9. تصویر فقط از طریق named slot.
10. عدم تکرار کامپوننت؛ اگر مشابه وجود دارد، reuse یا variant.
11. API کامپوننت پایدار بماند؛ تغییر breaking = هماهنگی.
12. کامپوننت frozen بدون فاز جدید تغییر نمی‌کند.
