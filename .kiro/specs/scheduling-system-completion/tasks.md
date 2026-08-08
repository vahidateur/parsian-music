# Implementation Plan: Scheduling System Completion

## Overview

Implement the approved design incrementally in PHP 8.3/Laravel and JavaScript, extending only existing scheduling owners. The plan preserves named routes, HTTP verbs, resource/DTO JSON shapes, stable IDs, legacy room strings, direct/enrollment relation authority, policies, and the no-parallel-engine rule. The approval gate is already satisfied; the deferred evidence-backed index decision remains out of scope.

## Tasks

- [ ] 1. Complete the read-only calendar projection and compatible client behavior
  - [ ] 1.1 Extend validated six-filter calendar querying at the existing query boundary
    - **Implementation owner / files:** `app/Http/Requests/Admin/CalendarEventRequest.php`, `app/Services/CalendarQueryService.php`, and `app/Models/Concerns/ScopesForSessionFilters.php`.
    - Validate supported date, teacher, student, instrument, legacy-room, status, and maximum-span inputs without writes; apply every supplied filter pre-materialization using inclusive dates, canonical direct/enrollment relation paths, exact legacy room strings, enum status, eager loading, and stable ordering. Omitted filters must add no predicate and query exclusions must retain their failing predicate/reason.
    - Preserve compatible validation failures, `$fillable`/Eloquent/policy boundaries, and direct-versus-enrollment relation authority; do not add a query owner or an unapproved index.
    - _Requirements: 1.2, 1.6, 2.1–2.7, 2.9, 9.1–9.2, 9.7–9.8, 10.3, 10.6, 12.1–12.6_

  - [ ] 1.2 Add ordered seven-boundary provenance tracing without changing the persisted event contract
    - **Implementation owner / files:** `app/Http/Controllers/Admin/CalendarController.php`, `app/Http/Resources/CalendarEventResource.php`, and `app/Services/SessionDisplayMapper.php`.
    - Record persisted result, query result, resource result, and endpoint JSON boundaries with inclusive range, all six filters, counts, ordered stable IDs, and explicit reasons; classify only the first evidenced difference as upstream missing persistence, intentional query exclusion, explicit relation error, or downstream difference.
    - Keep calendar reads free of generation, repair, synthetic events, and session writes; retain existing relation-error handling, event keys, resource/DTO serialization, and generic safe failures.
    - _Requirements: 1.1–1.8, 2.9, 9.1–9.2, 10.1–10.2, 10.5–10.6, 12.1–12.5_

  - [ ] 1.3 Correct FullCalendar transport, reject-only normalization, and calendar-surface compatibility
    - **Implementation owner / files:** `resources/js/calendar/fullcalendar.js` and `resources/js/calendar/calendar-app.js`.
    - Send FullCalendar’s complete visible inclusive range and only supported filters; trace fetch, normalized, and rendered boundaries; retain every valid persisted event exactly once and record explicit malformed-event rejection without synthesis, substitution, or hiding valid IDs.
    - Preserve existing composition, RTL date/time readability, keyboard/focus behavior, semantic status feedback, non-color cues, reduced-motion behavior, and 44px controls. Implement the approved initial-render fit gate at the specified viewports: render when current content fits, block only when fit fails or cannot be determined, and never block based only on possible future overflow.
    - Preserve existing Blade/route contracts and do not add inline handlers or frontend business logic.
    - _Requirements: 1.5–1.8, 2.8, 2.10, 10.4, 11.1–11.6, 12.2–12.4_

  - [ ]* 1.4 Extend compatible calendar feature and query-count coverage
    - **Implementation owner / files:** `tests/Feature/Admin/CalendarControllerTest.php` and `tests/Feature/Admin/CalendarQueryPerformanceTest.php`.
    - Cover all six filters, inclusive endpoints, omitted filters, invalid/excessive spans, stable event keys, direct/enrollment paths, eager loading/query-count baseline, read-only zero results, relation errors, authorization secrecy, and seven-boundary example traces.
    - _Requirements: 1.1–1.8, 2.1–2.10, 9.1–9.8, 10.1–10.6, 12.2–12.5_

  - [ ]* 1.5 Write property test for filtered persisted membership
    - **Property 1: Filtered persisted membership.** Create `tests/js/properties/calendar-filtered-membership.property.test.js` using pinned `fast-check` 4.3.0, at least 100 runs, and comment tag `Feature: scheduling-system-completion, Property 1: Filtered persisted membership`.
    - Assert ordered persisted membership, omitted-filter behavior, canonical identities, exact legacy rooms, enum statuses, inclusive dates, and recorded query exclusions.
    - **Validates: Requirements 1.2, 2.1–2.5, 2.7, 2.9.**

  - [ ]* 1.6 Write property test for read-only projection identity
    - **Property 2: Read-only projection identity.** Create `tests/js/properties/calendar-read-only-projection.property.test.js` with 100+ generated persisted matching sessions.
    - Assert every matching stable ID and persisted display field remains exactly once across projection boundaries, with zero session writes and no synthetic event.
    - **Validates: Requirements 1.7, 9.1–9.2, 12.5.**

  - [ ]* 1.7 Write property test for earliest provenance difference
    - **Property 3: Earliest provenance difference.** Create `tests/js/properties/calendar-earliest-provenance-difference.property.test.js` with 100+ generated boundary traces.
    - Assert the first unequal count/ordered-ID boundary is reported with full trace context and no invented cause or downstream compensation.
    - **Validates: Requirements 1.4–1.6.**

  - [ ]* 1.8 Write property test for visible-range transport preservation
    - **Property 4: Visible-range transport preservation.** Create `tests/js/properties/calendar-visible-range-transport.property.test.js` with 100+ generated visible ranges and selected days.
    - Assert the request preserves visible inclusive start/end values and a selected day cannot narrow either endpoint.
    - **Validates: Requirements 2.8.**

  - [ ]* 1.9 Write property test for reject-only normalization
    - **Property 5: Reject-only normalization.** Create `tests/js/properties/calendar-reject-only-normalization.property.test.js` with 100+ mixed valid/malformed payloads.
    - Assert valid persisted IDs survive unchanged while every malformed event receives an explicit rejection reason and no event is created, repaired, substituted, or hidden.
    - **Validates: Requirements 1.8, 2.10.**

  - [ ]* 1.10 Write property test for invalid-range read-only behavior
    - **Property 14: Invalid range is read-only.** Create `tests/js/properties/calendar-invalid-range-read-only.property.test.js` with 100+ over-maximum spans.
    - Assert compatible validation and an unchanged persisted `ClassSession` set.
    - **Validates: Requirements 2.6, 9.7.**

- [ ] 2. Implement rolling recurrence, occurrence safety, and the approved invocation boundary
  - [ ] 2.1 Enforce occurrence and retry identity at the existing scheduling persistence boundary
    - **Implementation owner / files:** `app/Models/ClassSession.php`, `app/Models/RecurringSchedule.php`, and one new additive migration under `database/migrations/` whose physical representation is derived from the inspected existing schema.
    - Implement the approved MySQL 8 persistence invariant: a database-enforced `Occurrence_Key` duplicate guard and persisted retry identity, preserving current session IDs, legacy room strings, historical data, existing casts, active semantics, and direct/enrollment relations.
    - Add no broad calendar/conflict/legacy-room index: retain the deferred evidence gate and only include the minimum additive enforcement required by the approved invariant; fail before mutation if the target database cannot enforce it.
    - _Requirements: 3.2–3.3, 4.2–4.7, 7.3, 9.3–9.5, 10.3, 12.1, 12.5–12.6_

  - [ ] 2.2 Extend the sole generator for rolling reconciliation and register the approved console invocation
    - **Implementation owner / files:** `app/Services/SessionGeneratorService.php` and `routes/console.php`.
    - Reconcile each active schedule over one effective whole-day horizon (default 30; accept 1–365), cover month boundaries, lock the `RecurringSchedule` and evaluate room/conflict state inside the same transaction as the final write, and use persisted retry identity for identical success/retry and changed-input rejection.
    - Roll back run-created rows on disabled, conflict, no-room, lock, persistence, or retry failures; preserve history and return controlled compatible outcomes. Register exactly one Artisan reconciliation command that delegates only to this service and is run by the approved scheduler/host cron—not a calendar read or parallel scheduler.
    - _Requirements: 3.1–3.8, 4.1–4.7, 5.4, 7.3, 9.8, 10.1–10.5, 12.1–12.4_

  - [ ]* 2.3 Add generator transaction, command, and database-capability integration coverage
    - **Implementation owner / files:** `tests/Feature/Admin/SessionGeneratorServiceTest.php` and `tests/Feature/Console/RecurringScheduleReconciliationCommandTest.php`.
    - Cover default/configured horizons, month crossings, disabled schedules, rollback/retry, lock contention, database-enforced duplicate safety, retry identity replay/changed input, command delegation, and safe failure responses.
    - _Requirements: 3.1–3.8, 4.1–4.7, 7.3, 10.1–10.5, 12.2–12.4_

  - [ ]* 2.4 Write property test for rolling-horizon coverage
    - **Property 6: Rolling-horizon coverage.** Create `tests/js/properties/recurrence-rolling-horizon.property.test.js` with 100+ active schedules and accepted/invalid horizons.
    - Assert all eligible successive weekly occurrences, including month crossings, are covered inside the effective horizon and invalid values create no rows.
    - **Validates: Requirements 3.1, 3.3, 3.4.**

  - [ ]* 2.5 Write property test for idempotent occurrence identity
    - **Property 7: Idempotent occurrence identity.** Create `tests/js/properties/recurrence-idempotent-occurrence.property.test.js` with 100+ generated valid occurrences and retry identities.
    - Assert identical retries return their original success and commit at most one row; changed input with a reused identity is rejected without scheduling mutations, including concurrent-attempt simulations backed by separate integration tests.
    - **Validates: Requirements 4.1–4.3, 4.5–4.7.**

  - [ ]* 2.6 Write property test for failed recurrence decisions
    - **Property 8: Failed recurrence decisions persist nothing.** Create `tests/js/properties/recurrence-failed-decision.property.test.js` with 100+ locked conflict/no-room decisions.
    - Assert controlled failure with no `ClassSession` or calendar event persisted.
    - **Validates: Requirements 3.8, 5.4.**

- [ ] 3. Centralize compatible room selection, conflict details, and atomic session mutations
  - [ ] 3.1 Add preference and deterministic fallback only to the existing room boundary
    - **Implementation owner / files:** `app/Services/RoomResolver.php` and `app/Services/RoomOptionProvider.php`.
    - Implement the approved reversible preference mapping—Violin `101`, Piano `103`, Voice `102`, Guitar `102`, Drums `104`—and use the existing boundary alone to choose an active compatible available preferred room or lowest numeric compatible fallback for the full interval.
    - Preserve exact legacy strings byte-for-byte, return controlled no-room conflict with no write, and make manual create/edit, generation, availability, and seeding reuse this owner without room CRUD or a parallel engine.
    - _Requirements: 5.1–5.7, 6.7, 8.5, 10.3, 12.1–12.3, 12.6_

  - [ ] 3.2 Make the shared conflict owner status-aware and capable of structured details
    - **Implementation owner / files:** `app/Services/ConflictDetectionService.php`.
    - Classify cancelled overlaps as non-blocking and completed overlaps as blocking historical evidence; return complete applicable teacher, student, room, enrollment, recurring, existing-session, and time-range detail through the established owner.
    - Preserve parameterized Eloquent querying, existing overlap ownership, safe authorization separation, and no-write behavior for a rejected proposal.
    - _Requirements: 4.1, 4.6, 5.4, 6.1–6.5, 6.8–6.9, 7.3, 10.2–10.6, 12.1–12.5_

  - [ ] 3.3 Make existing create/edit services the only availability, recheck, and audit boundary
    - **Implementation owner / files:** `app/Http/Requests/Admin/SessionCreateRequest.php`, `app/Http/Requests/Admin/SessionEditRequest.php`, `app/Services/SessionCreateService.php`, `app/Services/SessionEditService.php`, `app/Models/AuditRecord.php`, and `app/Services/AuditRecordService.php`.
    - Validate actual scheduling inputs; calculate exactly `AVAILABLE` or `CONFLICT` through existing services; recompute edits from current state excluding only self; lock and recheck room/conflict state within the final mutation transaction.
    - Add the smallest lifecycle-audit adapter at the existing audit boundary so successful create/edit commits one immutable accepted record containing session/action, actor, source, time, changed fields, and safe before/after metadata. Roll back session, related counter, and audit writes on every failure; preserve rejected/bulk audit behavior and add no route, DTO, policy, or audit subsystem.
    - _Requirements: 6.1–6.9, 7.1–7.6, 10.1–10.6, 12.1–12.6_

  - [ ]* 3.4 Add room, conflict, availability, audit, and rollback integration coverage
    - **Implementation owner / files:** `tests/Feature/Admin/RoomResolverTest.php`, `tests/Feature/Admin/ConflictDetectionServiceTest.php`, and `tests/Feature/Admin/SessionMutationAuditTest.php`.
    - Cover all preferences, unavailable preferred room, numeric fallback, no-room result, legacy preservation, available/conflict details, cancelled/completed behavior, edit re-evaluation, authorization secrecy, stale/lock/audit failures, and atomic create/edit rollback with immutable accepted records.
    - _Requirements: 5.1–5.7, 6.1–6.9, 7.1–7.6, 10.1–10.6, 12.2–12.5_

  - [ ]* 3.5 Write property test for deterministic room choice and legacy preservation
    - **Property 9: Deterministic room choice and legacy preservation.** Create `tests/js/properties/room-deterministic-selection.property.test.js` with 100+ instrument proposals and candidate sets.
    - Assert preferred selection, lowest-numeric fallback, controlled no-room behavior, and byte-for-byte legacy-string preservation.
    - **Validates: Requirements 5.1–5.5.**

  - [ ]* 3.6 Write property test for availability and status-aware blocking
    - **Property 10: Availability and status-aware blocking.** Create `tests/js/properties/availability-status-aware-blocking.property.test.js` with 100+ authorized valid proposals and conflicting-session sets.
    - Assert exactly one availability state, complete applicable details, cancelled-only non-blocking, and completed-history blocking.
    - **Validates: Requirements 6.1, 6.3–6.5.**

  - [ ]* 3.7 Write property test for edit equals fresh evaluation
    - **Property 11: Edit equals fresh evaluation.** Create `tests/js/properties/availability-edit-fresh-evaluation.property.test.js` with 100+ conflict-relevant edits.
    - Assert edit availability equals a fresh proposed-state evaluation that excludes only the edited session.
    - **Validates: Requirements 6.2, 6.6.**

- [ ] 4. Make persisted demo scheduling realistic and repeat-safe
  - [ ] 4.1 Rework DemoSeeder through the approved scheduling owners
    - **Implementation owner / files:** `database/seeders/DemoSeeder.php`.
    - Create persisted fixtures only on Saturday–Thursday, create adjacent operating-day slots from 09:00 through 21:00, and apply deterministic duration allocation—`floor(N / 100)` 60-minute sessions at every 100th position and all others 30 minutes; zero population creates no sessions.
    - Use the existing occurrence safety, `RoomResolver`/`RoomOptionProvider`, and `ConflictDetectionService`; run atomically, skip existing `Occurrence_Key` rows, preserve manual/unrelated fixtures, and roll back only run-created data on failure or interruption.
    - _Requirements: 3.8, 4.2, 5.6, 8.1–8.9, 10.3–10.6, 12.1–12.6_

  - [ ]* 4.2 Add DemoSeeder atomicity and fixture-contract integration coverage
    - **Implementation owner / files:** `tests/Feature/Database/DemoSeederSchedulingTest.php`.
    - Cover Friday absence, operating-day continuity, zero/nonzero duration allocations, shared room/conflict ownership, repeat-run occurrence identity, preservation of manual/unrelated fixtures, and full rollback on failure.
    - _Requirements: 8.1–8.9, 10.5–10.6, 12.2–12.6_

  - [ ]* 4.3 Write property test for deterministic demo fixture validity
    - **Property 12: Deterministic demo fixture validity.** Create `tests/js/properties/demo-fixture-validity.property.test.js` with 100+ ordered populations, including zero.
    - Assert Saturday–Thursday generation, adjacent 09:00–21:00 slots, and exact deterministic 99%/1% duration allocation.
    - **Validates: Requirements 8.1–8.4.**

  - [ ]* 4.4 Write property test for repeat-safe demo seeding
    - **Property 13: Repeat-safe demo seeding.** Create `tests/js/properties/demo-repeat-safe-seeding.property.test.js` with 100+ successful fixture populations.
    - Assert reruns preserve manual rows and leave the persisted `Occurrence_Key` set unchanged.
    - **Validates: Requirements 8.6, 8.9.**

- [ ] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Wire existing session entry points and verify end-to-end compatibility
  - [ ] 6.1 Connect manual generation and deletion through their approved existing mutation boundaries
    - **Implementation owner / files:** `app/Http/Controllers/Admin/ClassSessionController.php`.
    - Preserve existing named POST and PUT/PATCH behavior, `SessionPolicy` abilities, redirects, and errors while delegating manual generation to the same effective-horizon `SessionGeneratorService` and delete to the existing transactional mutation/audit boundary.
    - Do not add routes, HTTP verbs, availability endpoints, controllers, policy abilities, or bypasses; authorization must precede disclosure or mutation and failures must remain generic and safe.
    - _Requirements: 3.7, 7.1–7.5, 10.1–10.6, 12.1–12.4_

  - [ ]* 6.2 Add cross-owner backward-compatibility integration coverage
    - **Implementation owner / files:** `tests/Feature/Admin/SchedulingSystemCompatibilityTest.php`.
    - Exercise existing calendar and session routes/verbs through authorized and unauthorized paths, confirm resource/DTO keys and relation-path integrity, verify manual and reconciliation generation share one service, and assert no calendar read generates or repairs sessions.
    - _Requirements: 1.7, 3.7, 7.1–7.5, 10.1–10.6, 12.1–12.6_

  - [ ]* 6.3 Add calendar accessibility, RTL, and viewport-fit regression coverage
    - **Implementation owner / files:** `tests/js/calendar-accessibility-rtl-responsive.integration.test.js`.
    - Use the existing browser-test harness only; verify semantic availability/conflict feedback, keyboard/focus equivalence, readable RTL Persian with LTR date/time tokens, reduced motion, non-color cues, 44px targets, and fit-gated rendering at 390, 430, 768, 1024, 1366, 1600, and 1920 widths.
    - _Requirements: 10.4, 11.1–11.6, 12.2, 12.4._

- [ ] 7. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Each source file has exactly one implementation owner above; execution agents must not modify files owned by another task. New test files are owned by their named task.
- Tasks marked with `*` are optional test tasks. They remain required coverage in the approved quality plan but are not implemented when optional work is skipped.
- Every property task uses the installed pinned `fast-check` 4.3.0 convention with at least 100 runs and its required feature/property comment tag; no new dependency is added.
- Do not create a parallel calendar, scheduler, recurrence engine, conflict engine, room engine, audit engine, route, endpoint, DTO contract, or relation authority. Preserve legacy room strings, stable session IDs, compatible responses, policies, and existing tests.
- The evidence-backed index strategy remains deferred: implement only the approved minimum database occurrence enforcement and stop for owner approval before proposing any other index or breaking schema/public-contract change.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "2.1", "3.1", "3.2"] },
    { "id": 1, "tasks": ["1.2", "1.3", "2.2", "3.3"] },
    { "id": 2, "tasks": ["1.4", "1.5", "1.6", "1.7", "1.8", "1.9", "1.10", "2.3", "2.4", "2.5", "2.6", "3.4", "3.5", "3.6", "3.7", "4.1"] },
    { "id": 3, "tasks": ["4.2", "4.3", "4.4", "6.1"] },
    { "id": 4, "tasks": ["6.2", "6.3"] }
  ]
}
```
