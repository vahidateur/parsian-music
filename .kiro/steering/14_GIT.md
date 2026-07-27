---
inclusion: always
---

# 14 — GIT COMMIT CONVENTION

1. Conventional Commits: `feat:`, `fix:`, `refactor:`, `style:`, `docs:`, `chore:`, `perf:`, `test:`.
2. عنوان کوتاه (<70 کاراکتر)، توضیح در body.
3. هر commit یک تغییر منطقی.
4. commit فقط با اجازه صریح کاربر.
5. push به branch جدید، نه مستقیم به main/master.
6. پیام commit به انگلیسی، واضح و توصیفی.
7. هرگز force push یا reset --hard بدون اجازه.
8. فایل‌های حساس (`.env`, credentials) هرگز commit نشوند.
9. `.gitignore` به‌روز نگه داشته شود.
10. staging فایل‌های مشخص، نه `git add .` کورکورانه.
