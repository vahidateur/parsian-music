---
inclusion: always
---

# 05 — SECURITY

1. تمام ورودی‌ها validate شوند (Form Request).
2. Query ها parameterized (Eloquent/Query Builder)؛ هرگز raw concatenation.
3. `{{ }}` برای escape خودکار؛ `{!! !!}` فقط با محتوای کاملاً مطمئن.
4. CSRF token روی همه فرم‌ها.
5. Authorization با Policy/Gate، نه چک دستی پراکنده.
6. هیچ secret در کد یا Blade؛ فقط `.env`.
7. Mass assignment با `$fillable`.
8. Rate limiting روی endpoint های حساس (login, contact).
9. فایل آپلودی: validate نوع، اندازه، ذخیره خارج از webroot یا با نام امن.
10. HTTPS اجباری در production.
11. هیچ اطلاعات حساس در log یا پاسخ خطا.
12. Sanitize کردن خروجی‌های کاربر قبل از نمایش.
