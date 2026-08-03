# Requirements Document

## Introduction

این سند نیازمندی‌های اولویت ۱ محصول را تعریف می‌کند: تثبیت یک **پایه تست پاک** و **تکمیل تجربه کاربری عملیاتی** پنل مدیریت Parsian Music برای کاربران مدیر و منشی.

دو هدف در هم تنیده‌اند:

1. **Test Baseline** — مجموعه تست موجود باید سبز و قابل اتکا اجرا شود تا هر تغییر عملیاتی بعدی قابل تأیید باشد.
2. **Operational UX** — صفحه‌های روزمره پنل (فهرست، جزئیات، ایجاد، ویرایش، عملیات گروهی، فیلتر/جستجو، حالت خالی/خطا/بارگذاری، دیالوگ تأیید، بازخورد flash، صفحه‌بندی، کیبورد، RTL، a11y و رفتار یکسان در دو پوسته) باید کامل شوند تا آموزشگاه واقعاً از پنل قابل اداره باشد.

این سند فقط نیازمندی است. ایجاد یا تغییر `design.md`، `tasks.md` یا کد برنامه در این مرحله مجاز نیست.

## Verified Baseline (وضعیت فعلی راستی‌آزمایی‌شده)

- `php artisan test` در وضعیت فعلی: **۱ تست failed، ۱۸۷ تست passed** (۲۴۷۵ assertion). تست شکست‌خورده `tests/Feature/Admin/StudentHistoryTest.php:146` است که انتظار دیدن رشته `student_history` در خروجی `admin.students.show` را دارد.
- `npm run test:js` در وضعیت فعلی: **۱ تست failed از ۲۸**. `tests/js/properties/filter-scoping.property.test.js` به متغیرهای محیطی `TEST_ADMIN_PHONE` و `TEST_ADMIN_PASSWORD` و به یک سرور در حال اجرا وابسته است و بدون آن‌ها خطا می‌دهد؛ این وابستگی محیطی است، نه نقص منطق دامنه.
- مسیر named `admin.teachers.show` به `TeacherController@show` اشاره می‌کند، اما این متد در `app/Http/Controllers/Admin/TeacherController.php` **وجود ندارد** و view `resources/views/admin/teachers/show.blade.php` نیز وجود ندارد؛ یعنی یک entry point شکسته در پنل.
- `admin.sessions` مسیر named برای `edit`/`update` ندارد و `resources/views/admin/sessions/edit.blade.php` وجود ندارد. مالکیت این شکاف با مشخصه `admin-bulk-selection-actions` است.
- `admin.rooms`، `admin.instruments` و `admin.enrollments` مسیر `show` ندارند؛ جریان عملیاتی آن‌ها فهرست + فرم است.
- دو view موازی تقویم وجود دارد: `resources/views/admin/calendar.blade.php` و `resources/views/admin/calendar/index.blade.php`.
- Authorization: فقط `LeadController` از `$this->authorize(...)` و Policy استفاده می‌کند. `TeacherController`، `StudentController`، `ClassSessionController`، `RoomController`، `InstrumentController`، `StudentEnrollmentController` و `SettingsController` تنها به middleware `role:admin` (و برای settings/users به `role:admin,super_admin`) تکیه دارند، درحالی‌که `TeacherPolicy`، `StudentPolicy`، `SessionPolicy`، `EnrollmentPolicy`، `InvoicePolicy` و `UserPolicy` موجودند.
- Validation در بخشی از کنترلرهای admin به‌صورت inline `$request->validate(...)` انجام می‌شود، نه Form Request.
- بازخورد flash در وضعیت فعلی چندپاره است: بخشی از viewها `x-dashboard.alert-card`، بخشی `admin/partials/flash.blade.php` و بخشی `div` با رنگ hardcode (`bg-emerald-500/10`, `text-red-300`) استفاده می‌کنند.
- کامپوننت‌های موجود قابل استفاده مجدد: `resources/views/components/ui/{table,pagination,button,checkbox,input,select,textarea,form-field,alert,loading-state,skeleton,tooltip}.blade.php`، `components/dashboard/empty-state.blade.php`، `components/admin/{shell,sidebar,topbar,drawer,breadcrumb-area,status-badge}.blade.php`، `components/modal.blade.php` و `admin/partials/sort-th.blade.php`.
- `RoleEnum` فقط شامل `super_admin`، `admin`، `teacher`، `student` است؛ **نقش مستقل «منشی» در سامانه وجود ندارد**. افزودن نقش جدید خارج از دامنه این سند است.
- پوسته‌ها: `data-admin-theme="dark"` (پیش‌فرض) و `"glass"`؛ قواعد Glass فقط زیر `[data-admin-theme="glass"]` در `resources/css/admin/glass.css` و توکن‌ها در `resources/css/admin/tokens.css`، `resources/css/design-tokens.css` و `resources/css/semantic-tokens.css` هستند.
- تست: PHPUnit با testsuiteهای `Unit` و `Feature`، SQLite in-memory. تست JS با `node --test` و `fast-check@4.3.0`. تست مرورگر با Playwright در `tests/browser/`.
- دستورهای تأیید پروژه: `php artisan test`، `npm run build`، `npm run test:js`، `php artisan optimize:clear`.

## Glossary

- **Admin_Panel**: سطح مدیریتی احراز هویت‌شده Laravel زیر prefix `admin/` با layout `layouts/dashboard.blade.php`.
- **Operational_UX**: مجموعه رفتارهای روزمره اداره آموزشگاه در Admin_Panel شامل فهرست، جزئیات، ایجاد، ویرایش، عملیات گروهی، جستجو/فیلتر/مرتب‌سازی، صفحه‌بندی، حالت خالی/بارگذاری/خطا، دیالوگ تأیید، بازخورد flash، ناوبری کیبورد، RTL و دسترسی‌پذیری. شامل بازطراحی بصری نیست.
- **Admin_Role**: نقش `admin` یا `super_admin` در `RoleEnum` که مجوز عملیات مدیریتی دارد.
- **Secretary_Role**: نقش کاربردی «منشی» به‌عنوان persona عملیاتی؛ در وضعیت فعلی هیچ مقدار مستقلی در `RoleEnum` ندارد و به `admin` نگاشت می‌شود. تعریف نقش مجزا خارج از دامنه است، اما مجوزدهی باید Policy-محور بماند تا افزودن نقش در آینده به تغییر UI نیاز نداشته باشد.
- **Operational_Action**: هر عمل حالت‌تغییردهنده مدیریتی شامل create، update، delete، toggle، status change، attach/detach، assign، convert، issue، cancel، duplicate، payment و Bulk_Action.
- **Test_Baseline**: وضعیتی که `php artisan test` و `npm run test:js` و `npm run build` روی commit جاری بدون failure و بدون error پایان می‌یابند و همه تست‌های حذف‌نشده یا passed یا صریحاً skipped با دلیل ثبت‌شده‌اند.
- **Baseline_Gate**: قرارداد تأییدی که هر تغییر این مشخصه باید قبل از اعلام اتمام از آن عبور کند: `php artisan test` + `npm run test:js` + `npm run build`.
- **Flaky_Test**: تستی که با کد و داده ثابت در اجراهای متوالی نتیجه متفاوت می‌دهد یا نتیجه‌اش به منابع خارج از کنترل مجموعه تست (شبکه، سرور در حال اجرا، متغیر محیطی، زمان سیستم، ترتیب اجرا) وابسته است.
- **Quarantined_Test**: تست شناخته‌شده Flaky_Test یا وابسته به محیط که با علامت skip صریح، دلیل خوانا و ارجاع به شکاف مربوط از اجرای پیش‌فرض خارج شده و حذف نشده است.
- **Environment_Dependent_Test**: تستی که برای اجرا به سرویس یا credential خارج از قرارداد پیش‌فرض تست نیاز دارد.
- **Operational_List**: صفحه فهرست مدیریتی یک موجودیت با جستجو/فیلتر، مرتب‌سازی، صفحه‌بندی و ردیف‌های عملیاتی.
- **List_Context**: مجموعه پارامترهای فعال فهرست شامل فیلترها، عبارت جستجو، ستون و جهت مرتب‌سازی و شماره صفحه.
- **Record_Detail**: صفحه جزئیات یک رکورد عملیاتی که داده persisted و اقدام‌های مجاز را نشان می‌دهد.
- **Record_Form**: فرم ایجاد یا ویرایش یک رکورد عملیاتی همراه با validation و بازخورد خطای فیلد.
- **Bulk_Action**: عمل گروهی روی مجموعه‌ای از رکوردهای انتخاب‌شده یک نوع موجودیت در یک درخواست، مطابق قرارداد مشخصه `admin-bulk-selection-actions`.
- **Selection_Set**: مجموعه رکوردهایی از یک نوع موجودیت که کاربر صریحاً برای یک Bulk_Action انتخاب کرده است.
- **Feedback_Channel**: مسیر واحد نمایش نتیجه عملیات به کاربر شامل flash موفق، flash خطا و خطای سطح فیلد.
- **Confirmation_Dialog**: دیالوگ معنایی تأیید برای عملیات مخرب یا غیرقابل بازگشت با `role="dialog"`، `aria-modal="true"`، focus trap و بازگشت focus.
- **Empty_State**: نمایش صریح «داده‌ای وجود ندارد» با تفکیک «هیچ رکوردی ثبت نشده» از «نتیجه‌ای برای این فیلتر نیست».
- **Loading_State**: نمایش وضعیت در حال انجام برای درخواست async یا submit، شامل جلوگیری از ارسال تکراری.
- **Error_State**: نمایش شکست عملیات با پیام قابل فهم و مسیر بازیابی، بدون افشای SQL، stack trace یا داده حساس.
- **Authorization_Layer**: ارزیابی مجوز از طریق Policy/Gate لاراول برای هر Operational_Action، نه چک نقش پراکنده و نه پنهان‌کردن کنترل در UI.
- **Theme_Layer**: دو پوسته `dark` و `glass` پنل مدیریت و توکن‌های آن.
- **Theme_Token_File**: فایل‌های `resources/css/design-tokens.css`، `resources/css/semantic-tokens.css`، `resources/css/admin/tokens.css` و `resources/css/admin/glass.css`.
- **Frozen_Area**: بخشی که مالک محصول تغییر آن را ممنوع کرده است: UI صفحه ورود، UI بصری Teacher Hero و Teacher Profile، و تعریف دو پوسته `dark` و `glass`.
- **Deferred_Scope**: دامنه‌ای که تصمیم اجرای آن به بعد موکول شده و در این مشخصه پیاده نمی‌شود.
- **Superseded_Scope**: دامنه‌ای که مالکیت آن به مشخصه دیگری منتقل شده و در این مشخصه تکرار نمی‌شود.
- **Responsive_Contract**: الزام رفتار درست در عرض‌های ۳۹۰، ۴۳۰، ۷۶۸، ۱۰۲۴، ۱۳۶۶، ۱۶۰۰ و ۱۹۲۰ پیکسل بدون سرریز افقی.
- **A11y_Contract**: الزامات دسترسی‌پذیری شامل ناوبری کامل کیبورد، focus ring مشاهده‌پذیر، `aria-current="page"`، `aria-label` روی دکمه آیکونی، focus trap دیالوگ، کنتراست حداقل WCAG AA و اعلام نتیجه با live region.
- **Verification_Command_Set**: `php artisan test`، `npm run test:js`، `npm run build` و `php artisan optimize:clear`.

## Requirements

### Requirement 1: Clean and Reproducible Test Baseline

**User Story:** به‌عنوان مالک محصول، می‌خواهم مجموعه تست سبز و قابل تکرار باشد تا هر کار عملیاتی بعدی قابل تأیید باشد.

#### Acceptance Criteria

1. WHEN `php artisan test` is executed on the working tree with its bounded CI timeout of 300 seconds, THE Admin_Panel SHALL finish with exit status zero, zero failed tests and zero errored tests.
2. WHEN `npm run test:js` is executed on the working tree with its bounded CI timeout of 300 seconds, THE Admin_Panel SHALL finish with exit status zero, zero failed tests and zero errored tests.
3. WHEN `npm run build` is executed on the working tree with its bounded CI timeout of 300 seconds, THE Admin_Panel SHALL finish with exit status zero and SHALL produce the configured build artifacts.
4. WHEN `php artisan test`, `npm run test:js` or `npm run build` reaches its timeout, THE Admin_Panel SHALL fail the Baseline_Gate and SHALL report the command, timeout and last available output.
5. WHEN the Verification_Command_Set is executed twice in separate clean test processes without source changes, THE Admin_Panel SHALL produce identical passed, failed and skipped counts and SHALL report identical skip identifiers and reasons.
6. THE Admin_Panel SHALL keep the existing PHPUnit `Unit` and `Feature` testsuites, SQLite in-memory connection and `node --test` JavaScript runner as the default test contract.
7. WHEN a test requires persisted data, THE Admin_Panel SHALL create that data inside the test through a factory or idempotent test seeder and SHALL not read pre-existing development data.
8. THE Admin_Panel SHALL isolate database state, filesystem state, environment variables, cookies and browser state for each test so that one test cannot change the result of another test.
9. IF any Baseline_Gate command fails, THEN THE Admin_Panel SHALL mark the task incomplete and SHALL report the command, exit status, failure summary and affected test identifier instead of reporting success.
10. WHEN `php artisan optimize:clear` is executed before verification, THE Admin_Panel SHALL clear stale framework caches and the Verification_Command_Set SHALL produce the same result as a run from a clean cache state.
11. THE Admin_Panel SHALL treat a passing Baseline_Gate as the completion condition for every task in this specification.

### Requirement 2: Repair of Identified Broken Behavior

**User Story:** به‌عنوان مدیر، می‌خواهم نقص‌های شناسایی‌شده فعلی برطرف شوند تا تست‌های سبز واقعاً رفتار درست را نشان دهند.

#### Acceptance Criteria

1. WHEN an Admin_Role user requests `admin.teachers.show` for an existing Teacher, THE Admin_Panel SHALL return HTTP 200 and SHALL render the teacher name, status, persisted profile values and related operational data required by the teacher detail contract.
2. IF an Admin_Role user requests `admin.teachers.show` for a nonexistent Teacher identifier, THEN THE Admin_Panel SHALL return HTTP 404 and SHALL render the shared not-found Error_State.
3. WHEN an Admin_Role user requests `admin.students.show` for an existing Student with history entries, THE Admin_Panel SHALL render a detail section with the stable machine-readable identifier `student_history` and SHALL render the persisted history entries in deterministic order.
4. WHEN an Admin_Role user requests `admin.students.show` for an existing Student without history entries, THE Admin_Panel SHALL return HTTP 200 and SHALL render the shared Empty_State for an empty student history.
5. THE Admin_Panel SHALL expose exactly one rendered calendar view target for the named route `admin.calendar.index`, SHALL use one canonical view path and SHALL remove or repurpose the unused parallel calendar view so that no unreferenced duplicate view remains.
6. WHEN an authorized actor resolves a named route in the `admin.` namespace with valid parameters, THE Admin_Panel SHALL map that route to an existing controller method and an existing view or an explicitly documented redirect/response target.
7. WHEN an authorized actor invokes an `admin.` route with a nonexistent model identifier, THE Admin_Panel SHALL return the documented HTTP 404 response and SHALL not return a generic HTTP 500 response.
8. THE Admin_Panel SHALL cover every named route in the `admin.` namespace with at least one automated test asserting its documented HTTP or redirect status for an authorized actor.
9. FOR ALL named routes in the `admin.` namespace, THE Admin_Panel SHALL respond without an unhandled exception when invoked by an authorized actor with valid parameters.

### Requirement 3: Flaky and Environment-Dependent Test Handling

**User Story:** به‌عنوان توسعه‌دهنده، می‌خواهم تست‌های ناپایدار یا وابسته به محیط به‌صورت شفاف مدیریت شوند تا اجرای پیش‌فرض قابل اتکا باشد.

#### Acceptance Criteria

1. WHEN a test produces different results in any three consecutive runs with unchanged code and data, THE Admin_Panel SHALL classify that test as a Flaky_Test and SHALL either remove the nondeterminism or convert the test into a Quarantined_Test.
2. WHEN a test depends on network access, a running HTTP server, credentials, system time or execution order, THE Admin_Panel SHALL classify that dependency as Flaky_Test or Environment_Dependent_Test and SHALL record the dependency in the test metadata or skip reason.
3. WHEN a test is converted into a Quarantined_Test, THE Admin_Panel SHALL preserve the test file, use an explicit skip marker, record a human-readable reason of at most 200 characters and reference the exact open gap or issue identifier.
4. WHERE `tests/js/properties/filter-scoping.property.test.js` requires `TEST_ADMIN_PHONE`, `TEST_ADMIN_PASSWORD` and a running HTTP server, THE Admin_Panel SHALL skip the test with an explicit environment reason when any requirement is absent and SHALL execute the test when all requirements are present.
5. THE Admin_Panel SHALL preserve every assertion in a test that exercises a Frozen_Area and SHALL not weaken, delete or broaden that test's matchers to obtain a passing result.
6. THE Admin_Panel SHALL include stable quarantine identifiers, skip counts, reasons and gap references in default test output so that two unchanged runs expose the same quarantine inventory.
7. FOR ALL executed tests, THE Admin_Panel SHALL produce the same result when the tests are run in either order or in an isolated full-suite order.
8. WHEN the dependency or nondeterminism that caused a Quarantined_Test is removed and the test passes in three consecutive runs, THE Admin_Panel SHALL un-quarantine the test and SHALL return it to the default test run.

### Requirement 4: Test Data Support for Admin Flows

**User Story:** به‌عنوان توسعه‌دهنده، می‌خواهم factory و seeder همه جریان‌های مدیریتی را پوشش دهند تا نوشتن تست عملیاتی ممکن باشد.

#### Acceptance Criteria

1. THE Admin_Panel SHALL provide factories for `User`, `Teacher`, `Student`, `Instrument`, `TeacherInstrument`, `Enrollment`, `ClassSession`, `Attendance`, `Room`, `Subscription`, `Invoice`, `InvoiceItem`, `InvoicePayment` and `Lead`.
2. WHEN a factory creates a record without explicit overrides, THE Admin_Panel SHALL produce a persistable record satisfying database constraints and the validation contract of the corresponding Record_Form.
3. WHERE a model has an Enum-backed status column, THE Admin_Panel SHALL expose one factory state for every value in that Enum, including every state in `StudentStatusEnum`, `TeacherStatusEnum`, `EnrollmentStatusEnum`, `SessionStatusEnum`, `AttendanceStatusEnum`, `InvoiceStatusEnum`, `PaymentStatusEnum`, `LeadStatusEnum` and `RoleEnum` where applicable.
4. WHEN a test creates an authorized actor, THE Admin_Panel SHALL provide factory states for every `RoleEnum` value: `super_admin`, `admin`, `teacher` and `student`.
5. THE Admin_Panel SHALL provide factory states for a record with a required parent, for a record with an existing parent supplied by the test and for a record whose deletion is blocked by at least one dependency.
6. WHEN a required parent is not supplied, THE Admin_Panel SHALL create that parent through its factory; WHEN a required parent is supplied, THE Admin_Panel SHALL reuse the supplied parent and SHALL not create a duplicate parent.
7. WHEN a factory state requests deletion dependencies, THE Admin_Panel SHALL create the minimum valid dependent records needed to exercise the blocked-deletion path, and WHEN the independent state is requested, THE Admin_Panel SHALL create no deletion-blocking dependency.
8. WHEN a test seeder is executed twice against the same database state, THE Admin_Panel SHALL produce the same record set without duplicate-key failure or unbounded record growth.
9. WHEN a factory is requested with a count of 25, THE Admin_Panel SHALL create exactly 25 records with valid, non-conflicting required attributes.
10. THE Admin_Panel SHALL keep factory defaults deterministic for identifiers and relationships where the test does not explicitly request random data.

### Requirement 5: Consistent Operational List Behavior

**User Story:** به‌عنوان منشی، می‌خواهم همه فهرست‌های پنل رفتار یکسان جستجو، فیلتر، مرتب‌سازی و صفحه‌بندی داشته باشند تا کار روزمره قابل پیش‌بینی باشد.

#### Acceptance Criteria

1. THE Admin_Panel SHALL provide search or filter controls, sortable column controls and pagination on every Operational_List of teachers, students, sessions, enrollments, rooms, instruments, invoices, leads and users.
2. THE Admin_Panel SHALL use a default page size of 20 records for every Operational_List and SHALL expose the applied page size in the server-side list contract.
3. WHEN a search term is submitted, THE Admin_Panel SHALL normalize leading and trailing whitespace, normalize equivalent Persian and Arabic characters and limit the normalized term to 100 characters before applying the search.
4. WHEN a normalized search term or filter is submitted, THE Admin_Panel SHALL apply that value on the server and SHALL return only records matching the resulting List_Context.
5. THE Admin_Panel SHALL restrict sorting to a server-side whitelist of sortable columns for each Operational_List, SHALL accept only `asc` or `desc` direction and SHALL fall back to the defined default column and direction for an invalid column or direction.
6. THE Admin_Panel SHALL append a stable unique-record-key tie-breaker to every list order so that records with equal primary sort values have deterministic order across requests and pages.
7. WHEN pagination is navigated, THE Admin_Panel SHALL preserve every active filter, normalized search term, sort column, sort direction and page-size value in the resulting request.
8. WHEN a filter or search control is rendered, THE Admin_Panel SHALL display the currently applied normalized value of that control.
9. WHEN at least one filter or search value is applied, THE Admin_Panel SHALL expose a control that clears every applied filter and search value and returns the Operational_List to its default List_Context.
10. IF a page number is missing, non-numeric, below 1 or greater than the last available page, THEN THE Admin_Panel SHALL return the nearest valid page or an empty page with HTTP 200 and SHALL preserve the other valid List_Context values.
11. IF a filter value is not in the server-defined filter set or has an invalid type, THEN THE Admin_Panel SHALL ignore that filter, use the documented default and SHALL not interpolate the value into a query.
12. THE Admin_Panel SHALL render the total matching record count for the applied List_Context on every Operational_List.
13. FOR ALL applied List_Context values, THE Admin_Panel SHALL render each matching record on exactly one page of the paginated result and SHALL render no record that fails the applied List_Context.
14. FOR ALL applied List_Context values, THE Admin_Panel SHALL produce the same record set and the same order when the same List_Context is submitted again.
15. WHEN an Operational_List renders related data, THE Admin_Panel SHALL eager-load the required relations before rendering and SHALL execute no database query inside the Blade layer.
16. WHEN an Operational_List contains up to 100 records matching the default page size and relation contract, THE Admin_Panel SHALL return the server response within 2 seconds in the project test environment, excluding network transfer time.

### Requirement 6: Detail, Create and Edit Flows

**User Story:** به‌عنوان مدیر، می‌خواهم برای هر موجودیت عملیاتی مسیر کامل مشاهده، ایجاد و ویرایش داشته باشم تا نیازی به دسترسی مستقیم به پایگاه‌داده نباشد.

#### Acceptance Criteria

1. THE Admin_Panel SHALL provide a Record_Detail screen for teacher, student, invoice and lead, and SHALL provide list-and-form flows for teacher, student, instrument, room, enrollment, invoice, lead and user.
2. WHERE an entity is intentionally list-and-form only, THE Admin_Panel SHALL not require or invent a separate detail route; this applies to rooms, instruments and enrollments.
3. WHEN an Operational_List row is rendered, THE Admin_Panel SHALL expose only the row entry points and destructive actions that the current actor is authorized to perform.
4. WHEN a Record_Form is submitted with valid input, THE Admin_Panel SHALL authorize the action, persist the record and redirect to the documented Record_Detail or Operational_List target with a success message in the Feedback_Channel.
5. THE Admin_Panel SHALL validate every Record_Form submission through a Form Request or equivalent server-side validation contract before any write occurs, including required fields, type constraints, maximum lengths, numeric bounds, enum membership, relationship existence and date ordering bounds.
6. WHEN a Record_Form receives valid input containing surrounding whitespace or accepted Persian/Arabic equivalent characters, THE Admin_Panel SHALL normalize those values before persistence and SHALL display the normalized persisted values on the edit form.
7. IF a Record_Form is submitted with invalid input, THEN THE Admin_Panel SHALL return the form with submitted values retained and SHALL render a localized error message associated with each invalid field.
8. IF a Record_Form submission targets a nonexistent record, THEN THE Admin_Panel SHALL return HTTP 404, SHALL render the shared not-found Error_State and SHALL leave every persisted record unchanged.
9. IF a Record_Form submission fails validation or authorization, THEN THE Admin_Panel SHALL leave every persisted record unchanged.
10. WHEN an Operational_Action changes more than one persisted record or changes a record and its required relations, THE Admin_Panel SHALL perform all writes inside one database transaction and SHALL roll back every write when any step fails.
11. WHEN a Record_Detail screen renders a value, THE Admin_Panel SHALL render only persisted data of that record and SHALL render a localized placeholder for an absent value.
12. THE Admin_Panel SHALL keep class-session edit and update behavior owned by `admin-bulk-selection-actions` and SHALL not add duplicate class-session form, route or persistence behavior in this specification.
13. FOR ALL valid Record_Form submissions, THE Admin_Panel SHALL produce a persisted record whose stored field values equal the submitted values after normalization, and re-rendering the edit form SHALL display those stored values.

### Requirement 7: Empty, Loading and Error States

**User Story:** به‌عنوان منشی، می‌خواهم در نبود داده، هنگام انتظار و در زمان خطا پیام روشن ببینم تا ندانم‌کاری نکنم.

#### Acceptance Criteria

1. THE Admin_Panel SHALL implement Empty_State, Loading_State and Error_State through the shared `components/dashboard/empty-state`, `components/ui/loading-state` and shared alert/error component variants rather than per-page state markup.
2. WHEN an Operational_List returns no record and no filter or search value is applied, THE Admin_Panel SHALL render an Empty_State stating that no record exists and SHALL expose the authorized create entry point.
3. WHEN an Operational_List returns no record while at least one filter or search value is applied, THE Admin_Panel SHALL render an Empty_State stating that no record matches the applied List_Context and SHALL expose the clear-filters control.
4. WHILE a form submission or asynchronous operational request is in progress, THE Admin_Panel SHALL render a Loading_State within 200 milliseconds of the request start, SHALL disable the triggering control and SHALL prevent a second submission of the same request.
5. IF an asynchronous operational request exceeds its configured timeout, THEN THE Admin_Panel SHALL remove the Loading_State, re-enable the triggering control and render an Error_State with a retry or return path.
6. IF an Operational_Action fails because of a server error, THEN THE Admin_Panel SHALL render an Error_State with a localized message and a retry or return path.
7. IF an Operational_Action fails, THEN THE Admin_Panel SHALL exclude SQL text, stack traces, file paths, credentials, authorization tokens and personal contact data from the rendered message.
8. WHEN an asynchronous data request fails, THE Admin_Panel SHALL preserve the last successfully rendered persisted data and SHALL not replace it with fabricated placeholder records.
9. IF an actor is unauthenticated or unauthorized for an Operational_List request, THEN THE Admin_Panel SHALL render no protected records, SHALL return the documented HTTP 401 or 403 response and SHALL not expose a misleading successful empty list.
10. WHEN a request completes successfully or with an error, THE Admin_Panel SHALL replace the Loading_State with exactly one corresponding success or Error_State and SHALL prevent stale responses from overwriting newer state.

### Requirement 8: Unified Feedback and Confirmation

**User Story:** به‌عنوان مدیر، می‌خواهم نتیجه هر عمل را یکسان و قابل اعتماد ببینم و عملیات مخرب بدون تأیید اجرا نشود.

#### Acceptance Criteria

1. THE Admin_Panel SHALL render operational success, failure and validation feedback through exactly one shared Feedback_Channel component across every admin screen.
2. WHEN an Operational_Action succeeds, THE Admin_Panel SHALL render a localized success message between 10 and 160 characters that names the affected entity and the performed action.
3. WHEN an Operational_Action fails, THE Admin_Panel SHALL render a localized failure message between 10 and 200 characters that names the failed action or recovery path without exposing sensitive implementation details.
4. THE Admin_Panel SHALL expose success feedback with `role="status"`, failure feedback with `role="alert"` and field validation feedback through an accessible association with the invalid field.
5. WHILE a Feedback_Channel message is visible, THE Admin_Panel SHALL keep it visible for at least 4 seconds and SHALL provide a dismiss control; a validation error SHALL remain visible until correction or navigation.
6. WHEN an actor invokes delete, detach, cancel, irreversible status change or another explicitly destructive Operational_Action, THE Admin_Panel SHALL open a Confirmation_Dialog before sending the request.
7. THE Admin_Panel SHALL include the affected entity name, the destructive action, the consequence and the explicit confirm and cancel actions in the Confirmation_Dialog.
8. WHEN an actor cancels or closes a Confirmation_Dialog, THE Admin_Panel SHALL send no request and SHALL leave every persisted record unchanged.
9. WHEN an actor confirms a Confirmation_Dialog, THE Admin_Panel SHALL send exactly one request for the confirmed action and SHALL disable the confirmation control until the response completes.
10. WHEN a newer request completes, THE Admin_Panel SHALL discard older pending feedback so that stale success or failure messages cannot replace the newest result.
11. THE Admin_Panel SHALL render the Feedback_Channel and Confirmation_Dialog using Design System tokens and existing component variants, without inline style attributes, inline event handlers, hardcoded color values or inline confirmation logic.

### Requirement 9: Bulk Selection Integration

**User Story:** به‌عنوان منشی، می‌خواهم عملیات گروهی در فهرست‌ها با همان قواعد فیلتر و مجوز کار کند تا کارهای تکراری سریع و بی‌خطر انجام شود.

#### Acceptance Criteria

1. THE Admin_Panel SHALL treat `admin-bulk-selection-actions` as the sole owner of bulk selection, bulk execution, bulk result and bulk audit behavior, and SHALL define here only integration with Operational_List and shared invariants.
2. WHEN an Operational_List renders bulk selection controls, THE Admin_Panel SHALL use the shared bulk selection component and request contract from `admin-bulk-selection-actions` rather than a per-page duplicate.
3. WHEN List_Context changes by search, filter, sort, page or entity change, THE Admin_Panel SHALL clear the Selection_Set for that Operational_List before a Bulk_Action can be submitted.
4. WHEN a Bulk_Action request is received, THE Authorization_Layer SHALL resolve stale or missing record identifiers, evaluate the corresponding Policy ability for every resolved record and complete authorization before any record is changed.
5. FOR ALL Selection_Set values, THE Admin_Panel SHALL keep the rendered selected count equal to the number of distinct selectable records in the Selection_Set and SHALL not count stale or duplicate identifiers.
6. FOR ALL Bulk_Action requests, THE Admin_Panel SHALL report a result whose succeeded, skipped and failed counts sum exactly to the resolved selection size, including stale records in the documented failed or skipped category.
7. IF the Selection_Set is empty, THEN THE Admin_Panel SHALL send no mutation request and SHALL render the shared empty-selection feedback.
8. FOR ALL Bulk_Action requests in which no record satisfies the action precondition, THE Admin_Panel SHALL leave every persisted record unchanged.
9. FOR ALL Bulk_Action requests, applying the same request twice with the same Selection_Set and the same persisted state SHALL produce the same resulting persisted state as applying it once.
10. IF at least one record in the Selection_Set is not authorized for the requested action, THEN THE Admin_Panel SHALL leave that record unchanged and SHALL classify it as failed with an authorization reason.
11. IF a selected record was deleted or is no longer available before execution, THEN THE Admin_Panel SHALL leave the remaining records governed by the request contract and SHALL report the stale record without changing an unrelated record.

### Requirement 10: Policy-Based Authorization for Every Operational Action

**User Story:** به‌عنوان مالک سامانه، می‌خواهم هر عمل مدیریتی از Policy/Gate عبور کند تا کنترل دسترسی قابل ممیزی و قابل توسعه باشد.

#### Acceptance Criteria

1. WHEN an Operational_Action is requested, THE Authorization_Layer SHALL evaluate a named Policy or Gate ability for that action before validation of business input completes and before any persisted record is changed.
2. THE Admin_Panel SHALL provide named `viewAny`, `view`, `create`, `update` and `delete` abilities for teacher, student, class session, enrollment, room, instrument, invoice, lead and user.
3. THE Admin_Panel SHALL provide a named ability for every non-CRUD Operational_Action exposed by those entities, including status change, attach/detach, assign, convert, issue, cancel, duplicate, payment and each bulk action.
4. THE Admin_Panel SHALL cover every state-changing `admin.` route with an automated test that supplies an authorized actor, invokes the route and asserts the named ability was evaluated before mutation.
5. IF an actor lacks the required ability for an Operational_Action, THEN THE Admin_Panel SHALL return HTTP 403 and SHALL leave every persisted record unchanged.
6. IF a request has no authenticated actor, THEN THE Admin_Panel SHALL return HTTP 401 or the documented login redirect and SHALL leave every persisted record unchanged.
7. IF a state-changing request has a missing or invalid CSRF token, THEN THE Admin_Panel SHALL reject the request with the documented CSRF response and SHALL leave every persisted record unchanged.
8. THE Admin_Panel SHALL keep role middleware as a coarse boundary and SHALL not compare role strings in controller bodies, Blade bodies or JavaScript bodies; per-record permission SHALL come from the Authorization_Layer.
9. WHEN an actor lacks the ability for an Operational_Action, THE Admin_Panel SHALL omit or disable the corresponding UI control and SHALL still reject a directly submitted request for that action.
10. THE Admin_Panel SHALL resolve Secretary_Role permissions through the Authorization_Layer and SHALL map the current persona to `admin` without adding a new `RoleEnum` value; introducing a distinct secretary role later SHALL require no change to admin view templates.
11. FOR ALL Operational_Action requests submitted by an unauthorized actor, THE Admin_Panel SHALL produce a forbidden or unauthenticated response and an unchanged database state.

### Requirement 11: Keyboard, Focus and Accessibility

**User Story:** به‌عنوان کاربر پنل، می‌خواهم همه کارها با کیبورد و صفحه‌خوان قابل انجام باشد تا استفاده روزمره خسته‌کننده و ناممکن نباشد.

#### Acceptance Criteria

1. THE Admin_Panel SHALL make every interactive operational control reachable and operable using the keyboard alone in DOM order matching the visual reading order.
2. WHEN a focused interactive control is activated with Enter or Space according to its native semantics, THE Admin_Panel SHALL perform the same action as pointer activation without requiring a pointer event.
3. THE Admin_Panel SHALL render a visible focus indicator on every focused interactive control with at least 3:1 contrast against adjacent colors and a focus area not less than 2 CSS pixels in its thinnest dimension.
4. THE Admin_Panel SHALL use `<a>` elements for navigation targets and `<button>` elements for actions, and SHALL render native form controls for checkbox, select and text input semantics.
5. THE Admin_Panel SHALL set `aria-current="page"` on the navigation item matching the active admin route and SHALL not set that value on an inactive item.
6. THE Admin_Panel SHALL provide an accessible name for every icon-only control through visible text or `aria-label`.
7. WHEN a Confirmation_Dialog or drawer opens, THE Admin_Panel SHALL expose `role="dialog"` and `aria-modal="true"`, SHALL link the dialog to its heading, SHALL trap focus inside the dialog, SHALL close on Escape and SHALL return focus to the invoking control on close.
8. WHEN an Operational_Action result is rendered without a full page reload, THE Admin_Panel SHALL announce the result through a live region without moving focus unexpectedly.
9. THE Admin_Panel SHALL meet at least the WCAG AA contrast ratio of 4.5:1 for normal text, 3:1 for large text and 3:1 for operational control borders and focus indicators in both Theme_Layer themes.
10. THE Admin_Panel SHALL render one `h1` per admin screen and SHALL keep heading levels in descending order without skipping a level.
11. WHILE the user agent reports `prefers-reduced-motion: reduce`, THE Admin_Panel SHALL suppress non-essential motion and SHALL preserve every operational state transition.

### Requirement 12: RTL and Responsive Behavior

**User Story:** به‌عنوان کاربر فارسی‌زبان، می‌خواهم پنل در همه اندازه‌های صفحه و در چیدمان راست‌به‌چپ درست کار کند تا روی لپ‌تاپ و موبایل قابل استفاده باشد.

#### Acceptance Criteria

1. THE Admin_Panel SHALL render every operational screen with `dir="rtl"` document direction and SHALL express directional spacing, borders and positioning through logical CSS properties.
2. FOR EACH viewport width of 390, 430, 768, 1024, 1366, 1600 and 1920 CSS pixels, THE Admin_Panel SHALL render every operational screen with document scroll width no more than 1 CSS pixel greater than the viewport width.
3. WHILE the viewport width is below 768 pixels, THE Admin_Panel SHALL render each Operational_List as stacked records or an equivalent responsive record layout that presents every displayed field without horizontal document overflow.
4. WHERE the pointer is coarse, THE Admin_Panel SHALL render every interactive operational control with a hit area of at least 44 by 44 CSS pixels.
5. THE Admin_Panel SHALL constrain operational content containers to a maximum width of 1600 CSS pixels so that content remains readable at 1920 pixels.
6. WHEN a table must scroll horizontally inside its own container, THE Admin_Panel SHALL keep keyboard and pointer scrolling inside that container and SHALL keep document scroll width within the 1 CSS pixel overflow bound.
7. THE Admin_Panel SHALL render numeric and date values with an explicit direction so that digits, decimal separators and date separators keep their intended order in an RTL context.
8. WHEN a displayed value exceeds its available inline size, THE Admin_Panel SHALL wrap, truncate with an accessible full-value alternative or otherwise contain the value without changing document width.
9. THE Admin_Panel SHALL keep dropdowns, tooltips, drawers, dialogs and other overlays fully within the viewport or provide an internal scroll area at every Responsive_Contract width.

### Requirement 13: Theme Parity Without Theme Redesign

**User Story:** به‌عنوان مالک محصول، می‌خواهم صفحه‌های تکمیل‌شده در هر دو پوسته درست کار کنند بی‌آنکه پوسته‌ها بازطراحی شوند.

#### Acceptance Criteria

1. THE Admin_Panel SHALL render every completed operational screen correctly under `data-admin-theme="dark"` and under `data-admin-theme="glass"` at every width in the Responsive_Contract.
2. THE Admin_Panel SHALL style new or completed operational screens using existing Design System tokens and existing component variants, and SHALL not duplicate component-level theme rules per page.
3. THE Admin_Panel SHALL keep every Theme_Token_File byte-identical in token names, token values and theme selector structure unless an explicit product-owner approval records a token change as a separate decision.
4. IF a completed operational screen needs a visual value that no existing token provides, THEN THE Admin_Panel SHALL report the gap for a separate decision and SHALL not introduce a hardcoded color, radius, font or spacing value.
5. WHEN the theme toggle switches the active theme, THE Admin_Panel SHALL keep the current screen operable, SHALL persist the selected theme in the existing cookie contract and SHALL preserve every applied List_Context.
6. IF the theme cookie contains an unsupported value, THEN THE Admin_Panel SHALL fall back to `dark`, SHALL rewrite or ignore the invalid cookie and SHALL render the screen without an exception.
7. THE Admin_Panel SHALL add no `!important` declaration outside the existing `[x-cloak]` exception.
8. FOR ALL completed operational screens, THE Admin_Panel SHALL render every text, control, overlay and feedback layer visible, legible and non-overlapping in both Theme_Layer themes at every width in the Responsive_Contract.
9. WHEN a glass overlay is rendered over a glass surface, THE Admin_Panel SHALL preserve readable text and control contrast at WCAG AA thresholds without adding an unapproved visual token.

### Requirement 14: Frozen Area Preservation

**User Story:** به‌عنوان مالک محصول، می‌خواهم بخش‌های frozen دست‌نخورده بمانند تا کار عملیاتی به بازطراحی بصری سرریز نکند.

#### Acceptance Criteria

1. THE Admin_Panel SHALL treat the following exact paths as the frozen preservation set: `resources/views/auth/login.blade.php`, `resources/views/components/auth/`, `resources/css/teacher/hero.css`, `resources/css/teacher/biography.css`, `resources/views/components/ui/teacher/`, `resources/css/design-tokens.css`, `resources/css/semantic-tokens.css`, `resources/css/admin/tokens.css` and `resources/css/admin/glass.css`.
2. THE Admin_Panel SHALL keep the frozen files byte-identical to the approved baseline, including typography, color, background, layout, glass treatment, spacing, token values and selector structure.
3. WHERE a login change is required by a functional defect or a security defect, THE Admin_Panel SHALL limit that change to the defective behavior and SHALL require explicit product-owner approval before changing any frozen presentation bytes.
4. THE Admin_Panel SHALL keep the Teacher Hero and Teacher Profile visual implementation unchanged and SHALL treat the Teacher Hero visual phase as Deferred_Scope.
5. WHEN a preservation test detects a missing, renamed, moved or byte-different frozen path, THE Admin_Panel SHALL fail the preservation gate and SHALL report the exact path and difference.
6. THE Admin_Panel SHALL add preservation checks that fail when a completed operational change alters the frozen cascade, theme selector structure or protected visual implementation indirectly.
7. WHEN an explicit approval exception is granted, THE Admin_Panel SHALL record the exact path, approved byte change, reason and approval reference before applying the change; an unrecorded exception SHALL fail validation.
8. FOR ALL changes delivered under this specification, THE Admin_Panel SHALL keep every frozen path unchanged except for an approved exception and SHALL update the approved baseline only after the same explicit approval is recorded.

### Requirement 15: Scope Reconciliation With Existing Specifications

**User Story:** به‌عنوان مالک محصول، می‌خواهم مرز این مشخصه با مشخصه‌های موجود روشن باشد تا کار تکراری و متناقض تولید نشود.

#### Acceptance Criteria

1. THE Admin_Panel SHALL classify every requested work item as owned here, Superseded_Scope or Deferred_Scope before implementation and SHALL record that classification per specification or roadmap item.
2. THE Admin_Panel SHALL treat bulk selection, bulk execution, session editing, real calendar data, session notes and real room contracts as Superseded_Scope owned by `admin-bulk-selection-actions` and SHALL consume that contract rather than redefining it.
3. THE Admin_Panel SHALL treat calendar rendering behavior as Superseded_Scope owned by `admin-calendar-module` and SHALL limit calendar work here to Baseline_Gate compliance and Operational_UX integration.
4. THE Admin_Panel SHALL treat scheduling domain stabilization as Superseded_Scope owned by `scheduling-phase1-stabilization` and `scheduling-stabilization`.
5. THE Admin_Panel SHALL treat settings module behavior as Superseded_Scope owned by `admin-settings` and `admin-settings-module`, lead management UI as Superseded_Scope owned by `crm-ui-lead-management`, admin shell layout as Superseded_Scope owned by `admin-shell-layout-fix`, and demo/sample data as Superseded_Scope owned by `demo-data-system`.
6. THE Admin_Panel SHALL preserve existing passing tests for every Superseded_Scope owner and SHALL not duplicate its routes, persistence contract or visual implementation in this specification.
7. THE Admin_Panel SHALL treat the exact visual roadmap items `teacher-hero-visual-foundation`, `teacher-profile-page`, `ui-phase-5-premium-dashboard` and `ui-phase-5a-premium-ui` as Deferred_Scope.
8. THE Admin_Panel SHALL treat every visual redesign roadmap item, new notification-provider architecture, mobile API contract and mobile workflow as Deferred_Scope or excluded scope, and SHALL include no Priority 3, Priority 4 or Priority 5 work in this specification.
9. IF an existing specification task conflicts with a Frozen_Area or an owned contract, THEN THE Admin_Panel SHALL classify that task as Deferred_Scope or Superseded_Scope, SHALL report the conflict and SHALL not execute the task here.
10. WHEN an item has both a baseline-integration portion and an owned or deferred portion, THE Admin_Panel SHALL classify and verify each portion separately rather than treating the entire item as owned here.

### Requirement 16: Performance and Verification Discipline

**User Story:** به‌عنوان توسعه‌دهنده، می‌خواهم تکمیل عملیاتی باعث افت کارایی یا کد رهاشده نشود تا نگهداری ممکن بماند.

#### Acceptance Criteria

1. WHEN an Operational_List or Record_Detail renders related records, THE Admin_Panel SHALL execute a bounded number of database queries whose count does not grow with the number of rendered rows, and SHALL assert that bound in an automated test.
2. THE Admin_Panel SHALL build every database query through Eloquent or the query builder with bound parameters and SHALL contain no raw concatenated query string.
3. THE Admin_Panel SHALL keep business logic out of Blade templates and JavaScript modules and SHALL place that logic in controllers, services or actions with automated coverage for the resulting behavior.
4. THE Admin_Panel SHALL contain no `dd`, `dump`, `var_dump`, `print_r`, `console.log`, commented-out code block, dead code, unreferenced view, unused CSS rule or unreferenced JavaScript module introduced by this work.
5. WHEN a Blade template or configuration file changes, THE Admin_Panel SHALL remain correct after `php artisan optimize:clear` and SHALL not depend on stale compiled views or cached configuration.
6. WHEN the default teacher, student, session, enrollment, room, instrument, invoice, lead or user Operational_List is requested with its default List_Context, THE Admin_Panel SHALL return the first response within 2 seconds in the project test environment, excluding network transfer time.
7. WHEN a task of this specification is reported complete, THE Admin_Panel SHALL have passed every command in the Verification_Command_Set on the resulting working tree.
8. IF any Verification_Command_Set command fails, THEN THE Admin_Panel SHALL report the command, exit status, first actionable failure and affected file or test identifier and SHALL not mark the task complete.

## Correctness Properties for Property-Based Testing

خواص زیر کاندیدای تست ویژگی‌محور هستند و در فاز design رسمی و به property با تگ تبدیل می‌شوند.

1. **Pagination and filter round trip** — برای هر List_Context معتبر، اجتماع رکوردهای همه صفحه‌ها برابر مجموعه رکوردهای منطبق است و هیچ رکوردی در دو صفحه ظاهر نمی‌شود. (پشتیبان: 5.7، 5.13، 5.14)
2. **Filter monotonicity** — افزودن هر فیلتر معتبر به List_Context تعداد نتایج را افزایش نمی‌دهد. (پشتیبان: 5.4، 5.13)
3. **Sort whitelist invariant** — برای هر ستون مرتب‌سازی درخواستی، ستون اعمال‌شده عضو whitelist سرور است و جهت اعمال‌شده `asc` یا `desc` است. (پشتیبان: 5.5)
4. **Selection set size invariant** — اندازه شمارنده نمایش‌داده‌شده برابر تعداد شناسه‌های یکتا و قابل انتخاب در Selection_Set است. (پشتیبان: 9.5)
5. **Bulk result conservation** — مجموع succeeded، skipped و failed برابر اندازه selection حل‌شده است. (پشتیبان: 9.6)
6. **No-op bulk idempotence** — اجرای دوباره همان Bulk_Action روی همان Selection_Set و همان حالت persisted، حالت persisted را تغییر نمی‌دهد. (پشتیبان: 9.8)
7. **Authorization holds for every selected record** — برای هر Selection_Set، هر رکورد تغییریافته مجوز Policy مربوط را داشته است. (پشتیبان: 9.4، 9.10، 10.5، 10.11)
8. **Unauthorized non-mutation** — برای هر عمل و هر actor بدون مجوز یا بدون CSRF معتبر، snapshot پایگاه‌داده قبل و بعد یکسان است. (پشتیبان: 10.5، 10.6، 10.7، 10.11)
9. **Form round trip** — برای هر ورودی معتبر Record_Form، مقادیر ذخیره‌شده پس از normalization با ورودی برابرند و فرم ویرایش همان مقادیر را نمایش می‌دهد. (پشتیبان: 6.6، 6.13)
10. **Validation atomicity** — برای هر ورودی نامعتبر، هیچ رکوردی تغییر نمی‌کند. (پشتیبان: 6.7، 6.9، 6.10)
11. **Factory persistability** — هر factory بدون override رکورد قابل ذخیره تولید می‌کند. (پشتیبان: 4.2)
12. **Seeder idempotence** — اجرای دوباره seeder تستی مجموعه رکورد یکسان و بدون خطای کلید تکراری تولید می‌کند. (پشتیبان: 4.8)
13. **No horizontal overflow** — برای هر عرض Responsive_Contract و هر پوسته، عرض scroll سند حداکثر یک CSS pixel بیشتر از عرض viewport است. (پشتیبان: 12.2، 13.1، 13.8)
14. **Frozen area immutability** — برای هر تغییر تحویل‌شده، فایل‌های Frozen_Area بدون تغییر می‌مانند مگر با استثنای دارای approval صریح. (پشتیبان: 14.2، 14.7، 14.8)
15. **Test order independence** — برای هر ترتیب اجرای تست‌ها، نتیجه هر تست ثابت است. (پشتیبان: 1.5، 1.8، 3.7)
