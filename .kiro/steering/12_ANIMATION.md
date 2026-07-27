---
inclusion: always
---

# 12 — ANIMATION & MOTION

1. انیمیشن فقط از توکن‌های `--duration-*` و `--ease-*`.
2. transition روی property مشخص، نه `all` (به‌جز موارد ساده).
3. hover نباید layout jump ایجاد کند (فقط color/opacity/glow/shadow).
4. `prefers-reduced-motion: reduce` همیشه رعایت شود.
5. Motion آهسته و elegant؛ هرگز flashy یا bouncy زیاد.
6. Drawer/Modal با ease طبیعی (`cubic-bezier(.22,.61,.36,1)`).
7. انیمیشن ورود (entrance) ظریف؛ بدون حواس‌پرتی.
8. GPU-friendly: `transform` و `opacity`، نه `top/left/width`.
9. بدون autoplay انیمیشن مزاحم یا loop بی‌پایان پرسروصدا.
10. Duration استاندارد: fast ~150ms، standard ~250ms، slow ~400ms.
