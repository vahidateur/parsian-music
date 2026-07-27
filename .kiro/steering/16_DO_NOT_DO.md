---
inclusion: always
---

# 16 — DO NOT DO (هرگز انجام نده)

> این فایل حتی از Rules مهم‌تر است. موارد زیر مطلقاً ممنوع‌اند.

## Frontend / CSS
- Inline Style (`style="..."`) در Blade فرانت
- Inline JS (`onclick`, `onmouseover`, `onblur`, ...)
- Duplicate CSS
- Duplicate Token
- Magic Number
- Hardcoded Color
- Hardcoded Radius
- Hardcoded Font
- `!important` (به‌جز `[x-cloak]`)
- SVG Inline طولانی (بیش از ~20 خط)
- Image Base64
- Unoptimized Image
- Unused CSS / Unused JS

## Components / Architecture
- Duplicate Component
- Business Logic in Blade
- Business Logic in JS
- Alpine برای Styling

## Laravel / Backend
- Anonymous Route (بدون name)
- Raw SQL (concatenation)
- N+1 Query
- Fat Controller
- Mass assignment بدون `$fillable`
- `migrate:fresh` / `db:wipe` بدون اجازه صریح

## Debug / Dead Code
- `console.log`
- `dd()`, `dump()`, `var_dump()`, `print_r()`
- Debugbar در production
- Commented Code
- Dead Code
- Temporary Code
- TODO رهاشده در production
- Blocking JS

## Performance
- JS بلاک‌کننده در head
- تصویر بدون ابعاد (layout shift)
- کتابخانه سنگین وقتی سبک وجود دارد
