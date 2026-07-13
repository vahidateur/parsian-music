# Requirements Document

## Introduction

Complete CRM UI for Lead Management in the Parsian Music ERP SaaS admin panel. The backend is
fully implemented (Lead model, LeadService, LeadPolicy, LeadStatusEnum, LeadPriorityEnum,
LeadSourceEnum, ConvertLeadData DTO, and all routes + LeadController actions). This feature
covers only the UI layer: Blade views, partials, and the Persian (Farsi) translation strings
required to make all existing and new views render correctly.

**Current state (already built):**
- Five Blade views: `index`, `show`, `create`, `edit`, `kanban`
- All ten routes wired in `web.php`
- `LeadController` fully implemented with `index`, `kanban`, `create`, `store`, `show`, `edit`,
  `update`, `destroy`, `assign`, `scheduleFollowUp`, `updateStatus`, and `convert` actions

**Gaps identified (what this spec must deliver):**
1. All `admin.*` translation keys used in lead views are **missing** from `lang/fa/admin.php`
2. The `convert` form on `show.blade.php` is missing the `start_date` field (validated by controller)
3. The kanban view drag-and-drop is explicitly marked as "not wired yet" in a code comment
4. The `show` page timeline is static — it only shows `created_at`, `updated_at`, and
   `converted_at`; no real activity/note entries exist
5. Navigation sidebar already links to Leads; no new navigation work needed

---

## Glossary

- **Admin_Panel**: The Laravel Blade admin dashboard served under `/admin/*`
- **Lead**: A prospective student captured in the CRM before conversion
- **LeadController**: `App\Http\Controllers\Admin\LeadController` — the thin HTTP layer
- **LeadService**: `App\Services\LeadService` — all business logic lives here
- **LeadPolicy**: `App\Policies\LeadPolicy` — authorization gates
- **LeadStatusEnum**: Six statuses: `new → contacted → interested → trial_scheduled → registered / lost`
- **LeadPriorityEnum**: Three priorities: `high`, `medium`, `low`
- **LeadSourceEnum**: Seven sources: `website`, `instagram`, `telegram`, `phone`, `walk_in`, `referral`, `other`
- **ConvertLeadData**: Readonly DTO with optional `skillLevel`, `startDate`, `notes`
- **SkillLevelEnum**: `beginner`, `intermediate`, `advanced`, `expert`
- **Jalalian**: `App\Helpers\Jalalian` — converts Carbon dates to Persian (Shamsi) calendar format
- **Translation_File**: `lang/fa/admin.php` — the single Farsi translation file for the admin panel
- **Kanban_View**: The board view at `/admin/leads/kanban` grouping leads by status column
- **Index_View**: The table/list view at `/admin/leads` with filters, sorting, and pagination

---

## Requirements

### Requirement 1: Persian Translation Strings for All Lead Views

**User Story:** As an admin, I want all lead management pages to display readable Persian text,
so that the UI is fully functional and not broken by missing translation keys.

#### Acceptance Criteria

1. IF a translation key referenced via `__('admin.X')` in any lead view does not exist in
   `lang/fa/admin.php`, THEN that view SHALL be treated as incomplete — a key is considered
   missing when the raw dot-notation string (e.g., `"admin.leads"`) appears as rendered output.
2. WHEN a lead view is rendered, THE Admin_Panel SHALL display Persian text for every label,
   button, heading, placeholder, and flash message; no element shall display a raw
   dot-notation key string (e.g., `"admin.lead_created"`) as its visible text.
3. THE Translation_File SHALL include the following lead-specific keys, each mapped to a
   non-empty Persian string value (at minimum):
   `leads`, `manage_leads`, `new_lead`, `kanban_view`, `list_view`, `leads_kanban`,
   `kanban_subtitle`, `lead_information`, `lead_timeline`, `lead_created`, `lead_last_updated`,
   `lead_converted`, `back_to_leads`, `edit_lead`, `delete_lead_confirm`, `lead_created_successfully`,
   `lead_updated_successfully`, `lead_deleted_successfully`, `lead_assigned_successfully`,
   `lead_followup_scheduled_successfully`, `lead_status_updated_successfully`,
   `lead_converted_successfully`, `create_lead`, `update_lead`, `register_lead_desc`,
   `update_lead_desc`, `lead_full_name_placeholder`, `update_status`, `assign_lead`,
   `unassigned`, `schedule_follow_up`, `convert_lead`, `convert_enrollment_hint`,
   `overdue`, `no_leads_found`, `no_leads_in_column`, `all_priorities`, `all_sources`,
   `all_admins`, `assigned_admin`, `preferred_instrument`, `preferred_teacher`,
   `next_follow_up`, `source`, `priority`, `email`, `age`, `history_lead_created_desc`,
   `history_lead_status_desc`, `view_student`, `select`.
4. IF a key already exists in `lang/fa/admin.php` (e.g., `full_name`, `phone`, `notes`,
   `save`, `cancel`, `edit`, `delete`, `view`, `search`, `filter`, `clear`, `all_statuses`,
   `select_instrument`, `skill_level`, `skill_levels.*`, `select_teacher`, `select_level`,
   `optional`), THEN the Translation_File SHALL NOT add a duplicate entry for that key.

### Requirement 2: Lead List Page (Index)

**User Story:** As an admin, I want to see all leads in a sortable, filterable, paginated table,
so that I can quickly find and manage any lead.

#### Acceptance Criteria

1. WHEN an authenticated admin navigates to `/admin/leads` with no sort parameters, THE
   Admin_Panel SHALL display all leads paginated at 15 per page, ordered by `created_at`
   descending by default.
2. WHEN the admin submits the filter form, THE Index_View SHALL filter leads by any combination
   of `full_name` (partial match, case-insensitive), `phone` (partial match, case-insensitive),
   `status` (exact match against `LeadStatusEnum` values), `priority` (exact match against
   `LeadPriorityEnum` values), `source` (exact match against `LeadSourceEnum` values), and
   `assigned_to` (exact match by user ID).
3. WHEN the admin clicks a sortable column header for one of the 7 sortable columns
   (`full_name`, `phone`, `status`, `priority`, `source`, `created_at`, `next_follow_up_at`),
   THE Index_View SHALL re-sort the list by that column in the toggled direction (first click:
   descending; second click on same column: ascending) while preserving all active filter
   values in the query string.
4. THE Index_View SHALL display each lead row with: full name (linked to `/admin/leads/{id}`),
   phone, status badge (color-coded per `LeadStatusEnum::color()`), priority badge
   (color-coded per `LeadPriorityEnum::color()`), source, assigned admin name,
   next follow-up date.
5. WHEN a lead's `next_follow_up_at` is earlier than the current server time AND the lead's
   status is not `registered` or `lost`, THE Index_View SHALL apply a visually distinct
   background color to that row and display the follow-up date in rose/red text.
6. WHEN no leads match the active filters, THE Index_View SHALL display the empty-state
   component with a Persian message.
7. THE Index_View SHALL show navigation buttons to the Kanban view and to the create lead form.
8. IF the authenticated admin does not have `viewAny` permission per LeadPolicy, THEN THE
   Admin_Panel SHALL return an HTTP 403 response for the index route.

### Requirement 3: Lead Kanban View

**User Story:** As an admin, I want to see leads grouped by pipeline stage in a kanban board,
so that I can understand the funnel at a glance.

#### Acceptance Criteria

1. WHEN an authenticated admin navigates to `/admin/leads/kanban`, THE Kanban_View SHALL
   display one column per `LeadStatusEnum` case (6 columns total) in enum declaration order:
   `New`, `Contacted`, `Interested`, `TrialScheduled`, `Registered`, `Lost`.
2. EACH column SHALL display the column's Persian label (from `LeadStatusEnum::label()`),
   a count badge showing the number of leads in that status, and cards for all leads currently
   in that status.
3. EACH kanban card SHALL display: full name, phone, priority badge (color-coded per
   `LeadPriorityEnum::color()`), preferred instrument name, assigned admin name, and the
   Persian text `«سررسید گذشته»` in rose/red color when `$lead->isOverdue()` returns true.
4. EACH kanban card SHALL be an anchor link to `/admin/leads/{id}`.
5. WHEN a column contains no leads, THE Kanban_View SHALL display a Persian empty-column
   message (e.g., `«هیچ سرنخی در این ستون وجود ندارد»`).
6. THE Kanban_View SHALL render all lead cards without additional database queries beyond the
   initial eager-loaded collection (i.e., no per-card queries for `assignedUser` or
   `preferredInstrument`).
7. THE Kanban_View SHALL NOT render `draggable="true"` on any card element; instead it SHALL
   display a static informational note instructing the admin to open the lead detail page
   to change status.
8. THE Kanban_View SHALL show navigation buttons to the list view (`/admin/leads`) and to the
   create lead form (`/admin/leads/create`).
9. WITHIN each column, cards SHALL be ordered with the most recently created leads first
   (matching the controller's `.latest()` ordering).

### Requirement 4: Create Lead Form

**User Story:** As an admin, I want to create a new lead, so that I can capture prospective
students from any channel.

#### Acceptance Criteria

1. WHEN an authenticated admin with `create` permission submits the create form with valid
   data, THE LeadController SHALL pass the validated data to `Lead::create()`, set `status`
   to `LeadStatusEnum::New->value`, and redirect to `/admin/leads` with a Persian success
   flash message.
2. THE Create_Form SHALL include the following fields with the specified validation bounds:
   - `full_name`: required, string, max 255 characters
   - `phone`: required, string, max 20 characters
   - `email`: optional, valid email format, max 255 characters
   - `age`: optional, integer, min 1, max 120
   - `source`: required, must be a valid `LeadSourceEnum` value
   - `priority`: optional, must be a valid `LeadPriorityEnum` value, defaults to `medium`
   - `preferred_instrument_id`: optional, must exist in `instruments` table
   - `preferred_teacher_id`: optional, must exist in `teachers` table
   - `assigned_to`: optional, must exist in `users` table
   - `next_follow_up_at`: optional, valid date
   - `notes`: optional, string (no length limit enforced by controller)
3. WHEN validation fails, THE Create_Form SHALL re-render with `old()` values preserved and
   per-field Persian error messages displayed adjacent to that field.
4. THE Create_Form SHALL set `status` to `LeadStatusEnum::New` automatically and SHALL NOT
   expose a `status` field to the user.
5. IF the admin does not have `create` permission per LeadPolicy, THEN THE Admin_Panel SHALL
   return an HTTP 403 response.
6. WHEN the admin submits the create form with `full_name` or `phone` absent or empty, THE
   Create_Form SHALL re-render with validation errors and SHALL NOT persist any record.

### Requirement 5: Edit Lead Form

**User Story:** As an admin, I want to edit a lead's details, so that I can correct information
or update preferences.

#### Acceptance Criteria

1. WHEN an authenticated admin with `update` permission submits the edit form with valid data,
   THE LeadController SHALL call `$lead->update()` with the validated data (excluding `status`)
   and redirect to `/admin/leads/{id}` with a Persian success flash message.
2. THE Edit_Form SHALL pre-fill all of the following fields from the existing lead record
   using `old()` fallback: `full_name`, `phone`, `email`, `age`, `source`, `priority`,
   `preferred_instrument_id`, `preferred_teacher_id`, `assigned_to`, `next_follow_up_at`,
   `notes`.
3. THE Edit_Form SHALL NOT expose the `status` field — status transitions happen exclusively
   through the status-update action on the show page.
4. THE Edit_Form SHALL enforce the same validation rules as the create form:
   - `full_name`: required, string, max 255
   - `phone`: required, string, max 20
   - `email`: optional, valid email, max 255
   - `age`: optional, integer, min 1, max 120
   - `source`: required, valid `LeadSourceEnum` value
   - `priority`: optional, valid `LeadPriorityEnum` value
   - `preferred_instrument_id`: optional, exists in `instruments`
   - `preferred_teacher_id`: optional, exists in `teachers`
   - `assigned_to`: optional, exists in `users`
   - `next_follow_up_at`: optional, valid date
   - `notes`: optional, string
5. WHEN validation fails, THE Edit_Form SHALL re-render with `old()` values and per-field
   Persian error messages displayed adjacent to each failing field.
6. IF the admin does not have `update` permission per LeadPolicy, THEN THE Admin_Panel SHALL
   return an HTTP 403 response.

### Requirement 6: Lead Detail / Show Page

**User Story:** As an admin, I want to see all information about a lead on one page and perform
all actions from there, so that I don't need to navigate away to manage a lead.

#### Acceptance Criteria

1. WHEN an authenticated admin navigates to `/admin/leads/{lead}`, THE Admin_Panel SHALL display
   all lead fields: full name, phone, email, age, source, status, priority, assigned admin,
   preferred instrument, preferred teacher, next follow-up date (with overdue indicator), notes,
   created_at, and converted_at (if converted).
2. THE Show_Page SHALL display a static timeline showing at minimum: lead creation event
   (with source), last updated event (with current status), and conversion event (if converted,
   with a link to the student record).
3. THE Show_Page SHALL display a "Update Status" card in the sidebar showing only the valid
   next-state options per `LeadStatusEnum::canTransitionTo()`, hidden when the lead is terminal.
4. THE Show_Page SHALL display an "Assign Lead" card with a select of admin users and a save
   button wired to `PATCH /admin/leads/{lead}/assign`.
5. THE Show_Page SHALL display a "Schedule Follow-up" card with a datetime-local input wired to
   `PATCH /admin/leads/{lead}/follow-up`.
6. WHEN the lead's status allows transition to `Registered` and the lead is not yet converted,
   THE Show_Page SHALL display a "Convert to Student" card in the sidebar.
7. THE Convert_Card SHALL include: `skill_level` select (optional, from SkillLevelEnum),
   `start_date` date input (optional), and `notes` textarea (optional), all wired to
   `POST /admin/leads/{lead}/convert`.
8. WHEN the lead is already converted, THE Show_Page SHALL display a link to the student record
   and hide the Convert_Card.
9. THE Show_Page SHALL display success and error flash messages at the top of the content area.
10. IF the admin does not have `view` permission per LeadPolicy, THEN THE Admin_Panel SHALL
    return an HTTP 403 response.
11. THE Show_Page SHALL eager-load `assignedUser`, `preferredInstrument`, `preferredTeacher`,
    and `convertedStudent` to prevent N+1 queries.

### Requirement 7: Status Transition Enforcement in UI

**User Story:** As an admin, I want the status-update UI to only show valid next statuses,
so that I cannot attempt an illegal transition.

#### Acceptance Criteria

1. THE Update_Status_Select SHALL only render `<option>` elements for statuses where
   `$lead->status->canTransitionTo($candidate)` returns true.
2. WHEN the admin submits a status update with an invalid transition, THE Admin_Panel SHALL
   redirect back with a Persian error flash message (the DomainException message from
   `Lead::transitionTo()` surfaced to the UI).
3. WHILE the lead status is terminal (`Registered` or `Lost`), THE Show_Page SHALL hide the
   Update_Status_Card entirely.

### Requirement 8: Lead Conversion to Student Workflow

**User Story:** As an admin, I want to convert a qualified lead into a student (with optional
enrollment), so that the CRM pipeline flows into student management.

#### Acceptance Criteria

1. WHEN the admin submits the Convert_Card form with valid data, THE LeadController SHALL
   construct a `ConvertLeadData` DTO and call `LeadService::convert()`, then redirect to the
   new student's show page with a Persian success flash message.
2. WHEN `skill_level` is provided AND the lead has both `preferred_instrument_id` and
   `preferred_teacher_id`, THE LeadService SHALL create a `StudentEnrollment` as part of
   the conversion transaction.
3. WHEN `skill_level` is omitted, THE LeadService SHALL convert the lead to a student without
   creating an enrollment.
4. WHEN `start_date` is provided in the Convert_Card form, THE LeadService SHALL use it as the
   enrollment `started_at` date; otherwise the enrollment starts today.
5. IF the lead is already converted, THEN THE Admin_Panel SHALL return an error flash message
   and not attempt a second conversion.
6. IF the lead's status does not allow transition to `Registered` (i.e., status is not
   `TrialScheduled`), THEN THE Admin_Panel SHALL display a DomainException error flash message.
7. THE Convert_Card SHALL only be visible when `LeadPolicy::convert()` returns true for the
   authenticated user.

### Requirement 9: Lead Deletion

**User Story:** As an admin, I want to delete a lead, so that I can remove test data or
duplicate entries.

#### Acceptance Criteria

1. WHEN an authenticated admin with `delete` permission confirms the deletion dialog and
   submits the delete form, THE LeadController SHALL soft-delete the lead and redirect to the
   index with a Persian success flash message.
2. THE Delete_Button SHALL trigger a browser `confirm()` dialog with a Persian confirmation
   message before submitting the DELETE form.
3. IF the admin does not have `delete` permission per LeadPolicy, THEN THE Admin_Panel SHALL
   return an HTTP 403 response.

### Requirement 10: Authorization Gates on All Actions

**User Story:** As a system admin, I want all CRM actions to be gated by LeadPolicy, so that
unauthorized users cannot perform lead operations.

#### Acceptance Criteria

1. THE LeadController SHALL call `$this->authorize()` with the appropriate LeadPolicy method
   before every action: `viewAny` (index, kanban), `view` (show), `create` (create, store),
   `update` (edit, update, assign, scheduleFollowUp, updateStatus), `delete` (destroy),
   `convert` (convert).
2. IF the authenticated user's `id` equals `lead->assigned_to`, THEN THE Admin_Panel SHALL
   grant access for `view` and `update` policy checks regardless of the user's role, per
   `LeadPolicy::view()` and `LeadPolicy::update()` logic.
3. WHEN an unauthenticated user attempts any lead route, THE Admin_Panel SHALL return an
   HTTP 302 redirect to the login page without exposing any lead data.
4. WHEN an authenticated user fails a policy check on any lead action, THE Admin_Panel SHALL
   return an HTTP 403 response.

### Requirement 11: UI Consistency and Component Reuse

**User Story:** As a developer, I want all lead views to follow the same patterns as other admin
views, so that the codebase is maintainable and the UI is visually consistent.

#### Acceptance Criteria

1. THE Lead_Views SHALL extend `layouts.dashboard` and yield the `breadcrumb` and `content`
   sections following the same pattern as `admin.students.*` views.
2. THE Lead_Views SHALL reuse existing Blade components: `x-dashboard.section-header`,
   `x-dashboard.chart-container`, `x-dashboard.alert-card`, `x-dashboard.empty-state`,
   `x-dashboard.activity-timeline-item`, and `admin.partials.sort-th`.
3. THE `create.blade.php` and `edit.blade.php` views SHALL include an `@php` block defining
   `$btnPrimary`, `$btnSecondary`, and `$inputClass` Tailwind class variables, matching the
   pattern already used in `index.blade.php`, rather than repeating inline class strings.
4. THE Lead_Views SHALL display all dates via `\App\Helpers\Jalalian::fromCarbon()` for
   Persian calendar formatting.
5. THE Lead_Views SHALL use `LeadStatusEnum::cases()`, `LeadPriorityEnum::cases()`, and
   `LeadSourceEnum::cases()` directly in Blade templates for all dropdowns and badges,
   not hardcoded strings.
6. THE `partials/form-fields.blade.php` partial SHALL be reused by both create and edit forms
   with the `$lead` variable set to `null` (create) or the existing model (edit).

### Requirement 12: Overdue Follow-up Visibility

**User Story:** As an admin, I want overdue follow-up leads to stand out visually, so that I
never miss a follow-up deadline.

#### Acceptance Criteria

1. WHEN `$lead->isOverdue()` returns true, THE Index_View SHALL apply `bg-rose-500/[0.03]`
   to that table row and apply `text-rose-400` to the `next_follow_up_at` cell; WHEN
   `isOverdue()` returns false, THE Index_View SHALL apply no overdue-specific styling to
   that row.
2. WHEN `$lead->isOverdue()` returns true, THE Kanban_View card SHALL display the Persian
   text `«سررسید گذشته»` with `text-rose-400` styling below the card content; WHEN
   `isOverdue()` returns false, no overdue label SHALL appear on that card.
3. WHEN `$lead->isOverdue()` returns true, THE Show_Page SHALL render the follow-up date
   value with `text-rose-400` and append the Persian suffix `«(سررسید گذشته)»` adjacent to
   the date; WHEN `isOverdue()` returns false, the date SHALL render with default text color
   and no suffix.

### Requirement 13: No Backend Changes

**User Story:** As the project architect, I want the UI implementation to make zero backend
changes, so that the backend contracts are not accidentally broken.

#### Acceptance Criteria

1. IF any file outside of `resources/views/admin/leads/`, `resources/views/admin/leads/partials/`,
   and `lang/fa/admin.php` is modified during this implementation, THEN that modification
   SHALL be considered out of scope and reverted.
2. THE implementation SHALL NOT add new migrations, new models, new services, new DTOs, or
   new routes.
3. THE implementation SHALL NOT modify `app/Http/Controllers/Admin/LeadController.php`,
   `app/Services/LeadService.php`, `app/Models/Lead.php`, `app/Policies/LeadPolicy.php`,
   or any file under `app/Enums/`.
4. WHERE existing views already implement a feature correctly, THE implementation SHALL leave
   those views unchanged and only fill identified gaps.
