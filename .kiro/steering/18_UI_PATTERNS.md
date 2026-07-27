---
inclusion: always
---

# 18 — UI PATTERNS

> کاتالوگ الگوهای UI. هر کامپوننت جدید باید با variant موجود سازگار باشد.
> قبل از ساخت کامپوننت جدید، این فایل خوانده شود تا از تکرار و ناهماهنگی جلوگیری شود.

## Button
- **Primary** — پس‌زمینه طلایی، متن تیره، برای عمل اصلی.
- **Secondary** — border طلایی، پس‌زمینه transparent، متن طلایی.
- **Ghost** — بدون border، فقط متن، hover ملایم.
- **Glass** — سطح شیشه‌ای با blur و border ظریف.
- مشترک: rounded، soft hover، glow ملایم، focus ring، بدون layout jump.

## Card
- **Glass Card** — پس‌زمینه شیشه‌ای، blur، border طلایی کم‌رنگ.
- **Information Card** — ساختار برچسب/مقدار.
- **Premium Card** — سایه بلند، لبه طلایی، برجسته.
- مشترک: radius یکنواخت، padding از token، shadow نرم.

## Section
- **Hero** — Cinematic، Layered، Depth، Glow، Frame، Negative Space.
- **Content** — بلوک متن + عنوان، فاصله راحت.
- **Gallery** — grid تصاویر، lazy load.
- **Timeline** — رویدادهای زمانی عمودی.

## Table
- **Professional Table** — ردیف اول عنوان طلایی برجسته، divider طلایی بین ردیف‌ها، برچسب/مقدار.
- **Schedule Table** — جدول هفتگی، رنگ‌بندی نوع کلاس، responsive (کارت در موبایل).

## Badge
- **Experience** — border طلایی، متن طلایی، transparent.
- **Level** — سطح مهارت.
- **Featured** — برجسته، پرکنتراست.

## Chip
- **Instrument** — glass background، border ظریف.
- **Genre** / **Age** — همان الگو، رنگ متفاوت در صورت نیاز.
- مشترک: inline-flex، wrap خودکار، gap یکنواخت، radius کوچک.

## Divider
- **Gold Divider** — خط طلایی ظریف، جداکننده پاراگراف/بخش.
- **Glass Divider** — خط کم‌رنگ شیشه‌ای.

## Panel
- **Biography** — متن رزومه، پاراگراف‌ها با gold divider.
- **Quote** — نقل‌قول، کاغذ قدیمی/شیشه، سایه سه‌بعدی.
- **Sidebar** — پنل کناری اطلاعات.

## Breadcrumb
- **Global Pattern** — transparent، chevron باریک، آیتم قبلی خاکستری گرم، صفحه فعلی طلایی، RTL، reusable.

## اصول مشترک همه الگوها
- تمام مقادیر از Token.
- Glass = blur + border طلایی کم‌رنگ + پس‌زمینه نیمه‌شفاف.
- طلایی فقط accent/لبه، نه سطح بزرگ.
- hover ملایم، بدون jump.
- کاملاً responsive و RTL.
