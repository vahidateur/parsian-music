---
inclusion: always
---

# 08 — PERFORMANCE

1. CSS و JS از طریق Vite build و minify.
2. CSS variable برای theme به‌جای تکرار (کش بهتر).
3. کتابخانه سبک بر سنگین ترجیح.
4. حداقل JS ممکن؛ ترجیح CSS-only برای افکت بصری.
5. فونت با `preconnect` و `display=swap`.
6. Code-splitting برای chunk بزرگ (>500KB).
7. تصاویر: `webp`/`avif`، `loading="lazy"` پایین fold.
8. Eager loading برای N+1.
9. Cache برای Query سنگین و config.
10. حذف کد/CSS/JS استفاده‌نشده.
11. عدم بارگذاری JS بلاک‌کننده در head.
12. ابعاد تصویر مشخص برای جلوگیری از layout shift.
