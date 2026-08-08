# Implementation Plan: Calendar Persisted Session Projection Bugfix

## Overview

Restore the calendar as a strict, read-only projection of persisted `class_sessions`. The plan first proves the unapproved instrument-filter defect and current non-bug behavior, then narrows the contract and validates all projection boundaries without generating, reconciling, or redesigning events.

## Tasks

- [x] 1. Write the bug-condition exploration test on unfixed code
  - **Property 1: Bug Condition** - Read-only persisted-session projection
  - **IMPORTANT**: Write and run this scoped property/regression before changing production code; it must fail on the unfixed request/service pair.
  - In `tests/Feature/Admin/CalendarControllerTest.php`, create a persisted session that matches an inclusive range plus supplied teacher, student, and normalized room, then submit a different valid `instrument_id`.
  - Assert the persisted matching ID is absent from `CalendarQueryService` output before resource, endpoint, or FullCalendar stages; record this counterexample as the first mismatch caused by `forInstrument()`.
  - In the same coverage, assert `start=end=2026-08-05` with no rows returns `{"data": []}` and leaves the `class_sessions` count unchanged; recurring schedules must not create source rows or synthetic events.
  - Cover one matching ID through query, resource, endpoint JSON, and normalization, comparing ordered ID sets and counts at each boundary; the helper must fail at the first differing boundary and never repair a later collection.
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

- [x] 2. Write preservation property tests on unfixed code
  - **Property 2: Preservation** - Existing generation and approved calendar behavior
  - **IMPORTANT**: Use observation-first methodology: record current results for valid non-bug inputs, encode them, and verify they pass before the fix.
  - Add `tests/js/properties/calendar-persisted-session-projection.property.test.js` with the installed pinned `fast-check` 4.3.0; generate unique valid session IDs, valid `CalendarEventData` payloads, approved-filter subsets, and empty collections.
  - Assert `normalizeEventCollection()` preserves exactly one valid event per payload, the count and exact ID sequence, and existing card metadata; malformed payloads are rejected, never repaired.
  - Generate an `instrument_id` independently of the approved membership oracle and prove it does not participate in that oracle; inject one malformed representation per run and assert the boundary comparator names the first mismatch without mutating later representations.
  - Update `tests/js/unit/calendar-modules.test.js` and `tests/js/support/dom-harness.js` so form serialization recognises only `teacher_id`, `student_id`, and `room`; retain valid normalizer and malformed-payload observations.
  - Extend existing PHP fixtures for inclusive start/end boundaries, normalized active/inactive rooms, direct and enrollment-backed sessions, supplied teacher/student/room filters, and zero writes for every read request.
  - Update `tests/Feature/Admin/CalendarPageTest.php` and `tests/browser/admin-calendar.spec.js` to observe three approved UI controls, no instrument control/request parameter, and unchanged RTL FullCalendar navigation, drawer, loading/error, empty-state, accessibility, and responsive behavior.
  - Keep a generator-then-calendar observation: `SessionGeneratorService` persists the row before the endpoint reads its ID, and the endpoint makes no generator call.
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 3. Restore the approved read-only calendar projection
  - [x] 3.1 Narrow the calendar request, query, and UI contracts
    - Remove `instrument_id` validation, messages, and attribute label from `app/Http/Requests/Admin/CalendarEventRequest.php`; preserve authorization, existing JSON `422` behavior, date validation/order/92-day limit, and teacher/student/room validation.
    - Remove only the conditional `forInstrument()` predicate from `app/Services/CalendarQueryService.php`; retain date, teacher, student, normalized-room, eager-loading, ordering, and batched room-resolution behavior with no writes, generation, fallback, or synthetic events.
    - In `app/Http/Controllers/Admin/CalendarController.php`, remove the `Instrument` import/query and `instruments` view value. Remove the instruments value/prop/forwarding and instrument `<select>` from `resources/views/admin/calendar/index.blade.php`, `resources/views/components/calendar-layout.blade.php`, and `resources/views/components/event-filters.blade.php` without changing the approved controls, component layout, RTL behavior, or FullCalendar configuration.
    - Do not alter `CalendarEventResource.php`, `SessionDisplayMapper.php`, or `resources/js/calendar/fullcalendar.js`; boundary checks stay in tests and must not reconcile counts, substitute IDs, or alter later stages.
    - _Bug_Condition: `instrumentId` is supplied and a persisted session matching the inclusive date/teacher/student/room membership is excluded, or the first ordered projection boundary has different count or stable IDs._
    - _Expected_Behavior: `expectedBehavior(input, result)` returns zero writes and identical persisted matching IDs/counts through query, resource, endpoint, and normalized/rendered events._
    - _Preservation: generator ownership, approved filtering, eager loading, mapping, endpoint behavior, and current FullCalendar/RTL UI remain unchanged._
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 3.1, 3.2, 3.3, 3.4_
  - [x] 3.2 Verify the original exploration test now passes
    - **Property 1: Expected Behavior** - Read-only persisted-session projection
    - Re-run the same task 1 tests; the divergent valid `instrument_id` must be ignored, every matching persisted ID must appear exactly once through all stages, and empty ranges must remain empty with zero writes.
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_
  - [x] 3.3 Verify the original preservation tests still pass
    - **Property 2: Preservation** - Existing generation and approved calendar behavior
    - Re-run the same task 2 property, feature, page, and browser tests; do not create replacement tests.
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 4. Checkpoint - validate the complete bugfix
  - Run the focused `CalendarControllerTest` and `CalendarPageTest`, then `npm run test:calendar` (including the fast-check properties), the existing authenticated targeted browser calendar test, and `npm run build`.
  - After Blade changes, run `php artisan optimize:clear`; verify no test or build failures and that browser coverage preserves the existing responsive RTL calendar behavior.
  - Do not start a development server or watcher; use the configured authenticated browser environment only.


## Notes

- This plan validates only the persisted-session calendar projection repair. The deferred business-code, event-stream, generalized room/availability, business-rule, and orchestration clauses recorded in `bugfix.md` are not completed by this plan and require their own approved design and task plan.
- Property tasks use the existing pinned `fast-check` 4.3.0 and should run at least 100 generated cases; PHP feature coverage verifies real endpoint, query, database-write, and resource boundaries.
- Do not change schema, invoke generation from Calendar, add synthetic/fallback events, compensate for boundary mismatches, replace/redesign FullCalendar, or modify unrelated session/instrument tests.
- Browser validation uses its configured authenticated environment; no development server or watcher is started as part of this bugfix.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1", "2"] },
    { "id": 1, "tasks": ["3.1"] },
    { "id": 2, "tasks": ["3.2", "3.3"] },
    { "id": 3, "tasks": ["4"] }
  ]
}
```