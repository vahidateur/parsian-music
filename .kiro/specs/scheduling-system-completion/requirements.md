# Requirements Document

## Introduction

This requirements document defines the approved scope for completing recurring scheduling, repairing the calendar projection boundary, improving availability feedback, and making demo data realistic while preserving the existing Parsian Music architecture and public contracts.

This is a requirements-only phase. No production code, migration, route, test, seeder, configuration, existing specification, `design.md`, or `tasks.md` is authorized by this document. Implementation cannot begin until the project owner approves both the root-cause findings and the proposed file inventory.

## Evidence Discipline

**Verified findings** below are limited to files inspected during this phase. **Unverified**, **assumption**, and **to-prove** statements are requirements or investigation obligations, not claims about current repository behavior. The inspected path establishes the first conditional exclusion boundary for an existing persisted row; a hidden matching-row disappearance after that boundary has not been evidenced.

### Verified Findings

- The calendar read path is `CalendarController::events` → `CalendarEventRequest` → `CalendarQueryService::get` → `ClassSession` scopes/Eloquent query → `CalendarEventResource` → `SessionDisplayMapper`/`RelationPathResolver` → JSON → `resources/js/calendar/fullcalendar.js` normalization → rendering. `CalendarController::events` delegates only to `CalendarQueryService` and `CalendarEventResource`; it does not generate or repair sessions.
- `CalendarQueryService` queries `ClassSession`, applies `forDateRange`, applies teacher, student, and legacy string-room filtering, eager-loads `withEnrollmentDetails`, orders by schedule, and prepares `calendarRoom` metadata. It does not apply instrument or status filters in the inspected implementation.
- For an already-persisted `ClassSession` whose requested inclusive date range and supplied filters match it, the inspected query/resource/mapper/normalization path preserves its stable ID. `CalendarEventResource` and `SessionDisplayMapper` map or throw; they do not silently filter. Relation-path failures are explicit 409 responses. The frontend normalizer rejects malformed payloads, but valid mapped persisted sessions do not enter that rejection path.
- `ScopesForSessionFilters` defines date, teacher, student, and instrument scopes plus eager loading of enrollment and direct relation paths; no status scope was verified there.
- `CalendarEventResource` requires a `ClassSession` and delegates to `SessionDisplayMapper`; the inspected mapper does not create sessions or synthetic events.
- The existing calendar routes are named `admin.calendar.index` and `admin.calendar.events`, and are behind `auth` and `role:admin` middleware.
- `resources/js/calendar/fullcalendar.js` fetches an event feed, normalizes accepted payloads, rejects malformed payloads, and does not show a synthetic-event constructor in the inspected path. The inspected URL builder sends the selected day as both `start` and `end` instead of the complete FullCalendar fetch range; this is a separate upstream single-day narrowing defect.
- `SessionGeneratorService` accepts an occurrence count expressed as weeks, defaults to eight weeks, reads duplicate candidates before its transaction, and creates `ClassSession` rows inside a transaction. Database locks, an occurrence uniqueness constraint, retry identity, and a scheduled invoker were not verified in the inspected code.
- `ClassSessionController::generate` is the existing manual generation entry point and passes `8` to `SessionGeneratorService` for active schedules.
- `routes/console.php` contains the inspected `inspire` and `e2e:seed-admin` commands; no recurring-generation command was verified there.
- `RecurringSchedule` persists enrollment, weekday, start time, duration, room, and `is_active`, and exposes an `active` scope.
- The current `class_sessions` schema persists a legacy string `room`; later schema work adds nullable direct `student_id`, `teacher_id`, and `instrument_id`. Existing indexes include date/status and direct identity/status combinations, but index sufficiency for the requested workload is not proven.
- `SessionCreateService` validates room and conflicts before a transaction, then persists a session and may update a subscription counter inside a transaction. `SessionEditService` locks and reloads the session inside a transaction and recomputes conflicts for scheduling changes. The inspected delete action calls `delete()` directly in the controller.
- `ConflictDetectionService` is the shared overlap owner for teacher, room, enrollment, and student paths. The inspected implementation does not classify cancelled sessions as non-blocking or completed sessions as historical blocking.
- `SessionPolicy` owns create, update, delete, view, and generate abilities; the calendar feed also has admin middleware.
- `SessionStatusEnum` defines `scheduled`, `completed`, `cancelled`, and `missed`.
- `DemoSeeder` uses weekday and duration formulas that do not establish the requested Saturday–Thursday, Friday-free, 09:00–21:00, 99%/1% invariant. The inspected fixture contains 45/60/90-minute values and separate calendar-session seeding.
- Generic `AuditRecord` and `AuditRecordService` infrastructure exists for bulk execution and rejected-operation records. Session-specific create/edit/delete audit coverage was not verified.

### Root-Cause Findings and Required Proof

The confirmed missing-row root cause is upstream: the recurring occurrence was never persisted. `CalendarController` does not generate or repair sessions, and `SessionGeneratorService` is invoked only through the existing manual generation path, with a fixed eight-week window and no verified scheduled rolling invoker. A calendar read cannot repair missing persistence.

For an already-persisted `ClassSession` whose requested inclusive date range and supplied filters match it, no later silent-drop boundary has been proven. The first conditional boundary where an existing persisted row can be excluded is `CalendarQueryService::get()`, specifically the inclusive `forDateRange` predicate or optional teacher/student/legacy-room predicates. A nonmatching request is intentional query exclusion, not a hidden downstream disappearance. `CalendarEventResource` and `SessionDisplayMapper` map or throw, relation-path failures are explicit 409 responses, and malformed frontend normalization is reject-only; valid mapped persisted sessions do not enter that rejection path.

There is a separate confirmed frontend transport defect: `fullcalendar.js` sends the selected day as both `start` and `end` instead of the complete FullCalendar fetch range. Sessions on other days in the requested UI range can therefore be excluded upstream by the query request. This single-day range narrowing is distinct from malformed-event normalization.

The implementation phase must instrument, in order: persisted `ClassSession` result → query result/scopes-Eloquent result → `CalendarEventResource` result → endpoint JSON → fetch payload → FullCalendar normalized collection → rendered event identity. Every boundary SHALL record the requested inclusive range, all six supported filters (date range, teacher, student, instrument, room, and status), count, ordered stable IDs, and explicit rejection reason. The first boundary with a count or ordered-ID difference is the only proven boundary for a future counterexample; no hidden matching-row disappearance may be claimed without that evidence. No downstream synthetic event or compensation is permitted.

The preferred room mapping, compatible-room authority, and exact index changes remain **to prove** before design approval. They must not be presented as current facts.

## Glossary

- **ClassSession**: One persisted `class_sessions` row representing one lesson occurrence.
- **RecurringSchedule**: A persisted weekly schedule that is active or disabled.
- **Calendar_Projection**: The read-only projection of persisted `ClassSession` rows into the existing FullCalendar event contract.
- **CalendarQueryService**: The existing service that owns calendar Eloquent filtering, eager loading, ordering, and legacy room preparation.
- **CalendarEventResource**: The existing resource that serializes one persisted `ClassSession`.
- **Calendar_Feed**: The named `admin.calendar.events` JSON endpoint.
- **FullCalendar**: The existing frontend calendar integration.
- **Occurrence_Key**: The approved stable identity of one recurring occurrence, including schedule identity, scheduling identity, local date, and start time.
- **Rolling_Horizon**: The configurable future calendar-day span persisted for each active recurring schedule; the default is 30 days when no setting exists.
- **Availability_Result**: A server-owned result with exactly one state: `AVAILABLE` or `CONFLICT`.
- **Conflict_Detail**: A structured blocking explanation containing resource category and applicable teacher, student, room, existing-session, and time data.
- **Authorized_Scheduler**: An actor authorized by the existing route middleware and applicable `SessionPolicy` ability.
- **Session_Audit_Record**: An immutable record of a successful ClassSession create, edit, or delete.
- **Approval_Gate**: Explicit owner approval of root-cause findings and the proposed file inventory.

## Current and Expected Behavior

| Area | Current behavior verified | Expected behavior |
|---|---|---|
| Calendar source | The inspected path reads persisted `ClassSession` rows and maps them to JSON; `CalendarController` does not generate or repair sessions. For a persisted row matching the requested range and filters, no later silent-drop boundary has been evidenced; resource/mapper failures are explicit throws or 409 responses, and malformed normalization is reject-only. | Every persisted matching row appears exactly once; no synthetic, demo-only, hidden, or frontend-generated event exists. |
| Recurrence | Manual generation uses an eight-week occurrence count; no scheduled invoker was verified. | Active schedules continue until disabled and are reconciled to the configured rolling horizon. |
| Range | The query predicate is inclusive, but the frontend transport sends the selected day as both bounds; this can intentionally narrow the query upstream to one day of the UI range. | The complete requested FullCalendar inclusive range reaches the endpoint unchanged and is filtered consistently. |
| Duplicate safety | Duplicate candidates are read before the transaction; lock/unique-key protection was not verified. | Repeated or concurrent generation commits at most one row per Occurrence_Key. |
| Rooms | ClassSession persistence uses a legacy room string; requested instrument preference is not verified. | The five required preferences and deterministic compatible fallback are applied by one existing room owner. |
| Filters | Teacher, student, and room are applied in CalendarQueryService; instrument and status are not applied there. | Date, teacher, student, instrument, room, and status are explicit supported filters. |
| Conflicts | Shared overlap checks exist; requested status semantics and structured pre-save details are not verified. | Teacher/student/room/recurring conflicts are server-owned, cancelled is non-blocking, and completed history blocks. |
| Demo data | Current formulas include Friday risk, sparse times, and non-target durations. | Saturday–Thursday only, Friday-free, continuous 09:00–21:00 operation, 99% 30-minute and 1% 60-minute sessions. |
| Audit | Generic audit infrastructure exists; session lifecycle audit is not verified. | Each successful create/edit/delete has one atomic immutable audit record. |

## Explicit Non-Goals

1. Replacing FullCalendar, the existing calendar page, event drawer, public JSON shape, route names, HTTP verbs, or Blade contracts.
2. Creating a parallel calendar query, scheduler, recurrence model, conflict engine, room engine, route family, policy family, DTO family, or duplicate business logic.
3. Generating, repairing, or reconciling sessions during a calendar read.
4. Adding drag/drop, resize, force override, holiday, vacation, equipment, capacity, or unrelated scheduling dimensions.
5. Changing enrollment, subscription, attendance, invoice, payment, teacher-profile, or student-profile contracts except where an existing session transaction already touches them.
6. Rewriting historical room strings or existing session IDs.
7. Weakening, removing, or bypassing existing tests, policies, validation, eager loading, escaping, CSRF, or authorization.
8. Creating implementation files, migrations, routes, tests, seeders, configuration, `design.md`, or `tasks.md` during this requirements phase.

## Requirements

### Requirement 1: Persisted Calendar Projection

**User Story:** As an academy administrator, I want the calendar to represent persisted sessions exactly, so that calendar state is trustworthy.

#### Acceptance Criteria

1. WHEN a Calendar_Feed request is evaluated and a matching identifier is absent at the persisted-result boundary, THE Calendar_Projection SHALL classify the missing occurrence as upstream missing persistence and SHALL report that no persisted ClassSession exists.
2. WHEN a persisted ClassSession is excluded by an inclusive date-range, teacher, student, instrument, room, or status predicate, THE Calendar_Projection SHALL classify the result as intentional query exclusion and SHALL report the predicate and rejection reason.
3. IF relation resolution fails for a persisted matching ClassSession, THEN THE Calendar_Projection SHALL return the existing explicit relation error and SHALL report the relation-resolution rejection reason.
4. IF none of upstream missing persistence, intentional query exclusion, or explicit relation error applies and a persisted matching ClassSession disappears or is duplicated between projection boundaries, THEN THE Calendar_Projection SHALL classify the first differing boundary as downstream disappearance or duplication and SHALL not assign an unproven upstream cause.
5. WHEN projection tracing is enabled, THE Investigation_Record SHALL trace exactly these seven boundaries in order: persisted ClassSession result, query result/scopes-Eloquent result, CalendarEventResource result, endpoint JSON, fetch payload, FullCalendar normalized collection, and rendered event identity.
6. WHEN any of the seven boundaries is traced, THE Investigation_Record SHALL record the requested inclusive range, all six filters (date range, teacher, student, instrument, room, and status), count, ordered stable IDs, and explicit rejection reason.
7. WHEN a valid persisted ClassSession matches a Calendar_Feed request, THE Calendar_Projection SHALL preserve its stable ID exactly once through all seven boundaries, SHALL perform no session write, and SHALL return no synthetic event.
8. IF the frontend normalizer rejects a malformed payload, THEN THE Calendar_Projection SHALL record the explicit malformed-normalizer rejection and SHALL never repair, substitute, or synthesize the rejected session or its ID.

### Requirement 2: Calendar Range and Filters

**User Story:** As an administrator, I want precise calendar filters, so that the calendar shows only requested sessions.

#### Acceptance Criteria

1. WHEN a valid Calendar_Feed request is received, THE CalendarQueryService SHALL support exactly six filters: date range, teacher, student, instrument, room, and status.
2. WHEN a date range is supplied, THE CalendarQueryService SHALL apply inclusive start and end endpoints.
3. WHEN a teacher, student, or instrument filter is supplied, THE CalendarQueryService SHALL use the canonical direct or enrollment relation path for that identity.
4. WHEN a room filter is supplied, THE CalendarQueryService SHALL preserve and filter the existing legacy room string contract without inventing a room ID for a legacy row.
5. WHEN a status filter is supplied, THE CalendarQueryService SHALL filter by the existing `SessionStatusEnum` values.
6. IF a date, identity, room, status, or other supported filter value is malformed or unsupported, THEN THE Calendar_Feed SHALL return a compatible validation response and SHALL perform no write or generation.
7. WHEN a supported filter is omitted, THE CalendarQueryService SHALL impose no restriction for that filter.
8. WHEN FullCalendar requests a visible range, THE FullCalendar transport SHALL send the complete visible start and end range to the Calendar_Feed rather than replacing the end with the selected day.
9. WHEN a persisted ClassSession does not satisfy a supplied filter predicate, THE CalendarQueryService SHALL exclude the row at the query boundary and SHALL record the predicate and intentional exclusion reason.
10. IF an endpoint payload contains a malformed event, THEN THE FullCalendar normalizer SHALL reject that payload with an explicit reason and SHALL retain every other valid event without hiding, creating, or substituting a valid persisted event.

### Requirement 3: Active Recurrence and Rolling Horizon

**User Story:** As an academy scheduler, I want active schedules to continue producing future persisted sessions, so that future dates do not depend on manual generation.

#### Acceptance Criteria

1. WHEN a RecurringSchedule is active, THE recurring-generation owner SHALL reconcile every eligible occurrence within the effective whole-day Rolling_Horizon.
2. WHEN no horizon setting exists, THE recurring-generation owner SHALL use a 30-day Rolling_Horizon.
3. WHEN a horizon setting is supplied, THE recurring-generation owner SHALL accept only a whole-day value from 1 through 365 and SHALL reject every value outside that bound without persistence.
4. WHEN eligible occurrences cross a calendar-month boundary, THE recurring-generation owner SHALL cover every successive date block without skipping an eligible occurrence.
5. WHEN a RecurringSchedule is disabled during generation, THE recurring-generation owner SHALL stop generation for that schedule, roll back the in-progress generation transaction, preserve pre-existing ClassSession history, and report a controlled non-success outcome.
6. WHEN a generation run fails for any other reason, THE recurring-generation owner SHALL roll back all rows created by that run, expose a controlled non-success outcome, and leave repair to a subsequent generation run rather than a calendar read.
7. WHEN manual generation or scheduled generation is invoked, THE invocation SHALL use the same SessionGeneratorService and effective horizon owner.
8. IF an occurrence has a conflict or no compatible room, THEN THE recurring-generation owner SHALL persist no ClassSession or event for that occurrence and SHALL report the applicable controlled failure; THE recurring-generation owner SHALL create no synthetic event, and every calendar event SHALL correspond to a persisted ClassSession.

### Requirement 4: Transactional and Idempotent Generation

**User Story:** As an academy owner, I want recurring generation to be safe under retries and concurrency, so that sessions are not duplicated or partially committed.

#### Acceptance Criteria

1. WHEN recurring generation begins an occurrence decision, THE recurring-generation owner SHALL begin the database transaction before conflict evaluation and SHALL include the final ClassSession write in that same transaction.
2. WHEN concurrent workers process the same Occurrence_Key, THE recurring-generation owner SHALL protect the decision with database locks and/or a database-enforced uniqueness constraint so that at most one ClassSession commits.
3. WHEN a generation request is retried with the same idempotency identity and identical input, THE recurring-generation owner SHALL return the original success outcome and SHALL create no duplicate row.
4. WHEN a generation request is retried after failure, THE recurring-generation owner SHALL reconcile committed eligible rows, SHALL retry missing eligible occurrences, and SHALL expose no partial row from a failed operation.
5. IF an idempotency identity is reused with changed input, THEN THE recurring-generation owner SHALL reject the request and SHALL mutate no scheduling row.
6. WHEN room or conflict state is evaluated, THE recurring-generation owner SHALL read that state after acquiring the required locks and inside the same transaction that protects the final write.
7. IF the target database cannot enforce the Occurrence_Key uniqueness invariant, THEN THE scheduling operation SHALL fail before any mutation and SHALL use no in-memory fallback for duplicate protection.

### Requirement 5: Instrument Room Preferences and Fallback

**User Story:** As an academy scheduler, I want suitable rooms selected consistently, so that generated and manual sessions use compatible resources.

#### Acceptance Criteria

1. WHEN an instrument is Violin, Piano, Voice, Guitar, or Drums, THE single room owner SHALL map the preferred room respectively to `101`, `103`, `102`, `102`, or `104`.
2. WHEN the preferred room is active, compatible, and available for the complete proposed interval, THE single room owner SHALL select the preferred room.
3. WHEN the preferred room is inactive, incompatible, or unavailable for any part of the complete interval, THE single room owner SHALL select the active compatible available room with the lowest numeric room value.
4. IF no active compatible available room exists, THEN THE single room owner SHALL return a controlled no-room conflict and SHALL persist no ClassSession or event.
5. WHEN a legacy room string is read or carried through a session operation, THE room owner SHALL preserve the exact persisted string and SHALL not rewrite it as a room identifier.
6. WHEN room selection is requested by manual creation, edit, recurring generation, availability, or DemoSeeder, THE operation SHALL use the same sole room owner and SHALL not introduce a second room-selection engine.
7. IF the existing schema cannot represent the required mapping, THEN THE design proposal SHALL document one reversible compatibility mapping at the existing room owner and SHALL not introduce a parallel engine.

### Requirement 6: Smart Availability and Conflict Details

**User Story:** As an authorized administrator, I want availability known before save, so that conflicts are explainable.

#### Acceptance Criteria

1. WHEN an authorized create or edit availability evaluation completes, THE existing scheduling boundary SHALL return exactly one Availability_Result state: `AVAILABLE` or `CONFLICT`.
2. WHEN an edit availability evaluation is requested, THE existing scheduling boundary SHALL evaluate the proposed teacher, student, instrument, date, time, duration, room, and current session identity; WHEN a create evaluation is requested, THE boundary SHALL evaluate the proposed values without a current session identity.
3. WHEN a conflict exists, THE Availability_Result SHALL include the applicable teacher, student, room, enrollment, recurring, or existing-session Conflict_Detail and SHALL include available teacher, student, room, existing-session identity, and conflicting time-range values for each value that exists.
4. WHEN a proposal overlaps a cancelled ClassSession, THE ConflictDetectionService SHALL classify the cancelled row as non-blocking.
5. WHEN a proposal overlaps a completed ClassSession, THE ConflictDetectionService SHALL return `CONFLICT` and SHALL retain the completed row as historical blocking evidence.
6. WHEN an edit changes a conflict-relevant field, THE SessionEditService SHALL recompute availability from current persisted state.
7. WHEN an `AVAILABLE` result proceeds to persistence, THE existing scheduling boundary SHALL lock and recheck current conflict and room state inside the transaction before the final write; IF the recheck finds a new conflict or fails, THEN THE boundary SHALL roll back the full transaction, including every partial change, and SHALL return an error.
8. IF authorization fails, THEN THE scheduling boundary SHALL return the compatible authorization failure and SHALL reveal no conflict details, scheduling identities, or protected data.
9. THE scheduling boundary SHALL reuse ConflictDetectionService, SessionCreateService, SessionEditService, RoomResolver, RoomOptionProvider, existing DTO/resource conventions, and SessionPolicy as the existing owners.

### Requirement 7: Session Transactions and Audit

**User Story:** As an academy owner, I want session mutations atomic and auditable, so that schedule history remains explainable.

#### Acceptance Criteria

1. WHEN an authorized ClassSession create, edit, or delete succeeds, THE existing session mutation owner SHALL commit exactly one immutable accepted Session_Audit_Record in the same database transaction as the mutation.
2. WHEN a Session_Audit_Record is accepted, THE audit owner SHALL store before and after values, actor identity, source surface, operation time, and changed fields, together with the session identity and action.
3. IF validation, authorization, conflict, lock, persistence, audit, connectivity, or any other failure occurs before or after a session write, THEN THE session mutation owner SHALL roll back every session, related-counter, and accepted-audit write and SHALL return a failed outcome.
4. WHEN a preview, rejected, failed, interrupted, or incomplete mutation occurs, THE audit owner SHALL create no accepted Session_Audit_Record.
5. WHEN a Session_Audit_Record is committed, THE audit owner SHALL keep the record immutable and readable after the ClassSession changes or is deleted.
6. IF the existing audit owner cannot carry the session lifecycle contract, THEN THE design SHALL propose only the smallest adapter at the existing audit boundary; IF the existing audit owner can carry the contract, THEN THE design SHALL propose no adapter.

### Requirement 8: Realistic Demo Seeder

**User Story:** As a developer or reviewer, I want realistic persisted demo sessions, so that calendar behavior is evaluated from real rows.

#### Acceptance Criteria

1. WHEN DemoSeeder creates recurring schedules or ClassSession demo rows, THE DemoSeeder SHALL use Saturday through Thursday only under the canonical weekday convention and SHALL create no Friday occurrence.
2. WHEN DemoSeeder creates an operating-day fixture, THE DemoSeeder SHALL create adjacent sequential slots covering 09:00 through 21:00 without a giant artificial gap.
3. WHEN DemoSeeder receives a nonzero ordered population of N sessions, THE DemoSeeder SHALL use the deterministic rule `N_60 = floor(N / 100)` and `N_30 = N - N_60`, assign exactly `N_60` 60-minute sessions at deterministic every-100th positions, and assign exactly `N_30` 30-minute sessions, thereby implementing the 99%/1% rule with deterministic rounding for every nonzero N.
4. IF DemoSeeder receives a zero population, THEN THE DemoSeeder SHALL create no sessions and SHALL skip duration-rounding logic.
5. WHEN DemoSeeder assigns a room, THE DemoSeeder SHALL use the sole approved room preference/fallback owner and SHALL use the shared conflict owner before persistence.
6. WHEN DemoSeeder runs repeatedly, THE DemoSeeder SHALL use Occurrence_Key to skip existing fixture sessions, preserve manual changes, and create no duplicate occurrence.
7. WHEN DemoSeeder succeeds, THE DemoSeeder SHALL report success only after the complete intended batch commits, and THE Calendar_Projection SHALL expose only persisted seeded ClassSession rows.
8. IF DemoSeeder fails or is interrupted, THEN THE DemoSeeder SHALL roll back and remove every schedule and ClassSession created by that run, SHALL preserve all pre-existing data, and SHALL return a controlled failure.
9. IF an existing fixture conflicts with the new distribution, THEN THE DemoSeeder SHALL preserve the unrelated fixture contract and SHALL not silently rewrite unrelated fixtures.

### Requirement 9: Query Performance and Index Review

**User Story:** As an academy operator, I want calendar and conflict queries bounded and responsive, so that correctness improvements remain operationally safe.

#### Acceptance Criteria

1. WHEN CalendarQueryService loads sessions, THE query boundary SHALL eager-load every relation required by CalendarEventResource before materialization and SHALL issue no per-row relation query.
2. WHEN CalendarQueryService receives filters, THE query boundary SHALL apply all pre-materialization date and identity predicates before materializing the collection.
3. WHEN the schema is reviewed, THE index review SHALL cover teacher identity, student identity, room identity or legacy room, session date, status, instrument identity, and Occurrence_Key lookup dimensions.
4. WHEN index changes are proposed, THE design SHALL propose only evidence-backed additive indexes and SHALL preserve the existing legacy room contract.
5. WHEN a legacy room identifier column is absent, THE design SHALL identify one legacy room index choice or approved compatibility column and SHALL not add a parallel room engine.
6. WHEN a normal-load calendar request is measured against the existing 50-session fixture, THE implementation SHALL record and preserve the established eager-loading and query-count baseline.
7. WHEN a requested date span exceeds the supported maximum, THE Calendar_Feed SHALL return a controlled no-write validation response.
8. IF an index or query change would break an existing schema or public contract, THEN THE Approval_Gate SHALL block the change; WHEN the owner explicitly approves the breaking change, THE Approval_Gate SHALL unblock only that approved change.

### Requirement 10: Security and Authorization

**User Story:** As an academy owner, I want scheduling data protected, so that only authorized operators can read or mutate sessions.

#### Acceptance Criteria

1. WHEN a calendar, availability, create, edit, generate, delete, or system-initiated scheduling operation is requested, THE existing middleware and SessionPolicy boundary SHALL authorize the operation before exposing data or permitting mutation.
2. IF authorization fails, THEN THE scheduling boundary SHALL return the compatible authorization failure and SHALL reveal no scheduling, teacher, student, room, conflict, or availability details.
3. WHEN actual scheduling input exists, THE validation boundary SHALL explicitly validate dates, times, durations, identities, statuses, rooms, filters, and idempotency values before mutation; WHEN no scheduling input exists, THE validation boundary SHALL not run scheduling-input validation.
4. WHEN user-controlled values are rendered, THE existing Resource, Blade, and FullCalendar boundaries SHALL escape output and SHALL use no inline event handlers.
5. WHEN a transaction or retry fails, THE public response SHALL use a safe generic failure response and SHALL omit SQL, exception traces, lock details, and sensitive data.
6. THE feature SHALL preserve Laravel security contracts, including Form Request validation, parameterized Eloquent queries, `$fillable`, named routes, CSRF protection, and policy ownership.

### Requirement 11: Accessibility, RTL, and Responsive Compatibility

**User Story:** As an administrator using Persian RTL surfaces, I want scheduling feedback operable and understandable, so that the enhancement does not reduce accessibility.

#### Acceptance Criteria

1. WHEN a calendar or session form displays an Availability_Result or Conflict_Detail, THE Calendar_Surface SHALL expose semantic accessible controls and status semantics.
2. WHEN a user operates any scheduling control by keyboard, THE Calendar_Surface SHALL provide the same navigation, availability, validation, save, and error behavior as pointer interaction and SHALL preserve visible focus.
3. WHEN Persian text is displayed in RTL, THE Calendar_Surface SHALL preserve readable RTL Persian text and readable LTR date/time tokens without changing persisted values.
4. WHEN the calendar or scheduling form is rendered, THE Calendar_Surface SHALL provide 44 by 44 CSS pixel minimum touch targets, WCAG AA-compatible contrast, no color-only communication, and reduced-motion behavior.
5. WHEN a user changes a room or time, THE Calendar_Surface SHALL preserve focus and expose the updated availability state without inaccessible layout movement.
6. WHILE the Calendar_Surface is rendered at 390, 430, 768, 1024, 1366, 1600, or 1920 viewport widths, THE implementation SHALL verify horizontal fit before initial rendering, SHALL block initial rendering when content does not fit or fit cannot be determined, SHALL permit rendering when current content fits, and SHALL not block current rendering based only on potential future overflow; intermediate viewport widths are outside this criterion.

### Requirement 12: Compatibility and Single Ownership

**User Story:** As a maintainer, I want completion to extend current boundaries, so that established modules remain stable.

#### Acceptance Criteria

1. WHEN scheduling behavior changes, THE implementation SHALL reuse the current CalendarQueryService, RecurringSchedule, ClassSession, SessionGeneratorService, repositories/services, DTOs, resources, policies, authorization boundaries, and other established owners rather than replacing them.
2. WHEN a request, response, route, HTTP verb, DTO, Blade contract, or client is affected, THE implementation SHALL preserve backward-compatible behavior and existing response keys, meanings, and relation-path integrity.
3. IF any proposal introduces a parallel calendar, scheduler, conflict engine, room engine, audit engine, or duplicate business logic, THEN THE Approval_Gate SHALL reject the proposal, including when the parallel implementation is temporary, migration-only, or used for A/B testing.
4. WHEN existing calendar, session, policy, DTO, resource, and browser tests run, THE implementation SHALL preserve established behavior except for explicitly approved corrections and SHALL remain compatible with those tests.
5. WHEN a persisted session uses an enrollment-backed or direct relation path, THE implementation SHALL preserve the authoritative relation path and SHALL not silently mix relation authorities.
6. IF a schema adapter is required, THEN THE implementation SHALL use an explicitly approved reversible adapter that preserves historical data, room strings, session IDs, and public contracts.
7. WHEN implementation planning begins, THE plan SHALL assign exactly one implementation owner to each file and SHALL authorize no overlapping implementation agents.

### Requirement 13: Approval Gate

**User Story:** As the project owner, I want to approve evidence and impact boundaries before implementation, so that code changes do not rely on an unproven diagnosis.

#### Acceptance Criteria

1. WHEN this document is reviewed, THE project owner SHALL explicitly approve or reject the root-cause findings and the required first-disappearance provenance trace.
2. WHEN this document is reviewed, THE project owner SHALL explicitly approve or reject every file-inventory label, including `CHANGE`, `PRESERVE`, `ADAPTER`, `CANDIDATE`, `INSPECT`, and `EXTEND` labels.
3. IF root-cause approval, provenance-trace approval, or any file-inventory approval is pending or rejected, THEN the requirements-only directory state SHALL remain in effect and the specification SHALL block creation of `design.md`, `tasks.md`, and implementation changes.
4. IF the project owner explicitly approves the root cause, provenance trace, and every file-inventory label, THEN the next phase MAY create `design.md` only after requirements completion, and THE design SHALL preserve the explicit non-goals, compatibility constraints, and evidence discipline.
5. WHEN this requirements phase completes before approval, THE feature directory SHALL contain only the requirements document and existing config and SHALL contain no `design.md`, `tasks.md`, or implementation change.
6. WHEN all required approvals are explicitly approved, THE next phase SHALL proceed to design only; WHEN any required approval is pending or rejected, THE implementation SHALL remain blocked.

## Correctness Properties

These properties are obligations for the approved implementation; they are not claims that current code satisfies them.

### Persisted Projection

- For every valid range and filter set, the ordered event IDs SHALL equal the ordered IDs of persisted matching ClassSession rows exactly once.
- For every persisted matching row, date, local start, duration, status, relation display, and persisted room metadata SHALL remain equivalent through the projection boundaries.
- For every zero-result request, the projection SHALL perform zero session writes and SHALL return zero events.
- For every trace mismatch, the first mismatching boundary SHALL be reported without downstream compensation; a nonmatching query predicate SHALL be reported as intentional query exclusion.
- For every complete FullCalendar fetch range, the transport range SHALL equal the requested inclusive range; a selected-day value SHALL not narrow the endpoint request.
- For every malformed endpoint event, normalization SHALL reject the event with an explicit reason and SHALL not synthesize or substitute an ID; every valid mapped persisted event SHALL remain in the normalized collection and rendered identity set.

### Recurrence and Concurrency

- Repeating generation for one active schedule and Occurrence_Key any number of times SHALL leave at most one committed ClassSession for the key.
- Concurrent workers for equal occurrence input SHALL produce a database state equivalent to one successful generation, with no duplicate or partial row.
- A failed run followed by retry SHALL reconcile missing eligible occurrences while preserving committed occurrence identities.
- A disabled schedule SHALL produce no new future occurrence.

### Room and Conflict

- An available preferred room SHALL be selected before fallback.
- An unavailable preferred room SHALL select the first room in the deterministic compatible-active-available order.
- No available compatible room SHALL produce `CONFLICT` and zero persisted session writes.
- A proposal overlapping only cancelled rows SHALL remain available for otherwise satisfied constraints.
- A proposal overlapping completed history SHALL report blocking evidence.
- An edit result SHALL equal a fresh evaluation of the proposed state while excluding only the edited session from self-overlap.

### Seeder and Compatibility

- Every generated demo date SHALL be Saturday–Thursday; no generated demo date SHALL be Friday.
- Operating-day fixture sessions SHALL cover 09:00–21:00 without the approved giant-gap threshold.
- The measured duration distribution SHALL follow the documented 99%/1% rounding rule.
- Repeating DemoSeeder after success SHALL not change the set of persisted Occurrence_Keys.
- Existing clients using existing routes without new filters SHALL receive contract-compatible responses and policy outcomes.

## Transaction, Concurrency, Audit, and Performance Requirements

- Manual create, edit, delete, and recurring generation SHALL use database transactions covering authoritative conflict/room evaluation and final persistence.
- Appropriate row/resource locks and a database-enforced duplicate invariant SHALL protect the Occurrence_Key decision; exact strategy is a design decision after approval.
- Session edit SHALL preserve the existing optimistic version behavior and add lock-safe conflict recomputation rather than bypassing it.
- Successful create/edit/delete SHALL commit exactly one accepted-mutation audit record atomically with the mutation.
- Retry after success SHALL be idempotent; retry after rollback SHALL not expose partial writes.
- Cancelled sessions SHALL not block new overlap decisions; completed sessions SHALL remain historical blocking evidence.
- Calendar queries SHALL eager-load required relations and apply filters before resource mapping.
- Index review SHALL cover teacher identity, student identity, room identity or legacy room, session date, status, instrument identity, and occurrence lookup as the approved schema permits.
- The existing normal-load query-count baseline SHALL remain the performance baseline; measured regression requires owner approval and evidence.
- Rolling generation SHALL remain bounded by the effective horizon and SHALL not run as a side effect of a calendar request.

## Proposed File Inventory — Approval-Gated

This is a proposal, not implementation authorization. **Verified existing** means the path was inspected or located. **Candidate** means existence or final ownership is not verified. **Preserve** means no change is proposed unless approval changes the boundary. No file in this inventory is changed by this requirements phase.

### Current Calendar Projection Ownership

- **CHANGE — Verified existing:** `app/Http/Controllers/Admin/CalendarController.php` — preserve named endpoints and keep reads free of generation.
- **CHANGE — Verified existing:** `app/Http/Requests/Admin/CalendarEventRequest.php` — preserve date validation and add only approved filter validation.
- **CHANGE — Verified existing:** `app/Services/CalendarQueryService.php` — apply six filters, inclusive range, eager loading, and stable ordering.
- **CHANGE — Verified existing:** `app/Models/Concerns/ScopesForSessionFilters.php` — extend canonical instrument/status filtering without forking relation paths.
- **PRESERVE/ADAPTER — Verified existing:** `app/Http/Resources/CalendarEventResource.php`, `app/Services/SessionDisplayMapper.php`, `app/DTOs/CalendarEventData.php` — preserve persisted event shape and provenance.
- **CHANGE — Verified existing:** `resources/js/calendar/fullcalendar.js` — transport requested range/filter values and remain reject-only.
- **PRESERVE/CHANGE — Verified existing:** `resources/js/calendar/calendar-app.js`, `resources/views/admin/calendar/index.blade.php` — preserve FullCalendar, RTL, focus, and ownership boundaries.
- **PRESERVE — Verified existing:** `routes/web.php` — preserve route names, verbs, middleware, and contracts.

### Current Session and Conflict Ownership

- **CHANGE — Verified existing:** `app/Services/SessionCreateService.php` — move authoritative conflict/room evaluation into the transaction and integrate audit through the existing boundary.
- **CHANGE — Verified existing:** `app/Services/SessionEditService.php` — preserve row locking/version checks and return structured availability through a compatible adapter.
- **CHANGE — Verified existing:** `app/Http/Controllers/Admin/ClassSessionController.php` — preserve manual route and make delete transactional through the existing owner.
- **CHANGE — Verified existing:** `app/Services/ConflictDetectionService.php` — add one shared status-aware policy and structured details.
- **CHANGE — Verified existing:** `app/Services/RoomResolver.php`, `app/Services/RoomOptionProvider.php` — own preference and deterministic fallback.
- **PRESERVE/CHANGE — Verified existing:** `app/Models/ClassSession.php`, `app/Models/RecurringSchedule.php`, `app/Enums/SessionStatusEnum.php` — preserve identifiers, casts, status values, and active semantics.
- **CHANGE — Verified existing:** `app/Http/Requests/Admin/SessionCreateRequest.php`, `app/Http/Requests/Admin/SessionEditRequest.php` — validate availability inputs without breaking current forms.
- **PRESERVE — Verified existing:** `app/Policies/SessionPolicy.php` — reuse authorization abilities.
- **CANDIDATE — Existence not verified:** one DTO/adapter under `app/DTOs/` for Availability_Result and Conflict_Detail if current contracts cannot carry the response.

### Recurrence and Invocation Ownership

- **CHANGE — Verified existing:** `app/Services/SessionGeneratorService.php` — rolling horizon, successive blocks, transaction/locks, and idempotency.
- **CHANGE — Verified existing:** `app/Http/Controllers/Admin/ClassSessionController.php` — delegate manual generation to the same effective horizon owner.
- **CHANGE — Verified existing:** `routes/console.php` — add an approved invocation only if this remains the established console owner.
- **CANDIDATE — Existence not verified:** one command/job/scheduler path under the existing Laravel console ownership for recurring reconciliation.
- **PRESERVE/CHANGE — Verified existing:** `app/Models/RecurringSchedule.php` — disabled schedules retain history.

### Persistence, Index, and Audit Ownership

- **INSPECT/PRESERVE — Verified existing:** `database/migrations/0001_01_01_000008_create_class_sessions_table.php`, `database/migrations/2026_07_05_000000_decouple_class_sessions_from_enrollment.php`, and the existing recurring-schedule migration — preserve historical fields and inspect indexes.
- **INSPECT/PRESERVE — Verified existing:** existing report/index migrations — avoid duplicate or conflicting indexes.
- **CANDIDATE — Existence not verified:** one additive migration for approved Occurrence_Key uniqueness and evidence-backed query indexes, subject to the legacy room decision.
- **CHANGE/ADAPTER — Verified existing:** `app/Models/AuditRecord.php`, `app/Services/AuditRecordService.php` — reuse generic audit ownership for session lifecycle records.
- **INSPECT/PRESERVE — Verified existing:** the existing audit-record migration — extend only through explicit approval.

### Demo Data Ownership

- **CHANGE — Verified existing:** `database/seeders/DemoSeeder.php` — Saturday–Thursday persisted coverage, Friday exclusion, 09:00–21:00 continuity, duration distribution, shared room ownership, and repeat-run idempotency.
- **PRESERVE — Verified existing:** `database/seeders/TestDataSeeder.php` — do not weaken E2E fixture contracts.

### Tests and Browser Validation

- **EXTEND — Verified existing:** `tests/Feature/Admin/CalendarControllerTest.php` — persisted projection, filters, range boundaries, and read-only behavior.
- **CANDIDATE — Existence not verified:** additional calendar performance, cross-module, session scheduling, generator, conflict, room, resource, transaction, audit, concurrency, seeder, property, and browser/RTL tests under their current test owners.
- **PRESERVE — Verified existing:** all unrelated tests; no test removal or weakening is permitted.

### Explicitly Out of Scope for the Inventory

- New parallel calendar, scheduler, conflict, room, or audit subsystems.
- Breaking routes, APIs, Blade contracts, public resource shapes, or historical data.
- Unapproved production code, migration, route, test, seeder, or configuration changes during this requirements phase.
- `design.md` and `tasks.md` before the Approval_Gate.

## Testing Requirements

1. WHEN implementation is reviewed for progression, THE approved test suite SHALL cover each supported date, teacher, student, instrument, room, and status filter and both inclusive range boundaries, and THE implementation SHALL remain blocked until every filter type is covered.
2. WHEN implementation is authorized, THE approved test suite SHALL trace the requested inclusive range, all six supported filters, counts, ordered persisted stable IDs, and explicit rejection reasons through persisted-result, query-result, resource, endpoint JSON, fetch payload, FullCalendar normalization, and rendered identity boundaries, including zero-result read-only behavior and separate assertions for query exclusion, frontend single-day range narrowing, and malformed reject-only normalization.
3. WHEN implementation is authorized, THE approved test suite SHALL cover 30-day default recurrence, configurable horizon, disabled schedules, month boundaries, failure/retry, and concurrent generation.
4. WHEN implementation is authorized, THE approved test suite SHALL include property-based or parameterized repeated-generation checks with database assertions after rollback.
5. WHEN implementation is authorized, THE approved test suite SHALL cover all five room preferences, preferred-room unavailability, deterministic fallback, no-room failure, and legacy room preservation.
6. WHEN implementation is authorized, THE approved test suite SHALL cover AVAILABLE and CONFLICT results, complete conflict details, edit recomputation, cancelled non-blocking behavior, and completed historical blocking.
7. WHEN implementation is authorized, THE approved test suite SHALL cover create/edit/delete transaction rollback, audit failure, lock contention, stale version, and idempotency-key replay.
8. WHEN implementation is authorized, THE approved test suite SHALL cover Saturday–Thursday data, Friday absence, 09:00–21:00 continuity, giant-gap threshold, 99%/1% duration distribution, room compatibility, and repeat-run idempotency.
9. WHEN implementation is authorized, THE approved test suite SHALL retain existing eager-loading/query-count and browser tests and SHALL add RTL, keyboard, focus, reduced-motion, contrast, responsive, and no-overflow coverage where the surface changes.
10. WHEN implementation is authorized, THE approved validation SHALL run affected existing tests, targeted new tests, configured static/type checks, and the relevant asset build; this requirements phase SHALL run no implementation tests.

## Phase Completion Notice

This requirements phase is complete only after explicit approval of (1) the root-cause findings and first-disappearance trace and (2) every proposed file inventory label. Implementation cannot begin before that approval. No design document, task document, production-code change, migration, route, test, seeder, or configuration change is authorized now.
