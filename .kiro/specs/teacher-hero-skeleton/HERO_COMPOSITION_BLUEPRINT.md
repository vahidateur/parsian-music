# Phase 2.3 — Hero Composition Blueprint

> فقط چیدمان و ترکیب (Composition). هیچ CSS و هیچ HTML جدید در این مرحله نوشته نمی‌شود.
> این سند مبنای پیاده‌سازی فازهای بعد است. پس از تأیید → Freeze.

---

## هدف

تعریف کامل **ترکیب لایه‌ای Hero** و **ترتیب کل صفحه استاد** قبل از هرگونه کدنویسی بصری.
مطابق تصویر مرجع: کتابخانه جادویی موسیقی، سینمایی، طلایی، تیره.

---

## Hero Purpose

> The Hero is responsible for creating an **emotional first impression**.
> It is **NOT** an information block.
> It introduces the teacher through **atmosphere, composition, portrait, and storytelling**.
> The visitor should feel they have entered the teacher's world **before reading any text**.

---

## ساختار موجود (Frozen — Phase 1.5)

```
.teacher-hero (grid 58fr / 42fr)
├── .hero-left
│    └── .background-stack
│         ├── #teacher-background-slot
│         └── #teacher-decoration-slot
└── .hero-right
      ├── figure (portrait: #teacher-frame-slot + #teacher-photo-slot)
      └── .teacher-info (h1, p, badge, chips, cta)
```

این معماری تغییر نمی‌کند. لایه‌های بصری فقط داخل slotهای موجود اضافه می‌شوند.

---

## لایه‌های Hero (Z-Index Composition)

| # | Layer | محل | Z-Index | فاز |
|---|-------|-----|---------|-----|
| 1 | **AI Background** | `#teacher-background-slot` | `--z-hero-background` (0) | 2.4 |
| 2 | **Portrait Glow** | داخل `figure`، پشت frame | بین 0 و 20 | 2.5 |
| 3 | **Golden Frame** | `#teacher-frame-slot` | `--z-hero-portrait` (20) | 2.5 |
| 4 | **Teacher Photo** | `#teacher-photo-slot` | `--z-hero-portrait` (20) | 2.5 |
| 5 | **Teacher Information** | `.teacher-info` (h1, role, badge, chips) | `--z-hero-info` (30) | 2.6 |
| 6 | **CTA** | `.teacher-cta-wrap` | `--z-hero-info` (30) | 2.6 |
| 7 | **Hero Decorations** (smoke, light, particles) | `#teacher-decoration-slot` | `--z-hero-decoration` (10) | 2.7 |

**نکته:** Decoration (z:10) بین background (0) و portrait (20) قرار می‌گیرد — دود/نور پشت پرتره، جلوی پس‌زمینه.

---

## Layer Specifications

### Layer 1 — AI Background
> The background artwork is **unique for every teacher**.
> It reflects:
> • Instrument • Personality • Teaching style • Musical atmosphere
>
> **No generic backgrounds.** Every hero artwork must be generated specifically for that teacher.

نمونه‌ها:
- استاد ویولن → تالار چوبی، پنجره گوتیک، نور گرم
- استاد پیانو → سالن بزرگ با پیانوی گرند
- استاد دف → فضای عرفانی ایرانی
- استاد گیتار → استودیو مدرن

### Layer 3+4 — Portrait Frame
> The portrait frame is an **independent artwork**.
> Frame is **never merged** with the background.
> Frame can be replaced later without affecting layout.
> Portrait image is **always independent** from frame.

---

## Hero Assets (همه مستقل — No Merged Assets)

- Background
- Portrait
- Frame
- Decoration
- Glow

> All independent. No merged assets.
> برای هر استاد فقط این asset ها + محتوا عوض می‌شود — بدون تغییر حتی یک خط CSS.

---

## ترتیب کامل صفحه استاد (Page Composition)

```
┌─────────────────────────────────────┐
│ Navbar                    (Frozen)   │
├─────────────────────────────────────┤
│ Breadcrumb                (Frozen)   │
├─────────────────────────────────────┤
│ HERO                      (2.3–2.7)  │
│   AI Background · Portrait · Info    │
├─────────────────────────────────────┤
│ Quick Stats (اختیاری)      (Future)  │
│   ★ rating · هنرجو · کلاس · سابقه   │
├─────────────────────────────────────┤
│ Glass Biography Section              │
│   ┌─────────────────────────────┐   │
│   │ Glass Panel                 │   │
│   │  ├ Biography (رزومه)        │   │
│   │  └ Professional Card (جدول) │   │
│   └─────────────────────────────┘   │
├─────────────────────────────────────┤
│ Harry Potter Wall (Brick)            │
│   راست: Teacher Whisper (نجوا)      │
│   چپ:  Weekly Schedule (برنامه)     │
├─────────────────────────────────────┤
│ Related Courses (اختیاری)  (Future)  │
├─────────────────────────────────────┤
│ Footer                     (Future)  │
└─────────────────────────────────────┘
```

---

## تصمیمات کلیدی Composition

1. **Portrait = ۴ لایه** — Glow → Golden Frame → Photo (+ background پشت همه).
2. **Quick Stats** — لایه اختیاری بین Hero و Biography. اگر داده واقعی نبود، فقط جای آن رزرو شود (ثبت در CONTENT_MODEL انجام شد).

### Glass Biography
> Acts as the **storytelling section**.
> Contains:
> - Biography
> - Teaching philosophy
> - Professional experience
> - Achievements
>
> Every subsection separated by an **elegant gold divider**.
> **No long uninterrupted paragraphs.**

ساختار: یک **Glass Surface بزرگ** که `Professional Card` روی آن قرار می‌گیرد (مطابق مرجع) — نه دو Box جدا.

### Professional Card
> **Sticky** on desktop.
> Normal flow on mobile.
> Acts as a **quick navigation panel**.
> Every row scrolls smoothly to its related Biography section (anchor link).

### Harry Potter Wall
بخش با پس‌زمینه دیوار آجری (Brick):
- **راست:** Teacher Whisper
- **چپ:** Weekly Schedule

### Weekly Schedule
> - Current classes
> - Availability
> - Private classes
> - Online classes
> - Group classes
> - Holiday notice
>
> همه از پنل مدیریت پر می‌شوند.

### Teacher Whisper (خاص‌ترین بخش سایت)
> A **personal handwritten note**.
> Maximum **120 words**.
> Displayed on **aged magical paper**.
> The paper is **decorative only**.
> The content is **editable from admin panel**.
>
> این بخش هویت سایت را می‌سازد.

---

## فازهای پیاده‌سازی بعدی (پس از Freeze این Blueprint)

| فاز | عنوان | محتوا |
|-----|-------|-------|
| 2.4 | Hero Background | تصویر AI در `#teacher-background-slot` + overlay/vignette |
| 2.5 | Portrait Frame | Glow + Golden Frame + Photo |
| 2.6 | Teacher Information | تایپوگرافی نهایی، badge، chips، CTA |
| 2.7 | Hero Decorations | smoke, light, particles در decoration-slot |
| 3.x | Glass Biography | Glass Panel + Professional Card |
| 3.x | Harry Potter Wall | Brick + Whisper + Schedule |

---

## Hero Artwork Pipeline

هر Hero این asset های مستقل را دارد (نه یک عکس واحد):

```
storage/app/public/ui/teacher/{teacher-slug}/hero/
├── background.webp
├── background-mobile.webp
├── portrait.webp
├── frame.webp
├── glow.webp
└── decorations.webp
```

Hero خودش این‌ها را روی هم می‌چیند (compose). تعویض هر asset مستقل است:
- تعویض قاب → فقط `frame.webp`
- نور بیشتر → فقط `glow.webp`
- پس‌زمینه جدید → فقط `background.webp`
- قاب متفاوت برای استاد دف → فقط `frame.webp`

**هیچ CSS تغییر نمی‌کند.**

## Asset Manifest

Hero اسم فایل‌ها را نمی‌داند؛ Manifest تعیین می‌کند:

```json
{
  "background": "background.webp",
  "background_mobile": "background-mobile.webp",
  "portrait": "portrait.webp",
  "frame": "frame.webp",
  "glow": "glow.webp",
  "decorations": "decorations.webp"
}
```

## Hero as Scene (نه Background Image)

هر استاد یک **صحنه سینمایی** دارد که داستان او را روایت می‌کند — نه صرفاً decoration.

| استاد | صحنه |
|-------|------|
| ویولن | تالار چوبی · پنجره گوتیک · نور گرم · دود کم · ویولن قدیمی · نت روی میز |
| پیانو | سالن اپرا · پیانوی گرند · چلچراغ · نور ماه · کف مرمر |
| دف | فضای ایرانی · آجر · شمع · خوشنویسی · نور طلایی |
| گیتار | استودیو مدرن |

## Phase 2.4 — سه مرحله

| مرحله | عنوان | خروجی |
|-------|-------|--------|
| **2.4A** | Hero Scene Blueprint | فقط جای همه چیز، بدون تصویر |
| **2.4B** | Generate AI Assets | ساخت تمام تصاویر |
| **2.4C** | Integrate Assets | جایگذاری asset ها |

دلیل: اگر مستقیم سراغ AI برویم، هر تعویض تصویر ممکن است Layout را بشکند.

## Asset Principle (تصمیم دائمی)

هر تصویر فقط برای **همان بخش** تولید می‌شود — نه تصویر عمومی که بعداً برش بخورد.
Hero, Portrait, Frame, Harry Potter Wall, Teacher Whisper, آیکون‌های تزئینی — هر asset نقش مستقل خودش را دارد.

---

## Scalability Principle

این پروژه فقط برای امروز نیست. اگر دو سال بعد ۵۰ استاد داشتیم، برای هر استاد فقط این‌ها عوض می‌شوند:
- Hero Background
- Portrait
- Frame
- Biography
- Schedule
- Whisper

**بدون تغییر حتی یک خط CSS.** این همان معماری مقیاس‌پذیری هدف پروژه است.

---

## اصول (از Steering)

- هیچ تغییر معماری Frozen (Phase 1.5).
- تصویر فقط از طریق named slot.
- تمام مقادیر از Token.
- بدون inline style/JS.
- هر فاز: Blueprint → Implementation → Review & Freeze.

---

_Status: **FROZEN** (Phase 2.3 — 2026-07-14). تغییر معماری = فاز جدید._
