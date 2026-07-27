---
inclusion: always
---

# 01 — ARCHITECTURE

1. هر کامپوننت یک مسئولیت واحد دارد (Single Responsibility).
2. کامپوننت‌های Leaf (badge, chip, button) نباید Layout صفحه را کنترل کنند — فقط layout داخلی خودشان.
3. Layout صفحه فقط توسط کامپوننت wrapper کنترل می‌شود — **Single Layout Owner**.
4. هر کامپوننت مستقل و قابل استفاده مجدد در چند صفحه باشد.
5. کامپوننت‌های عمومی (navbar, footer, breadcrumb) جدا از کامپوننت‌های صفحه‌ای.
6. هیچ business logic داخل Blade نباشد.
7. داده از Controller/ViewModel می‌آید، نه از داخل View (به‌جز mock موقت با کامنت صریح).
8. یک فاز پس از تأیید Freeze می‌شود؛ فقط bugfix مجاز، تغییر معماری = فاز جدید.
9. معماری چند لایه: primitive → semantic → component.
10. Zero Layout Shift: placeholder با ابعاد نهایی.
11. هر بخش ماژول مستقل: ساخت → تست → Freeze.
12. سلسله‌مراتب z-index فقط از توکن، هرگز عدد خام.
