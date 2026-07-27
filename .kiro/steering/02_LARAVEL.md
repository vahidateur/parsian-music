---
inclusion: always
---

# 02 — LARAVEL & BACKEND

1. Controller لاغر؛ منطق در Service/Action.
2. داده به View از طریق ViewModel/DTO، نه Query در Blade.
3. Route model binding: `/teachers/{teacher}` نه پیدا کردن دستی.
4. Route ها named باشند (dot notation): `teachers.show`.
5. Enum برای مقادیر ثابت (status, role, ...).
6. Migration برای هر تغییر schema؛ هرگز تغییر دستی DB.
7. **هرگز** `migrate:fresh`, `db:wipe`, `migrate:fresh --seed` بدون اجازه صریح کاربر.
8. Eager loading برای جلوگیری از N+1.
9. Form Request برای validation.
10. Policy/Gate برای authorization.
11. Config و env: هیچ secret در کد.
12. `$fillable` برای کنترل mass assignment.
13. Resource/DTO برای پاسخ API.
14. Queue برای کارهای سنگین (ایمیل، نوتیفیکیشن).
