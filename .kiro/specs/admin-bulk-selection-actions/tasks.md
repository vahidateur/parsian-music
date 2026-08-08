# Implementation Plan: Admin Bulk Selection, Session Editing and Real Calendar Data

## Overview

این برنامه، طراحی موجود را به promptهای اجرایی افزایشی برای یک code-generation LLM تبدیل می‌کند. هر prompt فقط شامل نوشتن، تغییر یا تست کد است؛ هر مرحله بر خروجی مرحله قبل بنا می‌شود و در پایان همه اجزا به routeها، viewها و orchestrator فعلی متصل می‌شوند.

پیاده‌سازی بر مبنای PHP/Laravel برای backend و JavaScript/Alpine.js برای state و calendar UI انجام می‌شود. مسیرهای موجود، Policy/Gateها، Design System، FullCalendar و قراردادهای واقعی database باید reuse شوند؛ هیچ mock/fake/sample/default داده‌ای در production اضافه نشود.

## Tasks

- [x] 1. Establish shared domain contracts and persistence foundations
  - [x] 1.1 Create immutable DTOs/value objects for `BulkCommand`, `BulkItemResultData`, `BulkResultData`, `Filter_Context`, session edit/notes responses, calendar events and room options.
    - Encode entity/action/mode/result states and stable identifiers without duplicating business rules in controllers or Blade.
    - _Requirements: 3.1–3.2, 3.11–3.13, 5.4, 7.2, 8.2, 9.2, 10.1_
  - [x] 1.2 Add the additive `audit_records` migration, model and privacy-allowlisted `AuditRecordService`.
    - Store execution/rejection events, actor, action/context, aggregate counts and item reason identifiers; never store phone, notes, credentials or raw request payloads.
    - _Requirements: 4.9–4.13_
  - [x] 1.3 Add isolated factories/fixtures and generators for teachers, students, sessions, enrollments, rooms, protected dependencies, relation conflicts and policy actors used by unit and feature tests.
    - Keep fixtures database-backed where integration behavior is under test and use domain doubles for PBT iterations.
    - _Requirements: 3.3–3.10, 4.5–4.8, 7.2–7.15, 9.1–9.12, 10.1–10.10_

- [x] 2. Implement selection context and bulk backend execution
  - [x] 2.1 Implement `SelectionContextService` plus teacher/student list query services and `BulkRowViewData`.
    - Preserve existing filters, sort whitelist and pagination; sign an immutable entity/filter/sort snapshot that excludes page and supports expiry/invalidation and server-side all-filtered resolution.
    - _Requirements: 1.1–1.8, 2.1–2.6, 5.9_
  - [x] 2.2 Implement `BulkPreviewRequest`, `BulkRequest`, named preview/execution routes and thin `AdminBulkActionController` endpoints.
    - Validate one entity and one action per request, duplicate/unknown/wrong-entity IDs, signed context, CSRF/auth boundaries and context-specific 422 responses before item processing.
    - _Requirements: 2.2, 2.5–2.8, 5.1, 5.4–5.7_
  - [x] 2.3 Implement `BulkAuthorizationService` using `viewAny` and per-record `update`/`delete` Policy/Gate checks.
    - Ensure authorization occurs before resolving or mutating unauthorized selections and never depends on hidden UI controls or role-only checks.
    - _Requirements: 4.6, 5.1–5.3, 5.8, 6.1_
  - [x] 2.4 Implement `StatusTransitionAction`, `ProtectedDependencyChecker`, `DeleteTeacherAction`, `DeleteStudentAction` and `BulkActionService`.
    - Use entity Enums, persist same-state status writes as success, reject paused/graduated/unknown raw statuses, process valid items in independent locked transactions, and preserve protected dependencies without cascading.
    - _Requirements: 3.3–3.10, 4.5–4.8, 6.1–6.7_
  - [x] 2.5 Implement `BulkResultResource` and localized result/error payloads.
    - Return stable selection/context metadata when item details are absent; otherwise return each stable ID once with localized reason category and complete/partial outcome classification.
    - _Requirements: 3.11–3.13, 6.2–6.4, 6.8_
  - [x] 2.6 Wire accepted and rejected bulk requests to exactly one execution or rejected-operation audit event after processing, including privacy filtering and aggregate/item outcomes.
    - Do not create execution audit data for canceled confirmations; preserve successful item commits when unrelated items fail.
    - _Requirements: 4.9–4.13, 6.7_
  - [ ]* 2.7 Write the Eris PBT for **Property 1: Selection-context isolation** using the exact tag `Feature: admin-bulk-selection-actions, Property 1: Selection-context isolation`.
    - Generate entity/page/filter/sort transitions and assert authorized-only selection, context retention, invalidation and no cross-context execution.
    - **Validates: Requirements 1.1, 1.6, 2.3–2.6**
  - [ ]* 2.8 Write the Eris PBT for **Property 3: Enum-driven status action idempotence** using the exact design-property tag.
    - Cover active/inactive teachers and students, same-state writes, paused/graduated rejection and unknown raw statuses.
    - **Validates: Requirements 3.3–3.10**
  - [ ]* 2.9 Write the Eris PBT for **Property 4: Protected-dependency deletion invariant** using the exact design-property tag.
    - Assert protected entity/relations survive, eligible deletion removes only the target, and the confirmation-warning predicate requires an existing selected item.
    - **Validates: Requirements 4.2, 4.5–4.8**
  - [ ]* 2.10 Write the Eris PBT for **Property 5: Malformed-request atomicity** using the exact design-property tag.
    - Generate unsupported action/entity, empty, duplicate, wrong-entity, unknown/tampered context and unauthorized requests; compare complete database snapshots before/after.
    - **Validates: Requirements 2.7–2.8, 5.1–5.8, 6.6**
  - [ ]* 2.11 Write the Eris PBT for **Property 6: Bulk-result conservation** using the exact design-property tag.
    - Assert count conservation, unique item IDs, success classification for completed same-state writes, and complete versus partial outcomes including all-failed/all-skipped cases.
    - **Validates: Requirements 3.7, 3.11–3.13, 6.1–6.4**
  - [x]* 2.12 Add focused bulk unit/feature/security tests for routes, CSRF/auth, context preview, dependency checks, per-item transactions, partial outcomes and audit privacy.
    - Include disappeared-after-selection as a concurrent skip and malformed input as atomic rejection.
    - _Requirements: 2.1–2.8, 4.1–4.13, 5.1–5.9, 6.1–6.8_

- [x] 3. Build the reusable teacher/student bulk-selection UI
  - [x] 3.1 Integrate `toolbar.blade.php`, `confirmation-dialog.blade.php` and `result-summary.blade.php` into the existing teacher and student list views.
    - Render exactly one semantic row control only for selectable rows, one header control, localized labels and server-provided allowed actions without queries or business logic in Blade.
    - _Requirements: 1.1–1.8, 3.1, 12.1–12.3, 12.7_
  - [x] 3.2 Implement the Alpine selection state machine for current-page selection, entity/filter/sort/page invalidation, refresh/logout clearing and non-persistent browser-session behavior.
    - Maintain selected count and header checked/indeterminate/unchecked states from selectable visible IDs only.
    - _Requirements: 1.2–1.6, 1.8, 2.3–2.4, 12.4_
  - [x] 3.3 Implement explicit all-filtered preview, server-resolved count, delete confirmation, exactly-once submission, pending state, recovery actions and accessible live result/error announcements.
    - Use semantic dialog markup, `x-trap`, Escape/backdrop/close behavior, approved Button/Card variants and no inline styles or handlers.
    - _Requirements: 2.1–2.8, 4.1–4.4, 4.12, 12.2–12.5, 12.7_
  - [ ]* 3.4 Write the fast-check PBT for **Property 2: Header selection invariant** using the exact design-property tag and at least 100 cases.
    - Generate selectable/non-selectable visible row sets and assert header state plus exclusion of non-selectable rows.
    - **Validates: Requirements 1.1, 1.2, 1.5, 1.7**
  - [ ]* 3.5 Add browser/accessibility tests for checkbox semantics, indeterminate state, all-filtered confirmation/cancel/confirm, live announcements, keyboard behavior and context clearing.
    - Cover mobile touch targets and no horizontal overflow at the mandated viewport widths.
    - _Requirements: 1.1–1.8, 2.1–2.8, 4.1–4.4, 12.1–12.7_

- [x] 4. Implement relation, room and session-edit backend services
  - [x] 4.1 Implement `RelationPathResolver` with enrollment/direct tuple loading and conflict/missing-path classification.
    - Return one canonical `ResolvedRelationPath`; never combine names from conflicting paths and expose only stable IDs in diagnostics.
    - _Requirements: 7.3, 7.8–7.9, 9.3–9.6_
  - [x] 4.2 Implement `RoomResolver` and `RoomOptionProvider` against persisted `Room` records.
    - Support active/inactive/unresolved/null resolution, exact-name validation, batch lookup and capacity checks for the legacy string column; never create synthetic options.
    - _Requirements: 7.4, 10.1–10.10_
  - [x] 4.3 Implement `SessionEditRequest`, named `admin.sessions.edit/update` routes and thin `ClassSessionController` edit/update orchestration.
    - Whitelist editable fields, reject protected fields when submitted, validate persisted relations, room rules, dates/times/duration/status, `updated_at` and signed return context.
    - _Requirements: 7.1–7.5, 7.10–7.12_
  - [x] 4.4 Implement transactional `SessionEditService` and conflict re-checks.
    - Lock and re-read the session, resolve one relation path, reload Room, rerun schedule conflicts on relevant changes, update only permitted fields, and keep subscription/derived counters consistent or roll back together.
    - _Requirements: 7.6–7.10, 7.15_
  - [x] 4.5 Migrate existing session create/store flow to `RoomOptionProvider` and the same Form Request/service room contract.
    - Remove hardcoded room names while preserving existing route behavior and allowing unchanged inactive/unresolved historical room values only on permitted edits.
    - _Requirements: 7.4, 10.3–10.6_
  - [x] 4.6 Implement `UpdateSessionNotesRequest`, `SessionNotesNormalizer` and transactional `SessionNotesService`.
    - Authorize before notes resolution/validation, trim Unicode boundaries, normalize empty to null, derive length limits from schema/config, enforce optimistic concurrency and return an HTML-safe persisted DTO.
    - _Requirements: 8.1–8.10, 11.3, 11.6–11.7_
  - [ ]* 4.7 Write the Eris PBT for **Property 7: Session edit protected-field and failure invariant** using the exact design-property tag.
    - Generate valid/invalid permitted edits, protected-field attempts, relation/room/conflict/concurrency failures and assert full original snapshot preservation on failure.
    - **Validates: Requirements 7.2, 7.5–7.10**
  - [ ]* 4.8 Write the Eris PBT for **Property 8: Session notes normalization and optimistic round trip** using the exact design-property tag.
    - Generate accepted notes, boundary whitespace, empty values and stale tokens; assert normalized persistence, null placeholder and authoritative persisted value after failure.
    - **Validates: Requirements 8.1–8.8, 11.6–11.7**
  - [ ]* 4.9 Add session service unit/feature tests for protected fields, Form Request errors, real room validation, relation conflicts, conflict reruns, rollback, optimistic tokens and derived counters.
    - Assert missing sessions never create replacements and controllers remain orchestration-only.
    - _Requirements: 7.1–7.15, 8.1–8.10_

- [x] 5. Add session-list and session-edit views
  - [x] 5.1 Extend `admin.sessions.index` with eager-loaded `SessionDisplayData`, authorized named edit links and persisted active/inactive room filter options.
    - Keep pagination/filter/sort context and display inactive historical or unresolved legacy room values without query execution in Blade.
    - _Requirements: 7.1, 7.12–7.14, 10.2, 10.5–10.6_
  - [x] 5.2 Implement the session edit form using semantic labels, existing Design System Button/Card/Table variants and the exact editable field set.
    - Render active replacement rooms, historical inactive values and unavailable legacy values honestly; preserve signed return context and localized field errors.
    - _Requirements: 7.2–7.5, 7.10–7.12, 10.3–10.6, 12.1–12.3, 12.7_
  - [ ]* 5.3 Add browser/feature tests for named edit entry points, persisted values, protected-field rejection, room option behavior and same filter/sort/page redirect.
    - Assert no fake/default room or replacement session is rendered.
    - _Requirements: 7.1–7.14, 10.2–10.6_

- [x] 6. Implement the real calendar API and room-aware query pipeline
  - [x] 6.1 Implement `CalendarEventRequest` and `CalendarQueryService` for valid date ranges, persisted filters, eager-loaded enrollment/direct relations and one explicit parameterized room batch query.
    - Preserve the existing ۹۲-day contract; reject missing/malformed/reversed/oversized ranges and invalid filters before returning data.
    - _Requirements: 9.1, 9.3, 9.8, 10.7–10.8, 12.9_
  - [x] 6.2 Update `CalendarEventResource` and `SessionDisplayMapper` to map only persisted session data.
    - Include stable ID, date/time/end, duration, status/label, notes/version, room, `Room_Resolution`, relation DTO and `can_update_notes` without queries or mixed fallback names.
    - _Requirements: 9.2, 9.4–9.7, 11.1–11.2, 11.8_
  - [x] 6.3 Wire `CalendarController` and real room filter options to the existing named `admin.calendar.events` feed.
    - Resolve active/inactive rooms from database records, return exact filter-scoped sessions, preserve empty results and never emit client-only/fake data.
    - _Requirements: 9.1, 9.10, 10.1, 10.7–10.10, 12.11–12.12_
  - [x] 6.4 Add relation-conflict diagnostics and generic error handling at the calendar boundary.
    - Return the defined 409/422/500 responses, log only stable session/relation identifiers for conflicts, and exclude SQL, stack traces, credentials and sensitive values.
    - _Requirements: 9.6, 9.8–9.9, 10.8, 12.12_
  - [ ]* 6.5 Write the Eris/domain PBT for **Property 9: Calendar source consistency** using the exact design-property tag.
    - Generate persisted sessions with enrollment/direct paths and assert same-record scalar mapping, one consistent relation path and no event on conflict.
    - **Validates: Requirements 9.1–9.7, 11.1–11.2**
  - [ ]* 6.6 Write the Eris/domain PBT for **Property 10: Calendar filter scoping** using the exact design-property tag.
    - Generate valid persisted teacher/student/instrument/room filters and invalid room filters; assert every event matches and invalid input never broadens the query.
    - **Validates: Requirements 9.1, 9.8, 10.7–10.8**
  - [ ]* 6.7 Write the Eris/domain PBT for **Property 11: Room-option referential validity** using the exact design-property tag.
    - Generate active, inactive, null and unresolved room states and assert option ID/name referential validity, inactive exclusion from session input and display-only legacy values.
    - **Validates: Requirements 10.1–10.10**
  - [ ]* 6.8 Write the fast-check PBT for **Property 12: No fabricated fallback data** using the exact design-property tag and at least 100 cases.
    - Generate empty/null/unresolved/error conditions and assert only persisted raw unavailable values, localized placeholders or errors are rendered; successful status rendering has no error indicator.
    - **Validates: Requirements 7.13–7.14, 8.5, 8.8–8.9, 9.9–9.11, 10.5–10.6, 11.2, 11.8, 12.12**
  - [ ]* 6.9 Add calendar integration/resource tests for eager loading, query-free mapping, one event per real matching session, range/filter validation, room resolution, 409 conflict logging, generic 500 and empty results.
    - Assert no N+1 and no mock/fake/sample/default session/person/instrument/room data.
    - _Requirements: 9.1–9.12, 10.1–10.10, 12.9, 12.12_

- [x] 7. Extend the existing calendar UI and notes drawer
  - [x] 7.1 Connect the existing FullCalendar orchestrator and filter modules to the real event/resource contract and persisted room options.
    - Preserve last successfully rendered real events on feed errors, show retry on failure, and show the existing localized empty state for an honest empty response.
    - _Requirements: 9.10–9.12, 10.1, 10.7–10.8, 12.11–12.12_
  - [x] 7.2 Extend the existing event drawer with Alpine persisted/draft notes state and the named notes PATCH endpoint.
    - Implement authorization-aware edit control, duplicate-submit prevention, busy state, success replacement/refetch, 403/409/422/500 retry behavior and draft/persisted separation.
    - _Requirements: 8.1–8.8, 8.10, 9.12, 11.1–11.7_
  - [x] 7.3 Apply the existing RTL, Design System, accessibility and responsive contracts to bulk UI, session list/edit and calendar drawer.
    - Preserve semantic controls, `role=dialog`, `aria-modal`, heading linkage, `x-trap`, Escape/focus restoration, live announcements, reduced-motion behavior, logical CSS and 44px coarse-pointer targets at 390–1920px.
    - _Requirements: 11.3–11.8, 12.1–12.10_
  - [ ]* 7.4 Add browser/accessibility/visual smoke tests for real calendar events, drawer focus lifecycle, notes states, retry/refetch, room resolution, RTL labels, reduced motion, touch targets and no horizontal overflow at 390, 430, 768, 1024, 1366, 1600 and 1920 widths.
    - Assert no inline presentation styles/handlers and no client-only persisted data contracts.
    - _Requirements: 8.1–8.8, 9.10–9.12, 11.1–11.8, 12.1–12.12_

- [x] 8. Complete authorization, audit and relation-observability coverage
  - [x] 8.1 Add named-route security feature tests covering authentication, CSRF, Policy/Gate bypass attempts, authorization-before-resolution and wrong-entity/tampered-context rejection for every state-changing endpoint.
    - Assert forbidden requests do not resolve protected targets or mutate records; notes authorization precedes notes validation/resolution.
    - _Requirements: 5.1–5.8, 8.10, 11.3, 12.11_
  - [ ]* 8.2 Write the Eris/integration PBT for **Property 13: Authorization non-mutation and ordering** using the exact design-property tag.
    - Generate unauthorized actors and all protected operation types; assert forbidden responses, unchanged records and notes authorization ordering.
    - **Validates: Requirements 5.1–5.3, 5.8, 8.10, 11.3**
  - [ ]* 8.3 Write the Eris/integration PBT for **Property 14: Audit completeness and privacy** using the exact design-property tag.
    - Generate accepted/rejected/canceled bulk flows and assert exactly one appropriate event, complete aggregate metadata and absence of sensitive fields.
    - **Validates: Requirements 4.9–4.13**
  - [ ]* 8.4 Write the Eris/integration PBT for **Property 15: Relation-conflict observability** using the exact design-property tag.
    - Generate every enrollment/direct identity conflict and assert defined 409 response, no mixed event and ID-only administrative diagnostic.
    - **Validates: Requirement 9.6**
  - [ ]* 8.5 Add audit/security integration tests for exactly-once execution/rejection events, no event on confirmation cancel, privacy allowlist, conflict diagnostics and successful-item preservation.
    - Verify policy, CSRF and transaction boundaries through the real named routes.
    - _Requirements: 4.9–4.13, 5.1–5.8, 6.6–6.8, 9.6_

- [x] 9. Wire the complete feature and verify cross-module contracts
  - [x] 9.1 Register all named routes, policy mappings, service bindings, resources, view data providers and frontend module imports without introducing anonymous routes or duplicate UI components.
    - Ensure controllers remain thin and all mutations use the specified Form Request → Policy → Service/Action → Resource/DTO boundaries.
    - _Requirements: 5.8–5.9, 7.1, 8.10, 9.1, 11.3, 12.8, 12.11_
  - [x] 9.2 Add cross-module feature tests for post-session-edit calendar refresh, post-notes-save event refresh, room filter consistency, list context preservation and real-data-only responses.
    - Cover persisted values across list, edit form, calendar event and drawer without fallback substitution.
    - _Requirements: 7.12–7.14, 8.6–8.8, 9.7, 9.10–9.12, 10.5–10.8_
  - [ ]* 9.3 Add the final automated acceptance suite that runs the required unit, PBT, integration, security, browser/accessibility and responsive checks with property tags discoverable in test output.
    - Include at least 100 iterations for each PBT and preserve the existing project test/build conventions.
    - _Requirements: 1.1–1.8, 2.1–2.8, 3.1–3.13, 4.1–4.13, 5.1–5.9, 6.1–6.8, 7.1–7.15, 8.1–8.10, 9.1–9.12, 10.1–10.10, 11.1–11.8, 12.1–12.12_

- [x] 10. Checkpoint - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional test tasks and may be skipped for a faster MVP; core implementation tasks are not optional.
- Each task is an incremental code-generation prompt and references concrete requirements. No task asks for deployment, user acceptance testing, training, documentation-only work or production data changes.
- All 15 correctness properties from `design.md` have dedicated PBT tasks. Properties use Eris for PHP/domain behavior and fast-check 4.3.0 for JavaScript pure helpers; each must run at least 100 cases and include the exact feature/property tag.
- Unit, integration, security, browser and visual checks complement PBT. PBT must not replace Eloquent/I/O, policy wiring, audit persistence or layout/accessibility tests.
- Use existing routes/components/Design System and preserve RTL, reduced-motion, no-inline-style/handler, no-query-in-Blade, eager-loading and real-data-only constraints.
- Do not change `requirements.md`, `design.md` or application code as part of this planning workflow. After this file is created, execution begins by opening `tasks.md` and clicking **Start task** next to an item.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.3"] },
    { "id": 1, "tasks": ["1.2", "2.1", "4.1", "4.2"] },
    { "id": 2, "tasks": ["2.2", "2.3", "2.4", "4.3", "6.1"] },
    { "id": 3, "tasks": ["2.5", "2.6", "4.4", "4.5", "4.6", "6.2"] },
    { "id": 4, "tasks": ["3.1", "3.2", "5.1", "6.3", "6.4"] },
    { "id": 5, "tasks": ["2.7", "2.8", "2.9", "2.10", "2.11", "3.3", "4.7", "4.8", "5.2", "6.5", "6.6", "6.7", "6.8", "7.1", "7.2"] },
    { "id": 6, "tasks": ["2.12", "3.4", "4.9", "5.3", "6.9", "7.3", "8.1"] },
    { "id": 7, "tasks": ["3.5", "7.4", "8.2", "8.3", "8.4", "8.5", "9.1"] },
    { "id": 8, "tasks": ["9.2"] },
    { "id": 9, "tasks": ["9.3"] }
  ]
}
```
