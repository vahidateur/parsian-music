---
inclusion: always
---

# 15 — WORKFLOW

1. هر بخش ماژول مستقل: ساخت → تست → Freeze.
2. پس از هر تغییر: `npm run build` و رفع خطا قبل از ادامه.
3. پس از تغییر Blade/config: `php artisan optimize:clear`.
4. تست بصری در همه breakpoints قبل از تأیید.
5. بخش Frozen بدون فاز جدید تغییر نمی‌کند.
6. یک بخش در هر زمان؛ عدم پرش بین ماژول‌های ناتمام.
7. قبل از تغییر روی frozen، `DECISION_LOG.md` خوانده شود.
8. مغایرت با Rules → اول اعلام، سپس راه‌حل سازگار.
9. تصمیم مهم در `DECISION_LOG.md` ثبت شود.
10. هرگز کد موقت/دیباگ (`dd`, `console.log`) در نسخه نهایی.
