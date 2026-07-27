---
inclusion: always
---

# 17 — DECISION LOG

> هر تصمیم مهم فقط یک بار ثبت می‌شود. قبل از تغییر یک بخش frozen، این فایل خوانده شود.

---

## 2026-07-14 — Design System Foundation
- **Decision:** توکن‌ها سه لایه: primitive (`--gold-300`) → semantic (`--teacher-color-*`) → component (`--nav-*`).
- **Reason:** تغییر theme و نگهداری آسان‌تر.
- **Status:** Active.

## 2026-07-14 — Teacher Hero Architecture
- **Decision:** Hero دو ستون: `.hero-left` (background-stack) + `.hero-right` (portrait + info). نسبت 58/42. تمام layout در `teacher-theme.css`.
- **Reason:** Single Layout Owner؛ آماده افزودن smoke/light/frame در فازهای بعد بدون بازطراحی.
- **Status:** Frozen (Phase 1.5).

## 2026-07-14 — No Presentation Logic in Blade
- **Decision:** هیچ inline style یا inline JS در فرانت. تمام ظاهر در CSS، تمام state در Alpine.
- **Reason:** نگهداری، کش، Design System، Dark Mode آینده.
- **Status:** Active (Rule 03, 04, 16).

## 2026-07-14 — Global Navbar
- **Decision:** Navbar شیشه‌ای، sticky، ارتفاع 80px (desktop). موبایل: همبرگر راست، لوگو چپ، drawer از راست. Focus Trap با `@alpinejs/focus`.
- **Reason:** UX بهتر RTL؛ کامپوننت مشترک همه صفحات.
- **Status:** Frozen (Phase 2.1). فقط لوگو/لینک قابل تغییر.

## 2026-07-14 — @alpinejs/focus
- **Decision:** نصب `@alpinejs/focus` به‌عنوان زیرساخت focus trap کل سایت (drawer, modal, lightbox, filters, search, profile menu).
- **Reason:** استاندارد، سبک، reusable.
- **Status:** Active.

## 2026-07-14 — Steering Knowledge Base
- **Decision:** قوانین به فایل‌های راهبردی جدا در `.kiro/steering/` تقسیم شد (00–17) به‌جای یک فایل بزرگ.
- **Reason:** استفاده دقیق‌تر مدل از هر موضوع؛ نگهداری آسان.
- **Status:** Active.

## 2026-07-14 — Split CSS into Modular Architecture
- **Decision:** تقسیم CSS به معماری ماژولار.
  - `site-theme.css` (z-index, x-cloak)
  - `components/navbar.css`
  - `components/breadcrumb.css`
  - `teacher/hero.css`
  - app.css فقط import.
- **Reason:** `teacher-theme.css` داشت به یک stylesheet یکپارچه (monolithic) تبدیل می‌شد. Navbar و Breadcrumb کامپوننت‌های global هستند؛ Hero مخصوص صفحه است.
- **Status:** FROZEN. (اگر کسی خواست دوباره همه را در یک فایل جمع کند، این دلیل را ببیند.)

## 2026-07-14 — Global Breadcrumb
- **Decision:** Breadcrumb عمومی، transparent، chevron باریک، آیتم قبلی خاکستری/فعلی طلایی، RTL، generic (items prop). فاصله navbar→24px→breadcrumb→32px→hero.
- **Reason:** جزئی از هویت بصری؛ reusable همه صفحات.
- **Status:** Frozen (Phase 2.2).

## 2026-07-14 — Steering Additions (UI Patterns + Content Model)
- **Decision:** افزودن `18_UI_PATTERNS.md` (کاتالوگ variant کامپوننت‌ها) و `19_CONTENT_MODEL.md` (schema موجودیت‌ها).
- **Reason:** جلوگیری از تکرار/ناهماهنگی UI و تغییر schema وسط پروژه.
- **Status:** Active.

## 2026-07-14 — Three-Stage Delivery Process
- **Decision:** هر بخش جدید: Blueprint (چیدمان) → Implementation (کد) → Review & Freeze.
- **Reason:** جلوگیری از دوباره‌کاری ساختاری در بخش‌های پیچیده.
- **Status:** Active.

## 2026-07-14 — Hero Composition Blueprint
- **Decision:** Hero = سیستم روایی ۷ لایه (نه صرفاً layout). Purpose: احساس اول، نه بلوک اطلاعات. ترتیب کامل صفحه تعریف شد (Navbar → Breadcrumb → Hero → Quick Stats → Glass Biography → HP Wall → Related Courses → Footer). سند: `HERO_COMPOSITION_BLUEPRINT.md`.
- **Reason:** تثبیت composition و storytelling قبل از UI؛ جلوگیری از بازطراحی.
- **Status:** FROZEN (Phase 2.3).

## 2026-07-14 — Hero Artwork Pipeline & Manifest
- **Decision:** هر Hero مجموعه asset مستقل دارد (background, background-mobile, portrait, frame, glow, decorations) + Asset Manifest (JSON). Hero آن‌ها را compose می‌کند و اسم فایل را نمی‌داند.
- **Reason:** تعویض هر asset بدون تغییر CSS؛ مقیاس‌پذیری برای ۵۰+ استاد.
- **Status:** FROZEN.

## 2026-07-14 — Hero as Scene + Purpose-Built Assets
- **Decision:** Background هر استاد یک «صحنه سینمایی» منحصربه‌فرد است که داستان او را روایت می‌کند. هر تصویر سایت فقط برای همان بخش تولید می‌شود (نه عمومی/برش‌خورده).
- **Reason:** هویت بصری، تمایز هر استاد، نگهداری مستقل.
- **Status:** Active.

## 2026-07-14 — Phase 2.4 Three-Stage Split
- **Decision:** Phase 2.4 به سه مرحله: 2.4A Scene Blueprint (بدون تصویر) → 2.4B Generate AI Assets → 2.4C Integrate.
- **Reason:** جلوگیری از شکستن Layout هنگام تعویض تصاویر AI.
- **Status:** Active.

## 2026-07-14 — Hero Artwork Pipeline & Asset Manifest
- **Decision:** هر Hero = ۶ فایل asset مستقل (background, lighting, frame, portrait, decorations, grading) + Manifest JSON. Pipeline: Scene Spec → AI Production → Manifest → Integration.
- **Reason:** تعویض هر asset بدون تغییر CSS؛ مقیاس‌پذیری ۵۰+ استاد.
- **Storage:** `storage/app/public/ui/teacher/hero/{background/violin|piano|daf|guitar, lighting, frame, portrait, decorations, grading, manifest}/`
- **Status:** FROZEN.

## 2026-07-14 — AI Scene Library
- **Decision:** کتابخانه صحنه‌ها در `AI_SCENES/` با مشخصات کامل (environment, camera, composition, color, materials, forbidden). Scene مستقل از مدل AI.
- **Reason:** یکدستی بصری برای همه اساتید؛ قابل استفاده با هر مدل (GPT Image, Midjourney, Flux).
- **Scenes:** 01_violin_gothic_hall, 02_piano_grand_room, 03_daf_persian_chamber, 04_guitar_modern_studio.
- **Status:** Active. قابل گسترش.

## 2026-07-14 — Phase 2.4B = AI Scene Specification (not artwork)
- **Decision:** قبل از تولید تصویر، AI Scene Specification کامل نوشته می‌شود. تولید تصویر = Phase 2.4C.
- **Reason:** تغییر مشخصات ارزان است؛ تولید مجدد تصویر گران است.
- **Status:** Active.

---

_قالب ثبت تصمیم جدید:_
```
## YYYY-MM-DD — عنوان
- Decision: ...
- Reason: ...
- Status: Active / Frozen
```

## 2026-07-23 — Compact Admin Dashboard Density
- **Decision:** داشبورد Admin از فلسفه‌ی رابط Apple-inspired پیروی می‌کند: تراکم اطلاعات بیشتر، فاصله‌های کوتاه‌تر بر مبنای grid هشت‌تایی، محتوای افقی گسترده‌تر، و glass فقط در جایی که ارزش بصری/عملکردی دارد.
- **Reason:** کاهش فضای خالی و افزایش محتوای قابل مشاهده بدون کاهش خوانایی، دسترسی‌پذیری یا رفتار responsive.
- **Status:** Active.

## 2026-07-24 — Admin Glass Theme (day) alongside Dark (night)
- **Decision:** پنل مدیریت دو پوسته دارد: `data-admin-theme="dark"` (پیش‌فرض شب) و `"glass"` (روز، شیشه‌ای روشن). مارکر روی `<html>` در `layouts/dashboard.blade.php` از کوکی `pm_admin_theme` سمت سرور رندر می‌شود (بدون flash)؛ کلید تغییر در topbar با Alpine (`adminShell.toggleTheme`) کوکی + localStorage را می‌نویسد.
- **Reason:** پوسته روشن باید کاملاً scope شده باشد تا پوسته دارک دست‌نخورده بماند. تمام قواعد Glass زیر `[data-admin-theme="glass"]` در `resources/css/admin/glass.css` است و توکن‌های `--admin-color-*` را remap می‌کند.
- **Status:** Active.
