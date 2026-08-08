# Requirements Document

## Introduction

This specification defines interactive, backend-authoritative scheduling for the Parsian Music Academy admin panel. The feature covers complete `ClassSession` editing, calendar drag/drop and resize, availability and conflict decisions, smart alternatives, drag-preview projections, immutable teacher/student business codes, academy scheduling rules, busy-data seeding, optimistic concurrency, audit history, availability caching, recurring schedule association and expansion, and modular scheduling-domain boundaries.

The feature is an additive evolution of the existing session and calendar contracts. The existing calendar remains a read-only projection of persisted `class_sessions`; the completed `calendar-persisted-session-projection` bugfix remains completed scope and SHALL be preserved, not reopened, replaced, or compensated for by this feature. This document defines the investigation-first implementation contract required before production code is written.

## Glossary

- **Academy**: The configured Parsian Music scheduling owner and tenant boundary for rules, resources, and sessions.
- **ClassSession**: The persisted `class_sessions` record representing one scheduled lesson occurrence.
- **Session_Edit_Surface**: The existing named admin session edit route and its compatible form/API representation.
- **Calendar_Surface**: The existing admin FullCalendar page, event feed, drawer, filters, and navigation.
- **Scheduling_Domain**: The backend boundary that evaluates proposals, availability, conflicts, rules, suggestions, recurrence, and scheduling mutations.
- **Schedule_Proposal**: A requested session state containing session identity when applicable, student, teacher, instrument, date, start time, duration, room, and client version token.
- **Availability_Result**: The backend response for a Schedule_Proposal, containing exactly one state: `AVAILABLE`, `CONFLICT`, or `INVALID`, plus explainable decision details.
- **Verification_Suite**: The independently runnable unit, property, integration, browser, architecture, and operational checks required by this specification.
- **DATA_REPAIR_MIGRATION**: The explicitly typed and approved migration operation permitted to repair an existing Business_Code.
- **Blocking_Conflict**: An overlap or rule violation that rejects a proposal unless an explicitly authorized Force_Override applies.
- **Force_Override**: An explicit, authorized mutation instruction that bypasses only documented overridable conflicts, records a reason, and never bypasses integrity, authorization, or impossible-resource rules.
- **Soft_Conflict**: A documented conflict category that an authorized Force_Override may bypass.
- **Hard_Constraint**: A non-overridable rule, including invalid references, unauthorized resources, closed academy time, invalid room requirements, relation-path integrity failure, or stale version state.
- **Teacher_Buffer**: Configurable time reserved before and after a teacher session for conflict evaluation.
- **Room_Requirement**: The required room capability or room identity constraints for an instrument or session.
- **Academy_Scheduling_Rules**: Versioned effective settings for working days/hours, daily limits, consecutive-session limits, lunch breaks, Teacher_Buffer, and Room_Requirements.
- **Busy_Seed**: Deterministic fixture data representing occupied scheduling intervals for development, test, or demo environments.
- **Business_Code**: A server-generated, non-secret identifier assigned to a teacher or student for operational identification.
- **Recurring_Schedule**: The persisted weekly or recurring source associated with eligible ClassSession occurrences.
- **Occurrence_Expansion**: Backend generation or reconciliation of eligible Recurring_Schedule occurrences into persisted ClassSession records.
- **Session_Version**: The concurrency token representing the version observed by a client before mutation.
- **Session_Audit_Record**: An immutable record of an accepted ClassSession mutation, including actor, source, versions, before/after values, override data, and timestamp.
- **Availability_Cache**: A backend cache of computed Availability_Result values keyed by all scheduling inputs and relevant rule/resource versions.
- **Investigation_Record**: The evidence-backed root-cause, current-architecture, impact, ownership, and risk record produced before implementation.
- **Impact_Matrix**: The complete add/change/preserve/out-of-scope file inventory grouped by technical layer.
- **Implementation_Plan**: The independently testable sequence of production changes and verification tasks created after investigation and design approval.
- **Existing_Projection_Fix**: The completed persisted-session projection behavior documented in `.kiro/specs/calendar-persisted-session-projection/`.
- **Authorized_Scheduler**: An authenticated actor granted the applicable SessionPolicy and scheduling-domain abilities.
- **Scheduling_Proposal_API**: The backend endpoint contract for availability, preview, suggestion, or mutation requests.

## Scope Boundaries and Preservation

The feature SHALL reuse the existing `ClassSession`, `RecurringSchedule`, `SessionCreateService`, `SessionEditService`, `ConflictDetectionService`, `SessionGeneratorService`, `CalendarQueryService`, `CalendarEventResource`, `SessionPolicy`, named routes, FullCalendar module architecture, and DTO/resource conventions unless the approved design proves a boundary change necessary. Any boundary change SHALL be documented in the Investigation_Record, Impact_Matrix, architecture review, and risk register before implementation.

The feature SHALL NOT modify the existing calendar-persisted-session-projection specification, its completed tasks, or application code during this requirements phase. Production code, migrations, seeders, tests, design, and task documents are subsequent phases.

## Requirements

### Requirement 1: Investigation-First Implementation Contract

**User Story:** As a project owner, I want implementation to begin with evidence and impact analysis, so that scheduling changes do not repeat existing defects or break established contracts.

#### Acceptance Criteria

1. WHEN production implementation begins, THE Scheduling_Investigation SHALL produce an Investigation_Record describing current behavior, intended behavior, confirmed root causes, hypotheses, evidence sources, ownership boundaries, assumptions, and unresolved questions.
2. WHEN current architecture is analyzed, THE Scheduling_Investigation SHALL trace ClassSession creation, editing, calendar projection, conflict evaluation, room resolution, recurrence generation, authorization, settings, and audit behavior from request entry to persistence or response.
3. WHEN impact analysis is completed, THE Impact_Matrix SHALL inventory every affected or inspected file by schema/migration, model/enum, policy, request, controller, service/action, DTO/resource, route, Blade, JavaScript/CSS/Vite, translation/configuration/scheduler, factory/seeder, test, and documentation layer.
4. WHEN a file is listed in the Impact_Matrix, THE Impact_Matrix SHALL label the file as add, change, preserve, or out of scope and SHALL state the contract or behavior affected by that label.
5. WHEN architecture validation is completed, THE Investigation_Record SHALL verify all of the following simultaneously before validation completes: single ownership for scheduling decisions, no duplicate conflict engine, no scheduling logic in Blade or JavaScript, compatibility with the existing Calendar_Surface, and compatibility with the existing API/UI contracts.
6. IF an implementation proposal crosses an existing owner boundary, THEN THE Investigation_Record SHALL identify the boundary, reason for crossing, alternative considered, migration impact, and approval required before production changes.
7. WHEN risk assessment is completed, THE Investigation_Record SHALL record each risk's likelihood, impact, affected boundary, mitigation, verification evidence, rollback approach, and residual risk.
8. WHEN the design phase begins, THE Implementation_Plan SHALL decompose the approved architecture into independently testable tasks with prerequisites, affected files, fixtures, acceptance criteria, validation commands, and rollback checkpoints.

### Requirement 2: Complete ClassSession Editing

**User Story:** As an authorized administrator, I want to edit every supported session field from one consistent surface, so that a persisted lesson can be corrected without bypassing domain rules.

#### Acceptance Criteria

1. WHEN an Authorized_Scheduler opens the Session_Edit_Surface, THE Session_Edit_Surface SHALL present the persisted student, teacher, instrument, date, start time, duration, status, room, notes, recurring association state, and Session_Version according to the approved edit contract.
2. WHEN an Authorized_Scheduler submits a valid Schedule_Proposal for an existing ClassSession, THE Scheduling_Domain SHALL validate and persist every permitted editable field in one transaction.
3. WHEN an existing ClassSession is enrollment-backed, THE Scheduling_Domain SHALL allow detection of a student, teacher, or instrument conflict with the authoritative enrollment relationship and SHALL fail the mutation without changing persisted data.
4. WHEN an existing ClassSession is directly associated with student, teacher, and instrument records, THE Scheduling_Domain SHALL allow detection of incompatible relation-path mixing and SHALL reject the mutation without changing persisted data.
5. IF a Schedule_Proposal contains a protected financial, enrollment, recurrence, or identity field outside the approved edit contract, THEN THE Scheduling_Domain SHALL reject the proposal with a field-specific validation error, SHALL prevent all mutation operations, and SHALL perform no persistence.
6. WHEN a ClassSession mutation succeeds, THE Scheduling_Domain SHALL return the authoritative persisted representation, including the new Session_Version, resolved room state, status, relation display data, and audit identity.
7. IF a ClassSession mutation fails validation, authorization, conflict evaluation, or persistence, THEN THE Scheduling_Domain SHALL preserve the prior persisted ClassSession and SHALL return a localized, field-specific failure where a field is identifiable.

### Requirement 3: Calendar Drag, Drop, and Resize

**User Story:** As an authorized administrator, I want to move and resize calendar sessions interactively, so that schedule corrections require fewer steps while retaining the same backend protections as form editing.

#### Acceptance Criteria

1. WHEN an Authorized_Scheduler completes a calendar drag or resize gesture, THE Calendar_Surface SHALL submit a Schedule_Proposal containing the session identifier, proposed date/time/duration, unchanged required relation values, room context, and Session_Version to the Scheduling_Proposal_API.
2. WHEN the Scheduling_Proposal_API accepts a drag or resize proposal, THE Calendar_Surface SHALL render the returned authoritative ClassSession state and SHALL not infer or overwrite server-owned values.
3. IF the Scheduling_Proposal_API rejects a drag or resize proposal, THEN THE Calendar_Surface SHALL restore the last authoritative event position and size, SHALL display the localized rejection reason, and SHALL retain the event's prior persisted identity.
4. WHEN a drag or resize proposal is in flight, THE Calendar_Surface SHALL prevent duplicate mutation submission for the same event and SHALL expose a pending state without modifying persisted data locally.
5. IF an actor lacks the applicable SessionPolicy or scheduling-domain ability, if authorization cannot be determined with confidence, or if the API response is ambiguous or delayed, THEN THE Calendar_Surface SHALL ignore the drag or resize gesture, SHALL disable preview capability for the gesture, and SHALL hide all session details until the API explicitly accepts the proposal with clear authorization confirmation.
6. WHEN the Scheduling_Proposal_API explicitly accepts a drag or resize proposal with clear authorization confirmation, THE Calendar_Surface SHALL permit the associated preview or mutation interaction.
7. WHEN a drag or resize operation is performed by keyboard or an equivalent accessible control, THE Calendar_Surface SHALL submit the same backend Schedule_Proposal as a pointer gesture.

### Requirement 4: Backend-Owned Availability and Conflict Decisions

**User Story:** As an academy administrator, I want one authoritative backend decision for every proposed slot, so that previews and final saves cannot disagree because of client-side scheduling logic.

#### Acceptance Criteria

1. THE Scheduling_Domain SHALL own all availability, overlap, rule, room, recurrence, and force-override decisions for Schedule_Proposal values.
2. WHEN the Scheduling_Proposal_API evaluates a valid Schedule_Proposal, THE Scheduling_Proposal_API SHALL return an Availability_Result with exactly one state: `AVAILABLE`, `CONFLICT`, or `INVALID`.
3. WHEN an Availability_Result has `CONFLICT` state, THE Scheduling_Proposal_API SHALL identify every applicable resource category among teacher, student, enrollment, room, academy rule, and recurring occurrence, SHALL identify blocking session identifiers when present, and SHALL provide each blocking time range in the academy timezone.
4. WHEN a proposed interval physically overlaps another interval, THE Scheduling_Domain SHALL classify the intervals as overlapping and conflicting regardless of Teacher_Buffer or other Academy_Scheduling_Rules values.
5. WHEN one interval ends exactly when another begins, THE Scheduling_Domain SHALL classify the intervals as non-overlapping unless a configured Teacher_Buffer creates an effective overlap.
6. WHEN a proposed interval overlaps a cancelled ClassSession, THE Scheduling_Domain SHALL classify the cancelled ClassSession as non-blocking.
7. WHEN a proposed interval overlaps a completed historical ClassSession, THE Scheduling_Domain SHALL classify the completed historical ClassSession as blocking unless an approved rule explicitly changes historical handling.
8. IF a Schedule_Proposal violates a Hard_Constraint, THEN THE Scheduling_Proposal_API SHALL return `INVALID` or `CONFLICT` with a non-overridable reason and SHALL not offer that proposal as available.
9. WHEN the same Schedule_Proposal is evaluated across separate API calls or server restarts under unchanged persisted state and rule/resource versions, THE Scheduling_Domain SHALL return equivalent decision categories and conflict identities regardless of cache state.
10. WHEN persisted scheduling state changes, THE Scheduling_Domain SHALL evaluate the changed state and SHALL be permitted to return a different decision category or conflict identity.

### Requirement 5: Explicit Conflict Policy and Force Overrides

**User Story:** As an academy administrator, I want conflict behavior and override authority to be explicit, so that an urgent schedule change cannot silently violate policy.

#### Acceptance Criteria

1. THE Scheduling_Domain SHALL treat every detected overlap or rule violation as blocking by default unless the violation is explicitly classified as Soft_Conflict.
2. WHEN an Authorized_Scheduler submits a proposal without Force_Override and a Blocking_Conflict is detected, THE Scheduling_Domain SHALL reject the proposal and SHALL leave all persisted scheduling data unchanged.
3. WHEN an Authorized_Scheduler submits a proposal with Force_Override, THE Scheduling_Domain SHALL verify the actor's dedicated override ability, explicit confirmation, and non-empty reason before evaluating any overridable Soft_Conflict.
4. IF a Force_Override lacks authorization, confirmation, or a reason, THEN THE Scheduling_Domain SHALL reject the proposal with stable code `UNAUTHORIZED_OVERRIDE` and SHALL not persist a session or audit record.
5. IF a proposal contains a Hard_Constraint, THEN THE Scheduling_Domain SHALL reject the proposal even when Force_Override is present.
6. WHEN a Force_Override accepts a Soft_Conflict, THE Scheduling_Domain SHALL persist the mutation only after recording the override reason, actor, conflict identities, and prior Session_Version in a Session_Audit_Record.
7. WHEN a proposal is rejected, THE Scheduling_Proposal_API SHALL distinguish ordinary conflict, unauthorized override, hard constraint, and stale version outcomes with stable machine-readable codes and localized messages.

### Requirement 6: Smart Suggestions

**User Story:** As an administrator, I want useful alternative slots and rooms when a proposal conflicts, so that resolving a schedule problem does not require manual trial and error.

#### Acceptance Criteria

1. WHEN a valid Schedule_Proposal has `CONFLICT` state, THE Scheduling_Domain SHALL be able to return a ranked set of alternative Schedule_Proposal values without persisting any alternative.
2. WHEN suggestions are returned, THE Scheduling_Domain SHALL fully evaluate every suggestion with the same conflict, Academy_Scheduling_Rules, room, Teacher_Buffer, authorization, and timezone rules used for final mutation before returning the suggestion.
3. WHEN suggestions are returned, THE Scheduling_Domain SHALL include the proposed date, start time, duration, room when applicable, availability state, and machine-readable explanation for each suggestion.
4. THE Scheduling_Domain SHALL rank suggestions deterministically using the approved ordering of temporal distance, resource preservation, room suitability, and rule compliance.
5. IF no authorized and available alternative exists within the requested search window, THEN THE Scheduling_Proposal_API SHALL return an empty suggestion set with a localized reason explaining whether no authorized alternative exists or authorized alternatives were filtered out by the search window, room requirements, conflicts, or another documented criterion.
6. WHEN a user selects a suggestion, THE Calendar_Surface SHALL submit the selected values for fresh backend evaluation rather than treating the suggestion as an authorization or availability guarantee.

### Requirement 7: Drag Preview Projections

**User Story:** As an administrator, I want to see the projected result while moving a session, so that I can understand conflicts before committing a change.

#### Acceptance Criteria

1. WHILE an Authorized_Scheduler is dragging or resizing a calendar event, THE Calendar_Surface SHALL be able to request a non-persisting preview for the current Schedule_Proposal.
2. WHEN the Scheduling_Proposal_API processes a preview request, THE Scheduling_Domain SHALL prevent every ClassSession operation, including creating, updating, deleting, or generating a ClassSession, before returning the preview result.
3. WHEN a preview state is `AVAILABLE`, THE Calendar_Surface SHALL display the projected date/time/duration/room using presentation state supplied by the backend and SHALL identify the projection as uncommitted.
4. WHEN a preview state is `CONFLICT` or `INVALID`, THE Calendar_Surface SHALL display the backend reason and SHALL identify the affected resource or rule without presenting the projection as saved.
5. THE Calendar_Surface JavaScript SHALL restrict itself to gesture orchestration, request transport, response rendering, pending state, and rollback; THE Calendar_Surface JavaScript SHALL not implement overlap, buffer, working-hour, daily-limit, lunch, recurrence, room-capability, or force-override decisions.
6. THE Calendar_Surface Blade templates SHALL contain structure, labels, accessibility attributes, and server-provided data only; THE Calendar_Surface Blade templates SHALL not query models or evaluate scheduling rules.
7. IF a preview request is stale, cancelled, unauthorized, or malformed, THEN THE Scheduling_Proposal_API SHALL return a non-available result, THE Calendar_Surface SHALL discard the preview, and THE Calendar_Surface SHALL not change persisted or authoritative event state.

### Requirement 8: Immutable Teacher and Student Business Codes

**User Story:** As an academy operator, I want stable operational codes for teachers and students, so that records remain unambiguous across scheduling, imports, and support workflows.

#### Acceptance Criteria

1. WHEN a Teacher or Student is created through an approved application path, THE Scheduling_Domain SHALL obtain a unique non-empty Business_Code for the created teacher or student from the backend code owner before the record becomes available to scheduling.
2. WHEN an existing Teacher or Student lacks a Business_Code during an approved backfill, THE Scheduling_Domain SHALL assign exactly one unique Business_Code without changing the record's primary key or relationships.
3. WHEN a Teacher or Student is updated, THE Scheduling_Domain SHALL preserve the existing Business_Code byte-for-byte unless the request is specifically typed as `DATA_REPAIR_MIGRATION` and the data-repair migration is explicitly approved.
4. IF a request attempts to set, replace, or clear a Teacher or Student Business_Code through a user-editable field or scheduling payload, THEN the backend SHALL reject the field and SHALL retain the persisted Business_Code.
5. WHEN concurrent creation or backfill requests target Business_Code allocation, THE backend code owner SHALL guarantee uniqueness through at least one of transactional coordination or database enforcement and SHALL retry or return a controlled failure without duplicate codes.
6. THE Scheduling_Proposal_API SHALL expose Business_Code values only to actors authorized to view the corresponding Teacher or Student record and SHALL not treat a Business_Code as an authentication secret.
7. WHEN Business_Code values are displayed in Persian admin surfaces, THE Calendar_Surface and Session_Edit_Surface SHALL use localized labels, escaped output, and the approved documented code format.

### Requirement 9: Academy Scheduling Rules and Teacher Buffer

**User Story:** As an academy administrator, I want scheduling constraints to be configurable and centrally enforced, so that every schedule surface follows academy policy.

#### Acceptance Criteria

1. THE Academy_Scheduling_Rules SHALL provide a versioned effective configuration for enabled weekdays, academy opening time, academy closing time, minimum and maximum session duration, daily session limit, consecutive-session limit, lunch interval, Teacher_Buffer before a session, Teacher_Buffer after a session, and timezone, and SHALL enforce meaningful non-contradictory values for every configured constraint.
2. WHEN an academy rule has no explicit override, THE Academy_Scheduling_Rules SHALL apply the documented academy default recorded by the approved design and SHALL expose the effective source and version to the Scheduling_Domain.
3. WHEN a Schedule_Proposal starts before academy opening, ends after academy closing, or falls on a disabled weekday, THE Scheduling_Domain SHALL return a Hard_Constraint result identifying the violated working-hour rule.
4. WHEN a Schedule_Proposal causes a teacher to exceed the configured daily session limit, THE Scheduling_Domain SHALL return a Hard_Constraint result identifying the teacher and limit.
5. WHEN a Schedule_Proposal causes a teacher to exceed the configured consecutive-session limit, THE Scheduling_Domain SHALL return a Hard_Constraint result identifying the teacher and consecutive sequence.
6. WHEN a Schedule_Proposal overlaps a configured teacher lunch interval, THE Scheduling_Domain SHALL return a Hard_Constraint result identifying the lunch interval.
7. WHEN a Schedule_Proposal is evaluated for a teacher, THE Scheduling_Domain SHALL expand the configured Teacher_Buffer on both sides of every blocking teacher interval and SHALL include the effective buffer in the Availability_Result.
8. WHEN an authorized administrator changes Academy_Scheduling_Rules, THE backend SHALL validate the complete effective configuration, increment the rules version, invalidate affected Availability_Cache entries, and preserve prior Session_Audit_Record values.
9. IF Academy_Scheduling_Rules contain contradictory, missing, or out-of-range values, THEN the settings boundary SHALL reject the change with field-specific localized errors and SHALL preserve the last valid configuration.

### Requirement 10: Room Requirements and Availability

**User Story:** As an academy administrator, I want room requirements to be enforced centrally, so that a lesson is not scheduled into an unsuitable or unavailable room.

#### Acceptance Criteria

1. WHEN a Schedule_Proposal requires a room, THE Scheduling_Domain SHALL validate the room's existence, active state, academy ownership, capability match, and interval availability before returning `AVAILABLE`.
2. WHEN a Schedule_Proposal omits a required room, THE Scheduling_Domain SHALL return a Hard_Constraint result identifying the missing Room_Requirement.
3. WHEN a selected room is inactive, unauthorized, incompatible, or occupied, THE Scheduling_Domain SHALL return a `CONFLICT` or `INVALID` result with the exact room reason and applicable blocking interval.
4. WHEN a Schedule_Proposal allows room selection, THE Scheduling_Domain SHALL use the approved active-room ordering and SHALL return only rooms that satisfy Room_Requirements and all relevant conflicts.
5. WHEN room availability is evaluated for a preview, suggestion, create, edit, drag, resize, recurrence expansion, or busy seed, THE Scheduling_Domain SHALL use the same Room_Requirement and room-conflict policy.
6. THE Calendar_Surface SHALL display room availability and room conflict details returned by the backend without deriving room suitability in Blade or JavaScript.

### Requirement 11: Optimistic Concurrency

**User Story:** As an academy operator, I want concurrent edits to be detected rather than overwritten, so that the last browser response cannot silently erase another administrator's work.

#### Acceptance Criteria

1. WHEN a ClassSession is loaded for mutation, THE Session_Edit_Surface and Calendar_Surface SHALL carry the current Session_Version with the editable representation.
2. WHEN a mutation includes a Session_Version equal to the locked persisted version, THE Scheduling_Domain SHALL evaluate the proposal and commit all related writes atomically.
3. IF a mutation includes a missing, malformed, or stale Session_Version, THEN THE Scheduling_Domain SHALL reject the mutation with a stable stale-version code, SHALL return the latest authorized representation, and SHALL perform no partial scheduling write.
4. WHEN a stale-version rejection is returned, THE Calendar_Surface and Session_Edit_Surface SHALL preserve the user's uncommitted values separately from the authoritative latest values and SHALL offer an explicit reload or review path.
5. WHEN a Force_Override is submitted against a current version, THE Scheduling_Domain SHALL still evaluate all Hard_Constraints and SHALL record the current version and override reason in the Session_Audit_Record.
6. WHEN concurrent mutations target the same ClassSession or conflicting resources, THE Scheduling_Domain SHALL always use transaction locking or equivalent atomic coordination for those concurrent mutations, regardless of whether the mutations ultimately conflict, so that no accepted result violates the final conflict policy.
7. WHEN optimistic concurrency rejects a request, THE Scheduling_Domain SHALL not increment the accepted mutation version and SHALL not create a Session_Audit_Record for the rejected mutation.

### Requirement 12: Versioned Session Audit History

**User Story:** As an academy owner, I want a complete immutable history of accepted schedule changes, so that schedule decisions remain explainable and reviewable.

#### Acceptance Criteria

1. WHEN a ClassSession create, edit, drag, resize, force override, recurrence expansion, or approved deletion succeeds, THE Scheduling_Domain SHALL append exactly one Session_Audit_Record for the accepted mutation.
2. WHEN a Session_Audit_Record is appended, THE record SHALL contain the ClassSession identifier, action type, actor identifier, source surface, prior Session_Version, resulting Session_Version, timestamp, changed fields, before values, after values, conflict summary, and override reason when applicable.
3. THE Session_Audit_Record SHALL be immutable after creation, SHALL preserve historical values independently of later ClassSession changes, and SHALL use a versioned representation for schema evolution.
4. IF a preview, validation failure, unauthorized request, stale request, or rejected conflict does not change persisted scheduling state, THEN the Scheduling_Domain SHALL not append an accepted-mutation Session_Audit_Record.
5. WHEN an Authorized_Scheduler requests session history, THE Scheduling_Domain SHALL return audit records in deterministic version and timestamp order with localized action labels and access-controlled values.
6. IF an actor lacks audit-history authorization, THEN the backend SHALL return an authorization failure without exposing before values, after values, actor details, or conflict details.
7. WHEN an audit write fails during an otherwise valid mutation, THE Scheduling_Domain SHALL roll back the mutation and SHALL return a controlled failure without presenting the ClassSession as successfully changed.

### Requirement 13: Recurring Schedule Association and Expansion

**User Story:** As an academy scheduler, I want recurring schedules to remain associated with generated occurrences, so that future schedule changes are traceable without duplicating sessions.

#### Acceptance Criteria

1. WHEN a Recurring_Schedule is eligible for expansion, THE Scheduling_Domain SHALL evaluate each occurrence through the same Availability_Result, Academy_Scheduling_Rules, Room_Requirement, conflict, and authorization policy used by manual scheduling.
2. WHEN an eligible occurrence is expanded, THE Scheduling_Domain SHALL persist exactly one associated ClassSession identified by the Recurring_Schedule, occurrence date, and occurrence start time.
3. WHEN Occurrence_Expansion runs repeatedly or concurrently, THE Scheduling_Domain SHALL remain idempotent and SHALL not create duplicate ClassSession records or duplicate accepted audit records for one occurrence.
4. IF an occurrence is unavailable because of a Blocking_Conflict or Hard_Constraint, THEN THE Scheduling_Domain SHALL leave the occurrence unpersisted, SHALL retain an explainable expansion result, and SHALL not create a synthetic Calendar event.
5. WHEN a user edits one recurring occurrence, THE Scheduling_Domain SHALL preserve the Recurring_Schedule association and SHALL distinguish an occurrence override from a Recurring_Schedule-wide change in the Session_Audit_Record.
6. WHEN a user requests a Recurring_Schedule-wide change, THE Scheduling_Domain SHALL present the affected occurrence set and SHALL require explicit scope confirmation before mutating more than one ClassSession; THE Scheduling_Domain SHALL prevent every schedule-wide mutation without explicit scope confirmation.
7. WHEN a Recurring_Schedule is inactive, THE Scheduling_Domain SHALL stop new Occurrence_Expansion while preserving existing persisted ClassSession records and their calendar projection; THE Scheduling_Domain SHALL prevent every deletion attempt for sessions from an inactive Recurring_Schedule until the schedule is reactivated or permanently removed.
8. WHEN a Recurring_Schedule is active, THE Scheduling_Domain SHALL prevent every deletion attempt for its existing ClassSession records and calendar projections regardless of source or workflow.
9. WHEN a recurring occurrence is created, THE Calendar_Surface SHALL discover the occurrence only after persistence through the existing Calendar_Surface feed, SHALL not ask the calendar to generate or synthesize the occurrence, and SHALL prevent all calendar synthesis after feed discovery.

### Requirement 14: Availability Caching Architecture

**User Story:** As an academy operator, I want availability checks to remain responsive without weakening correctness, so that frequent previews do not overload the database or serve unsafe decisions.

#### Acceptance Criteria

1. THE Availability_Cache SHALL key every cached Availability_Result by academy identity, proposal resources, date/time/duration, room, excluded session identity, effective Academy_Scheduling_Rules version, resource version, recurrence version, and authorization scope.
2. WHEN a cached Availability_Result is returned, THE Scheduling_Domain SHALL verify that the cache key versions and freshness policy remain valid before treating the result as reusable; IF verification fails, THEN THE Scheduling_Domain SHALL explicitly mark the cached result as non-reusable.
3. WHEN a ClassSession, room, teacher availability, student availability, Recurring_Schedule, Academy_Scheduling_Rules, or resource authorization changes, THE backend SHALL invalidate or version-bypass every affected Availability_Cache entry before a subsequent final mutation decision.
4. WHEN a final mutation is submitted, THE Scheduling_Domain SHALL re-evaluate the proposal against current persisted state and SHALL not treat a preview or cache hit as final authorization.
5. IF Availability_Cache storage is unavailable or a cached value is incomplete, THEN THE Scheduling_Domain SHALL compute the result from the authoritative backend data source or return a controlled unavailable result without accepting an unsafe mutation.
6. THE Availability_Cache SHALL use bounded retention, namespaced keys, stampede protection, and observability for hit, miss, invalidation, and stale-result counts without logging sensitive user data.
7. IF Availability_Cache observability accidentally logs sensitive user data, THEN the Scheduling_Domain SHALL continue operating, SHALL preserve cache and mutation correctness, and SHALL expose the violation to monitoring for remediation.
8. WHEN cache behavior is tested, THE implementation plan SHALL independently verify equivalent fresh and valid cached decisions, invalidation after each affected mutation category, stale-result rejection, and concurrent request safety.

### Requirement 15: Busy Seeding and Deterministic Test Data

**User Story:** As a developer and tester, I want representative busy schedules, so that conflicts, suggestions, and constraints can be validated without hand-built production records.

#### Acceptance Criteria

1. WHEN a permitted development, test, or demo Busy_Seed runs, THE Busy_Seed SHALL create persisted busy data through the Scheduling_Domain's canonical ClassSession and resource contracts.
2. WHEN a Busy_Seed runs twice with the same seed identity, THE Busy_Seed SHALL produce the same logical records without duplicate ClassSession records or duplicate recurring associations.
3. WHEN Busy_Seed creates occupied intervals, THE Busy_Seed SHALL support named fixture groups for working hours, lunch boundaries, daily limits, consecutive-session limits, teacher buffers, room requirements, cancelled sessions, completed historical sessions, and competing-resource cases, and SHALL permit a targeted seed run to cover any documented subset of those groups.
4. WHEN Busy_Seed creates records, THE Busy_Seed SHALL obey Academy_Scheduling_Rules and SHALL not create invalid overlaps unless a fixture explicitly represents a documented Soft_Conflict or historical case.
5. WHEN a Busy_Seed is requested against a production environment without an explicit approved safety switch, THE seed boundary SHALL refuse execution and SHALL leave production data unchanged.
6. WHEN the explicit approved safety switch is present in a production environment, THE seed boundary SHALL allow execution only when the safety switch guarantees authorization and audit controls are applied.
7. WHEN the calendar reads Busy_Seed records, THE Existing_Projection_Fix SHALL project only persisted records and SHALL perform no generation, synthetic fallback, or availability mutation.

### Requirement 16: Security, Authorization, and Validation

**User Story:** As an academy owner, I want scheduling actions protected at every boundary, so that no unauthorized actor can inspect or mutate schedule data.

#### Acceptance Criteria

1. WHEN an actor requests a scheduling page, API, preview, suggestion, audit history, settings change, recurrence expansion, or mutation, THE backend SHALL enforce authentication and the applicable named policy or gate before reading protected data or performing domain work.
2. WHEN an actor lacks permission for a teacher, student, room, ClassSession, academy, or audit record, THE backend SHALL return the established authorization response without disclosing protected record existence or scheduling details.
3. WHEN scheduling input is received, THE backend SHALL validate types, identifiers, date/time formats, timezone interpretation, duration bounds, room values, enum values, code values, and version tokens before invoking domain mutation; IF no scheduling input is received, THEN the validation boundary SHALL skip mutation validation or return an explicit missing-input failure.
4. WHEN a scheduling mutation contains a disallowed field, mass-assignment attempt, untrusted HTML, or unrecognized override value, THE backend SHALL reject the input and SHALL not persist the value.
5. THE Scheduling_Domain SHALL use parameterized ORM/query-builder operations, explicit fillable attributes, transactions for multi-record changes, and safe error responses without secrets or sensitive values in logs.
6. WHEN an audit, conflict, or availability error is logged, THE backend SHALL log stable non-sensitive identifiers and classification data only, consistent with the existing calendar relation-path logging boundary; IF required audit logging cannot capture the required information, THEN THE Scheduling_Domain SHALL fail the scheduling operation and SHALL preserve the prior persisted state.
7. IF a request is cross-site, missing CSRF protection where required, rate-limited, or otherwise invalid at the transport boundary, THEN the backend SHALL reject the request before scheduling-domain mutation.

### Requirement 17: Existing API, UI, and Projection Preservation

**User Story:** As a maintainer, I want existing consumers and calendar behavior to continue working, so that interactive scheduling is additive rather than a breaking rewrite.

#### Acceptance Criteria

1. THE Existing_Projection_Fix SHALL remain the authoritative behavior for persisted-session calendar projection, stable identifiers, inclusive date membership, empty ranges, relation-path failures, and first-boundary diagnosis.
2. WHEN the Calendar_Surface reads events after an interactive mutation, THE Calendar_Surface SHALL continue using the existing named calendar event endpoint and SHALL receive only persisted ClassSession projections.
3. THE Scheduling_Domain SHALL not call Occurrence_Expansion, create synthetic events, reconcile projection counts, replace stable identifiers, or add availability candidates to the existing calendar event feed.
4. WHEN existing session list, edit, notes, attendance, room, filter, drawer, FullCalendar, RTL, and responsive contracts exist, THE implementation SHALL preserve the current observable behavior at all times, including when no external consumer is currently active, while allowing internal implementation changes that do not alter those external contracts.
5. WHEN new preview, availability, suggestion, audit, or mutation endpoints are introduced, THE backend SHALL add named additive contracts, preserve existing response fields and status meanings, and document authentication, authorization, validation, and version behavior.
6. IF an existing API or UI contract must change to support interactive scheduling, THEN the Impact_Matrix SHALL mark the contract as change, THE Investigation_Record SHALL identify all consumers, and THE design SHALL define compatibility or migration behavior before implementation.
7. THE implementation SHALL not modify `.kiro/specs/calendar-persisted-session-projection/` or replace the completed projection bugfix with a new calendar architecture.

### Requirement 18: Modular Scheduling-Domain Boundaries

**User Story:** As a maintainer, I want scheduling concerns separated behind stable domain boundaries, so that future rules and resources can evolve without another rewrite.

#### Acceptance Criteria

1. THE Scheduling_Domain SHALL separate proposal normalization, availability evaluation, conflict classification, rule evaluation, room resolution, suggestion ranking, recurrence expansion, mutation, audit recording, and cache coordination behind independently testable boundaries.
2. THE Scheduling_Domain SHALL expose transport-neutral domain contracts for Schedule_Proposal and Availability_Result so that form, calendar, recurrence, seed, and future clients use one decision owner.
3. THE Calendar_Surface SHALL depend on Scheduling_Proposal_API responses and SHALL not import model, query, conflict, recurrence, or rule implementations into Blade or JavaScript.
4. THE Session_Edit_Surface SHALL use server-provided DTOs or resources for options, values, policy flags, effective rules, room data, and Session_Version and SHALL not perform database queries in Blade.
5. WHEN a new scheduling resource or rule is added, THE Implementation_Plan SHALL identify the owning boundary, contract tests, cache-key impact, audit impact, authorization impact, and backward-compatibility impact before production implementation.
6. IF two proposed modules can independently decide the same conflict, availability, room, or rule outcome, THEN the architecture review SHALL reject the proposal and SHALL designate one canonical Scheduling_Domain owner before the proposal can proceed.
7. THE Scheduling_Domain SHALL use the existing modular-monolith architecture and SHALL not add a plugin-like dependency, heavy calendar replacement, or duplicate persistence abstraction without approved architecture evidence.

### Requirement 19: Localization, RTL, Accessibility, and Responsive Interaction

**User Story:** As a Persian-speaking administrator using different devices and input methods, I want interactive scheduling to remain understandable and operable, so that accessibility does not depend on pointer interaction or English-only messages.

#### Acceptance Criteria

1. WHEN a scheduling page or response is rendered for the Persian admin locale, THE Session_Edit_Surface and Calendar_Surface SHALL display localized labels, validation messages, conflict explanations, statuses, action results, and empty states without raw translation keys.
2. THE Calendar_Surface SHALL preserve RTL direction, Jalali date presentation, 24-hour time presentation, existing calendar locale behavior, and approved Western-digit conventions for machine-readable date/time values.
3. THE Calendar_Surface SHALL always provide an equivalent keyboard-operable edit path for drag/drop and resize, with visible focus, semantic labels, and backend-equivalent results, regardless of pointer or assistive-technology availability.
4. THE Calendar_Surface SHALL expose pending, available, conflict, invalid, stale, and rejected states to assistive technology through localized status messaging and SHALL provide visual status indicators beyond color alone, such as text, icons, or patterns.
5. WHEN the viewport is between 390px and 1920px, THE Calendar_Surface SHALL preserve the existing responsive layout and avoid horizontal overflow.
6. WHEN a touch pointer is used, THE Calendar_Surface SHALL maintain touch targets of at least 44 by 44 CSS pixels for interactive controls.
7. WHEN reduced motion is requested, THE Calendar_Surface SHALL suppress nonessential drag-preview and transition animation while preserving state and operation feedback.
8. WHEN a scheduling dialog or conflict review surface opens, THE Calendar_Surface SHALL provide dialog semantics, focus containment, Escape handling, focus restoration, and keyboard access consistent with the existing accessibility rules.

### Requirement 20: Performance and Operational Observability

**User Story:** As an academy operator, I want previews and schedule changes to remain responsive and diagnosable, so that caching and concurrency do not hide failures.

#### Acceptance Criteria

1. WHEN a typical availability or preview request is evaluated under normal configured load, THE Scheduling_Proposal_API SHALL meet the performance target established by the approved architecture baseline and SHALL expose measured evidence in the implementation verification record.
2. WHEN a calendar event mutation is submitted, THE Calendar_Surface SHALL show a pending state within the interaction response budget established by the approved UI baseline and SHALL not block unrelated page controls.
3. THE Scheduling_Domain SHALL instrument request outcome, conflict category, stale-version rejection, force-override use, cache hit/miss, suggestion count, mutation duration, and audit-write failure without logging sensitive payloads.
4. IF a scheduling dependency times out, fails, or returns an invalid contract, THEN THE backend SHALL fail closed for mutation, return a stable localized error, and preserve the last authoritative ClassSession state.
5. WHEN operational evidence is collected, THE Implementation_Plan SHALL include representative availability, conflict, suggestion, mutation, concurrency, cache, recurrence, and error measurements without requiring a development server or watcher.

### Requirement 21: Independently Testable Delivery and Verification

**User Story:** As a project owner, I want every scheduling behavior verified at its owning boundary, so that implementation can be accepted without relying on visual inspection alone.

#### Acceptance Criteria

1. WHEN the implementation plan is executed, THE Verification_Suite SHALL independently test proposal validation, conflict policy, force overrides, room requirements, Teacher_Buffer, working hours, daily limits, consecutive limits, lunch intervals, recurrence idempotency, business-code immutability, concurrency, audit immutability, cache invalidation, and projection preservation; THE Implementation_Plan SHALL not complete until every specified verification test passes.
2. WHEN a domain property varies across generated in-memory inputs, THE Verification_Suite SHALL use property-based tests for deterministic Scheduling_Domain logic and SHALL use representative integration tests for database, authorization, cache, scheduler, and external infrastructure behavior.
3. WHEN a parser, serializer, DTO, resource, or API payload is introduced, THE Verification_Suite SHALL verify valid input, invalid input, stable error shape, and parse/serialize round-trip equivalence where a reversible representation exists.
4. WHEN a calendar drag, resize, form edit, preview, suggestion selection, stale update, force override, recurring expansion, and busy seed scenario is exercised, THE Verification_Suite SHALL verify both the backend outcome and the UI rollback or authoritative-refresh behavior.
5. WHEN an Existing_Projection_Fix regression case is exercised, THE Verification_Suite SHALL verify persisted-session count and stable identifiers through query, resource, endpoint, normalization, and rendering boundaries without downstream compensation.
6. IF any verification identifies a first failing ownership boundary, THEN THE Verification_Suite SHALL report the first boundary, fixture or generated input, expected result, observed result, and responsible owner without masking the failure with a later-stage adjustment.
7. WHEN all implementation tasks are complete, THE delivery record SHALL include focused PHP tests, JavaScript tests, browser tests where configured, static architecture checks, `npm run build`, and applicable Laravel cache/config validation, while leaving application code unchanged during this requirements phase.
8. THE Verification_Suite SHALL include authorization-denial, CSRF, malformed-input, stale-version, hard-constraint, cache-failure, audit-failure, and production-seeding safety cases.

## Non-Functional and Scope Notes

- Production code SHALL not be written in the requirements phase.
- The subsequent design phase SHALL resolve any values not supplied by the request, including academy defaults, suggestion search window, cache freshness, performance budgets, exact business-code format, and exact endpoint payloads, and SHALL record those decisions before tasks are created.
- The subsequent tasks phase SHALL implement one canonical Scheduling_Domain and SHALL not place scheduling decisions in Blade, JavaScript, controllers, or duplicated calendar services.
- Existing route names, existing API consumers, FullCalendar configuration, RTL behavior, drawer behavior, room resolution, relation-path protection, and session note concurrency remain compatibility constraints unless an approved Impact_Matrix entry proves a controlled migration is required.
- No migration, seed, application, test, design, task, or existing specification file other than this feature's required config and requirements document is modified by this requirements phase.
