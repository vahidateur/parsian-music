# Requirements Document

## Introduction

The Demo Data System is a production-quality, idempotent database seeder for the Parsian Music Institute ERP. It generates a deterministic, realistic dataset that fully populates every module — dashboard, calendar, CRM, billing, attendance, and reports — so the application is immediately ready for customer demonstrations, QA runs, sales recordings, and developer onboarding without any manual data entry.

The seeder operates on top of an existing live database without destroying any real data. It must be safe to run multiple times.

## Glossary

- **DemoSeeder**: The single Laravel seeder class (`database/seeders/DemoSeeder.php`) that owns all demo data generation.
- **Institute_Profile**: The singleton `InstituteProfile` model representing the music school's identity and contact information.
- **Instrument**: A musical instrument or music discipline (e.g., Piano, Tar, Voice) defined in the `instruments` table.
- **Teacher**: A teaching staff member linked to a `User` account and one or more `Instruments`.
- **Student**: An enrolled learner linked to a `User` account.
- **Enrollment**: A `StudentEnrollment` record binding a Student to a Teacher and Instrument with a lifecycle status.
- **Recurring_Schedule**: A weekly recurrence rule (weekday + time + room) attached to an Enrollment.
- **Session**: A `ClassSession` record for a specific date derived from a Recurring_Schedule.
- **Attendance**: A `ClassAttendance` record recording a Student's presence status for a Session.
- **Invoice**: A financial document issued to a Student for an Enrollment period.
- **Invoice_Item**: A line item on an Invoice (e.g., monthly tuition).
- **Invoice_Payment**: A payment transaction recorded against an Invoice.
- **Lead**: A prospective student record in the CRM pipeline.
- **Notification**: An in-app notification record in the `notifications` table.
- **Subscription**: A monthly session allocation record linking a Student to a Teacher and Instrument.
- **Room**: A physical practice or teaching room in the `rooms` table.
- **Seed**: The fixed integer value `20260709` used as the deterministic random seed for all fake data.
- **Today**: The fixed anchor date `2026-07-11` used as "now" within the seeder, ensuring reproducibility.
- **Idempotent**: The property that running DemoSeeder multiple times produces the same final database state without creating duplicate records.

---

## Requirements

### Requirement 1: Institute Profile Initialization

**User Story:** As a developer running a demo, I want the institute profile to be pre-filled with realistic contact and identity data, so that the settings page and header logo area are never blank.

#### Acceptance Criteria

1. THE DemoSeeder SHALL populate the `institute_profile` table with `id = 1` if no row with `id = 1` exists.
2. THE DemoSeeder SHALL set `name` to a non-empty string that includes "موسیقی" (music) in Persian, along with non-empty values for `city`, `province`, `phone`, `mobile`, `email`, `website`, `instagram`, `telegram`, `address`, and `postal_code`.
3. THE DemoSeeder SHALL set `working_days` to a JSON array containing exactly the six values `['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday']`.
4. THE DemoSeeder SHALL set `working_hours_from` to `'08:00'` and `working_hours_to` to `'21:00'`.
5. WHEN a row with `id = 1` already exists in `institute_profile`, THE DemoSeeder SHALL NOT modify any field of that row.

---

### Requirement 2: Rooms

**User Story:** As a sales person doing a demo, I want the room management page to show a set of realistic rooms with names and capacities, so the institute looks operational.

#### Acceptance Criteria

1. THE DemoSeeder SHALL ensure at least 9 rooms exist in the `rooms` table after seeding, creating only those rooms whose `name` does not already exist.
2. THE DemoSeeder SHALL include at least one room with `capacity = 1`, at least one room with `capacity` in the range 2–3, and at least one room with `capacity` in the range 8–10.
3. THE DemoSeeder SHALL set `is_active = true` for every room it creates.
4. WHEN a room with the same `name` already exists in the `rooms` table, THE DemoSeeder SHALL NOT create a new room record.
5. THE DemoSeeder SHALL set `name` to a non-empty, distinct Persian string for every room it creates.

---

### Requirement 3: Instruments

**User Story:** As a developer, I want all common Persian and international instruments to exist in the database, so that teacher profiles, enrollments, and reports all show meaningful instrument names.

#### Acceptance Criteria

1. THE DemoSeeder SHALL ensure at least 35 instruments exist in the `instruments` table after seeding, creating only those instruments whose `slug` does not already exist.
2. THE DemoSeeder SHALL include at least 10 Persian instruments (Tar, Setar, Santoor, Kamancheh, Tonbak, Daf, Ney, Qanun, Oud, Violin-Iranian) and at least 15 international instruments (Piano, Violin, Cello, Classical Guitar, Electric Guitar, Flute, Drums, Keyboard, Bass Guitar, Trumpet, Saxophone, Clarinet, Harp, Accordion, Ukulele).
3. THE DemoSeeder SHALL set `name` to a non-empty English string and `name_fa` to a non-empty Persian string for every instrument it creates.
4. THE DemoSeeder SHALL set `is_active = true` for every instrument it creates.
5. WHEN an instrument with the same `slug` already exists in the `instruments` table, THE DemoSeeder SHALL NOT create a new instrument record.
6. THE DemoSeeder SHALL ensure every `name` value it inserts is unique across all instruments in the `instruments` table, as the column has a database-level UNIQUE constraint.

---

### Requirement 4: Admin Users

**User Story:** As a developer, I want ready-to-use admin credentials to exist after seeding, so I can log in immediately without creating accounts manually.

#### Acceptance Criteria

1. THE DemoSeeder SHALL create 1 super admin user with `role = 'super_admin'`, `phone = '09120000000'`, and a non-empty Persian `name` if no user with `phone = '09120000000'` exists.
2. THE DemoSeeder SHALL create 3 admin users with `role = 'admin'` and phones `'09120000001'`, `'09120000002'`, `'09120000003'` with non-empty Persian names if no user with each respective phone exists.
3. THE DemoSeeder SHALL set `password` to the bcrypt hash of `'12345678'` only at creation time for all admin users it creates.
4. THE DemoSeeder SHALL set `is_active = true` and `force_password_change = false` only at creation time for all admin users it creates.
5. IF a user with the target `phone` already exists, THEN THE DemoSeeder SHALL skip creating that user without modifying the existing record.
6. AFTER seeding, the `users` table SHALL contain exactly 1 user with `phone = '09120000000'` and `role = 'super_admin'`, and exactly 3 users with `role = 'admin'` and phones `'09120000001'`, `'09120000002'`, `'09120000003'`.

---

### Requirement 5: Teachers

**User Story:** As a sales person, I want to see a roster of 15 realistic Persian teachers, each with a bio and assigned instruments, so that the teacher management page looks credible.

#### Acceptance Criteria

1. THE DemoSeeder SHALL ensure exactly 15 `Teacher` records exist among the demo teacher phones after seeding, creating only those teachers whose `phone` does not already exist.
2. THE DemoSeeder SHALL assign each teacher between 2 and 3 instruments via `teacher_instruments`, selecting only from instruments where `is_active = true` in the `instruments` table.
3. THE DemoSeeder SHALL set `is_primary = true` and `skill_level = 'expert'` for the first instrument assigned to each teacher, and `is_primary = false` with `skill_level = 'advanced'` for all additional instruments.
4. THE DemoSeeder SHALL set `status = 'active'` for 14 teachers and `status = 'inactive'` for 1 teacher.
5. THE DemoSeeder SHALL set a non-empty Persian `bio` string and a `hire_date` equal to a fixed number of months before the seeder anchor date `2026-07-11` for each teacher.
6. THE DemoSeeder SHALL create one `User` account per teacher with `role = 'teacher'`, `is_active` equal to `true` if the teacher's `status = 'active'` or `false` if `status = 'inactive'`, using `firstOrCreate` keyed on `phone`, and link `teacher.user_id` to the created user.
7. WHEN DemoSeeder runs a second time, THE DemoSeeder SHALL NOT attach an instrument to a teacher that already has that instrument in `teacher_instruments`.
8. WHEN a teacher already has `user_id` set, THE DemoSeeder SHALL NOT create a new `User` record for that teacher.

---

### Requirement 6: Students

**User Story:** As a developer, I want 120 students of varied ages, statuses, and join dates, so that all student-facing reports, lists, and statistics show meaningful data.

#### Acceptance Criteria

1. THE DemoSeeder SHALL ensure exactly 120 `Student` records exist among the demo student phones after seeding, creating only those students whose `phone` does not already exist.
2. THE DemoSeeder SHALL distribute statuses as: 85 `active`, 15 `paused`, 12 `inactive`, 8 `graduated` across the 120 students.
3. THE DemoSeeder SHALL generate students at indices 0–29 with `join_date` between 3 and 24 months before `2026-07-11`, students at indices 30–59 with `join_date` between 6 and 36 months before `2026-07-11`, and students at indices 60–119 with `join_date` between 1 and 48 months before `2026-07-11`.
4. THE DemoSeeder SHALL assign a non-empty `parent_phone` for students at indices 0–59 and set `parent_phone = null` for students at indices 60–119.
5. THE DemoSeeder SHALL override criterion 3 for students at indices 105–119 and set their `join_date` to a value between 1 and 14 days before `2026-07-11`.
6. THE DemoSeeder SHALL create one `User` per student with `role = 'student'`, `is_active` derived from the student's status (`active` → `true`, others → `false`), `force_password_change = false`, using `firstOrCreate` keyed on `phone`, and link `student.user_id` to the created user.
7. WHEN a user with the same `phone` already exists, THE DemoSeeder SHALL NOT create a duplicate user or student record.

---

### Requirement 7: Enrollments

**User Story:** As a QA engineer, I want a full set of active, completed, and cancelled enrollments covering all teachers, so that teacher workload reports and enrollment statistics are non-zero.

#### Acceptance Criteria

1. THE DemoSeeder SHALL create 8 to 14 active `StudentEnrollment` records per teacher where `status = 'active'`, selecting instruments only from that teacher's own `teacher_instruments`.
2. THE DemoSeeder SHALL set `status = 'active'` for all enrollments in the primary batch and assign `skill_level` using the cycle `['beginner', 'intermediate', 'advanced', 'expert']` indexed by `enrollment_index % 4`.
3. THE DemoSeeder SHALL create between 35 and 45 additional enrollments distributed between `status = 'completed'` and `status = 'cancelled'`, each with a non-null `ended_at` date that is after `started_at` and on or before `2026-07-11`.
4. THE DemoSeeder SHALL build a composite key set of existing `(student_id, teacher_id, instrument_id)` triples — including soft-deleted rows — before inserting any enrollment.
5. IF an enrollment with the same `(student_id, teacher_id, instrument_id)` triple already exists (including soft-deleted), THEN THE DemoSeeder SHALL skip creating that enrollment.

---

### Requirement 8: Recurring Schedules

**User Story:** As a developer, I want every active enrollment to have a weekly recurring schedule, so that the calendar is populated with ongoing sessions.

#### Acceptance Criteria

1. THE DemoSeeder SHALL create exactly one `RecurringSchedule` per active enrollment, skipping enrollments that already have a recurring schedule.
2. THE DemoSeeder SHALL assign `weekday` as `enrollment_id % 6`, mapping 0 = Sunday through 5 = Friday, excluding Saturday.
3. THE DemoSeeder SHALL assign `start_time` from the set `['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00']` using index `enrollment_id % 8`.
4. THE DemoSeeder SHALL maintain a teacher-slot map keyed on `(teacher_id, weekday, start_time)` and, IF the slot is already occupied, SHALL cycle through available times before skipping and logging a warning with the `enrollment_id` and reason.
5. THE DemoSeeder SHALL assign a `room` from the list of rooms where `is_active = true`, cycling by enrollment index, and apply the same conflict-avoidance logic for room-slot collisions, logging a warning with the `enrollment_id` and reason if no slot is available.
6. THE DemoSeeder SHALL assign `duration_minutes` from `[45, 60, 90]` using index `enrollment_id % 3`.
7. THE DemoSeeder SHALL set `is_active = true` for all recurring schedules it creates.
8. WHEN a recurring schedule already exists for an enrollment, THE DemoSeeder SHALL NOT create a duplicate.

---

### Requirement 9: Class Sessions

**User Story:** As a sales person, I want the session calendar to show past, present, and future sessions with realistic statuses, so that the calendar never appears empty during a demo.

#### Acceptance Criteria

1. THE DemoSeeder SHALL generate `ClassSession` records for each active `RecurringSchedule` covering every matching weekday in the window from 60 days before `2026-07-11` to 60 days after `2026-07-11`.
2. FOR past sessions (session_date < `2026-07-11`), THE DemoSeeder SHALL set `status = 'completed'` for 70–80% of them, `status = 'missed'` for 10–16%, and `status = 'cancelled'` for 10–14%, using a deterministic formula based on `(session_index % 100)`.
3. FOR future sessions (session_date > `2026-07-11`), THE DemoSeeder SHALL set `status = 'scheduled'` for 93–97% and `status = 'cancelled'` for 3–7%, using a deterministic formula.
4. FOR sessions where `session_date = '2026-07-11'` (today), THE DemoSeeder SHALL set `status = 'scheduled'` for the majority, with up to 10% set to `'completed'` or `'cancelled'`. FOR sessions where `session_date = '2026-07-12'` (tomorrow), THE DemoSeeder SHALL set `status = 'scheduled'` for all of them.
5. THE DemoSeeder SHALL populate `session_fee`, `session_date`, `start_time`, `duration_minutes`, `room`, `student_id`, `teacher_id`, `instrument_id`, `enrollment_id`, and `recurring_schedule_id` for every session record it creates.
6. THE DemoSeeder SHALL build a set of existing `(enrollment_id, session_date)` pairs before inserting and SHALL skip any session whose pair already exists.
7. THE DemoSeeder SHALL only generate a session for a given date IF that date falls within the enrollment's `started_at` and `ended_at` (when set) boundaries.
8. THE DemoSeeder SHALL insert new session records in chunks of 500 rows per database call to stay within query size limits.

---

### Requirement 10: Attendance

**User Story:** As a QA engineer, I want attendance records for all past completed and missed sessions, so that the attendance report and student history pages show real data.

#### Acceptance Criteria

1. THE DemoSeeder SHALL create exactly one `ClassAttendance` record for every past `ClassSession` (session_date < `2026-07-11`) with `status = 'completed'` or `status = 'missed'` that does not already have an attendance record.
2. IF the session's `status = 'missed'`, THEN THE DemoSeeder SHALL set the attendance `status = 'absent'`.
3. IF the session's `status = 'completed'`, THEN THE DemoSeeder SHALL assign attendance `status` using the deterministic formula `session_id % 100`: 0–89 → `present`, 90–93 → `late`, 94–96 → `excused`, 97–99 → `absent`.
4. THE DemoSeeder SHALL set `marked_at` to `session_date` at `19:00:00` server local time for all attendance records it creates.
5. THE DemoSeeder SHALL set `student_id` on each attendance record to match the `student_id` of the corresponding `ClassSession`.
6. THE DemoSeeder SHALL use the composite key `(class_session_id, student_id)` to check for existing records and skip insertion if a matching row already exists.
7. THE DemoSeeder SHALL insert new attendance records in chunks of 500 rows per database call.

---

### Requirement 11: Subscriptions

**User Story:** As a billing admin, I want monthly subscription records for all active enrollments, so that the billing overview page shows subscription statuses and upcoming renewal dates.

#### Acceptance Criteria

1. THE DemoSeeder SHALL create one `Subscription` record per active enrollment where no subscription with the same `(student_id, teacher_id, instrument_id)` triple already exists.
2. THE DemoSeeder SHALL assign `payment_status` using the formula `enrollment_index % 20`: 0–12 → `paid`, 13–15 → `unpaid`, 16–19 → `overdue`.
3. THE DemoSeeder SHALL set `renewal_date` to a date 7–30 days before `2026-07-11` for `overdue` subscriptions, 2–9 days after `2026-07-11` for `unpaid`, and 15–45 days after `2026-07-11` for `paid`.
4. THE DemoSeeder SHALL set `sessions_used` to the actual count of `ClassSession` rows with `status = 'completed'` for that `enrollment_id`.
5. THE DemoSeeder SHALL set `sessions_allocated` to `max(sessions_used + 1, 4)`.
6. THE DemoSeeder SHALL set `monthly_fee` from the deterministic cycle `[2_500_000, 3_000_000, 3_500_000, 4_000_000, 4_500_000, 5_000_000]` indexed by `enrollment_index % 6`.

---

### Requirement 12: Invoices, Items, and Payments

**User Story:** As a billing admin, I want invoices in every status — paid, partially paid, issued, overdue, draft, and cancelled — so that the billing dashboard is fully populated.

#### Acceptance Criteria

1. THE DemoSeeder SHALL generate `1 + (enrollment_index % 3)` invoices per active enrollment, skipping enrollments that already have at least one invoice.
2. THE DemoSeeder SHALL assign invoice `status` using the 8-slot deterministic cycle indexed by `invoice_index % 8`: 0 → `paid`, 1 → `partially_paid`, 2 → `issued`, 3 → `overdue`, 4 → `paid`, 5 → `draft`, 6 → `overdue`, 7 → `cancelled`.
3. THE DemoSeeder SHALL create one `InvoiceItem` per invoice with `title` set to the enrollment's instrument name and `unit_price` in the range 3,000,000–6,500,000 IRR, derived deterministically from `enrollment_id`.
4. THE DemoSeeder SHALL set a discount of 200,000 IRR on invoices where `enrollment_index % 5 === 0`; all other invoices SHALL have `discount = 0`.
5. THE DemoSeeder SHALL create one `InvoicePayment` with `status = 'completed'` and `amount` equal to `(unit_price − discount + tax)` for every invoice with `status = 'paid'`.
6. THE DemoSeeder SHALL create one `InvoicePayment` with `status = 'completed'` and `amount` equal to `floor((unit_price − discount + tax) / 2)` for every invoice with `status = 'partially_paid'`.
7. THE DemoSeeder SHALL set `issue_date` to `(1 + invoice_index)` months before `2026-07-11` and `due_date` to `issue_date + 14 days`.
8. THE DemoSeeder SHALL NOT create any `InvoicePayment` record for invoices with `status = 'draft'` or `status = 'cancelled'`.
9. WHEN DemoSeeder runs a second time, THE DemoSeeder SHALL NOT create invoices for enrollments that already have at least one invoice row.

---

### Requirement 13: Leads

**User Story:** As a sales person, I want the CRM pipeline to show at least 40 leads at all stages of the pipeline, so that the CRM page is never empty during a demo.

#### Acceptance Criteria

1. THE DemoSeeder SHALL ensure at least 40 `Lead` records exist after seeding, creating only those leads whose `phone` does not already exist.
2. THE DemoSeeder SHALL include at least one lead for each `LeadStatusEnum` value: `new`, `contacted`, `interested`, `trial_scheduled`, `registered`, `lost`.
3. THE DemoSeeder SHALL distribute leads across all `LeadSourceEnum` values: `website`, `instagram`, `telegram`, `phone`, `walk_in`, `referral`, `other`.
4. THE DemoSeeder SHALL distribute leads across all `LeadPriorityEnum` values: `high`, `medium`, `low`.
5. THE DemoSeeder SHALL set `preferred_instrument_id` to an existing `instruments.id` for every lead.
6. THE DemoSeeder SHALL set `preferred_teacher_id` to an existing `teachers.id` that teaches the lead's preferred instrument for leads with `status` in `['interested', 'trial_scheduled', 'registered']`; IF no such teacher exists, THE DemoSeeder SHALL leave `preferred_teacher_id = null` and log a warning.
7. THE DemoSeeder SHALL set `next_follow_up_at` to a date 1–7 days before `2026-07-11` for leads with `priority = 'high'` and `status` not in `['registered', 'lost']`.
8. THE DemoSeeder SHALL set `assigned_to` to an existing `users.id` with `role` in `['admin', 'super_admin']`; IF no such user exists, THE DemoSeeder SHALL abort seeding leads and log an error.
9. THE DemoSeeder SHALL set `converted_student_id` to an existing `students.id` and `converted_at` to a date 1–30 days before `2026-07-11` for leads with `status = 'registered'`.
10. WHEN a lead with the same `phone` already exists, THE DemoSeeder SHALL NOT create a duplicate lead record.

---

### Requirement 14: Notifications

**User Story:** As a developer, I want the notification bell to show unread in-app notifications for the admin user, so that the UI notification indicator is never empty during a demo.

#### Acceptance Criteria

1. THE DemoSeeder SHALL create at least 30 notification records in the `notifications` table for the super admin user if fewer than 30 already exist for that user.
2. THE DemoSeeder SHALL create at least one notification for each `NotificationEventEnum` value: `student_created`, `enrollment_created`, `session_reminder`, `session_cancelled`, `attendance_marked`, `payment_due`, `payment_received`, `teacher_assigned`.
3. THE DemoSeeder SHALL set `notifiable_type = 'App\Models\User'` and `notifiable_id` to the super admin user's `id` for every notification it creates.
4. THE DemoSeeder SHALL set `data` to a valid JSON string with at least `title` and `body` keys whose values contain only Unicode characters in the Persian/Arabic range (U+0600–U+06FF) or whitespace.
5. THE DemoSeeder SHALL leave `read_at = null` for exactly `floor(total_notifications / 3)` notifications and set `read_at` to a timestamp between 30 days before `2026-07-11` and `2026-07-11` for the remainder.
6. WHEN the super admin already has 30 or more notification records in the `notifications` table, THE DemoSeeder SHALL insert zero additional notification records.

---

### Requirement 15: Idempotency and Safety

**User Story:** As a DevOps engineer, I want the DemoSeeder to be safe to run repeatedly without corrupting the database, so that I can re-run it during development without fear.

#### Acceptance Criteria

1. THE DemoSeeder SHALL use `firstOrCreate` or `updateOrCreate` for all records that are created individually, keyed on the unique business identifier (`phone` or `slug`).
2. THE DemoSeeder SHALL check for existing records by composite key before bulk-inserting: `(enrollment_id, session_date)` for sessions; `(class_session_id, student_id)` for attendance; `(student_id, teacher_id, instrument_id)` for subscriptions.
3. WHEN the DemoSeeder begins execution, THE DemoSeeder SHALL disable foreign key checks via `DB::statement('SET FOREIGN_KEY_CHECKS=0')` and SHALL re-enable them via `DB::statement('SET FOREIGN_KEY_CHECKS=1')` before the run completes.
4. THE DemoSeeder SHALL NEVER call `truncate()`, `Schema::drop()`, `DB::statement('DROP TABLE ...')`, `delete()` without a specific WHERE clause, or any migration-related Artisan command.
5. IF a phone number collision is detected when creating a `User` for a teacher, THEN THE DemoSeeder SHALL generate a fallback phone in the format `0900` followed by the teacher `id` zero-padded to 7 digits. IF a collision is detected for a student, THE DemoSeeder SHALL use `0901` followed by the student `id` zero-padded to 7 digits.
6. THE DemoSeeder SHALL initialise both `fake()->seed(20260709)` and `mt_srand(20260709)` once at the start of the run to ensure all generated values are deterministic and reproducible.
7. AFTER the full seeding run completes, THE DemoSeeder SHALL print a human-readable console summary showing the final record count for each of the following entities: institute_profile, rooms, instruments, users, teachers, students, enrollments, recurring_schedules, class_sessions, attendances, subscriptions, invoices, invoice_items, invoice_payments, leads, notifications.
8. WHEN a bulk-insert batch contains a row whose composite key already exists in the target table, THE DemoSeeder SHALL skip that row without aborting the rest of the batch.

---

### Requirement 16: Relational Integrity

**User Story:** As a developer, I want every generated record to satisfy all foreign key constraints, so that no orphaned records exist after seeding.

#### Acceptance Criteria

1. THE DemoSeeder SHALL only insert `ClassSession` rows where `enrollment_id`, `student_id`, `teacher_id`, `instrument_id`, and `recurring_schedule_id` each reference an existing row in their respective tables at the time of insertion.
2. THE DemoSeeder SHALL only insert `ClassAttendance` rows where `class_session_id` references an existing row in `class_sessions` and `student_id` references an existing row in `students` at the time of insertion.
3. THE DemoSeeder SHALL only insert `Invoice` rows where `student_id` references an existing row in `students`, and `enrollment_id` (when non-null) references an existing row in `student_enrollments`, at the time of insertion.
4. THE DemoSeeder SHALL only insert `Lead` rows where `preferred_instrument_id` (when non-null), `preferred_teacher_id` (when non-null), `assigned_to` (when non-null), and `converted_student_id` (when non-null) each reference an existing row in their respective tables at the time of insertion.
5. AFTER seeding completes, THE DemoSeeder SHALL query for orphaned rows (FK column references an id absent from the referenced table) in `class_sessions`, `class_attendances`, and `invoices` and SHALL log a warning containing the count and entity type for each orphaned group found.
6. IF an instrument `slug` from the teacher assignment list does not map to an existing active instrument, THEN THE DemoSeeder SHALL skip assigning that instrument to the teacher and log a warning with the slug value.
7. IF a required FK value (e.g., `enrollment_id` for a session) cannot be resolved to an existing row, THEN THE DemoSeeder SHALL skip creating that record and log a warning with the entity type and the unresolvable key value.

---

### Requirement 17: Data Realism and Coverage

**User Story:** As a sales person, I want every module to show realistic, contextually coherent data, so that customers never see empty pages, zero statistics, or obviously fake content during a demo.

#### Acceptance Criteria

1. THE DemoSeeder SHALL generate data such that the `students` table contains at least 85 rows with `status = 'active'`, the `teachers` table contains at least 14 rows with `status = 'active'`, and at least 1 `ClassSession` row has `session_date = '2026-07-11'`.
2. THE DemoSeeder SHALL generate data such that at least 1 `InvoicePayment` row with `status = 'completed'` has `created_at` in the calendar month of July 2026, so the dashboard revenue widget returns a non-zero value.
3. THE DemoSeeder SHALL generate sessions such that at least one session falls on a date within the current weekly view, the current monthly view, and the current yearly agenda view relative to `2026-07-11`.
4. THE DemoSeeder SHALL generate at least one `Lead` for each `LeadStatusEnum` value so that the CRM funnel chart displays all pipeline stages.
5. THE DemoSeeder SHALL generate at least one `Invoice` for each `InvoiceStatusEnum` value so that the billing status breakdown chart shows all categories.
6. THE DemoSeeder SHALL generate at least one `ClassAttendance` row for each `AttendanceStatusEnum` value so that the attendance report shows all status categories.
7. THE DemoSeeder SHALL set `name` and `full_name` fields for teachers, students, and leads to strings that: (a) consist only of Unicode characters in the Persian/Arabic block (U+0600–U+06FF) or whitespace, (b) contain no Latin characters or digits, and (c) consist of at least two space-separated parts.
8. THE DemoSeeder SHALL set all `phone` fields to strings matching the pattern `^09[0-9]{9}$` using a prefix from the valid Iranian mobile operator set (`0910`–`0919`, `0930`–`0939`, `0935`–`0935`, `0912`, `0911`, etc.).
9. THE DemoSeeder SHALL generate at least one `ClassSession` for each `SessionStatusEnum` value (`scheduled`, `completed`, `cancelled`, `missed`) so that the session-status chart widget on the dashboard displays all categories.
