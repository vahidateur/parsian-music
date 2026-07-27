---
inclusion: always
---

# 04 — CSS (Owner of Appearance)

1. هر چیز مربوط به ظاهر فقط در CSS: `color`, `margin`, `padding`, `shadow`, `transition`, `radius`, `font`, `hover`, `active`, `animation`, ابعاد layout.
2. هیچ مقدار hardcode: نه `#hex`, نه `px` خام برای رنگ/فاصله. همه از Token.
3. فایل‌های theme: `design-tokens.css` → `semantic-tokens.css` → `site-theme.css` / `teacher-theme.css`.
4. Import ها قبل از `@tailwind` directives.
5. سازماندهی با هدر بخش‌بندی‌شده و شماره‌گذاری (01 Root, 02 Hero, ...).
6. یک selector = یک مسئولیت. تکرار layout در چند فایل ممنوع.
7. Mobile-first؛ سپس media queries.
8. Logical properties (`margin-inline`, `padding-block`, `inset`) برای RTL.
9. **هرگز `!important`** (به‌جز `[x-cloak]`).
10. نام کلاس BEM: `.block__element--modifier`.
11. `prefers-reduced-motion` رعایت شود.
12. CSS استفاده‌نشده حذف شود.
