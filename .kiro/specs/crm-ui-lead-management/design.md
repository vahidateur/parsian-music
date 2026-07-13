# Technical Design Document

## Overview

Complete CRM UI implementation for Lead Management covering five Blade views, Persian translation strings, and form enhancements. This is a **UI-only** spec — no backend changes.

---

## Design Rules

- Reuse existing Lead model.
- Reuse LeadService.
- Reuse LeadPolicy.
- Reuse existing Enums.
- Do not redesign backend.
- Keep controllers thin.
- Business logic stays in Services.
- Maximum 5 modified files per task.
- No unrelated refactoring.
- No duplicated code.
- No duplicated queries.
- Prevent N+1.
- Use eager loading.
- Use pagination.
- Reuse dashboard components.
- Reuse Blade components.
- No placeholder buttons.
- No dead links.
- Every visible action must work.
- Do not execute Artisan commands.
- Do not execute Git commands.
- Do not modify database schema unless explicitly requested.
- Prefer composition over inheritance.
- Prefer reusable methods over duplicated implementations.
- Keep methods under 40 lines whenever practical.
- Keep code compact and readable.
- Use transactions for multi-step operations.
- Follow existing project architecture.

---

## Architecture & Component Design

### View Layer

**Current State (No Changes Needed):**
- `resources/views/admin/leads/index.blade.php` — Complete with CSS vars, filters, sorting, pagination, overdue styling ✓
- `resources/views/admin/leads/partials/form-fields.blade.php` — Shared form partial, reusable ✓

**Gaps to Fill:**

| File | Gap | Solution |
|------|-----|----------|
| `show.blade.php` | Missing `start_date` field in Convert_Card | Add `<input type="date" name="start_date">` (optional) |
| `kanban.blade.php` | `draggable="true"` still on cards; missing static note | Remove draggable; add inline note below column headers |
| `create.blade.php` | Missing `@php` block with CSS vars | Add `$btnPrimary`, `$btnSecondary`, `$inputClass` vars |
| `edit.blade.php` | Missing `@php` block with CSS vars | Add same CSS vars as create |
| `lang/fa/admin.php` | ~50 missing translation keys | Add all lead-specific keys |

### Translation Strategy

All 50+ missing keys grouped by context:

**Navigation & Lead Management (5 keys):**
- `leads`, `manage_leads`, `new_lead`, `kanban_view`, `list_view`

**Kanban View (3 keys):**
- `leads_kanban`, `kanban_subtitle`, `no_leads_in_column`

**Show Page - Info Section (5 keys):**
- `lead_information`, `lead_timeline`, `lead_created`, `lead_last_updated`, `lead_converted`

**Show Page - Actions (8 keys):**
- `back_to_leads`, `edit_lead`, `delete_lead_confirm`, `update_status`, `assign_lead`, `schedule_follow_up`, `convert_lead`, `convert_enrollment_hint`

**Forms (7 keys):**
- `create_lead`, `update_lead`, `register_lead_desc`, `update_lead_desc`, `lead_full_name_placeholder`, `unassigned`, `overdue`

**Flash Messages (6 keys):**
- `lead_created_successfully`, `lead_updated_successfully`, `lead_deleted_successfully`, `lead_assigned_successfully`, `lead_followup_scheduled_successfully`, `lead_status_updated_successfully`, `lead_converted_successfully`

**Filters & Select (5 keys):**
- `all_priorities`, `all_sources`, `all_admins`, `assigned_admin`, `preferred_instrument`, `preferred_teacher`, `next_follow_up`, `source`, `priority`, `email`, `age`

**Timeline (2 keys):**
- `history_lead_created_desc`, `history_lead_status_desc`

**Student Conversion (1 key):**
- `view_student`

### Form Field Design

**Reusable Partial:** `partials/form-fields.blade.php`
- 11 fields: full_name, phone, email, age, source, priority, preferred_instrument_id, preferred_teacher_id, assigned_to, next_follow_up_at, notes
- Per-field error messages via `@error`
- Dynamic `old()` fallback for edit mode
- All validation bounds enforced client-side via HTML5

**Create & Edit Views:**
- Extend `layouts.dashboard`
- Yield `breadcrumb` and `content` sections
- Define `$btnPrimary`, `$btnSecondary`, `$inputClass` in `@php` block
- Include `partials/form-fields` with `$lead` = null (create) or model (edit)
- Form method: POST for create (route: `admin.leads.store`), PUT for edit (route: `admin.leads.update`)
- Cancel button links back to index or show page

### Convert to Student Form

**Location:** Show page sidebar, visible only when:
- Lead is not yet converted
- Lead status allows transition to Registered

**Fields:**
1. `skill_level` — select (optional, SkillLevelEnum)
2. `start_date` — date input (optional) — **NEW**
3. `notes` — textarea (optional) — in ConvertLeadData DTO

**Wiring:** POST `/admin/leads/{lead}/convert` → LeadController → LeadService::convert()

### Kanban View

**Columns:** 6 per LeadStatusEnum cases (New, Contacted, Interested, TrialScheduled, Registered, Lost)

**Column Header:**
- Persian label from `LeadStatusEnum::label()`
- Count badge with lead count per status
- **Static note below columns:** "تغییر وضعیت را از صفحه جزئیات سرنخ انجام دهید" (Edit status from lead detail page)

**Cards (removed `draggable="true"`):**
- Full name, phone, priority badge, preferred instrument, assigned admin
- Overdue indicator: `«سررسید گذشته»` in rose/red when `$lead->isOverdue()`
- Link to `/admin/leads/{id}`
- Order: latest created first (`.latest()` in controller)

**Empty Column:** Persian message "هیچ سرنخی در این ستون وجود ندارد"

### Overdue Styling (Three Views)

**Index View:**
- Row background: `bg-rose-500/[0.03]` when overdue
- Follow-up cell text: `text-rose-400 font-medium`

**Kanban View:**
- Card label: `«سررسید گذشته»` with `text-rose-400`

**Show Page:**
- Follow-up date value: `text-rose-400`
- Append suffix: `(سررسید گذشته)`

---

## Database & Backend

**No changes.** All backend components already exist:
- Lead model with `isOverdue()`, `isConverted()`, `isTerminal()` methods
- LeadStatusEnum with `canTransitionTo()`, `isTerminal()`, `label()`, `color()` methods
- LeadPriorityEnum, LeadSourceEnum, SkillLevelEnum with `label()`, `color()` methods
- LeadService with `convert()` method
- LeadPolicy with all authorization gates
- ConvertLeadData DTO (read-only)
- LeadController with all 12 actions fully implemented

---

## Correctness Properties (Property-Based Testing)

### Property 1: Translation Completeness
**Specification:** Every translation key referenced in lead views exists in `lang/fa/admin.php` with non-empty Persian value.

**Test:**
- Scan all lead views for `__('admin.X')` calls
- Verify each key exists in admin.php
- Verify value is not an empty string or raw key

**Implementation:** PHPUnit test iterating view files and checking translation array.

### Property 2: Form Field Consistency
**Specification:** Create and Edit forms render identical fields in identical order (via reusable partial).

**Test:**
- Render both forms
- Extract field HTML and compare structure
- Verify `old()` fallback works for both modes

**Implementation:** Browser/feature test comparing DOM elements.

### Property 3: Overdue Indicator Accuracy
**Specification:** Overdue indicator appears if and only if `$lead->isOverdue()` returns true AND status is not terminal.

**Test:**
- Create leads with various follow-up dates
- Assert overdue styling/text appears only when expected
- Assert does not appear for terminal statuses

**Implementation:** PHPUnit feature test with Carbon date mocking.

### Property 4: No N+1 Queries
**Specification:** Show page renders without additional per-field queries beyond initial eager-load.

**Test:**
- Profile query count on show page
- Assert exactly 1 query for lead + related data (eager-loaded)
- Assert no per-attribute queries for assignedUser, preferredInstrument, etc.

**Implementation:** Query logging in test.

### Property 5: Status Transition Validation
**Specification:** Only valid status transitions render as options in Update_Status select; invalid submissions trigger error redirect.

**Test:**
- Assert only `canTransitionTo()` true options render
- Attempt invalid transition
- Assert redirect with error flash message

**Implementation:** Feature test submitting invalid status.

---

## File Modifications Summary

| File | Type | Changes |
|------|------|---------|
| `lang/fa/admin.php` | Translation | Add ~50 lead-specific keys |
| `show.blade.php` | View | Add `start_date` field to Convert_Card |
| `kanban.blade.php` | View | Remove `draggable="true"`; add static note |
| `create.blade.php` | View | Add `@php` block with CSS vars |
| `edit.blade.php` | View | Add `@php` block with CSS vars |

**Total Modified Files:** 5 (within Design Rules limit)

---

## Implementation Sequence

1. **Translation Keys** — Add all 50+ keys to `lang/fa/admin.php`
2. **Create/Edit Forms** — Add `@php` blocks to both views
3. **Show Page Convert** — Add `start_date` field to Convert_Card form
4. **Kanban View** — Remove draggable, add static note
5. **Testing** — Verify all forms render, all links work, all translations load

---

## Reusable Components Used

- `layouts.dashboard` — extend for all views
- `x-dashboard.section-header` — Index, Kanban headers
- `x-dashboard.chart-container` — Index table, Kanban columns
- `x-dashboard.alert-card` — Flash messages
- `x-dashboard.empty-state` — No leads, no leads in column
- `admin.partials.sort-th` — Sortable column headers
- `partials/form-fields` — Create and Edit form inputs

---

## No Dead Links / Every Action Works

- View links: `/admin/leads/{id}` → show action ✓
- Edit links: `/admin/leads/{id}/edit` → edit action ✓
- Delete forms: `DELETE /admin/leads/{id}` → destroy action ✓
- Status updates: `PATCH /admin/leads/{id}` → updateStatus action ✓
- Assign: `PATCH /admin/leads/{id}/assign` → assign action ✓
- Follow-up: `PATCH /admin/leads/{id}/follow-up` → scheduleFollowUp action ✓
- Convert: `POST /admin/leads/{id}/convert` → convert action ✓
- Create: `POST /admin/leads` → store action ✓
- Navigation: Sidebar links to `/admin/leads` and `/admin/leads/kanban` ✓

---

## Next Steps

Move to `tasks.md` to define implementation tasks with dependencies and acceptance criteria.

