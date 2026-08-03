# Implementation Plan: Admin Operational UX Baseline

## Overview

این برنامه طراحی موجود را به مجموعه‌ای از promptهای افزایشی برای یک code-generation LLM تبدیل می‌کند. هر مرحله فقط شامل نوشتن، تغییر یا تست کد است، بر خروجی مرحله قبل بنا می‌شود و در پایان همه اجزا به routeها، controllerها، viewها و componentهای موجود پنل مدیریت متصل می‌شوند؛ هیچ کد رهاشده یا لایه بی‌مصرف باقی نمی‌ماند.

پیاده‌سازی با **PHP/Laravel** برای backend و **JavaScript/Alpine.js** برای state تعاملی UI انجام می‌شود؛ ظاهر فقط از طریق CSS و توکن‌های موجود بیان می‌شود (بدون inline style، بدون inline JS، بدون رنگ hardcode، بدون `!important`). property testهای مدل‌محور با `fast-check@4.3.0` در `tests/js/properties/` و property testهای دامنه‌ی persisted با PHPUnit به‌صورت iteration روی ورودی‌های تولیدشده‌ی deterministic در `tests/Feature`/`tests/Unit` نوشته می‌شوند (کتابخانه PBT برای PHP در پروژه نصب نیست).

قواعد ثابت اجرا: migration فقط برای تغییر schema و هرگز `migrate:fresh`/`db:wipe`؛ فایل‌های Frozen_Area دست‌نخورده؛ مالکیت bulk/session/calendar در specهای مالک؛ و پس از هر مرحله `npm run build` + `php artisan optimize:clear` و سپس Baseline_Gate.

## Tasks

- [x] 1. تثبیت baseline محافظت‌شده و فهرست routeها
  - [x] 1.1 پیاده‌سازی preservation gate برای Frozen_Area
    - افزودن تست PHPUnit که برای هر مسیر مجموعه frozen (login view، `components/auth/`، `teacher/hero.css`، `teacher/biography.css`، `components/ui/teacher/`، `design-tokens.css`، `semantic-tokens.css`، `admin/tokens.css`، `admin/glass.css`) وجود فایل و hash بایت را با baseline ثبت‌شده مقایسه کند و مسیر و اختلاف دقیق را در پیام failure گزارش دهد.
    - افزودن بررسی وجود approval record برای هر استثنا و fail کردن استثنای ثبت‌نشده؛ همچنین بررسی نبود `!important` جدید و نبود تغییر ساختار selector پوسته.
    - _Requirements: 13.3, 13.7, 14.1, 14.2, 14.5, 14.6, 14.7, 14.8_

  - [x] 1.2 ساخت route inventory قابل تست برای namespace `admin.`
    - نوشتن helper تست که تمام routeهای named `admin.*` را از `routes/web.php` استخراج کند و برای هر route، وجود controller method و target (view/redirect/JSON) را assert کند.
    - علامت‌گذاری routeهای متعلق به specهای مالک به‌عنوان consume-only تا رفتار آن‌ها بازتعریف نشود.
    - _Requirements: 2.6, 2.8, 15.1, 15.6_

  - [ ]* 1.3 نوشتن property test برای مصونیت Frozen_Area
    - **Property 10: Frozen Area Immutability**
    - iteration روی مجموعه مسیرهای frozen و سناریوهای rename/move/byte-diff/cascade change با assert شکست gate و گزارش مسیر دقیق.
    - **Validates: Requirements 13.3, 13.4, 13.5, 13.6, 13.7, 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8**

- [x] 2. تثبیت ایزوله‌سازی تست، quarantine و Baseline_Gate
  - [x] 2.1 اعمال quarantine صریح روی تست وابسته به محیط
    - تغییر `tests/js/properties/filter-scoping.property.test.js` تا در نبود `TEST_ADMIN_PHONE`، `TEST_ADMIN_PASSWORD` یا base URL قابل دسترس، با skip صریح، شناسه ثابت quarantine و دلیل حداکثر ۲۰۰ کاراکتر خارج شود و در حضور همه prerequisiteها همان assertionهای فعلی اجرا شوند.
    - حفظ فایل و همه assertionها؛ افزودن چاپ inventory پایدار skip (شناسه، دلیل، gap reference) در خروجی پیش‌فرض.
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.8_

  - [x] 2.2 تثبیت ایزوله‌سازی state تست
    - به‌روزرسانی `tests/TestCase.php` و پیکربندی PHPUnit موجود (testsuiteهای `Unit`/`Feature`، SQLite in-memory) برای ریست دیتابیس، فایل‌سیستم موقت، متغیرهای محیطی، cookie و session بین تست‌ها بدون افزودن دستور تخریبی migration.
    - افزودن پیکربندی معادل ایزوله‌سازی برای runner `node --test` (پاکسازی state ماژول/محیط بین تست‌ها).
    - _Requirements: 1.6, 1.7, 1.8, 3.7_

  - [x] 2.3 پیاده‌سازی runner و reporter برای Baseline_Gate
    - افزودن script قابل اجرا (composer/npm script) که `php artisan optimize:clear`، `php artisan test`، `npm run test:js` و `npm run build` را در processهای جدا با timeout ۳۰۰ ثانیه اجرا کند و command، exit status، شمار passed/failed/skipped، اولین failure قابل اقدام و شناسه تست را ثبت کند.
    - رفتار timeout به‌عنوان failure؛ عدم اعلام success در صورت هر exit غیرصفر.
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.9, 1.10, 1.11, 16.7, 16.8_

  - [ ]* 2.4 نوشتن property test برای idempotence و ایزوله‌سازی داده تست
    - **Property 9: Test Data Idempotence and Isolation**
    - iteration روی ترتیب‌های اجرای تست و اجرای دوباره seeder در process تمیز با assert یکسان بودن مجموعه رکورد و نتیجه تست.
    - **Validates: Requirements 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 4.8**

- [x] 3. Checkpoint — Baseline_Gate اولیه
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. تکمیل factory و seeder تست
  - [x] 4.1 افزودن factoryهای غایب
    - ساخت factory برای `TeacherInstrument`، `StudentEnrollment`، `ClassSession`، `ClassAttendance`، `Room`، `Subscription`، `Invoice`، `InvoiceItem` و `InvoicePayment` با naming موجود پروژه و default قابل persist سازگار با constraintهای دیتابیس و قرارداد validation فرم مربوط.
    - _Requirements: 4.1, 4.2, 4.10_

  - [x] 4.2 افزودن stateهای Enum، والد و وابستگی حذف
    - افزودن یک state برای هر مقدار `StudentStatusEnum`، `TeacherStatusEnum`، `EnrollmentStatusEnum`، `SessionStatusEnum`، `AttendanceStatusEnum`، `InvoiceStatusEnum`، `PaymentStatusEnum`، `LeadStatusEnum` و stateهای `super_admin`/`admin`/`teacher`/`student` روی `UserFactory`.
    - افزودن stateهای «والد لازم ساخته‌شود»، «والد داده‌شده reuse شود» و «دارای وابستگی مسدودکننده حذف» در برابر state مستقل با کمینه رکورد لازم.
    - _Requirements: 4.3, 4.4, 4.5, 4.6, 4.7_

  - [x] 4.3 ساخت seeder تستی deterministic و idempotent
    - افزودن seeder مخصوص تست که با اجرای دوباره روی همان state، همان مجموعه رکورد را بدون خطای duplicate key و بدون رشد نامحدود تولید کند و از `DemoSeeder`/داده development مستقل باشد.
    - _Requirements: 1.7, 4.8, 4.10_

  - [ ]* 4.4 نوشتن property test برای قابلیت persist و reuse والد در factory
    - **Property 8: Factory Persistability and Parent Reuse**
    - iteration روی همه مدل‌های الزامی و stateهای Enum با assert رکورد پایدار، reuse دقیقاً یک‌بار والد داده‌شده و ساخت دقیقاً ۲۵ رکورد بدون تضاد شناسه در `count(25)`.
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.9, 4.10**

- [x] 5. پیاده‌سازی ListContext و لایه query فهرست‌ها
  - [x] 5.1 ساخت `ListContextNormalizer` و DTO مربوط
    - پیاده‌سازی DTO immutable `ListContext` با فیلدهای `entity, search, filters, sort, direction, page, per_page, normalized_query, context_fingerprint`، trim و normalize کاراکترهای معادل فارسی/عربی، برش جست‌وجو به ۱۰۰ کاراکتر، `per_page` پیش‌فرض ۲۰ از allow-list، whitelist ستون مرتب‌سازی، direction فقط `asc|desc` و fallback صریح.
    - ورودی خام کاربر هرگز به `orderBy` یا query string تزریق نشود؛ فیلتر ناشناخته ignore و default قراردادی اعمال شود.
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.8, 5.11, 16.2_

  - [ ]* 5.2 نوشتن property test برای canonical بودن List Context
    - **Property 1: Canonical List Context**
    - **Validates: Requirements 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.11**

  - [x] 5.3 پیاده‌سازی `*ListQuery` و DTOهای فهرست
    - ساخت query service برای فهرست teacher، student، session، enrollment، room، instrument، invoice، lead و user که `ListContext` را مصرف، filter/sort را map، relationهای رندرشده را `with`/`withCount` و `paginate(20)->withQueryString()` را اجرا کند، همراه tie-breaker کلید یکتا و total count.
    - ساخت `OperationalRowData` و `OperationalListData` با `allowed_actions`، `selectable`، filter options، sort whitelist، empty-mode و policy flags؛ هیچ query در Blade.
    - _Requirements: 5.1, 5.6, 5.12, 5.13, 5.14, 5.15, 5.16, 16.1, 16.2_

  - [ ]* 5.4 نوشتن property test برای صفحه‌بندی فیلترشده و ترتیب پایدار
    - **Property 2: Filtered Pagination and Stable Ordering**
    - **Validates: Requirements 5.4, 5.5, 5.6, 5.7, 5.10, 5.12, 5.13, 5.14, 5.15, 16.1**

  - [x] 5.5 اتصال فهرست‌ها به controller و view
    - thin کردن متدهای index کنترلرهای admin با مصرف `ListContext` و `*ListQuery`، رندر کنترل‌های جست‌وجو/فیلتر با مقدار normalize‌شده جاری، `admin/partials/sort-th`، pagination با حفظ کامل context، کنترل clear-filters و نمایش تعداد کل نتایج.
    - page نامعتبر به نزدیک‌ترین page معتبر یا صفحه خالی با HTTP 200 و حفظ سایر مقادیر context.
    - _Requirements: 5.1, 5.7, 5.8, 5.9, 5.10, 5.12_

  - [ ]* 5.6 نوشتن تست‌های feature برای رفتار فهرست
    - تست فیلتر/جست‌وجو/مرتب‌سازی/صفحه‌بندی/clear/count، assert کران تعداد query مستقل از تعداد ردیف و پاسخ زیر ۲ ثانیه برای context پیش‌فرض در محیط تست.
    - _Requirements: 5.1, 5.9, 5.10, 5.15, 5.16, 16.1, 16.6_

- [x] 6. Checkpoint — فهرست‌های عملیاتی
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. تکمیل لایه authorization بر پایه Policy
  - [x] 7.1 تکمیل Policyها و ثبت ability‌ها
    - تکمیل methodهای غایب در `TeacherPolicy`، `StudentPolicy`، `SessionPolicy`، `EnrollmentPolicy`، `InvoicePolicy`، `LeadPolicy`، `UserPolicy` و افزودن policy نام‌دار برای `Room` و `Instrument` با abilityهای `viewAny/view/create/update/delete` و ability نام‌دار برای هر action غیر CRUD (status change، attach/detach، assign، convert، issue، cancel، duplicate، payment).
    - نگاشت persona منشی به `admin` از طریق policy بدون افزودن مقدار جدید به `RoleEnum`.
    - _Requirements: 10.2, 10.3, 10.10_

  - [x] 7.2 اتصال authorization به کنترلرها و flagهای UI
    - افزودن `$this->authorize()`/`Gate::authorize()` قبل از هر mutation در `TeacherController`، `StudentController`، `ClassSessionController`، `RoomController`، `InstrumentController`، `StudentEnrollmentController`، `InvoiceController`، `LeadController` و `UserController`؛ حذف مقایسه رشته نقش از بدنه controller/Blade/JavaScript.
    - عبور policy flagها به DTO تا کنترل غیرمجاز omit/disable شود، در حالی که درخواست مستقیم همان action رد می‌شود.
    - _Requirements: 6.3, 10.1, 10.4, 10.8, 10.9_

  - [ ]* 7.3 نوشتن property test برای عدم تغییر داده در نبود مجوز
    - **Property 5: Authorization Non-Mutation**
    - **Validates: Requirements 6.3, 6.9, 9.4, 10.1, 10.5, 10.6, 10.7, 10.8, 10.9, 10.10, 10.11**

  - [ ]* 7.4 نوشتن تست‌های feature برای مرزهای دسترسی
    - تست ۴۰۱/redirect برای unauthenticated، ۴۰۳ برای فقدان ability، پاسخ قراردادی CSRF و assert ارزیابی ability نام‌دار قبل از mutation برای هر route حالت‌تغییردهنده `admin.`.
    - _Requirements: 10.4, 10.5, 10.6, 10.7, 10.11, 7.9_

- [x] 8. تکمیل Form Request، action و transaction
  - [x] 8.1 انتقال validation به Form Request
    - ساخت/تکمیل Form Request برای هر Record_Form مالکیت‌شده (teacher، student، instrument، room، enrollment، invoice، lead، user) با required، type/max، numeric bounds، enum membership، relationship existence، uniqueness و date ordering؛ حذف `$request->validate(...)` inline از کنترلرهای admin.
    - حفظ old input و پیام خطای localized مرتبط با هر فیلد نامعتبر.
    - _Requirements: 6.5, 6.7_

  - [x] 8.2 پیاده‌سازی Action/Service با normalization و transaction
    - انتقال منطق create/update/delete/status/attach/assign/payment به Action/Service، اعمال همان قرارداد normalization استفاده‌شده در Form Request قبل از persistence و اجرای هر تغییر چندرکوردی یا رکورد+relation در یک transaction با rollback کامل.
    - رکورد ناموجود → ۴۰۴ و عدم ساخت رکورد جانشین؛ هیچ business rule در Blade یا JavaScript.
    - _Requirements: 6.4, 6.6, 6.8, 6.9, 6.10, 6.11, 6.12, 6.13, 16.3_

  - [ ]* 8.3 نوشتن property test برای round trip فرم
    - **Property 6: Form Normalization Round Trip**
    - **Validates: Requirements 6.5, 6.6, 6.7, 6.11, 6.13**

  - [ ]* 8.4 نوشتن property test برای اتمیک بودن فرم نامعتبر
    - **Property 7: Invalid Form Atomicity**
    - **Validates: Requirements 6.7, 6.8, 6.9, 6.10, 7.6, 7.7, 7.8, 7.9**

- [x] 9. Checkpoint — فرم و مجوز
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. رفع رفتارهای شکسته شناسایی‌شده
  - [x] 10.1 تکمیل مسیر `admin.teachers.show`
    - افزودن متد `show` به `TeacherController` با route model binding، ability `view`، detail query دارای relationهای لازم و `RecordDetailData`؛ ساخت `resources/views/admin/teachers/show.blade.php` با نام، وضعیت، مقادیر persisted پروفایل و داده عملیاتی مرتبط، فقط با componentهای موجود و توکن‌ها.
    - شناسه ناموجود → ۴۰۴ و Error_State مشترک not-found؛ placeholder localized برای مقدار غایب.
    - _Requirements: 2.1, 2.2, 6.1, 6.11, 7.1_

  - [x] 10.2 تکمیل قرارداد تاریخچه هنرجو
    - رندر بخش جزئیات با شناسه ماشین‌خوان پایدار `student_history` در `admin.students.show` با ترتیب deterministic روی داده persisted و Empty_State مشترک در نبود رکورد تاریخچه.
    - _Requirements: 2.3, 2.4, 6.11, 7.2_

  - [x] 10.3 یکتاسازی view تقویم
    - تثبیت `resources/views/admin/calendar/index.blade.php` به‌عنوان تنها target رندرشده `admin.calendar.index` و حذف یا repurpose مستند `resources/views/admin/calendar.blade.php` بدون تغییر موتور رندر متعلق به spec مالک.
    - _Requirements: 2.5, 15.3, 16.4_

  - [ ]* 10.4 نوشتن تست‌های feature برای route inventory و پاسخ‌های خطا
    - assert status/redirect/JSON مستند برای هر route named `admin.` با actor مجاز، ۴۰۴ برای شناسه ناموجود به‌جای ۵۰۰ و نبود exception مدیریت‌نشده.
    - _Requirements: 2.6, 2.7, 2.8, 2.9_

- [x] 11. یکپارچه‌سازی حالت‌های مشترک و Feedback_Channel
  - [x] 11.1 ساخت Feedback_Channel مشترک
    - ساخت wrapper مشترک روی `x-ui.alert` برای success با `role="status"`، failure با `role="alert"` و خطای فیلد با `aria-invalid`/`aria-describedby`، با dismiss control و حداقل ۴ ثانیه نمایش؛ جایگزینی flashهای پراکنده و رنگ‌های hardcode در viewهای admin با این کانال.
    - پیام success بین ۱۰ تا ۱۶۰ و failure بین ۱۰ تا ۲۰۰ کاراکتر، localized و دارای نام موجودیت/اقدام یا مسیر بازیابی، بدون SQL/stack/path/token/PII.
    - _Requirements: 7.6, 7.7, 8.1, 8.2, 8.3, 8.4, 8.5, 8.11_

  - [x] 11.2 اعمال Empty/Loading/Error مشترک روی صفحه‌های مالکیت‌شده
    - استفاده از `x-dashboard.empty-state` با دو mode (بدون فیلتر با create entry point مجاز، با فیلتر با clear-filters) و `x-ui.loading-state` برای submit/async؛ Error_State با پیام localized و مسیر retry/return و حفظ آخرین داده persisted در خطای async.
    - حذف markup حالت per-page.
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.8, 7.9, 7.10_

  - [x] 11.3 یکپارچه‌سازی Confirmation_Dialog
    - ساخت variant مشترک روی `x-modal` با `role="dialog"`، `aria-modal="true"`، پیوند به heading، `x-trap`، Escape، backdrop معنایی و بازگشت focus؛ نمایش نام موجودیت، اقدام مخرب، پیامد و اقدام‌های صریح تأیید/انصراف برای delete/detach/cancel/تغییر وضعیت غیرقابل بازگشت.
    - انصراف هیچ درخواستی نفرستد؛ تأیید دقیقاً یک درخواست بفرستد و کنترل تأیید تا پایان پاسخ disabled شود.
    - _Requirements: 8.6, 8.7, 8.8, 8.9, 8.11, 11.7_

  - [x] 11.4 پیاده‌سازی ماژول state تعاملی Alpine
    - نگه‌داشتن فقط `pending`، `dialogOpen`، `feedback`، `lastRequestId` و focus در ماژول JS مشترک؛ جلوگیری از submit تکراری، نمایش loading ظرف ۲۰۰ms، کنار گذاشتن پاسخ‌های قدیمی با request/version guard و جایگزینی loading با دقیقاً یک success یا Error_State.
    - بدون inline handler، بدون business rule در JS.
    - _Requirements: 7.4, 7.5, 7.10, 8.9, 8.10, 16.3_

  - [x]* 11.5 نوشتن property test برای state بازخورد حافظ context
    - **Property 11: Context-Preserving Feedback State**
    - **Validates: Requirements 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 7.10, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 8.10, 8.11**

  - [x]* 11.6 نوشتن تست‌های واحد/feature برای قرارداد بازخورد
    - assert نقش‌های ARIA، طول و localization پیام، نبود افشای اطلاعات حساس، Empty_State دو حالته و پاسخ ۴۰۱/۴۰۳ به‌جای فهرست خالی گمراه‌کننده.
    - _Requirements: 7.2, 7.3, 7.7, 7.9, 8.2, 8.3, 8.4_

- [x] 12. اتصال فقط-integration به bulk و calendar
  - [x] 12.1 مصرف component و contract انتخاب گروهی مالک
    - استفاده از component انتخاب ردیف/هدر، شمارنده انتخاب، context fingerprint، endpoint و result DTO متعلق به `admin-bulk-selection-actions` در فهرست teacher/student و پاک شدن Selection_Set با هر تغییر search/filter/sort/page/entity/refresh پیش از امکان submit.
    - عدم ساخت component یا endpoint bulk موازی؛ بازخورد selection خالی از Feedback_Channel مشترک بیاید.
    - _Requirements: 9.1, 9.2, 9.3, 9.7, 15.2, 15.6_

  - [x]* 12.2 نوشتن property test برای invariant مجموعه انتخاب
    - **Property 3: Selection Set Invariant**
    - **Validates: Requirements 9.3, 9.5, 9.7**

  - [x]* 12.3 نوشتن property test برای پایستگی و idempotence نتیجه گروهی
    - **Property 4: Bulk Result Conservation and Idempotence**
    - **Validates: Requirements 9.6, 9.8, 9.11**

  - [x]* 12.4 نوشتن تست‌های regression برای مرز مالکیت
    - assert بدون تغییر ماندن تست‌های سبز موجود specهای مالک (bulk، calendar، settings، CRM lead، admin shell، demo data) و نبود route/persistence تکراری در این spec.
    - _Requirements: 9.1, 9.4, 9.8, 9.10, 15.1, 15.2, 15.3, 15.4, 15.5, 15.6, 15.9, 15.10_

- [ ] 13. Checkpoint — بازخورد و integration
  - Ensure all tests pass, ask the user if questions arise.

- [x] 14. تکمیل a11y، RTL/responsive و parity پوسته
  - [x] 14.1 اصلاح ساختار معنایی و کیبورد صفحه‌های مالکیت‌شده
    - تضمین یک `h1` در هر صفحه، ترتیب heading بدون پرش، `<a>` برای ناوبری و `<button>` برای اقدام، کنترل‌های native فرم، `aria-current="page"` روی آیتم فعال، نام accessible برای کنترل آیکونی و live region برای نتیجه بدون reload.
    - حفظ ترتیب DOM مطابق ترتیب خواندن بصری و عملکرد Enter/Space مطابق semantics بومی.
    - _Requirements: 11.1, 11.2, 11.4, 11.5, 11.6, 11.8, 11.10_

  - [x] 14.2 تکمیل رفتار responsive و RTL در CSS
    - افزودن قواعد CSS با logical properties برای فهرست‌ها زیر ۷۶۸px به‌صورت stacked record یا container scroll داخلی، سقف عرض ۱۶۰۰px، wrap/truncate accessible، containment overlay و touch target حداقل ۴۴×۴۴ روی pointer درشت.
    - جهت صریح برای اعداد و تاریخ؛ بدون token جدید، بدون رنگ hardcode، بدون `!important`، با احترام به `prefers-reduced-motion`.
    - _Requirements: 11.3, 11.11, 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8, 12.9, 13.2, 13.4, 13.7_

  - [x] 14.3 تثبیت parity پوسته و fallback کوکی
    - اطمینان از رندر درست صفحه‌های تکمیل‌شده در `data-admin-theme="dark"` و `"glass"` با variantهای موجود، حفظ List_Context و state صفحه هنگام toggle و بازگشت به `dark` با بازنویسی/نادیده‌گرفتن کوکی نامعتبر بدون exception.
    - بدون تغییر Theme_Token_File و بدون قاعده theme جدید per-page.
    - _Requirements: 13.1, 13.2, 13.5, 13.6, 13.8, 13.9_

  - [x]* 14.4 نوشتن تست‌های Playwright برای ماتریس viewport و پوسته
    - اجرای صفحه‌های مالکیت‌شده در عرض‌های ۳۹۰، ۴۳۰، ۷۶۸، ۱۰۲۴، ۱۳۶۶، ۱۶۰۰ و ۱۹۲۰ در هر دو پوسته با assert سرریز افقی حداکثر ۱px، containment overlay، touch target و خوانایی لایه‌ها.
    - _Requirements: 12.2, 12.3, 12.4, 12.5, 12.6, 12.9, 13.1, 13.8_

  - [x]* 14.5 نوشتن تست‌های a11y مرورگر
    - assert focus ring مشاهده‌پذیر با کنتراست حداقل ۳:۱، focus trap و بازگشت focus دیالوگ، Escape، اعلام live region، کنتراست WCAG AA متن و کنترل در هر دو پوسته و ناوبری کامل کیبورد برای مسیرهای عملیاتی.
    - _Requirements: 11.1, 11.2, 11.3, 11.7, 11.8, 11.9, 11.11, 13.9_

- [x] 15. پاکسازی و نظم فنی
  - [x] 15.1 حذف کد رهاشده و بررسی الگوهای ممنوع
    - حذف `dd`/`dump`/`var_dump`/`print_r`/`console.log`، بلوک کد کامنت‌شده، dead code، view بی‌ارجاع، قاعده CSS استفاده‌نشده و ماژول JS بی‌ارجاع ناشی از این کار؛ جایگزینی هر query رشته‌ای الحاقی با Eloquent/query builder و binding.
    - _Requirements: 16.2, 16.3, 16.4, 16.5_

  - [x]* 15.2 نوشتن تست static برای نظم فنی
    - assert نبود الگوهای ممنوع در `app/`, `resources/views/admin/`, `resources/js/`, `resources/css/admin/`، نبود query در Blade و درست بودن رفتار پس از `php artisan optimize:clear`.
    - _Requirements: 16.2, 16.3, 16.4, 16.5_

- [ ] 16. Checkpoint نهایی — کل Verification_Command_Set
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- زیرتسک‌های دارای `*` اختیاری‌اند و برای MVP سریع‌تر قابل رد شدن هستند؛ تسک‌های سطح اول اختیاری نیستند.
- هر تسک به بند نیازمندی مشخص ارجاع دارد تا traceability حفظ شود.
- شرط تکمیل هر تسک عبور کامل Baseline_Gate است: `php artisan optimize:clear`، `php artisan test`، `npm run test:js`، `npm run build` (هرکدام با timeout ۳۰۰ ثانیه).
- property testها با تگ دقیق `Feature: admin-operational-ux-baseline, Property N: <property text>` و حداقل ۱۰۰ iteration نوشته می‌شوند؛ propertyهای مدل‌محور با `fast-check@4.3.0` و propertyهای دامنه persisted با iteration deterministic در PHPUnit.
- رفتار bulk، ویرایش جلسه، داده واقعی تقویم و اتاق متعلق به specهای مالک است؛ در این برنامه فقط integration و invariant مشترک پیاده می‌شود.
- هیچ تسکی فایل‌های Frozen_Area یا Theme_Token_File را تغییر نمی‌دهد و هیچ تسکی `migrate:fresh`/`db:wipe` اجرا نمی‌کند.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "2.1", "2.2", "4.1"] },
    { "id": 1, "tasks": ["1.3", "2.3", "4.2", "5.1"] },
    { "id": 2, "tasks": ["2.4", "4.3", "5.2", "5.3"] },
    { "id": 3, "tasks": ["4.4", "5.4", "5.5", "7.1"] },
    { "id": 4, "tasks": ["5.6", "7.2", "8.1", "10.3"] },
    { "id": 5, "tasks": ["7.3", "8.2", "10.1"] },
    { "id": 6, "tasks": ["7.4", "8.3", "10.2", "11.1"] },
    { "id": 7, "tasks": ["8.4", "10.4", "11.2", "11.3"] },
    { "id": 8, "tasks": ["11.4", "12.1", "14.2"] },
    { "id": 9, "tasks": ["11.5", "11.6", "12.2", "12.3", "14.1"] },
    { "id": 10, "tasks": ["12.4", "14.3", "15.1"] },
    { "id": 11, "tasks": ["14.4", "14.5", "15.2"] }
  ]
}
```
