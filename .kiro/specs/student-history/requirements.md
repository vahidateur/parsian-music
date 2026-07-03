# Requirements Document

## Introduction

این ویژگی یک بخش تایم‌لاین/تاریخچه به صفحه نمایش هر هنرجو در پنل مدیریتی آموزشگاه پارسیان موزیک اضافه می‌کند. رویدادهای مهم چرخه‌ی حیات هنرجو — از ثبت‌نام تا تغییر استاد، برگزاری یا لغو کلاس، غیبت، تخفیف، و یادداشت مدیر — به ترتیب زمانی معکوس (جدیدترین اول) با تاریخ شمسی نمایش داده می‌شوند. رویدادها از جداول موجود محاسبه می‌شوند و نیازی به جدول جدید نیست.

## Glossary

- **Timeline**: بخش تاریخچه‌ای در صفحه‌ی نمایش هنرجو که رویدادها را به‌صورت زمانی نشان می‌دهد.
- **TimelineEvent**: یک رویداد منفرد در تایم‌لاین با نوع، زمان، توضیح، و اطلاعات زمینه.
- **StudentHistoryService**: سرویسی که رویدادهای تایم‌لاین را از جداول موجود جمع‌آوری و مرتب می‌کند.
- **EventTypeBadge**: نشانگر رنگی که نوع رویداد را در کارت تایم‌لاین نمایش می‌دهد.
- **JalaliTimestamp**: زمان شمسی تبدیل‌شده توسط `App\Helpers\Jalalian`.
- **Admin**: کاربر ادمین پنل مدیریت آموزشگاه.
- **Student**: هنرجوی ثبت‌شده در آموزشگاه (جدول `students`).
- **Enrollment**: ثبت‌نام هنرجو نزد استاد برای یک ساز (جدول `student_enrollments`).
- **ClassSession**: جلسه‌ی کلاس (جدول `class_sessions`).
- **ClassAttendance**: رکورد حضور/غیاب هنرجو در یک جلسه (جدول `class_attendances`).

---

## Requirements

### Requirement 1: جمع‌آوری رویدادهای تایم‌لاین

**User Story:** به عنوان یک Admin، می‌خواهم تمام رویدادهای مهم یک هنرجو را در یک مکان ببینم، تا بتوانم تاریخچه‌ی کامل فعالیت‌های وی را پیگیری کنم.

#### Acceptance Criteria

1. THE StudentHistoryService SHALL collect events of the following nine types for a given student: `student_created`, `enrollment_created`, `teacher_changed`, `instrument_changed`, `session_completed`, `session_cancelled`, `attendance_marked`, `discount_assigned`, `admin_note`.
2. WHEN deriving `student_created` events, THE StudentHistoryService SHALL use the `created_at` column of the `students` table.
3. WHEN deriving `enrollment_created` events, THE StudentHistoryService SHALL use the `created_at` column of each row in `student_enrollments` belonging to the student, including soft-deleted rows.
4. WHEN deriving `teacher_changed` events, THE StudentHistoryService SHALL use the `updated_at` column of `student_enrollments` rows where `teacher_id` differs from its value at creation time, as approximated by comparing `created_at` and `updated_at`.
5. WHEN deriving `instrument_changed` events, THE StudentHistoryService SHALL use the `updated_at` column of `student_enrollments` rows where `instrument_id` differs from its value at creation time, as approximated by comparing `created_at` and `updated_at`.
6. WHEN deriving `session_completed` events, THE StudentHistoryService SHALL query `class_sessions` joined through `student_enrollments` where `status` equals `completed`.
7. WHEN deriving `session_cancelled` events, THE StudentHistoryService SHALL query `class_sessions` joined through `student_enrollments` where `status` equals `cancelled`.
8. WHEN deriving `attendance_marked` events, THE StudentHistoryService SHALL query `class_attendances` for the student where `status` is `absent` or `late`, using `created_at` as the event timestamp.
9. WHEN deriving `discount_assigned` events, THE StudentHistoryService SHALL query `class_sessions` joined through `student_enrollments` where `session_fee` is not null or `discount` is greater than zero, using `updated_at` as the event timestamp.
10. WHEN deriving `admin_note` events, THE StudentHistoryService SHALL emit one event using `updated_at` of the `students` row when the `notes` column is not null and `updated_at` differs from `created_at`.
11. THE StudentHistoryService SHALL return all collected events sorted by their timestamp in descending order (newest first).

---

### Requirement 2: ساختار داده‌ی TimelineEvent

**User Story:** به عنوان یک Admin، می‌خواهم هر رویداد شامل اطلاعات کافی برای درک زمینه باشد، تا نیازی به کلیک‌های اضافه نداشته باشم.

#### Acceptance Criteria

1. THE StudentHistoryService SHALL represent each TimelineEvent with the following fields: `type` (string), `timestamp` (Carbon), `description` (string), `meta` (array of key-value context data).
2. WHEN the event type is `enrollment_created`, THE StudentHistoryService SHALL include in `meta` the teacher name and instrument name of the enrollment.
3. WHEN the event type is `teacher_changed`, THE StudentHistoryService SHALL include in `meta` the new teacher name.
4. WHEN the event type is `instrument_changed`, THE StudentHistoryService SHALL include in `meta` the new instrument name.
5. WHEN the event type is `session_completed` or `session_cancelled`, THE StudentHistoryService SHALL include in `meta` the `session_date` (Jalali) and the instrument name of the enrollment.
6. WHEN the event type is `attendance_marked`, THE StudentHistoryService SHALL include in `meta` the attendance `status` value (`absent` or `late`) and the `session_date` (Jalali).
7. WHEN the event type is `discount_assigned`, THE StudentHistoryService SHALL include in `meta` the `session_fee` value and the `discount` value.
8. WHEN the event type is `admin_note`, THE StudentHistoryService SHALL include in `meta` the first 100 characters of the `notes` field.

---

### Requirement 3: نمایش تایم‌لاین در صفحه‌ی هنرجو

**User Story:** به عنوان یک Admin، می‌خواهم تایم‌لاین را به‌صورت بصری در صفحه‌ی هنرجو مشاهده کنم، تا اطلاعات به‌سرعت قابل خواندن باشند.

#### Acceptance Criteria

1. THE StudentController SHALL pass a `$timeline` variable (collection of TimelineEvents) to the `admin.students.show` view.
2. THE `admin.students.show` view SHALL render a timeline section below the enrollments table.
3. WHEN the `$timeline` collection is empty, THE timeline section SHALL display the translation key `admin.no_history_events`.
4. THE timeline section SHALL render each event as a card containing: an EventTypeBadge, a JalaliTimestamp, and a description line.
5. THE timeline section SHALL render events in the order provided by StudentHistoryService (descending by timestamp).
6. THE timeline section SHALL display a vertical connector line between consecutive event cards.
7. WHEN rendering a JalaliTimestamp, THE timeline card SHALL use `App\Helpers\Jalalian::fromCarbon()` with format `Y/m/d H:i`.

---

### Requirement 4: نشانگرهای رنگی نوع رویداد (EventTypeBadge)

**User Story:** به عنوان یک Admin، می‌خواهم هر نوع رویداد با رنگ متمایزی مشخص باشد، تا بتوانم سریعاً الگوها را تشخیص دهم.

#### Acceptance Criteria

1. THE EventTypeBadge SHALL assign distinct color schemes to event types as follows:
   - `student_created`: آبی روشن (sky/blue)
   - `enrollment_created`: سبز (emerald)
   - `teacher_changed`: بنفش (violet/purple)
   - `instrument_changed`: نارنجی (amber/orange)
   - `session_completed`: سبز تیره (green)
   - `session_cancelled`: قرمز (red)
   - `attendance_marked`: زرد/قرمز (yellow for late, red for absent)
   - `discount_assigned`: آبی-بنفش (indigo)
   - `admin_note`: خاکستری (gray)
2. THE EventTypeBadge SHALL display the Persian label for each event type using the translation key `admin.history_event_types.{type}`.
3. THE EventTypeBadge SHALL use Tailwind CSS utility classes for all colors.

---

### Requirement 5: متون فارسی و کلیدهای ترجمه

**User Story:** به عنوان یک Admin، می‌خواهم تمام متون رابط کاربری به فارسی و از طریق سیستم ترجمه‌ی لاراول باشند، تا قابلیت نگهداری حفظ شود.

#### Acceptance Criteria

1. THE `lang/fa/admin.php` file SHALL contain translation keys for all new UI strings, under the `history_event_types` sub-array and as top-level keys prefixed with `history_`.
2. THE timeline section heading SHALL use the translation key `admin.student_history`.
3. THE empty-state message SHALL use the translation key `admin.no_history_events`.
4. WHEN an `admin_note` event is displayed, THE description SHALL use the translation key `admin.history_note_excerpt`.
5. THE translation key `admin.history_event_types.student_created` SHALL have the value `ثبت هنرجو`.
6. THE translation key `admin.history_event_types.enrollment_created` SHALL have the value `ثبت‌نام جدید`.
7. THE translation key `admin.history_event_types.teacher_changed` SHALL have the value `تغییر استاد`.
8. THE translation key `admin.history_event_types.instrument_changed` SHALL have the value `تغییر ساز`.
9. THE translation key `admin.history_event_types.session_completed` SHALL have the value `کلاس برگزارشد`.
10. THE translation key `admin.history_event_types.session_cancelled` SHALL have the value `کلاس لغوشد`.
11. THE translation key `admin.history_event_types.attendance_marked` SHALL have the value `غیبت/تأخیر`.
12. THE translation key `admin.history_event_types.discount_assigned` SHALL have the value `تخفیف/شهریه`.
13. THE translation key `admin.history_event_types.admin_note` SHALL have the value `یادداشت مدیر`.

---

### Requirement 6: عملکرد و محدودیت‌ها

**User Story:** به عنوان یک Admin، می‌خواهم تایم‌لاین بدون کندی قابل ملاحظه بارگذاری شود، تا تجربه‌ی کاربری مناسب داشته باشم.

#### Acceptance Criteria

1. THE StudentHistoryService SHALL execute its queries using Eloquent relationships already loaded on the Student model where possible, to avoid N+1 query problems.
2. THE StudentHistoryService SHALL limit the timeline to the 100 most recent events when the total event count exceeds 100.
3. IF the StudentHistoryService encounters a database exception, THEN THE StudentController SHALL catch the exception, log it via `Log::error`, and pass an empty collection to the view without exposing error details to the Admin.
4. THE StudentHistoryService SHALL NOT touch `SessionGeneratorService` or any session-generation logic.
5. THE feature SHALL NOT include any payment module functionality.
