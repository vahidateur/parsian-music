# Requirements Document

## Introduction

این سند نسخه تکمیل‌شده مشخصه موجود `admin-bulk-selection-actions` است و دامنه آن را به‌صورت منسجم به عملیات گروهی استاد و شاگرد، مدیریت جلسه در فهرست جلسات، داده واقعی تقویم، یادداشت جلسه و اتاق‌های واقعی گسترش می‌دهد. این تصمیم از ایجاد مشخصه موازی با نام `admin-bulk-actions-and-session-data` جلوگیری می‌کند؛ نام دوم به‌عنوان برچسب دامنه جدید ثبت می‌شود، اما شناسه و پوشه مشخصه موجود حفظ می‌گردد.

هدف سند، تعریف رفتار قابل تست برای پنل مدیریت Parsian Music است. این سند در این مرحله فقط نیازمندی است و نباید باعث ایجاد یا تغییر `design.md`، `tasks.md` یا کد برنامه شود.

### Current-State Baseline and Technical Constraints

- مسیرهای فعلی مدیریت استاد و شاگرد به‌ترتیب `admin.teachers.index` و `admin.students.index` هستند و فهرست‌ها فیلتر، مرتب‌سازی و pagination دارند.
- وضعیت استاد از `TeacherStatusEnum` و وضعیت شاگرد از `StudentStatusEnum` خوانده می‌شود؛ `paused` و `graduated` برای شاگرد چرخه‌عمر مستقل دارند.
- مسیرهای فعلی `admin.sessions.index`، `admin.sessions.create`، `admin.sessions.store` و `admin.sessions.destroy` برای نمایش، ایجاد و حذف وجود دارند؛ در `admin.sessions` مسیر named برای `edit` یا `update` وجود ندارد و فهرست جلسه entry point ویرایش ندارد.
- `ClassSessionController` در وضعیت فعلی برای ایجاد از `Illuminate\Http\Request` عمومی استفاده می‌کند، اتاق‌ها را hardcode می‌کند و تغییر session و شمارنده subscription را در یک transaction واحد انجام نمی‌دهد؛ این‌ها محدودیت‌های فعلی هستند، نه رفتار مطلوب این سند.
- `ClassSession` دارای `enrollment` و روابط مستقیم `student`، `teacher` و `instrument` است. `CalendarEventResource` در وضعیت فعلی ابتدا enrollment و سپس در صورت نبودن relation از direct relation استفاده می‌کند، اما تعارض شناسه‌ها را به‌عنوان خطای یکپارچگی رد نمی‌کند.
- `ClassSession.room` در schema فعلی یک `string` nullable با طول محدود و بدون foreign key یا relation مدل به `Room` است، درحالی‌که جدول و مدل `Room` رکوردهای واقعی با `name` یکتا و `is_active` دارند. این ناسازگاری schema یک محدودیت فنی صریح است؛ تصمیم migration یا سازگارسازی باید در design ثبت شود و این requirements مجاز به ساختن relation جعلی یا مقدار جایگزین نیست.
- کنترلر تقویم برای index از نام `Room`های active استفاده می‌کند، اما فهرست جلسات و فرم ایجاد جلسه هنوز roomهای hardcode مانند `A101`، `A102` و `A103` دارند؛ این منبع دوگانه باید حذف و منبع واقعی database جایگزین شود. فیلتر باید بتواند رکورد واقعی inactive را برای یافتن سابقه انتخاب کند، اما مقدار legacy ناشناخته گزینه فیلتر نیست.
- endpoint فعلی `admin.calendar.events` داده را از `ClassSession` و eager loading مربوط به enrollment/direct relations می‌گیرد؛ قرارداد موجود تقویم بازه بیش از ۹۲ روز را رد می‌کند؛ هر mock، fake، sample، default یا fallback که وجود session، شخص، ساز، یادداشت یا اتاق را خلاف پایگاه‌داده نشان دهد، خارج از قرارداد است.
- Policyهای موجود `SessionPolicy`، `TeacherPolicy` و `StudentPolicy` برای مشاهده، ایجاد، update و delete مبنا هستند. پیاده‌سازی آینده باید مجوزهای دسته‌جمعی و ویرایش جلسه/یادداشت را با همین Policy/Gateها هماهنگ کند و صرفاً به role middleware یا UI متکی نباشد.
- فرم‌ها باید از Form Request یا قرارداد معادل موجود استفاده کنند؛ منطق کسب‌وکار باید در Service/Action و تغییرات چندمرحله‌ای در transaction قرار گیرد.

## Glossary

- **Admin_Bulk_Module**: قابلیت مشترک انتخاب و عملیات دسته‌جمعی در `Teacher_List` و `Student_List`.
- **Teacher_List**: فهرست مدیریتی رکوردهای `Teacher` در `admin.teachers.index`.
- **Student_List**: فهرست مدیریتی رکوردهای `Student` در `admin.students.index`.
- **Session_List**: فهرست مدیریتی `ClassSession` در `admin.sessions.index`.
- **Calendar_Module**: تقویم مدیریت و اجزای فیلتر، timeline، event card و drawer که از `Calendar_API` داده می‌گیرند.
- **Admin_User**: کاربر احراز هویت‌شده‌ای که بر اساس middleware و Policy/Gate مجوز عملیات مدیریتی دارد.
- **Selection_Set**: مجموعه رکوردهای یک نوع موجودیت که `Admin_User` صراحتاً برای یک درخواست انتخاب کرده است.
- **Current_Page_Mode**: حالتی که `Selection_Set` فقط رکوردهای صفحه فعلی را با context فعلی فهرست شامل می‌شود.
- **All_Filtered_Mode**: حالتی که `Admin_User` صراحتاً همه رکوردهای منطبق با context فیلتر فعلی را انتخاب می‌کند.
- **Filter_Context**: snapshot معتبر نوع موجودیت، فیلترهای فعال و مرتب‌سازی در لحظه انتخاب؛ شماره صفحه عضو snapshot نیست.
- **Bulk_Action**: یکی از عملیات اولیه `activate`، `deactivate` یا `delete` روی `Selection_Set`.
- **Valid_Status**: مقدار معتبر Enum همان موجودیت؛ استاد شامل `active/inactive` و شاگرد شامل `active/paused/inactive/graduated` است.
- **Protected_Dependency**: enrollment، subscription، invoice، attendance، class session یا converted lead مرتبط که حذف موجودیت را محدود می‌کند.
- **Item_Result**: نتیجه مستقل رکورد در دسته `succeeded`، `skipped` یا `failed` همراه با دلیل قابل نمایش.
- **Bulk_Result**: خلاصه نوع موجودیت، عمل، mode، تعداد کل، موفق، skipped و failed و Item_Resultها.
- **Bulk_Request**: درخواست معتبر سمت سرور برای دقیقاً یک `Bulk_Action` روی یک `Selection_Set`.
- **Audit_Record**: رکورد ممیزی شامل عامل، زمان، موجودیت، عمل، context، تعدادها و نتیجه، بدون داده حساس.
- **Session_Edit_Request**: درخواست معتبر برای تغییر فیلدهای قابل ویرایش یک `ClassSession`.
- **Editable_Session_Field_Set**: مجموعه دقیق `student_id`، `teacher_id`، `instrument_id`، `session_date`، `start_time`، `duration_minutes`، `status`، `room` و `notes`; فیلدهای enrollment، مالی و recurring خارج از این مجموعه‌اند.
- **Session_Validation_Rules**: قواعد قابل اندازه‌گیری جلسه شامل شناسه‌های موجود، تاریخ ISO معتبر، زمان `H:i` در بازه `15:00` تا `21:30`، duration صحیح در بازه ۳۰ تا ۱۲۰ دقیقه، status معتبر Enum و room فعال واقعی برای ایجاد یا جایگزینی.
- **Relation_Path**: مسیر واحد و معتبر resolution برای student، teacher و instrument؛ یا enrollment با سه شناسه سازگار یا روابط مستقیم persisted، بدون ترکیب نام‌ها از دو مسیر متعارض.
- **Session_Data_Source**: رکورد persisted `ClassSession` و روابط معتبر Eloquent آن، نه داده client-only یا mock.
- **Calendar_API**: endpoint نام‌گذاری‌شده `admin.calendar.events` برای خروجی JSON جلسات.
- **Calendar_Date_Range**: بازه معتبر درخواست Calendar_API که missing/malformed/reversed نیست و از سقف موجود ۹۲ روز تجاوز نمی‌کند.
- **Calendar_Event**: نمایش FullCalendar-compatible از یک `ClassSession` persisted با شناسه پایدار.
- **Room_Resolution**: وضعیت room در خروجی شامل `resolved_active`، `resolved_inactive` یا `unresolved_legacy` بر اساس رکورد واقعی `Room`.
- **Session_Notes**: مقدار nullable ستون `class_sessions.notes`.
- **Room_Record**: رکورد persisted مدل `Room` با شناسه، نام یکتا و وضعیت active/inactive.
- **Room_Option_Set**: گزینه‌های room که از رکوردهای واقعی `Room` برای فیلتر یا فرم جلسه ساخته می‌شود.
- **Real_Data_Contract**: قراردادی که هر مقدار نمایشی را فقط از Session_Data_Source یا Room_Record حاضر در database تولید می‌کند.
- **Protected_Context**: شرایطی که داده یا selection بین زمان انتخاب و اجرا تغییر کرده و نباید بدون ارزیابی دوباره overwrite شود.
- **Design_System**: tokenها و variantهای موجود پنل مدیریت برای رنگ، فاصله، radius، focus، motion، Glass و Button.

## Requirements

### Requirement 1: Multi-Selection in Teacher and Student Lists

**User Story:** به‌عنوان مدیر، می‌خواهم چند استاد یا شاگرد را از فهرست انتخاب کنم تا عملیات تکراری را یک‌جا انجام دهم.

#### Acceptance Criteria

1. THE Admin_Bulk_Module SHALL render exactly one semantic selection control for each visible Teacher or Student row for which the current Admin_User has at least one authorized Bulk_Action.
2. THE Admin_Bulk_Module SHALL render one header selection control that selects exactly all selectable visible rows and clears exactly all selected visible rows on the current page.
3. WHEN at least one row is selected, THE Admin_Bulk_Module SHALL display a selected count equal to the current Selection_Set size and SHALL expose only Bulk_Action controls authorized for the selected entity type and records.
4. WHEN no row is selected, THE Admin_Bulk_Module SHALL hide or disable every Bulk_Action control.
5. WHEN the header selection control selects a non-empty strict subset of selectable visible rows, THE Admin_Bulk_Module SHALL expose an indeterminate state on the header control; WHEN all selectable visible rows are selected, THE control SHALL be checked and non-indeterminate; WHEN no selectable visible row is selected, THE control SHALL be unchecked and non-indeterminate.
6. THE Admin_Bulk_Module SHALL retain Selection_Set state only in the current page session while the current page and context remain loaded; page change, filter change, entity change, refresh, logout or system restart SHALL clear the Selection_Set, and a subsequent browser session SHALL not restore it.
7. IF Teacher_List or Student_List has no selectable visible row, THEN THE Admin_Bulk_Module SHALL display the existing empty state and SHALL keep Bulk_Action controls hidden or disabled.
8. THE Admin_Bulk_Module SHALL preserve the existing list pagination and filtering behavior while adding selection state.

### Requirement 2: Page-Wide and Filter-Wide Selection

**User Story:** به‌عنوان مدیر، می‌خواهم همه نتایج یک فیلتر را در چند صفحه انتخاب کنم تا عملیات کامل و قابل پیش‌بینی باشد.

#### Acceptance Criteria

1. WHEN Admin_User selects every selectable visible row in Current_Page_Mode, THE Admin_Bulk_Module SHALL offer an explicit All_Filtered_Mode control for the current Filter_Context.
2. WHEN Admin_User confirms All_Filtered_Mode, THE Admin_Bulk_Module SHALL allow confirmation directly for the current Filter_Context without requiring a prior visible-row selection and SHALL display the server-resolved total count matching that context before submission.
3. THE Admin_Bulk_Module SHALL scope All_Filtered_Mode to exactly one entity type and one immutable Filter_Context snapshot containing the active filters and sort, and SHALL not include page number in the snapshot.
4. WHEN Admin_User changes entity type, filter, sort, page or leaves the list before execution, THE Admin_Bulk_Module SHALL discard All_Filtered_Mode and SHALL invalidate the related Selection_Set.
5. THE Admin_Bulk_Module SHALL resolve All_Filtered_Mode on the server from the Filter_Context rather than requiring every matching identifier in the browser, and SHALL revalidate the resolved records before mutation.
6. WHEN Bulk_Request execution begins, THE Admin_Bulk_Module SHALL authorize the requested action and validate Filter_Context again before resolving All_Filtered_Mode or changing a record.
7. IF Filter_Context is invalid, expired or irreproducible, THEN THE Admin_Bulk_Module SHALL reject Bulk_Request with a context-specific validation error and SHALL preserve every record state.
8. IF Admin_User submits an empty Selection_Set, THEN THE Admin_Bulk_Module SHALL reject Bulk_Request with a distinct empty-selection error and SHALL preserve every record state; the response SHALL provide a recovery path to select at least one row or explicitly choose All_Filtered_Mode.

### Requirement 3: Bulk Actions and Status Transition Rules

**User Story:** به‌عنوان مدیر، می‌خواهم فعال‌سازی، غیرفعال‌سازی و حذف را از یک نوار ابزار واحد اجرا کنم و وضعیت چرخه‌عمر رکوردها ناخواسته تغییر نکند.

#### Acceptance Criteria

1. THE Admin_Bulk_Module SHALL provide `activate`, `deactivate` and `delete` as the initial Bulk_Action set for Teacher_List and Student_List.
2. THE Admin_Bulk_Module SHALL submit one Bulk_Request for exactly one entity type and exactly one Bulk_Action.
3. WHEN `activate` targets a Teacher with status `inactive`, THE Admin_Bulk_Module SHALL change the Teacher status to `active`.
4. WHEN `deactivate` targets a Teacher with status `active`, THE Admin_Bulk_Module SHALL change the Teacher status to `inactive`.
5. WHEN `activate` targets a Student with status `inactive`, THE Admin_Bulk_Module SHALL change the Student status to `active`.
6. WHEN `deactivate` targets a Student with status `active`, THE Admin_Bulk_Module SHALL change the Student status to `inactive`.
7. WHEN a status Bulk_Action targets a record already in the requested status, THE Admin_Bulk_Module SHALL perform a database write that persists the requested status value and SHALL classify the record as succeeded.
8. IF a status Bulk_Action targets a Student with status `paused` or `graduated`, THEN THE Admin_Bulk_Module SHALL classify the record as failed with an invalid-transition reason and SHALL preserve the status.
9. IF a record contains a status outside its Valid_Status values, THEN THE Admin_Bulk_Module SHALL classify the record as failed and SHALL preserve the raw stored status.
10. THE Admin_Bulk_Module SHALL use the relevant entity Enum as the source of truth for accepted statuses and transitions.
11. THE Admin_Bulk_Module SHALL return a Bulk_Result containing total, succeeded, skipped and failed counts after every accepted Bulk_Request.
12. WHEN Bulk_Result contains Item_Result entries, THE Admin_Bulk_Module SHALL identify every entry by stable record identifier and localized reason.
13. WHEN Bulk_Result contains no Item_Result entries, THE Admin_Bulk_Module SHALL include a stable Selection_Set reference or stable identifier metadata.

### Requirement 4: Safe Bulk Deletion and Auditability

**User Story:** به‌عنوان مالک سامانه، می‌خواهم حذف دسته‌جمعی با تأیید، وابستگی‌سنجی و ممیزی انجام شود تا سوابق آموزشی از بین نرود.

#### Acceptance Criteria

1. WHEN Admin_User invokes `delete` with a non-empty Selection_Set, THE Admin_Bulk_Module SHALL open a Bulk_Confirmation_Dialog before sending Bulk_Request.
2. THE Admin_Bulk_Module SHALL display the localized permanent-deletion warning in Bulk_Confirmation_Dialog if and only if the Selection_Set contains at least one existing selected item, and SHALL display entity type, selected count and deletion action whenever the warning is displayed.
3. WHEN Admin_User cancels Bulk_Confirmation_Dialog, THE Admin_Bulk_Module SHALL send no deletion request and SHALL preserve Selection_Set and every record.
4. WHEN Admin_User confirms Bulk_Confirmation_Dialog, THE Admin_Bulk_Module SHALL send exactly one delete Bulk_Request for the current Selection_Set.
5. WHEN a selected Teacher or Student has a Protected_Dependency, THE Admin_Bulk_Module SHALL classify that record as failed and SHALL preserve the record and related records.
6. WHEN a selected Teacher or Student has no Protected_Dependency and deletion authorization succeeds, THE Admin_Bulk_Module SHALL permanently delete that record.
7. THE Admin_Bulk_Module SHALL never cascade-delete a Protected_Dependency as a side effect of bulk deletion.
8. WHEN one delete Bulk_Request contains eligible and protected records, THE Admin_Bulk_Module SHALL delete eligible records and SHALL report protected records as failed Item_Result entries.
9. WHEN a valid Bulk_Request reaches execution, THE Admin_Bulk_Module SHALL create exactly one Audit_Record containing actor, timestamp, entity type, Bulk_Action, selection mode, Filter_Context and aggregate counts.
10. WHEN Bulk_Request produces Item_Result failures or skips, THE Admin_Bulk_Module SHALL associate reason categories and stable identifiers with Audit_Record.
11. WHEN authorization or request validation rejects Bulk_Request, THE Admin_Bulk_Module SHALL create one rejected-operation audit event and SHALL create no execution Audit_Record.
12. WHEN Admin_User cancels Bulk_Confirmation_Dialog before submission, THE Admin_Bulk_Module SHALL create no execution Audit_Record.
13. THE Admin_Bulk_Module SHALL exclude phone numbers, notes, credentials and other sensitive field values from Audit_Record payloads.

### Requirement 5: Authorization, Validation and Request Integrity

**User Story:** به‌عنوان مالک سامانه، می‌خواهم هر تغییر گروهی با کنترل‌های Laravel و مجوز رکورد مرتبط انجام شود تا دستکاری request به تغییر غیرمجاز منجر نشود.

#### Acceptance Criteria

1. WHEN an unauthenticated request reaches a bulk endpoint, THEN THE Admin_Bulk_Module SHALL require authentication before resolving Selection_Set or changing records.
2. WHEN Admin_User lacks the relevant TeacherPolicy or StudentPolicy ability for a requested status change, THEN THE Admin_Bulk_Module SHALL return a forbidden response before resolving or mutating the affected records and SHALL modify no record.
3. WHEN Admin_User lacks the relevant TeacherPolicy or StudentPolicy ability for deletion, THEN THE Admin_Bulk_Module SHALL return a forbidden response before resolving or mutating the affected records and SHALL modify no record.
4. THE Admin_Bulk_Module SHALL validate Bulk_Request action, entity type, non-empty identifiers, identifier type, Selection_Set mode and Filter_Context with a Form Request.
5. THE Admin_Bulk_Module SHALL require CSRF protection for every state-changing browser request.
6. IF Bulk_Request contains an unknown identifier, duplicate identifier, wrong-entity identifier or tampered Filter_Context, THEN THE Admin_Bulk_Module SHALL reject the affected input and SHALL not substitute another record.
7. IF action or entity type is outside the supported set, THEN THE Admin_Bulk_Module SHALL return a validation error and SHALL modify no record.
8. THE Admin_Bulk_Module SHALL authorize every requested Bulk_Action against the relevant Policy/Gate rather than relying on hidden or disabled UI controls.
9. THE Admin_Bulk_Module SHALL use parameterized Eloquent or query-builder constraints for all selection and filtering input.

### Requirement 6: Partial, Complete and Concurrent Bulk Outcomes

**User Story:** به‌عنوان مدیر، می‌خواهم نتیجه هر رکورد را حتی در عملیات ترکیبی ببینم و تغییر جدید یک رکورد با وضعیت قدیمی overwrite نشود.

#### Acceptance Criteria

1. THE Admin_Bulk_Module SHALL evaluate authorization, dependency eligibility and current state for each selected record at execution time.
2. WHEN every selected record succeeds, THE Admin_Bulk_Module SHALL report complete-success Bulk_Result with zero failed and skipped counts.
3. WHEN at least one selected record fails or is skipped during item processing, THE Admin_Bulk_Module SHALL report partial-success Bulk_Result.
4. WHEN every selected record fails or is skipped after item processing begins, THE Admin_Bulk_Module SHALL report partial-success Bulk_Result with zero succeeded count.
5. WHEN a record changes state between selection and execution, THE Admin_Bulk_Module SHALL re-evaluate the record and SHALL report the resulting Item_Result without blindly overwriting the newer state.
6. IF Bulk_Request is malformed before item processing, THEN THE Admin_Bulk_Module SHALL reject the request atomically and SHALL modify no record.
7. WHEN item-level processing continues after a failure, THE Admin_Bulk_Module SHALL preserve successful changes and SHALL report the failed item separately.
8. WHEN the response returns, THE Admin_Bulk_Module SHALL clear the executed Selection_Set and SHALL refresh the same list context before displaying Bulk_Result.

### Requirement 7: Session List Editing

**User Story:** به‌عنوان مدیر، می‌خواهم جلسه‌ای را از فهرست جلسات باز کنم و ویرایش کنم تا اصلاح برنامه به حذف و ایجاد دوباره وابسته نباشد.

#### Acceptance Criteria

1. THE Session_List SHALL provide a named edit entry point for every session that the current Admin_User is authorized to update.
2. THE Session_Edit_Request SHALL validate every field in Editable_Session_Field_Set against Session_Validation_Rules, SessionStatusEnum, relation existence and scheduling constraints.
3. THE Session_Edit_Request SHALL validate student, teacher and instrument identifiers against existing database records and SHALL load the three persisted records before the Service/Action evaluates Relation_Path.
4. THE Session_Edit_Request SHALL validate a supplied new or replacement room against an active Room_Record name and SHALL reject a literal value that has no matching active Room_Record; an existing inactive room value MAY remain unchanged while another permitted field is edited.
5. THE Session service SHALL enforce the protection of `enrollment_id`, `session_fee`, `discount` and `recurring_schedule_id` for every session-edit operation, including an operation explicitly requested by Admin_User, and SHALL reject any attempt to change those fields.
6. WHEN Admin_User submits a valid Session_Edit_Request, THE Session service SHALL load the persisted student, teacher, instrument and Room_Record, update only the permitted ClassSession fields and persist the result in a transaction.
7. WHEN Session_Edit_Request changes student, teacher, instrument, date, time, duration or room, THE Session service SHALL re-run the applicable conflict checks before persistence.
8. IF the requested student, teacher and instrument identifiers do not form one valid Relation_Path for the session, THEN THE Session service SHALL return a field-specific integrity error and SHALL preserve every original ClassSession field.
9. IF an enrollment-backed ClassSession has enrollment identifiers that conflict with submitted direct student, teacher or instrument identifiers, THEN THE Session service SHALL reject the edit as a data-integrity conflict and SHALL not mix values from the two paths.
10. IF Session_Edit_Request fails validation, authorization, integrity or conflict checks, THEN THE Session service SHALL preserve every original ClassSession field and SHALL return field-specific feedback.
11. IF the requested ClassSession does not exist, THEN THE Session_List SHALL return a not-found response and SHALL not create a replacement session.
12. WHEN update succeeds, THE Session_List SHALL return Admin_User to the same filter and pagination context with a localized success result.
13. THE Session_List SHALL display persisted session values from ClassSession and eager-loaded relations without query execution in Blade.
14. IF a legacy ClassSession room value has no matching Room_Record, THEN THE Session_List SHALL display the persisted legacy value as unavailable and SHALL not replace the value with a fake or default room.
15. THE Session service SHALL keep any affected subscription counter or related derived value consistent with the session update inside the same transaction when the domain model requires that counter change.

### Requirement 8: Editable and Persisted Session Notes

**User Story:** به‌عنوان مدیر، می‌خواهم یادداشت جلسه را از جزئیات جلسه ویرایش و ذخیره کنم تا اطلاعات آموزشی در خود جلسه قابل نگهداری باشد.

#### Acceptance Criteria

1. WHEN an authorized Admin_User opens Session_Notes for a ClassSession, THE Calendar_Module SHALL render a semantic editable control containing the persisted notes value.
2. WHEN Admin_User submits a valid notes value after upfront authorization succeeds, THE Session service SHALL persist the value in `class_sessions.notes` through the authorized request.
3. WHEN Admin_User clears Session_Notes, THE Session service SHALL persist a nullable empty value and SHALL not restore an old value.
4. IF notes input exceeds the configured database column and request validation limit, THEN THE Session service SHALL reject the request and SHALL preserve the previous notes value; the limit SHALL be derived from the existing schema/configuration contract rather than an invented numeric value.
5. IF notes input contains only whitespace, THEN THE Session service SHALL normalize the value according to the Session_Notes contract and SHALL display the corresponding empty-state placeholder.
6. WHEN notes save succeeds, THE Calendar_Module SHALL update the drawer value from the server response, clear any saved draft, and SHALL announce the save result to assistive technology.
7. IF notes save fails because of authorization, validation, conflict or server error, THEN THE Calendar_Module SHALL preserve the last persisted value, retain the unsaved draft for an explicit retry without presenting the draft as saved, and SHALL display a localized error.
8. THE Calendar_Module SHALL display the localized `بدون یادداشت` placeholder only when persisted Session_Notes is null or empty.
9. THE Session service SHALL escape Session_Notes when rendering HTML and SHALL not interpret notes as executable markup.
10. THE Session service SHALL authorize Session_Notes changes before resolving, validating or processing the notes mutation; IF authorization fails, THEN THE Session service SHALL return a forbidden response and SHALL perform no notes validation, persistence or further processing.

### Requirement 9: Real Session Data in Calendar

**User Story:** به‌عنوان مدیر، می‌خواهم تقویم جزئیات جلسه واقعی را نشان دهد تا کارت یا شاگردی که در database وجود ندارد نمایش داده نشود.

#### Acceptance Criteria

1. WHEN Calendar_API receives a valid date range, THE Calendar_API SHALL return one Calendar_Event only for each persisted ClassSession matching the range and filters.
2. THE Calendar_Event SHALL contain the persisted ClassSession identifier, session date, start time, duration-derived end time, SessionStatusEnum value and Session_Notes value.
3. THE Calendar_API SHALL eager-load enrollment student/teacher/instrument and direct student/teacher/instrument relations before resource transformation.
4. WHEN ClassSession has a non-null enrollment, THE Calendar_API SHALL resolve student, teacher and instrument from that enrollment as one consistent Relation_Path.
5. WHEN ClassSession has no enrollment, THE Calendar_API SHALL resolve student, teacher and instrument from the persisted direct relations as one consistent Relation_Path.
6. IF the enrollment relation and direct relation contain conflicting person or instrument identifiers, THEN THE Calendar_API SHALL not combine names from different paths, SHALL return a data-integrity error according to the API error contract and SHALL log the conflicting ClassSession and relation identifiers for administrative review without logging sensitive field values.
7. THE Calendar_Event SHALL return room, Room_Resolution and notes values from the same persisted ClassSession record that supplies the Calendar_Event identifier.
8. THE Calendar_API SHALL reject missing, malformed, reversed or oversized date ranges with field-specific JSON validation errors; an oversized range SHALL mean a range exceeding the existing 92-day calendar contract, and the API SHALL not return fake events.
9. IF the database query or resource transformation fails, THEN THE Calendar_API SHALL return a generic JSON server error without exposing SQL, credentials or internal stack details.
10. IF Calendar_API returns no matching ClassSession, THEN THE Calendar_Module SHALL display the existing localized empty state and SHALL not insert a sample or fallback event.
11. WHEN Calendar_API returns an error during navigation or filtering, THE Calendar_Module SHALL preserve the last successfully rendered real events and SHALL expose a retry action.
12. THE Calendar_Module SHALL refresh the affected Calendar_Event after a successful Session_Edit_Request or Session_Notes save so displayed data remains consistent with database data.

### Requirement 10: Real Room Records and Room Filtering

**User Story:** به‌عنوان مدیر، می‌خواهم اتاق‌های فیلتر و فرم جلسه از اتاق‌های واقعی سیستم بیایند تا اتاق غیرموجود به جلسه نسبت داده نشود.

#### Acceptance Criteria

1. THE Calendar_Module SHALL build Room_Option_Set from Room_Record rows returned by the database and SHALL preserve each option's persisted identifier and name.
2. THE Session_List SHALL build room filter options from all relevant Room_Record rows, including inactive records needed to find historical sessions, and SHALL not contain a hardcoded room-name list or an unresolved legacy value as an option.
3. THE session creation and edit forms SHALL build selectable room options from active Room_Record rows; creation and room replacement SHALL reject a room literal without a matching active record, while an existing inactive historical value may be preserved unchanged during another permitted edit.
4. WHEN a Room_Record is inactive, THE Room_Option_Set SHALL exclude that record from new-session and edited-session selectable options while allowing the record to remain available as a persisted historical reference.
5. WHEN a historical ClassSession references an inactive Room_Record name, THE Session_List and Calendar_Module SHALL display the persisted room name with `resolved_inactive` indicator and SHALL not silently replace it.
6. WHEN a historical ClassSession contains a room string with no Room_Record, THE Session_List and Calendar_Module SHALL display the persisted string as an `unresolved_legacy` value and SHALL not invent a Room_Record, default or replacement option.
7. WHEN Admin_User selects a room filter, THE Calendar_API SHALL validate the filter against a persisted Room_Record name, including inactive records, and SHALL return only matching persisted sessions.
8. IF room filter input does not match a Room_Record allowed by the filter contract, THEN THE Calendar_API SHALL return a validation error and SHALL return no unscoped session data.
9. WHEN RoomController creates, renames, deactivates or deletes a Room_Record, THE room references and historical ClassSession display contract SHALL preserve referential clarity, retain persisted legacy values when needed and SHALL not produce a duplicate room name.
10. THE Room_Option_Set SHALL preserve the database identifier and name needed to distinguish two records and SHALL not use a display-only fake identifier.

### Requirement 11: Calendar Detail Drawer and Editing Feedback

**User Story:** به‌عنوان مدیر، می‌خواهم با انتخاب یک جلسه جزئیات واقعی و وضعیت ذخیره یادداشت را در drawer ببینم.

#### Acceptance Criteria

1. WHEN Admin_User activates a Calendar_Event, THE Calendar_Module SHALL open an accessible detail drawer containing student, teacher, instrument, Jalali date, start time, duration, room, status and Session_Notes from that Calendar_Event.
2. IF a nullable persisted field has no value, THEN THE Calendar_Module SHALL display its localized placeholder and SHALL not display a fabricated value.
3. THE Calendar_Module SHALL provide an edit and save control for Session_Notes only when SessionPolicy authorizes the current Admin_User.
4. WHEN Admin_User closes the drawer with Escape, backdrop or close control, THE Calendar_Module SHALL close the drawer and SHALL restore focus to the triggering Calendar_Event.
5. THE Calendar_Module SHALL use `role="dialog"`, `aria-modal="true"`, an accessible heading and Alpine focus trapping for the drawer.
6. WHEN notes save is in progress, THE Calendar_Module SHALL disable duplicate save submission and SHALL expose a busy state.
7. IF notes save returns validation or server error, THEN THE Calendar_Module SHALL keep the drawer open, preserve the last persisted value and show a retryable localized error; WHEN no save error occurs, THE Calendar_Module SHALL not show a notes-save error state.
8. THE Calendar_Module SHALL use the status label and status badge corresponding to the persisted SessionStatusEnum value; IF rendering that status value actually fails, THEN THE Calendar_Module SHALL show a localized fallback text or error indicator and SHALL not infer status from date or mock data; WHEN status rendering succeeds, THE Calendar_Module SHALL not show a status-rendering error indicator.

### Requirement 12: Shared UI, RTL, Accessibility and Architecture Contract

**User Story:** به‌عنوان مدیر فارسی‌زبان، می‌خواهم قابلیت جدید با پنل مدیریت و الگوهای دسترسی‌پذیری پروژه یکپارچه باشد.

#### Acceptance Criteria

1. THE Admin_Bulk_Module, Session_List and Calendar_Module SHALL render under `dir="rtl"` with Persian labels, logical CSS properties and existing Design_System tokens.
2. THE Admin_Bulk_Module and Calendar_Module SHALL use semantic buttons, inputs, labels, checkboxes and dialogs with accessible names.
3. THE Bulk_Confirmation_Dialog and event drawer SHALL use keyboard focus trapping through the approved Alpine focus pattern, Escape handling and focus restoration.
4. THE Admin_Bulk_Module SHALL expose selected count, indeterminate state, disabled state, operation result and error result through accessible live messaging.
5. THE Admin_Bulk_Module and Calendar_Module SHALL keep touch targets at least 44 by 44 CSS pixels on coarse-pointer viewports.
6. THE Admin_Bulk_Module, Session_List and Calendar_Module SHALL avoid horizontal overflow from 390px through 1920px and SHALL follow the workspace responsive breakpoints.
7. THE interactive state SHALL be implemented in Alpine.js and Blade SHALL remain responsible for semantic structure; THE feature SHALL use neither inline event handlers nor inline presentation styles.
8. THE backend SHALL keep controllers thin, place business rules in Service/Action classes, validation in Form Requests, resource formatting in Resource/DTO classes and multi-step mutations in transactions; THE backend SHALL preserve these boundaries without requiring all data access to occur in one layer.
9. THE backend SHALL eager-load relations required by the fields rendered by Session_List and Calendar_Module; WHEN functional behavior requires data outside those eager-loaded relations, THE backend SHALL permit additional explicit, parameterized queries and SHALL not require every possible relation to be eager-loaded in advance.
10. IF Admin_User prefers reduced motion, THEN THE Admin_Bulk_Module and Calendar_Module SHALL disable nonessential transitions and animations; WHEN no reduced-motion preference is present, THE modules SHALL retain the configured nonessential motion behavior.
11. THE feature SHALL use named routes and SHALL not introduce anonymous routes or client-only data contracts for persisted sessions and rooms.
12. THE feature SHALL not introduce mock, fake, sample or default session/person/instrument/room data in production responses or rendered management views.

## Correctness Properties

### Property 1: Selection-context isolation

*For every* Selection_Set, only a row with at least one authorized Bulk_Action SHALL be selectable, and its state SHALL remain available only while the current page and context remain loaded. Page/filter/entity change, refresh, logout or system restart SHALL clear the Selection_Set and a subsequent browser session SHALL not restore it. The Selection_Set SHALL retain its original entity type and Filter_Context; after a context change, no identifier SHALL be executed under the new context and All_Filtered_Mode SHALL be discarded as required.

**Validates: Requirements 1.1, 1.6, 2.3–2.6**

### Property 2: Header selection invariant

*For every* visible row set, selecting all rows SHALL produce a checked non-indeterminate header, clearing all rows SHALL produce an unchecked non-indeterminate header, and selecting a strict non-empty subset SHALL produce an indeterminate header.

**Validates: Requirements 1.1, 1.2, 1.5**

### Property 3: Status-action idempotence

*For every* valid Teacher or Student and requested active/inactive status, applying the same accepted status action twice SHALL produce the same final status, SHALL perform the requested database write on each accepted application, SHALL classify an already-correct status as succeeded and SHALL not create a second semantic state transition.

**Validates: Requirements 3.3–3.7**

### Property 4: Protected-dependency deletion invariant

*For every* selected Teacher or Student with a Protected_Dependency, every delete execution SHALL preserve the entity and each related record; for every eligible entity without a Protected_Dependency, successful execution SHALL remove only that entity and SHALL not remove unrelated records. The permanent-deletion warning SHALL be rendered only when the Selection_Set contains at least one existing selected item.

**Validates: Requirements 4.5–4.8**

### Property 5: Malformed-request atomicity

*For every* Bulk_Request containing an unsupported action/entity, empty selection, duplicate or wrong-entity identifier, invalid Filter_Context or failed authorization, the database state before and after request handling SHALL be identical.

**Validates: Requirements 2.7, 2.8, 5.2–5.8, 6.6**

### Property 6: Bulk-result conservation

*For every* accepted Bulk_Request, `total` SHALL equal `succeeded + skipped + failed`, every processed identifier SHALL have at most one Item_Result, and every record for which the requested status write completes SHALL be represented as succeeded, including a record already in the requested status.

**Validates: Requirements 3.11–3.13, 6.1–6.4**

### Property 7: Session edit round trip

*For every* valid Session_Edit_Request and every other session-edit operation, the protected fields `enrollment_id`, `session_fee`, `discount` and `recurring_schedule_id` SHALL remain unchanged; a request attempting to change any protected field SHALL fail and SHALL preserve every original ClassSession field. For permitted fields, a valid consistent request SHALL round-trip through persistence, while a failed validation, authorization, integrity or conflict check SHALL return the original values unchanged.

**Validates: Requirements 7.2–7.10**

### Property 8: Session notes round trip

*For every* notes value accepted by Session_Notes validation under the existing schema/configuration length contract, save-then-read SHALL return the normalized persisted value; clearing notes SHALL return null/empty according to the contract and SHALL display `بدون یادداشت`. After a failed save, the last persisted value SHALL remain authoritative while the unsaved draft remains available only for explicit retry.

**Validates: Requirements 8.1–8.8**

### Property 9: Calendar source consistency

*For every* Calendar_Event returned by Calendar_API, the event identifier SHALL identify an existing ClassSession in the requested range, and all event date, time, duration, status, notes, room and Room_Resolution fields SHALL equal values from that same ClassSession record. Student, teacher and instrument values SHALL come from exactly one consistent Relation_Path; conflicting paths SHALL produce a data-integrity error rather than an event.

**Validates: Requirements 9.1–9.7**

### Property 10: Calendar filter scoping

*For every* valid teacher, student, instrument or Room_Record filter, every returned Calendar_Event SHALL satisfy that filter against persisted ClassSession relations or room value; an invalid room filter SHALL not broaden the query.

**Validates: Requirements 9.1, 9.8, 10.7, 10.8**

### Property 11: Room-option referential validity

*For every* Room_Option_Set entry, the option identifier and name SHALL correspond to one Room_Record returned by the database; inactive entries may be filter options for historical lookup but no inactive or literal-only entry SHALL be selectable for a new or edited session. Unknown legacy room strings SHALL remain display-only unresolved values.

**Validates: Requirements 10.1–10.10**

### Property 12: No fabricated fallback data

*For every* empty, null, unresolved legacy or failed data condition, rendered output SHALL be either a localized empty/placeholder/error state or the persisted raw value marked as unresolved; output SHALL not contain a new person, instrument, session or room absent from the database response. A status-rendering error indicator SHALL appear only when rendering the persisted status actually fails and SHALL be absent when rendering succeeds.

**Validates: Requirements 7.10, 7.13–7.14, 8.8, 9.10–9.11, 10.5–10.6, 11.2, 12.12**

### Property 13: Authorization non-mutation

*For every* Admin_User lacking the Policy/Gate ability for a session update, notes update, Teacher/Student status action or deletion, request handling SHALL return forbidden and SHALL leave every relevant persisted record unchanged. Authorization SHALL be checked before resolving or mutating the protected target; for Session_Notes, authorization SHALL be checked before notes resolution, validation or mutation begins.

**Validates: Requirements 5.1–5.3, 5.8, 8.10, 11.3**

### Property 14: Audit completeness and privacy

*For every* executed or rejected Bulk_Request, exactly one corresponding Audit_Record or rejected-operation event SHALL exist with actor, action/context and aggregate outcome data, and no Audit_Record SHALL contain phone, notes, credentials or other sensitive field values.

**Validates: Requirements 4.9–4.13**

### Property 15: Relation-conflict observability

*For every* persisted ClassSession whose enrollment and direct student, teacher or instrument identifiers conflict, Calendar_API SHALL return no mixed Calendar_Event, SHALL return the defined data-integrity error and SHALL create an administrative-review log containing only the stable session and relation identifiers required to diagnose the conflict.

**Validates: Requirement 9.6**

## Error and Response Contract

| Scenario | Required behavior |
|---|---|
| Unauthenticated state-changing request | HTTP 401 or the existing authentication redirect; no record resolution before authentication |
| Unauthorized action | HTTP 403; no mutation; for Session_Notes, authorization is checked before notes resolution, validation or further processing |
| Invalid Form Request | HTTP 422 with field-specific errors; no mutation |
| Invalid or tampered Selection_Set/Filter_Context | HTTP 422 or the existing validation response with a context-specific error; no mutation |
| Empty Selection_Set | HTTP 422 or the existing validation response with a distinct empty-selection error and selection recovery path; no mutation |
| Missing ClassSession/Teacher/Student/Room_Record | HTTP 404/422 according to endpoint contract; no substitute record |
| Scheduling conflict during session edit | HTTP 422 with field-specific conflict error; original session preserved |
| Enrollment/direct relation conflict | HTTP 409 or the existing integrity-error response contract; no mixed relation output and original session preserved |
| Inactive or unknown room on create/edit | HTTP 422 with field-specific room error; no mutation |
| Protected dependency during delete | Item_Result failed; protected record and related records preserved |
| Item-level mixed outcome | Bulk_Result partial-success with per-record reasons and successful changes preserved |
| Invalid or oversized Calendar date range | HTTP 422 with field-specific JSON validation errors; an oversized range is one exceeding the existing 92-day calendar contract; no events are returned |
| Notes value exceeds schema/configuration limit | HTTP 422 with field-specific notes error; previous persisted value remains authoritative and no invented numeric limit is implied |
| Calendar feed failure | Generic JSON error; client preserves last real events and exposes retry |
| Notes save failure | Drawer remains open; last persisted notes remains visible; localized retryable error |
| Empty real result | Existing localized empty state; no fake or fallback record |
| Unexpected server error | Generic localized response; internal SQL, stack trace and sensitive data excluded |

## Out of Scope

- Bulk operations for sessions, rooms, enrollments, payments, leads, courses, instruments or users; this document adds single-session editing and notes editing but does not add bulk session actions.
- New bulk actions beyond `activate`, `deactivate` and `delete` for Teacher_List and Student_List.
- Redesign of teacher or student profile pages.
- Automatic undo/recovery for permanent deletion.
- Replacing or migrating the legacy `class_sessions.room` column without an approved migration and design decision; the requirement only mandates a real-data contract and forbids fake room values.
- Full calendar features such as drag-and-drop, recurring event authoring, resource timeline, holiday management or room scheduling optimization.
- Creation of `design.md`, `tasks.md`, migrations or application code in this requirements phase.

## Decisions Requiring Design Confirmation

- `admin-bulk-selection-actions` remains the single specification; `admin-bulk-actions-and-session-data` is a domain label, not a parallel spec.
- Bulk deletion remains permanent, protected-dependency aware and non-cascading; blocked records are reported instead of force-deleted.
- Student status operations remain limited to `active ↔ inactive`; `paused` and `graduated` are not implicitly normalized.
- Selection across pages requires explicit All_Filtered_Mode confirmation and is bound to a Filter_Context snapshot.
- Item-level failures do not roll back unrelated successful items; malformed, unauthorized and invalid-context requests do not mutate records.
- Session edits and notes updates require SessionPolicy authorization, Form Request validation, Service/Action execution and transaction boundaries.
- Calendar display must use persisted ClassSession data and one consistent relation path; mock/fake/fallback records are forbidden.
- `Room` is the authoritative source for room options and room filters. The legacy string relationship in ClassSession is a known schema limitation requiring design/migration confirmation, while historical unresolved values remain visible instead of being replaced.
- Auditability is mandatory for executed and rejected bulk requests; canceling confirmation is not an executed operation.
- Calendar_Date_Range retains the existing maximum of ۹۲ days from the calendar contract; proposed alternative numeric ranges are not adopted in this requirements document.
- Session_Notes length and normalization follow the existing database/schema and request configuration; a numeric limit is not invented here and any schema change requires design/migration confirmation.
- Required rendered relations SHALL be eager-loaded, while additional explicit parameterized queries remain allowed when functional behavior needs data outside the eager-loaded set.
