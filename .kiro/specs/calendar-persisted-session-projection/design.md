# Calendar Persisted Session Projection Bugfix Design

## Overview

The admin calendar is a read-only projection of persisted `class_sessions`, with the approved lifecycle `RecurringSchedule → SessionGeneratorService → class_sessions → CalendarQueryService → CalendarEventResource → /admin/calendar/events → FullCalendar`. The confirmed defect is an unapproved `instrument_id` filter that travels from the calendar UI through `CalendarEventRequest` into `CalendarQueryService::get()`, where `forInstrument()` can remove a session that matches every approved filter. The fix removes that filter from the complete calendar read contract. It does not generate sessions, manufacture events, alter database schema, or redesign/replace FullCalendar.

## Glossary

- **Bug_Condition (C)**: A persisted session matches an inclusive requested date range and all explicitly supplied teacher, student, and room filters, yet an unapproved `instrument_id` predicate or a lifecycle boundary omission, duplication, or ID substitution prevents a one-to-one projection.
- **Property (P)**: Every matching persisted session is read exactly once, serialized exactly once, normalized/rendered exactly once, and retains its `class_sessions.id` at every stage; an empty persisted result stays empty with no writes.
- **Preservation**: `SessionGeneratorService` remains the sole generator; date/teacher/student/room filtering, eager loading, room resolution, event mapping, endpoint authorization/error behavior, and current FullCalendar configuration and RTL UI are unchanged.
- **Approved calendar filters**: `start`, `end`, optional `teacher_id`, optional `student_id`, and optional `room`. `start` and `end` form an inclusive date interval.
- **Stable identifier**: `ClassSession::getKey()` / `class_sessions.id`, mapped by `SessionDisplayMapper` to `CalendarEventData.id`, emitted by `CalendarEventResource` as JSON `id`, and retained by `normalizeEventPayload()`.
- **Projection boundaries**: `CalendarQueryService` query result → `CalendarEventResource` collection → `/admin/calendar/events` JSON → `normalizeEventCollection()` / current FullCalendar rendering.

## Bug Details

### Bug Condition

A calendar request must read persisted rows only. The defect occurs when the request includes `instrument_id`: `CalendarEventRequest` validates it and `CalendarQueryService::get()` conditionally calls `forInstrument()`. That predicate is outside the approved filter set and can omit a persisted row before resource serialization. Separately, any count or stable-ID difference at a projection boundary is a defect at that first boundary, never evidence that a later stage should synthesize or reconcile an event. An empty date is not a feed failure when no persisted source row exists.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input = { persistedSessions, start, end, teacherId?, studentId?, room?, instrumentId?,
                   queryEvents, resourceEvents, endpointEvents, normalizedEvents }
  OUTPUT: boolean

  matching := sessions in persistedSessions WHERE
    start <= session.session_date <= end
    AND matchesIfSupplied(session.teacher_id, teacherId)
    AND matchesIfSupplied(session.student_id, studentId)
    AND matchesNormalizedRoomIfSupplied(session.room, room)

  expectedIds := stableIds(matching)
  stages := [queryEvents, resourceEvents, endpointEvents, normalizedEvents]

  RETURN (instrumentId IS supplied AND stableIds(queryEvents) != expectedIds)
    OR firstBoundaryWithDifferentCountOrStableIds(expectedIds, stages) IS NOT null
END FUNCTION
```

### Examples

- For `start=end=2026-08-05`, with no persisted `class_sessions` on that date and demo schedules only on weekdays 1 and 4, the endpoint returns `{"data": []}`. The request creates neither a session nor a synthetic event.
- A persisted session on 2026-08-05 matching the supplied teacher, student, and room remains present when the request also contains another instrument ID; that unapproved parameter is ignored.
- Persisted session ID `42` produces one JSON event with `id: 42`, and normalizes to one FullCalendar event with `id: 42`.
- A session on either inclusive date boundary is included when it matches the approved filters; an out-of-range or other-room session is excluded.

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- `SessionGeneratorService::generateForSchedule()` continues to create future `class_sessions` under its existing rules before a calendar request can read them.
- `CalendarQueryService` continues to use `forDateRange()`, optional `forTeacher()`/`forStudent()`, normalized room matching, `withEnrollmentDetails()`, `orderBySchedule()`, and batched room resolution.
- `CalendarEventResource`, `SessionDisplayMapper`, current named endpoint, current FullCalendar modules/configuration, RTL layout, drawer, navigation, and approved filter interactions remain in place.
- Zero matching persisted rows continue to result in zero JSON and rendered events.

**Scope:** All inputs not meeting C retain their existing outcome. No schema migration, generator call from Calendar, synthetic event/fallback, resource-mapper rewrite, normalizer compensation, FullCalendar redesign, or endpoint replacement is permitted.

**Formal Specification:**
```
FUNCTION expectedBehavior(input, result)
  INPUT: input defined by isBugCondition; result = { writes, queryEvents, resourceEvents,
                                                     endpointEvents, normalizedEvents }
  OUTPUT: boolean

  matchingIds := stableIds(persistedSessionsMatchingApprovedFilters(input))

  RETURN result.writes = 0
    AND count(result.queryEvents) = count(matchingIds)
    AND count(result.resourceEvents) = count(matchingIds)
    AND count(result.endpointEvents) = count(matchingIds)
    AND count(result.normalizedEvents) = count(matchingIds)
    AND stableIds(result.queryEvents) = matchingIds
    AND stableIds(result.resourceEvents) = matchingIds
    AND stableIds(result.endpointEvents) = matchingIds
    AND stableIds(result.normalizedEvents) = matchingIds
END FUNCTION
```

## Hypothesized Root Cause

1. **Confirmed unapproved query predicate**: `CalendarQueryService::get()` invokes `forInstrument()` when validated filters contain `instrument_id`. This violates the approved query-membership contract.
2. **Confirmed over-broad request/UI contract**: `CalendarEventRequest`, `CalendarController::index()`, `admin/calendar/index.blade.php`, `calendar-layout`, and `event-filters` currently expose and forward the same unapproved field.
3. **Boundary-observability risk**: `normalizeEventCollection()` intentionally drops malformed payloads. A malformed upstream event can otherwise appear to be a rendering issue unless tests compare count and IDs in lifecycle order.
4. **Empty-range misdiagnosis risk**: a recurring schedule describes potential generation, not an event-feed source row. Calendar requests must not treat its weekday as a reason to create an event.

## Correctness Properties

Property 1: Bug Condition - Read-only persisted-session projection

_For any_ valid explicit inclusive range and optional teacher, student, and room filters, including a request which also carries an unapproved `instrument_id`, the fixed lifecycle SHALL project every and only matching persisted `class_sessions` record once through query, resource, JSON, and FullCalendar normalization/rendering with the same stable ID; with no matching persisted records it SHALL return, normalize, and render zero events while performing zero writes; and a deliberate mismatch SHALL be reported at the first differing boundary without downstream reconciliation.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7**

Property 2: Preservation - Existing generation and approved calendar behavior

_For any_ input where the bug condition does NOT hold (`isBugCondition` returns false), the fixed lifecycle SHALL preserve existing generator behavior, inclusive date/teacher/student/room membership, eager-loaded resource mapping, valid FullCalendar normalization/rendering, and the zero-event state.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4**

## Fix Implementation

### Changes Required

**1. Narrow the endpoint contract — `app/Http/Requests/Admin/CalendarEventRequest.php`**
- Delete the `instrument_id` validation rule plus its messages and attribute label.
- Preserve `start`/`end` format, ordering, 92-day limit, teacher/student existence checks, room validation, authorization, and route-specific JSON `422` response.
- Laravel's `validated()` output must therefore contain only the approved filter keys. A received `instrument_id` is ignored rather than becoming a query predicate or a validation error.

**2. Narrow query membership — `app/Services/CalendarQueryService.php`**
- Delete only the conditional `forInstrument()` block.
- Retain read-only query construction: `forDateRange()`, optional teacher/student scopes, normalized exact room condition, `withEnrollmentDetails()`, `orderBySchedule()`, and batch room resolution/validation.
- Do not inject `SessionGeneratorService`, create a model, update a model, or construct fallback events in this service.

**3. Remove the unapproved UI input without redesign — `app/Http/Controllers/Admin/CalendarController.php`, `resources/views/admin/calendar/index.blade.php`, `resources/views/components/calendar-layout.blade.php`, and `resources/views/components/event-filters.blade.php`**
- In `index()`, remove the `Instrument` import, instrument query, and compacted `instruments` view value.
- Remove the `:instruments` value from the page component call; remove the `instruments` prop and forwarding from `calendar-layout`; remove the `instruments` prop and the instrument `<select>` field from `event-filters`.
- Keep the existing teacher, student, and room controls, component structure, CSS classes, responsive filter behavior, localization, and FullCalendar layout intact. Instruments continue to be display metadata on session events.

**4. Preserve projection implementations — no production changes to `CalendarEventResource.php`, `SessionDisplayMapper.php`, or `resources/js/calendar/fullcalendar.js`**
- Their existing ID-preserving mapping/normalization behavior remains authoritative for valid event data.
- Do not add event generation, ID replacement, count reconciliation, filtering, or synthetic fallback in a later stage.
- Implement first-boundary comparison as test assertions, not request-time reconciliation logic: comparisons must stop on the earliest failure in the ordered sequence query→resource, resource→endpoint, endpoint→normalizer/render.

**5. Update affected coverage only**
- Update `tests/Feature/Admin/CalendarControllerTest.php`, `tests/Feature/Admin/CalendarPageTest.php`, `tests/js/unit/calendar-modules.test.js`, `tests/js/support/dom-harness.js`, and `tests/browser/admin-calendar.spec.js` to recognise exactly three approved UI/request filters.
- Add focused persisted-projection/property coverage as described below. Do not alter unrelated session or instrument-management tests.

## Testing Strategy

### Validation Approach

Use two phases. First run the exploratory regression test against the unfixed request/service pair to demonstrate that a non-matching `instrument_id` drops an otherwise matching persisted row at `CalendarQueryService`. Then apply the minimal removal and verify fix and preservation boundaries separately. Tests create source rows directly or invoke the existing generator first; the calendar endpoint never creates the source row.

### Exploratory Bug Condition Checking

**Goal**: confirm the first defect is query membership, not resource serialization or FullCalendar.

**Test Plan**: In `CalendarControllerTest`, create a persisted session with a date, teacher, student, and room matching the request, then include a different valid `instrument_id`. Before the fix, `forInstrument()` omits the session. The first comparison, expected persisted IDs→query IDs, fails; later stages are not used to explain or repair it.

**Test Cases**:
1. **Unapproved instrument exclusion**: send matching approved filters plus another instrument ID; observe the matching session is absent before the fix and present after it.
2. **Empty Wednesday range**: request `2026-08-05` with no persisted rows; assert `data` is empty and the `class_sessions` count is unchanged, irrespective of recurring schedule fixtures.
3. **Single-session stable identity**: one matching persisted row yields one endpoint event with the identical `id`, then one normalized event with that `id`.
4. **Inclusive boundary and room normalization**: include start/end boundary rows and a normalized matching room; exclude out-of-range and other-room rows.

**Expected Counterexamples**:
- Before the fix, `CalendarQueryService::forInstrument()` removes an otherwise matching persisted session.
- A zero-row result has no source records, proving it is not a generation or rendering failure.

### Fix Checking

**Goal**: verify all C inputs produce the approved read-only projection.

**Pseudocode:**
```
FOR ALL input WHERE isBugCondition(input) DO
  persisted := persistedSessionsMatchingApprovedFilters(input)
  result := calendarProjection_fixed(input)
  ASSERT expectedBehavior(input, result)
END FOR
```

### Preservation Checking

**Goal**: verify non-C inputs retain their prior behavior.

**Pseudocode:**
```
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT approvedProjection_original(input) = approvedProjection_fixed(input)
  ASSERT generatorBehavior_original(input) = generatorBehavior_fixed(input)
END FOR
```

Boundary assertions compare counts and ordered stable-ID sets in this fixed order: persisted query result→resource collection, resource collection→endpoint JSON, then endpoint JSON→normalized collection/rendered cards. A helper fails immediately at the first unequal pair; it must not mutate a later collection.

### Unit Tests

- In `tests/Feature/Admin/CalendarControllerTest.php`, replace the combined instrument-filter expectation with approved teacher/student/room filter assertions. Add a regression test that sends a divergent valid `instrument_id` alongside matching approved filters and receives the matching persisted session exactly once. Keep direct and enrollment-backed session coverage.
- Add endpoint cases for the inclusive start/end boundary, normalized active/inactive room handling, an empty `2026-08-05` response (`{"data": []}`), and an unchanged `class_sessions` count before/after every read request.
- In `tests/Feature/Admin/CalendarPageTest.php`, assert that the page supplies teacher/student/room filters and no instrument filter markup or instrument view prop.
- In `tests/js/unit/calendar-modules.test.js` and `tests/js/support/dom-harness.js`, change the fixture and serialization expectations to the three approved controls. Preserve existing normalizer tests proving valid resource events keep their IDs and malformed payloads are rejected rather than repaired.

### Property-Based Tests

- Add `tests/js/properties/calendar-persisted-session-projection.property.test.js` using the installed pinned `fast-check` 4.3.0. Generate arrays of valid, unique persisted session IDs and valid `CalendarEventData`-shaped payloads; verify `normalizeEventCollection()` preserves one event per payload, count, and exact ID sequence (Property 1).
- Generate valid empty collections, optional approved filters, and an arbitrary unapproved `instrument_id` request value. Assert the expected membership oracle ignores the instrument value, while count/ID comparisons are performed at each named boundary (Property 1).
- Generate one malformed representation at exactly one boundary. Assert the test comparison identifies that boundary first and leaves all later representations unchanged; do not encode compensation in application code (Property 1).
- Generate valid non-bug resource payloads and approved filter subsets; assert existing FullCalendar normalizer output and card metadata are unchanged (Property 2).

### Integration Tests

- Extend `tests/Feature/Admin/CalendarControllerTest.php` to compare persisted matching IDs with endpoint JSON IDs for empty, single-row, multi-row, inclusive-boundary, direct, and enrollment-backed fixtures, and assert zero database writes during every calendar request.
- Update `tests/browser/admin-calendar.spec.js`: set `filterKeys` to `teacher_id`, `student_id`, and `room`; assert no instrument filter control or `instrument_id` request parameter exists; retain the current RTL FullCalendar, navigation, drawer, loading/error, empty-state, accessibility, and responsive-breakpoint coverage.
- Keep one end-to-end generator-then-calendar regression: `SessionGeneratorService` persists the record first, and a subsequent endpoint request merely reads that ID. The endpoint must make no generator call and create no synthetic event.

### Planned Validation Commands

After implementation, run the focused PHP feature tests, `npm run test:calendar`, the targeted browser calendar test with its existing authenticated test environment, and `npm run build`. The browser suite requires its configured server and credentials; do not start a development server or watcher as part of this bugfix.
