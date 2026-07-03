# Requirements Document

## Introduction

این ویژگی یک ماژول مدیریت پرداخت شهریه (MVP) به پنل مدیریتی آموزشگاه پارسیان موزیک اضافه می‌کند. هر پرداخت به یک ثبت‌نام مشخص (`student_enrollment_id`) مرتبط است و شامل مبلغ کل شهریه، تخفیف، مبلغ پرداخت‌شده، بدهی باقی‌مانده (محاسبه‌شده)، تاریخ پرداخت (شمسی) و روش پرداخت است. یک بخش خلاصه‌ی مالی نیز به صفحه‌ی نمایش هنرجو اضافه می‌شود که کل بدهی، کل پرداختی و تاریخ آخرین پرداخت را نشان می‌دهد. این نسخه محدود به عملیات CRUD کامل روی پرداخت‌ها و نمایش خلاصه‌ی مالی است؛ یکپارچه‌سازی با تایم‌لاین تاریخچه‌ی هنرجو، درگاه پرداخت آنلاین، و گزارش‌های مالی پیشرفته خارج از محدوده‌ی این فاز هستند.

## Glossary

- **Payment**: رکورد پرداخت شهریه مرتبط با یک ثبت‌نام (جدول `payments`).
- **PaymentModule**: مجموعه‌ی کنترلر، مدل، درخواست‌های اعتبارسنجی و ویوهای مرتبط با مدیریت پرداخت‌ها.
- **PaymentController**: کنترلر پنل مدیریتی که عملیات CRUD روی Payment را پیاده‌سازی می‌کند.
- **Payment_Method**: روش پرداخت یک Payment، یکی از مقادیر `cash` (نقدی)، `card` (کارت)، `bank_transfer` (انتقال بانکی).
- **Payment_Status**: وضعیت محاسبه‌شده (نه ذخیره‌شده) یک Payment، یکی از مقادیر `fully_paid` (پرداخت کامل)، `partial` (ناقص)، `owing` (بدهکار).
- **Remaining_Balance**: بدهی باقی‌مانده‌ی یک Payment، محاسبه‌شده به‌صورت `amount_total - discount - amount_paid`.
- **StudentEnrollment**: ثبت‌نام هنرجو نزد استاد برای یک ساز (جدول `student_enrollments`).
- **Student**: هنرجوی ثبت‌شده در آموزشگاه (جدول `students`).
- **StudentFinancialSummary**: بخش نمایشی در صفحه‌ی نمایش هنرجو که کل بدهی، کل پرداختی، و تاریخ آخرین پرداخت را نشان می‌دهد.
- **PaymentsIndex**: صفحه‌ی فهرست پرداخت‌ها با ستون‌های قابل مرتب‌سازی.
- **Admin**: کاربر ادمین پنل مدیریت آموزشگاه.
- **JalaliDate**: تاریخ شمسی تولید‌شده توسط `App\Helpers\Jalalian`.

---

## Requirements

### Requirement 1: ثبت پرداخت جدید

**User Story:** به عنوان یک Admin، می‌خواهم یک پرداخت جدید برای یک ثبت‌نام هنرجو ثبت کنم، تا وضعیت مالی هنرجو به‌روز بماند.

#### Acceptance Criteria

1. THE PaymentController SHALL provide a form to create a Payment for a selected StudentEnrollment.
2. WHEN an Admin submits the payment creation form with a valid `student_enrollment_id`, `amount_total`, `discount`, `amount_paid`, `payment_date`, and `payment_method`, THE PaymentController SHALL create a Payment record.
3. IF the payment creation form is submitted without a valid `student_enrollment_id` that exists in `student_enrollments`, THEN THE PaymentController SHALL reject the request and return a validation error.
4. IF `amount_total` is missing, non-numeric, or negative, THEN THE PaymentController SHALL reject the request and return a validation error.
5. IF `discount` is provided and is non-numeric or negative, THEN THE PaymentController SHALL reject the request and return a validation error.
6. IF `amount_paid` is missing, non-numeric, or negative, THEN THE PaymentController SHALL reject the request and return a validation error.
7. IF `discount` is greater than `amount_total`, THEN THE PaymentController SHALL reject the request and return a validation error.
8. IF `amount_paid` is greater than `amount_total` minus `discount`, THEN THE PaymentController SHALL reject the request and return a validation error.
9. IF `payment_date` is missing or is not a valid date, THEN THE PaymentController SHALL reject the request and return a validation error.
10. IF `payment_method` is not one of `cash`, `card`, `bank_transfer`, THEN THE PaymentController SHALL reject the request and return a validation error.
11. WHERE `notes` is provided, THE PaymentController SHALL store `notes` as free text on the Payment record.
12. WHEN a Payment record is created, THE PaymentController SHALL compute and store `remaining_balance` as `amount_total - discount - amount_paid`.
13. WHEN a Payment is created successfully, THE PaymentController SHALL redirect to the PaymentsIndex with a success message using the translation key `admin.payment_created_successfully`.

---

### Requirement 2: ویرایش پرداخت

**User Story:** به عنوان یک Admin، می‌خواهم یک پرداخت ثبت‌شده را ویرایش کنم، تا اشتباهات ثبتی را اصلاح کنم.

#### Acceptance Criteria

1. THE PaymentController SHALL provide a form pre-filled with the current values of an existing Payment for editing.
2. WHEN an Admin submits the payment edit form with valid data, THE PaymentController SHALL update the Payment record.
3. THE payment edit form SHALL apply the same validation rules defined in Requirement 1 (items 4 through 10).
4. WHEN a Payment record is updated, THE PaymentController SHALL recompute and store `remaining_balance` as `amount_total - discount - amount_paid`.
5. WHEN a Payment is updated successfully, THE PaymentController SHALL redirect to the PaymentsIndex with a success message using the translation key `admin.payment_updated_successfully`.

---

### Requirement 3: حذف پرداخت

**User Story:** به عنوان یک Admin، می‌خواهم یک رکورد پرداخت اشتباه را حذف کنم، تا اطلاعات مالی هنرجو دقیق بماند.

#### Acceptance Criteria

1. WHEN an Admin requests deletion of a Payment, THE PaymentController SHALL delete the Payment record.
2. WHEN a Payment is deleted successfully, THE PaymentController SHALL redirect to the PaymentsIndex with a success message using the translation key `admin.payment_deleted_successfully`.
3. WHEN an Admin requests deletion of a Payment, THE PaymentsIndex SHALL display a confirmation prompt using the translation key `admin.delete_payment_confirm` before submitting the deletion request.

---

### Requirement 4: فهرست پرداخت‌ها با ستون‌های قابل مرتب‌سازی

**User Story:** به عنوان یک Admin، می‌خواهم فهرست همه‌ی پرداخت‌ها را با امکان مرتب‌سازی ببینم، تا بتوانم به‌سرعت وضعیت مالی هنرجویان را بررسی کنم.

#### Acceptance Criteria

1. THE PaymentsIndex SHALL display one row per Payment with the columns: هنرجو (student name), مبلغ (amount_total), تخفیف (discount), پرداخت شده (amount_paid), بدهی (remaining_balance), تاریخ (payment_date, JalaliDate), وضعیت (Payment_Status badge).
2. THE PaymentsIndex SHALL support sorting by `amount_total`, `discount`, `amount_paid`, `remaining_balance`, and `payment_date`, reusing the `admin/partials/sort-th.blade.php` pattern.
3. WHEN an Admin clicks a sortable column header, THE PaymentController SHALL reorder the PaymentsIndex results by the selected column and preserve the existing query string.
4. WHERE no sort column is specified in the request, THE PaymentController SHALL default to sorting by `payment_date` in descending order.
5. THE PaymentsIndex SHALL paginate results using the same page size convention as the existing admin index pages (15 per page).
6. WHEN the PaymentsIndex has no Payment records, THE PaymentsIndex SHALL display the translation key `admin.no_payments_found`.

---

### Requirement 5: نشانگر وضعیت پرداخت (محاسبه‌شده)

**User Story:** به عنوان یک Admin، می‌خواهم وضعیت هر پرداخت را با یک نشانگر رنگی ببینم، تا بدون محاسبه‌ی دستی متوجه وضعیت بدهی شوم.

#### Acceptance Criteria

1. THE PaymentModule SHALL NOT persist Payment_Status as a database column; Payment_Status SHALL be computed on read from `remaining_balance` and `amount_paid`.
2. IF `remaining_balance` equals zero, THEN THE PaymentModule SHALL classify the Payment_Status as `fully_paid` and display the translation key `admin.payment_statuses.fully_paid`.
3. IF `remaining_balance` is greater than zero AND `amount_paid` is greater than zero, THEN THE PaymentModule SHALL classify the Payment_Status as `partial` and display the translation key `admin.payment_statuses.partial`.
4. IF `remaining_balance` is greater than zero AND `amount_paid` equals zero, THEN THE PaymentModule SHALL classify the Payment_Status as `owing` and display the translation key `admin.payment_statuses.owing`.
5. THE PaymentsIndex SHALL render the Payment_Status badge using distinct Tailwind CSS color schemes for each of `fully_paid`, `partial`, and `owing`.

---

### Requirement 6: خلاصه‌ی مالی هنرجو

**User Story:** به عنوان یک Admin، می‌خواهم خلاصه‌ای از وضعیت مالی هر هنرجو را در صفحه‌ی پروفایل او ببینم، تا بدون رفتن به فهرست پرداخت‌ها وضعیت بدهی را بدانم.

#### Acceptance Criteria

1. THE `admin.students.show` view SHALL display a StudentFinancialSummary block containing: بدهی کل (total remaining balance across all Payments belonging to the Student's enrollments), پرداخت‌شده (sum of `amount_paid` across all Payments belonging to the Student's enrollments), and آخرین پرداخت (the `payment_date` of the Student's most recent Payment, formatted as JalaliDate).
2. WHEN computing the StudentFinancialSummary, THE StudentController SHALL aggregate Payment records across all StudentEnrollment records belonging to the Student.
3. IF a Student has no Payment records, THEN THE StudentFinancialSummary SHALL display zero for بدهی کل and پرداخت‌شده, and display the translation key `admin.no_payments_yet` for آخرین پرداخت.
4. THE StudentFinancialSummary block SHALL be positioned within the `admin.students.show` view, separate from the existing enrollments table and history timeline sections.

---

### Requirement 7: مدل داده و روش پرداخت

**User Story:** به عنوان یک Admin، می‌خواهم روش پرداخت هر رکورد را از بین گزینه‌های از پیش تعیین‌شده انتخاب کنم، تا داده‌ها یکسان و قابل گزارش‌گیری باشند.

#### Acceptance Criteria

1. THE Payment model SHALL belong to a StudentEnrollment via `student_enrollment_id`.
2. THE Payment model SHALL cast `payment_method` to a `Payment_Method` enum with values `cash`, `card`, `bank_transfer`.
3. THE Payment model SHALL cast `payment_date` to a date type for JalaliDate rendering.
4. THE Payment model SHALL cast `amount_total`, `discount`, `amount_paid`, and `remaining_balance` to decimal values.
5. WHERE a StudentEnrollment is soft-deleted, THE PaymentModule SHALL retain its associated Payment records without deletion.

---

### Requirement 8: متون فارسی و کلیدهای ترجمه

**User Story:** به عنوان یک Admin، می‌خواهم تمام متون رابط کاربری ماژول پرداخت به فارسی و از طریق سیستم ترجمه‌ی لاراول باشند، تا با بقیه‌ی پنل یکسان باشد.

#### Acceptance Criteria

1. THE `lang/fa/admin.php` file SHALL contain translation keys for all new UI strings introduced by the PaymentModule, including a `payment_statuses` sub-array with keys `fully_paid`, `partial`, `owing`.
2. THE PaymentsIndex page title SHALL use the translation key `admin.payments`.
3. THE payment creation form SHALL use the translation key `admin.create_payment`.
4. THE payment edit form SHALL use the translation key `admin.edit_payment`.
5. THE StudentFinancialSummary block heading SHALL use the translation key `admin.financial_summary`.
6. THE translation key `admin.payment_statuses.fully_paid` SHALL have the value `پرداخت کامل`.
7. THE translation key `admin.payment_statuses.partial` SHALL have the value `ناقص`.
8. THE translation key `admin.payment_statuses.owing` SHALL have the value `بدهکار`.

---

### Requirement 9: محدودیت‌های خارج از محدوده

**User Story:** به عنوان یک Admin، می‌خواهم مطمئن شوم ماژول پرداخت به بخش‌های دیگر سیستم آسیب نمی‌زند، تا پایداری ماژول‌های موجود حفظ شود.

#### Acceptance Criteria

1. THE PaymentModule SHALL NOT modify `ClassSession`, `RecurringSchedule`, `SessionGeneratorService`, or `ConflictDetectionService`.
2. THE PaymentModule SHALL NOT modify `App\Services\StudentHistoryService`.
3. THE PaymentModule SHALL NOT introduce an online payment gateway integration in this MVP.

---

## Open Questions (نیاز به تصمیم قبل از نهایی‌شدن)

این موارد طبق درخواست صریح تصمیم‌گیرنده به‌عنوان نکات باز ثبت شده‌اند و در پیش‌نویس بالا با یک فرض پیش‌فرض منطقی پوشش داده شده‌اند. لطفاً تأیید یا اصلاح کنید:

1. **سقف مبلغ پرداخت‌شده**: پیش‌نویس فعلی (Requirement 1, AC8) اجازه‌ی `amount_paid > amount_total - discount` را نمی‌دهد (رد کامل). آیا این رفتار درست است، یا باید مقدار اضافه‌پرداخت تا سقف مشخصی (مثلاً چند درصد) مجاز باشد؟
2. **نمایش رویدادهای پرداخت در تایم‌لاین هنرجو**: طبق محدودیت پروژه، `StudentHistoryService` در این فاز تغییر نمی‌کند و رویدادهای پرداخت در تایم‌لاین هنرجو ظاهر نمی‌شوند. آیا این باید به‌عنوان یک فاز بعدی (بدون تغییر مستقیم در این اسپک) ثبت شود؟
3. **چندین Payment برای یک Enrollment**: آیا هر ثبت‌نام می‌تواند چندین رکورد Payment داشته باشد (مثل پرداخت اقساطی)، یا هر ثبت‌نام باید فقط یک رکورد Payment داشته باشد؟ پیش‌نویس فعلی فرض می‌کند چندین Payment در طول زمان برای یک Enrollment مجاز است (اقساطی).
