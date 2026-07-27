---
inclusion: always
---

# 10 — RESPONSIVE

1. Breakpoints: Mobile <768, Tablet 768–1023, Laptop 1024–1279, Desktop ≥1280.
2. هیچ horizontal overflow در هیچ سایزی.
3. موبایل جداگانه طراحی شود، نه صرفاً `flex-direction: column`.
4. تست اجباری در: 390, 430, 768, 1024, 1366, 1600, 1920.
5. container و تصویر `max-width` داشته باشند (مانیتور بزرگ کشیده نشود).
6. Mobile-first CSS.
7. RTL کامل با logical properties.
8. Touch target حداقل 44×44px روی موبایل.
9. فونت و spacing responsive (clamp در صورت لزوم).
10. تصاویر responsive (`srcset`/`sizes` یا `max-width:100%`).
