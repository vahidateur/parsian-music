---
inclusion: always
---

# 19 — CONTENT MODEL

> تعریف داده هر موجودیت. قبل از migration/model این فایل مبنا است تا وسط پروژه schema عوض نشود.
> این مدل‌ها راهنما هستند؛ پیاده‌سازی نهایی با migration و اجازه کاربر.

## Teacher (استاد)
**Basic**
- name — نام کامل
- slug — نامک URL (`nazanin-hosseini`)
- photo — عکس پرتره
- hero_background — تصویر پس‌زمینه Hero (AI)
- role — سِمت/سازی که تدریس می‌کند
**Profile**
- biography — رزومه (چند پاراگراف)
- experience_years — سابقه تدریس (عدد)
- education — تحصیلات
- specialties — تخصص‌ها (چند مقدار: ویولن، سلفژ، ...)
- features — سه ویژگی سریع (icon + title، قابل ویرایش از پنل)
- quote — نجوای استاد (متن + منبع)
**Assets (Hero Pipeline)**
- hero_scene — اسم صحنه (مثل `violin-gothic-hall`)
- hero_manifest — مسیر Manifest JSON
**Relations**
- weekly_schedule — برنامه هفتگی کلاس‌ها
**Quick Stats (Future — optional)**
- rating — امتیاز (مثلاً 4.9)
- students_count — تعداد هنرجو
- classes_count — تعداد کلاس برگزارشده
- years_experience — سال‌های تجربه (از experience_years)
- توجه: اگر داده واقعی نبود، بخش UI فقط جای آن رزرو شود؛ حذف یک بخش مستقل ساده‌تر از بازطراحی است.

**Meta**
- seo (title, description, og_image)
- status (active/inactive/draft)
- display_order — ترتیب نمایش

## Course (دوره)
- title, slug, cover, short_description, description
- instrument_id (relation)
- teacher_id (relation)
- level (enum: beginner/intermediate/advanced)
- duration, price, schedule
- seo, status, display_order

## Instrument (ساز)
- name, slug, icon, image, description
- category, related_courses (relation)
- seo, status, display_order

## Blog (وبلاگ)
- title, slug, cover, excerpt, body
- author_id, category, tags
- published_at, reading_time
- seo, status

## Gallery (گالری)
- title, image, thumbnail, caption
- category, display_order, status

## Event (رویداد)
- title, slug, cover, description
- start_at, end_at, location
- capacity, price, status, seo

## اصول مشترک
- هر موجودیت: `slug` یکتا برای URL تمیز.
- هر موجودیت قابل نمایش: `seo` (title/description/og_image).
- هر موجودیت لیست‌شونده: `status` + `display_order`.
- تصاویر از طریق named slot / storage، نه hardcode.
- مقادیر ثابت (level, status) → Enum.
- مقادیر پولی → integer (کوچک‌ترین واحد، ریال).
