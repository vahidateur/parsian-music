---
inclusion: always
---

# 06 — DATABASE

1. هر تغییر schema فقط با Migration.
2. **هرگز** `migrate:fresh`, `db:wipe` بدون اجازه صریح کاربر.
3. نام جدول جمع snake_case (`teachers`, `class_sessions`).
4. نام ستون snake_case.
5. Foreign key با constraint و `onDelete` مشخص.
6. Index روی ستون‌های پرجستجو و foreign key.
7. از Enum/lookup table برای مقادیر ثابت.
8. timestamps و soft deletes در صورت نیاز.
9. Seeder و Factory برای داده تست.
10. هیچ Query در Blade؛ Eager loading برای N+1.
11. تراکنش (transaction) برای عملیات چند مرحله‌ای.
12. مقادیر پولی به‌صورت integer (کوچک‌ترین واحد) ذخیره شوند.
