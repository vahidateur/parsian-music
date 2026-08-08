# Requirements Document

## Introduction

This specification replaces the previous closed calendar-only plan for the academy scheduling area with one additive feature specification: `academy-scheduling-and-profiles`. The feature extends the existing Laravel modular-monolith boundaries for rooms, scheduling, recurring sessions, calendar projection/editing, student and teacher profiles, identity validation, media, timeline/activity, audit history, seed data, and operational documentation.

The approved architecture and business decisions are authoritative. The implementation SHALL preserve existing routes, named API contracts, Blade contracts, policies, DTOs, services, resource ownership, and the persisted-session calendar lifecycle. The feature SHALL not introduce a parallel calendar, scheduler, availability engine, conflict engine, profile system, or duplicate business logic.

The requirements phase creates this document and retains the existing requirements-first `.config.kiro` metadata only. It SHALL not modify production application code, migrations, seeders, tests, routes, configuration, or existing specifications, and it SHALL not create implementation agents, design documents, or task documents.

## Scope and Supersession

The former calendar-only task plan remains historical reference material; this document is the active replacement specification for the complete approved enhancement. The existing persisted-session projection behavior remains a compatibility constraint, not a feature to reopen or replace.

In scope are dynamic rooms, preferred-room resolution, authoritative availability and conflict detection, scheduling and recurring-session improvements, calendar editing through existing mutation owners, student and teacher identity profiles, Iranian identity validation, profile media, timeline, System Activity, audit history, business-code generation, database/performance hardening, realistic seed data, transactions and concurrency protection, and continuous documentation maintenance.

The implementation SHALL use the existing application architecture and SHALL add compatibility layers only where an existing route, payload, Blade contract, policy, DTO, service, or legacy/imported record requires one. The subsequent design phase SHALL resolve implementation details that this document intentionally leaves to the approved architecture, such as exact class names, migration sequence, endpoint payload extensions, lock-key encoding, and configuration key names.

## Glossary

- **Academy**: The Parsian Music Academy scheduling and profile owner.
- **ClassSession**: One persisted lesson occurrence in `class_sessions`; the authoritative source for calendar events.
- **RecurringSchedule**: A persisted schedule template that describes eligible recurring occurrences.
- **Scheduling_Domain**: The single existing or extended business boundary that normalizes proposals, evaluates availability/conflicts, resolves rooms, generates recurring occurrences, and owns scheduling mutations.
- **Availability_Result**: A backend decision containing exactly one state: `AVAILABLE`, `CONFLICT`, or `INVALID`, with explainable resource and rule details.
- **Blocking_Interval**: A persisted or configured interval that prevents a proposed session from being accepted.
- **Manual_Admin_Block**: An explicitly persisted or configured block created by an authorized administrator; it is a valid scheduling blocker and not an artificial gap.
- **Preferred_Room**: The room selected first for an instrument according to the approved instrument mapping.
- **Available_Room**: An active, academy-owned room that satisfies the session requirement and is free for the proposed interval.
- **Profile_Record**: A Student or Teacher record and its approved identity, operational, and profile attributes.
- **New_Profile_Record**: A Student or Teacher created after this feature's identity rules are enabled.
- **Imported_Record**: A pre-existing or externally imported Student or Teacher whose legacy data may not satisfy all new identity fields.
- **Iranian_National_ID**: A normalized ten-digit Iranian national identifier validated by the approved checksum algorithm.
- **Business_Code**: A server-generated, non-secret, immutable operational identifier for a Student or Teacher.
- **Profile_Photo**: A managed Student or Teacher portrait stored through the configured Laravel Storage disk with approved derivatives.
- **Timeline**: A chronological, profile-focused view of meaningful Student or Teacher lifecycle events.
- **System_Activity**: A chronological operational feed of relevant system and administrator actions, separate from the profile Timeline.
- **Audit_Record**: An immutable, access-controlled record of an accepted session or profile mutation, including actor, action, before/after values, and timestamp.
- **Compatibility_Layer**: An explicit adapter, nullable transition, legacy serializer, or route-preserving mechanism required to keep an existing contract or record usable.
- **Canonical_Email**: An email value normalized according to the approved application/database comparison rules before cross-model uniqueness validation.
- **Effective_Availability**: The availability result after resource occupancy, configured rules, teacher/student/room availability, and Manual_Admin_Block evaluation.

## Requirements

### Requirement 1: Preserve architecture and existing contracts

**User Story:** As a project owner, I want the enhancement to extend existing ownership boundaries, so that current academy behavior remains stable while scheduling and profiles become more capable.

#### Acceptance Criteria

1. THE Scheduling_Domain SHALL remain the single owner of availability, room resolution, conflict classification, recurring generation, and scheduling mutation decisions, while it may consume availability facts from an approved external authoritative source.
2. THE Calendar_Surface SHALL always remain a projection and editing surface over persisted ClassSession records, regardless of the surface role or interaction, and SHALL never generate or synthesize events.
3. THE feature SHALL preserve existing named routes, public API contracts, Blade props/slots, policies, DTOs, services, resources, and ownership boundaries when the Calendar_Surface is configured as the canonical persisted-session projection, or SHALL provide a documented Compatibility_Layer for any change that cannot preserve those contracts.
4. IF a proposed change would create a second calendar, scheduler, conflict engine, profile system, or business-rule implementation, THEN the architecture review SHALL reject the proposal regardless of whether an existing owner has yet been identified and SHALL direct the proposal to the existing ownership boundary.
5. WHEN a Compatibility_Layer is required, THE Compatibility_Layer SHALL document the legacy input/output, the translated canonical contract, the migration condition, and the removal or retention decision, and implementation SHALL be blocked until that documentation is complete.

### Requirement 2: Dynamic rooms and initial catalog

**User Story:** As an academy administrator, I want rooms to be persisted and managed dynamically, so that scheduling is not tied to hardcoded room strings.

#### Acceptance Criteria

1. WHEN the initial room seed runs in an empty or approved development/demo environment, THE Room_Catalog SHALL contain exactly three active rooms with identifiers `A101`, `A102`, and `A103`, and SHALL contain no other initially seeded room identity.
2. WHEN the initial room seed runs, THE Room_Catalog SHALL not create `Room104`, `A104`, or any equivalent fourth room.
3. THE Room_Catalog SHALL persist room identity, display label, active state, academy ownership, and capability data required by room resolution.
4. WHEN an authorized administrator creates, updates, deactivates, or reactivates a room, THE Room_Catalog SHALL validate the room contract and SHALL preserve the room identifier used by existing sessions.
5. IF a room identifier conflicts with an existing active or historical room identifier, THEN THE Room_Catalog SHALL reject the mutation with a field-specific error and SHALL preserve existing room references.
6. WHEN a room is inactive, THE Scheduling_Domain SHALL exclude the room from new Available_Room choices while retaining historical session display data that references the room.

### Requirement 3: Preferred-room resolution and fallback

**User Story:** As an academy scheduler, I want an instrument's preferred room to be selected when possible and a valid fallback when necessary, so that room preference improves planning without making preference mandatory.

#### Acceptance Criteria

1. WHEN a session instrument is Violin, THE Scheduling_Domain SHALL evaluate `A101` as the Preferred_Room.
2. WHEN a session instrument is Voice, THE Scheduling_Domain SHALL evaluate `A102` as the Preferred_Room.
3. WHEN a session instrument is Piano, THE Scheduling_Domain SHALL evaluate `A103` as the Preferred_Room.
4. WHEN a session instrument is Classical Guitar or Pop Guitar, THE Scheduling_Domain SHALL evaluate `A102` as the Preferred_Room.
5. WHEN a Preferred_Room is active, compatible, academy-owned, and available for the interval, THE Scheduling_Domain SHALL select the Preferred_Room.
6. IF a Preferred_Room is missing, inactive, incompatible, unauthorized, or occupied, THEN THE Scheduling_Domain SHALL evaluate active compatible rooms in the approved deterministic room-option order, SHALL allow the Preferred_Room to be selected if it appears as a valid fallback candidate, and SHALL select the first Available_Room when one exists; when fallback evaluation finds none, THE Scheduling_Domain SHALL apply the no-available-room outcome below.
7. IF fallback evaluation finds no Available_Room, THEN THE Scheduling_Domain SHALL return a conflict or invalid result, SHALL prevent session persistence, and SHALL never create a synthetic calendar event.
8. WHEN an instrument has no preferred mapping, THE Scheduling_Domain SHALL treat preference as absent and SHALL evaluate any available compatible room without rejecting the proposal solely because no preference exists.
9. THE Scheduling_Domain SHALL treat room preference as non-mandatory and SHALL never reject an otherwise valid session solely for a preference issue, including when an instrument has an approved Preferred_Room mapping.

### Requirement 4: Availability and continuous scheduling

**User Story:** As an academy administrator, I want availability to reflect actual resources and explicit policy blocks, so that the academy can schedule continuous lessons without artificial gaps.

#### Acceptance Criteria

1. WHEN the Scheduling_Domain evaluates a proposal, THE Availability_Result SHALL account for teacher, student, enrollment, room, academy-hours, configured availability, recurring occurrence, and Manual_Admin_Block constraints applicable to the proposal.
2. WHEN two intervals overlap physically, THE Scheduling_Domain SHALL classify the shared teacher, student, enrollment, or room resource as conflicting unless an explicitly approved override policy applies.
3. WHEN one interval ends exactly when another begins, THE Scheduling_Domain SHALL classify the intervals as non-overlapping unless a configured buffer or explicit block creates an overlap.
4. WHEN a ClassSession is cancelled, THE Scheduling_Domain SHALL treat the cancelled interval as non-blocking immediately for future availability evaluation, including a same-day rescheduling proposal.
5. WHEN any historical ClassSession overlaps a proposal, THE Scheduling_Domain SHALL treat the historical interval as blocking regardless of completion, attendance, or in-progress status, unless an approved business rule explicitly makes that historical class non-blocking.
6. THE Scheduling_Domain SHALL not insert artificial gaps between otherwise contiguous available sessions.
7. WHEN a teacher, student, room, or Manual_Admin_Block prevents a contiguous slot, THE Scheduling_Domain SHALL report that blocker and SHALL not describe the resulting gap as an artificial scheduling rule.
8. WHEN no teacher, student, enrollment, room, availability, Manual_Admin_Block, buffer, academy-hours, duration, authorization, or other explicitly configured hard constraint prevents the interval, THE Scheduling_Domain SHALL permit adjacent sessions that satisfy the configured rules; every condition that prevents adjacency SHALL be explicitly reported.
9. WHEN the same proposal is evaluated repeatedly against unchanged persisted state and rule versions, THE Scheduling_Domain SHALL return strictly equivalent decision state, blocker identities, resolved-room outcome, and evaluation result; any difference in those decision values SHALL violate the requirement.

### Requirement 5: Scheduling and conflict-detection improvements

**User Story:** As an authorized scheduler, I want create and edit decisions to be authoritative and explainable, so that conflicts are detected before persistence and cannot be bypassed accidentally.

#### Acceptance Criteria

1. WHEN an authorized actor submits a valid session proposal, THE Scheduling_Domain SHALL return exactly one Availability_Result state: `AVAILABLE`, `CONFLICT`, or `INVALID`.
2. WHEN the result is `CONFLICT`, THE Scheduling_Domain SHALL identify every applicable resource category among teacher, student, enrollment, room, configured availability, recurring occurrence, and Manual_Admin_Block.
3. WHEN the result is `CONFLICT`, THE Scheduling_Domain SHALL include each known blocking ClassSession identifier and academy-timezone interval without exposing unauthorized details.
4. IF a proposal violates an immutable or impossible rule, or any required scheduling condition fails, THEN THE Scheduling_Domain SHALL return `INVALID` or `CONFLICT` with a stable machine-readable reason, SHALL not offer the proposal as available, and SHALL prevent session persistence.
5. WHEN a proposal is available, THE Scheduling_Domain SHALL still repeat authoritative conflict and integrity checks during final persistence so that a concurrent write cannot make an accepted result invalid.
6. WHEN an authorized administrator requests alternatives for a conflicting proposal, THE Scheduling_Domain SHALL evaluate every returned alternative with the same rules used for final persistence and SHALL not persist an alternative merely by calculating it.
7. WHEN an administrator explicitly blocks a period, THE Scheduling_Domain SHALL persist the block through the approved owner and SHALL include it in future availability results.

### Requirement 6: Calendar projection and approved editing

**User Story:** As an academy administrator, I want to edit sessions from the existing calendar without creating a second scheduling system, so that calendar changes remain consistent with persisted data and existing UI contracts.

#### Acceptance Criteria

1. THE Calendar_Surface SHALL read events only from persisted ClassSession records through the existing calendar query, resource, endpoint, normalization, and rendering lifecycle.
2. WHEN a calendar request has no matching persisted ClassSession records, THE Calendar_Surface SHALL return zero events, perform zero scheduling writes, and SHALL prohibit event creation or synthesis at every internal and response stage.
3. WHEN a persisted ClassSession matches an inclusive requested range and approved filters, THE Calendar_Surface SHALL project that record exactly once with its stable persisted identifier.
4. IF any matching persisted ClassSession fails during calendar projection, THEN THE Calendar_Surface SHALL fail the entire calendar request with the existing compatible error contract and SHALL not return a partial event collection.
5. WHEN an authorized administrator edits, drags, or resizes a calendar session, THE Calendar_Surface SHALL submit the change to the existing session mutation owner with the session identifier, proposed values, and current concurrency token.
6. WHEN a calendar mutation succeeds, THE Calendar_Surface SHALL render the authoritative persisted response and SHALL not infer server-owned room, status, relation, or conflict values in browser code.
7. IF a calendar mutation fails authorization, validation, conflict detection, or concurrency checks, THEN THE Calendar_Surface SHALL restore or retain the last authoritative event state and SHALL display a localized reason without changing persisted data.
8. THE feature SHALL preserve existing calendar filters, FullCalendar architecture, RTL behavior, drawer behavior, named endpoints, resource contracts, and projection identity unless a documented Compatibility_Layer is required.
9. THE Calendar_Surface SHALL provide an equivalent keyboard-operable path for every drag, resize, and edit action that uses the same backend proposal and decision contract.

### Requirement 7: Recurring-session generation and retry safety

**User Story:** As an academy scheduler, I want recurring schedules to generate complete and traceable occurrences, so that calendar data is available without duplicate sessions after retries or concurrent runs.

#### Acceptance Criteria

1. WHEN an active RecurringSchedule has an eligible occurrence within the configured generation horizon, THE existing recurring-generation owner SHALL persist the occurrence as a ClassSession before the Calendar_Surface reads it.
2. WHEN a RecurringSchedule is inactive, THE recurring-generation owner SHALL not create new ClassSession occurrences for that schedule.
3. WHEN recurring generation is retried after a failure, THE recurring-generation owner SHALL resume or reconcile eligible occurrences without duplicating already persisted occurrences.
4. WHEN recurring generation runs repeatedly or concurrently for the same schedule and occurrence, THE persistence boundary SHALL allow exactly one ClassSession for the schedule, enrollment, date, and start time.
5. WHEN recurring generation resolves a room, THE recurring-generation owner SHALL use the same Preferred_Room and Available_Room fallback rules as manual create and edit flows.
6. IF no compatible room or required resource is available for an occurrence, THEN THE recurring-generation owner SHALL leave that occurrence unpersisted, record a controlled reason, and SHALL never create a synthetic calendar event to represent the skipped occurrence.
7. WHEN an occurrence is persisted successfully, THE recurring-generation owner SHALL retain its RecurringSchedule association and SHALL expose enough source identity for audit and history.
8. WHEN the generation process fails after some occurrences commit, THE retry contract SHALL preserve committed valid occurrences and SHALL make the remaining eligible occurrence set idempotently retryable.

### Requirement 8: Student and teacher identity profiles

**User Story:** As an academy administrator, I want complete Student and Teacher profiles, so that scheduling and operations use one reliable identity record.

#### Acceptance Criteria

1. WHEN an authorized administrator creates or updates a Profile_Record, THE existing Form Request, Action/Service, model, DTO/resource, and Policy ownership boundaries SHALL validate and persist only approved profile fields, and SHALL persist the profile only after validation passes.
2. THE Student_Profile SHALL support the approved identity and operational fields, including full name, canonical phone, optional guardian information, email, Iranian_National_ID, photo state, status, notes, join date, instruments, level, enrollments, teachers, subscriptions, attendance context, Timeline, and System_Activity where applicable.
3. THE Teacher_Profile SHALL support the approved identity and operational fields, including full name, canonical phone, email, Iranian_National_ID, photo state, status, biography, hire/employment data, specialties/instruments, work schedule, students, Timeline, and System_Activity where applicable.
4. WHEN a profile detail surface renders relations or operational summaries, THE profile contract SHALL provide resolved display data and SHALL not require a database query from Blade.
5. THE Student_Profile and Teacher_Profile SHALL use shared identity/detail presentation primitives where the existing component architecture permits, without merging unrelated business rules into a view.
6. IF a non-authorized actor submits a profile mutation, THEN THE existing Policy/Gate boundary SHALL deny the request, SHALL preserve the Profile_Record, and SHALL log the unauthorized attempt through the approved non-sensitive security-monitoring boundary.
7. WHEN any Profile_Record lacks an optional attribute, THE profile surface SHALL render the approved absent-value state without changing unrelated identity, enrollment, user-avatar, or operational data; THE profile surface SHALL protect unrelated identity and operational data regardless of whether optional attributes are present or absent.

### Requirement 9: Email, phone, and Iranian national-ID rules

**User Story:** As an academy administrator, I want identity values normalized and unique, so that duplicate or invalid profile identities cannot enter scheduling data.

#### Acceptance Criteria

1. WHEN a Student, Teacher, or another supported record type whose contract accepts an email supplies an email value, THE identity boundary SHALL normalize it to Canonical_Email and validate its format before persistence.
2. IF a supplied Canonical_Email already belongs to any Student or Teacher other than the record being updated, THEN THE identity boundary SHALL reject the mutation with a stable field-specific error and SHALL preserve both existing records; when no email is supplied in the current operation, THE identity boundary SHALL not reject the operation solely because of an existing duplicate legacy email.
3. WHEN a phone value is supplied, THE identity boundary SHALL canonicalize equivalent Iranian representations before validation, comparison, and persistence.
4. WHEN any Profile_Record supplies an Iranian_National_ID, THE identity boundary SHALL normalize it and SHALL validate its ten-digit format and approved Iranian checksum before duplicate checking; WHEN a New_Profile_Record is created while the general `national_id_required` setting is enabled, THE identity boundary SHALL require the value and SHALL reject a missing or invalid value immediately during creation.
5. IF an Iranian_National_ID has an invalid format, invalid checksum, or duplicate ownership, THEN THE identity boundary SHALL reject the mutation before persistence and SHALL return a categorized field-specific localized error matching the failure reason.
6. WHEN an existing or Imported_Record requires compatibility handling, THE migration/compatibility boundary SHALL permit a nullable or explicitly flagged national-ID state only for legacy/imported records that cannot yet satisfy the new rule; any non-null Iranian_National_ID on a legacy record SHALL still undergo normal format, checksum, and uniqueness validation.
7. IF the general `national_id_required` setting is enabled for New_Profile_Record creation, THEN THE compatibility boundary SHALL require the new profile's Iranian_National_ID and SHALL not allow a newly created profile to bypass that setting merely because a legacy record remains nullable.
8. WHEN a legacy national-ID value is normalized or backfilled, THE identity boundary SHALL preserve the record primary key and all existing relationships and SHALL report a duplicate only when normalization produces an actual duplicate; a unique normalized value SHALL not be reported as a duplicate.
9. THE database/application uniqueness boundary SHALL prevent two active Student or Teacher records from owning the same non-null normalized Iranian_National_ID.

### Requirement 10: Profile photos and managed storage

**User Story:** As an academy administrator, I want reliable profile portraits, so that Student and Teacher detail surfaces can display optimized images without orphaned files or hardcoded storage assumptions.

#### Acceptance Criteria

1. WHEN an authorized administrator uploads a profile photo, THE Profile_Media boundary SHALL validate the file type, size, dimensions, and ownership before storing it.
2. THE Profile_Media boundary SHALL store profile photos through a configurable Laravel Storage disk and SHALL not hardcode a public filesystem path or disk name in a Blade contract.
3. WHEN a valid profile photo has been stored successfully, THE Profile_Media boundary SHALL use the approved Intervention Image GD driver to create and retain the configured original, medium, and thumbnail representations.
4. WHEN a profile photo is replaced, THE Profile_Media boundary SHALL safely store the replacement before removing only the superseded managed derivatives and SHALL leave unrelated user-avatar and public-storage assets unchanged.
5. WHEN a profile has no managed photo, THE profile contract SHALL always provide the configured default-avatar representation with stable dimensions and descriptive alternative text, regardless of prior photo-validation state.
6. IF image validation or GD processing fails, THEN THE Profile_Media boundary SHALL return a controlled localized error, SHALL not expose filesystem details, and SHALL preserve the previous valid photo state.
7. WHEN a photo URL is supplied to a view or API consumer, THE Profile_Media boundary SHALL expose a storage-resolved URL or approved DTO field and SHALL not expose raw server paths.
8. THE profile image contract SHALL include intrinsic dimensions or a fixed-dimension placeholder to prevent layout shift.

### Requirement 11: Timeline and System Activity

**User Story:** As an academy operator, I want profile history and operational activity separated and understandable, so that I can review a person's lifecycle without confusing it with system actions.

#### Acceptance Criteria

1. THE Timeline boundary SHALL collect approved Student and Teacher lifecycle events from authoritative persisted sources and SHALL return deterministic newest-first ordering with a stable tie-breaker.
2. THE Timeline boundary SHALL provide event type, timestamp, localized description, and context metadata without exposing unauthorized personal or system data.
3. WHEN no Timeline event exists, THE profile surface SHALL render the approved localized empty state and SHALL not fabricate an event; IF the approved empty state cannot render, THEN THE profile surface SHALL render a secondary accessible text fallback.
4. THE System_Activity boundary SHALL represent relevant administrator and system actions separately from Timeline events, including actor, action, subject, timestamp, and localized summary when authorized.
5. WHEN a profile or session action is displayed in Timeline or System Activity, THE presentation SHALL preserve exactly these four required properties—Persian localization, RTL order, accessible status text, and source record identity; IF any one property cannot be met, THEN THE profile surface SHALL hide the entire affected profile action.
6. THE history boundaries SHALL eager-load required relations or use bounded batch queries and SHALL not issue one query per rendered event.
7. IF a history source fails, THEN THE profile surface SHALL return the approved safe error state rather than claiming that no events exist, SHALL log a non-sensitive diagnostic through the existing logging boundary, and SHALL not expose raw exception details.

### Requirement 12: Session and profile audit history

**User Story:** As an academy owner, I want accepted mutations to be immutable and reviewable, so that schedule and identity changes remain accountable.

#### Acceptance Criteria

1. WHEN any ClassSession is successfully created, updated, or deleted through any approved or compatibility mutation path, THE Audit boundary SHALL append exactly one immutable session Audit_Record for the accepted state change.
2. WHEN any Student or Teacher Profile_Record is successfully created, updated, or deleted through any approved or compatibility mutation path, THE Audit boundary SHALL append exactly one immutable profile Audit_Record for the accepted state change.
3. WHEN a recurring occurrence is generated or an approved calendar edit succeeds, THE Audit boundary SHALL record the source, actor/system identity, subject identifier, action, timestamp, and resulting persisted state.
4. WHEN an Audit_Record is appended, THE record SHALL contain before values, after values, changed fields, actor or system source, subject identifier, action type, timestamp, and schema/version metadata appropriate to the mutation.
5. IF a validation, authorization, conflict, stale-version, preview, or failed transaction does not change persisted state, THEN THE Audit boundary SHALL not create an accepted-mutation Audit_Record.
6. THE Audit_Record SHALL be immutable after creation and SHALL preserve historical before/after values independently of later subject changes.
7. IF an audit write or any other audit-related error occurs during an otherwise valid mutation, THEN THE owning transaction SHALL roll back the subject mutation and SHALL return a controlled failure; IF rollback cannot be completed, THEN THE entire operation SHALL fail closed and SHALL not report success.
8. WHEN an authorized actor requests history, THE Audit boundary SHALL return deterministic ordering and localized labels while redacting fields outside the actor's authorization.
9. IF an actor lacks audit-history authorization, THEN THE Audit boundary SHALL deny access, SHALL return no history data, and SHALL expose no before values, after values, actor details, or conflict details.

### Requirement 13: Business-code generation and immutability

**User Story:** As an academy operator, I want stable business codes for Students and Teachers, so that operational references remain reliable across imports and scheduling.

#### Acceptance Criteria

1. WHEN a Student or Teacher is created through an approved path, THE Business_Code_Generator SHALL assign one non-empty unique Business_Code before the record becomes available to scheduling.
2. WHEN an approved backfill encounters a Student or Teacher without a Business_Code, THE Business_Code_Generator SHALL assign exactly one code without changing the primary key or relationships.
3. WHEN a Profile_Record is updated, THE Business_Code_Generator SHALL preserve the existing Business_Code byte-for-byte.
4. IF a user-editable request attempts to set, replace, or clear a Business_Code, THEN THE identity boundary SHALL explicitly reject the request with a field-specific error and SHALL preserve the persisted code, including when the record currently has no code.
5. WHEN concurrent creation or backfill requests allocate codes, THE Business_Code_Generator SHALL use transactional coordination and database enforcement to prevent duplicates and SHALL return a controlled retryable failure when allocation cannot complete.
6. THE Business_Code SHALL be treated as a non-secret identifier and SHALL be visible only through the existing authorization rules for the corresponding profile.
7. WHEN a legacy code collision exists, THE approved typed repair path SHALL report the collision and SHALL change a code only with explicit repair approval, immutable audit evidence, and preserved subject relationships.

### Requirement 14: Database constraints, indexes, and query performance

**User Story:** As a system maintainer, I want database guarantees and efficient reads, so that correctness does not depend only on application code and profile/calendar surfaces remain responsive.

#### Acceptance Criteria

1. THE database schema SHALL enforce uniqueness and referential constraints for room identity, normalized profile identity values, business codes, and recurring-occurrence identity required by the approved model.
2. THE database schema SHALL provide indexes for frequently filtered or joined scheduling fields, including session date/time, teacher, student, enrollment, room, recurring schedule, profile email, national ID, business code, and audit subject/time access paths as applicable.
3. WHEN a constraint or index cannot be applied immediately because of existing data, THE migration/compatibility plan SHALL detect and report the blocking data before enabling the constraint, SHALL halt the migration if unrelated existing records would be affected, and SHALL not silently discard or invalidate records.
4. WHEN blocking data is resolved, THE migration/compatibility plan SHALL enable the constraint automatically or SHALL require explicit manual confirmation before enabling it; THE migration SHALL complete a successful blocking-data detection step before any enablement attempt.
5. THE Scheduling_Domain, profile detail contracts, Timeline, System Activity, and calendar feed SHALL eager-load or batch-load required relations, SHALL not produce an N+1 query pattern for a bounded result set, and SHALL use explicit error handling rather than silently discarding records when eager-loading fails.
6. WHEN a performance-sensitive read is executed, THE owning service SHALL select only the fields and relations required by the response contract and SHALL preserve stable ordering.
7. THE feature SHALL continuously meet the approved performance budget recorded by the design for configured representative workloads; WHEN a representative workload is configured, THE feature SHALL provide query-count or measurement evidence without changing functional results.
8. THE implementation SHALL use Eloquent/query-builder parameterization and SHALL not introduce raw SQL concatenation for any scheduling, identity, audit, or profile query.

### Requirement 15: Transactions and PostgreSQL concurrency protection

**User Story:** As an academy operator, I want concurrent changes to remain correct, so that two administrators or retrying jobs cannot create conflicting sessions, duplicate identities, or incomplete profile media.

#### Acceptance Criteria

1. WHEN a session create, update, delete, recurring occurrence generation, room assignment, or profile mutation changes multiple related records, THE owning Action/Service SHALL execute the complete logical mutation and its required audit write within one database transaction.
2. IF a validation, conflict, constraint, media, audit, or downstream persistence step fails inside the transaction, THEN THE transaction SHALL roll back all related writes and SHALL preserve the last committed state.
3. WHEN PostgreSQL evaluates any session proposal or recurring occurrence, THE Scheduling_Domain SHALL always acquire appropriate row locks and/or advisory locks for the evaluation, including affected ClassSession, RecurringSchedule, teacher, student/enrollment, and room resource keys, before the final conflict decision is committed.
4. WHEN PostgreSQL evaluates profile identity or Business_Code allocation concurrently, THE identity boundary SHALL use row/advisory locking and database uniqueness enforcement appropriate to the affected identity keys.
5. WHEN the current deployment adapter differs from PostgreSQL, THE Compatibility_Layer SHALL provide an equivalent atomic lock and uniqueness strategy and SHALL document the difference rather than silently weakening concurrency protection.
6. WHEN two concurrent operations target the same session or overlapping teacher, student, enrollment, or room resources, THE Scheduling_Domain SHALL commit at most the set of results permitted by the final conflict policy and SHALL return a controlled stale/conflict outcome for rejected work.
7. WHEN an operation is retried after a transient deadlock or serialization failure, THE retry boundary SHALL use bounded retry behavior and SHALL preserve idempotency without duplicating sessions, profiles, media references, codes, or audit records.

### Requirement 16: Realistic seeder and fixture behavior

**User Story:** As a developer and academy operator, I want seeded data to resemble real academy operations, so that scheduling, profiles, conflicts, and history can be validated meaningfully.

#### Acceptance Criteria

1. WHEN the approved demo/development seeder creates operating-day sessions, THE seeder SHALL create sessions from Saturday through Thursday and SHALL create no regular operating-day sessions on Friday.
2. WHEN the seeder creates normal lessons, THE seeder SHALL use the approved 30-minute default distribution and SHALL represent approved longer-duration exceptions only when the seeded lesson type requires them.
3. WHEN the seeder creates sessions within the operating window, THE seeder SHALL avoid artificial idle gaps and SHALL leave a gap only for exactly one of these reasons: teacher availability, student availability, room availability, or an explicit Manual_Admin_Block.
4. WHEN the seeder creates Student or Teacher profiles, THE seeder SHALL use realistic, normalized, unique, valid Iranian phone numbers, valid unique Iranian national IDs for new records, globally unique emails, photos/default-avatar states, and generated Business_Codes.
5. WHEN the seeder creates rooms, THE seeder SHALL seed only A101, A102, and A103 initially and SHALL not seed Room104.
6. WHEN the seeder creates recurring or persisted sessions, THE seeder SHALL use the same application owners and constraints as production paths rather than bypassing scheduling invariants with bulk inserts.
7. WHEN the seeder is rerun under its documented mode, THE seeder SHALL be deterministic or idempotent as specified by the design and SHALL not create duplicate identities, rooms, recurring occurrences, sessions, or audit history unexpectedly.

### Requirement 17: Authorization, validation, and secure file handling

**User Story:** As an academy owner, I want every scheduling and profile operation protected consistently, so that sensitive identity and operational data cannot be changed or disclosed by unauthorized actors.

#### Acceptance Criteria

1. WHEN a scheduling, room, profile, photo, Timeline, System Activity, or audit request is received, THE existing Policy/Gate boundary SHALL authorize the actor and validate the request before exposing protected data or performing mutation.
2. IF an actor is unauthenticated, lacks the required ability, or submits an invalid request, THEN THE endpoint SHALL return the existing compatible unauthorized or validation response, SHALL include no protected data, and SHALL perform no protected read or write.
3. WHEN a profile or scheduling payload is received, THE approved Form Request or equivalent validation boundary SHALL validate every writable field and SHALL reject unknown protected fields such as primary keys, audit metadata, and Business_Code.
4. WHEN a file is uploaded, THE Profile_Media boundary SHALL validate MIME type, extension, size, dimensions, and safe generated naming and SHALL store it outside direct unsafe path construction.
5. THE feature SHALL preserve CSRF, escaped output, parameterized queries, non-sensitive error responses, and existing rate/authorization protections.
6. IF a validation or authorization failure occurs, THEN THE system SHALL not create an audit record that represents an accepted mutation and SHALL not leak national IDs, phone numbers, file paths, or unrelated profile data.

### Requirement 18: Accessibility, RTL, and responsive profile/calendar surfaces

**User Story:** As a Persian-speaking administrator using keyboard, assistive technology, or a touch device, I want scheduling and profile interactions to remain understandable and operable.

#### Acceptance Criteria

1. THE profile and calendar surfaces SHALL preserve `lang="fa"`, `dir="rtl"`, semantic landmarks, logical layout properties, Jalali presentation conventions, and existing named route navigation.
2. THE profile and calendar surfaces SHALL provide keyboard access, visible focus indicators, semantic links/buttons, accessible labels, and an equivalent non-drag path for every scheduling mutation.
3. WHEN a dialog, drawer, conflict review, or photo control opens, THE surface SHALL provide dialog semantics where applicable, focus containment, Escape handling, focus restoration, and a non-pointer dismissal path.
4. THE profile and calendar surfaces SHALL communicate available, conflict, invalid, pending, cancelled, and completed states with text or semantics in addition to color.
5. WHEN the viewport is between the supported project breakpoints from 390px through 1920px, or is wider than 1920px, THE surfaces SHALL avoid horizontal overflow and SHALL preserve required information in responsive card/table representations.
6. THE profile and calendar surfaces SHALL provide at least 44 by 44 CSS-pixel touch targets for all interactive controls regardless of the current input method.
7. WHEN reduced motion is requested, THE surfaces SHALL suppress nonessential transition and preview motion while preserving content, focus, and operation feedback.

### Requirement 19: Continuous documentation system

**User Story:** As a maintainer, I want architecture and business decisions documented with the implementation, so that future changes do not recreate duplicate logic or undocumented contracts.

#### Acceptance Criteria

1. WHEN an approved feature decision changes architecture ownership, THE documentation system SHALL update `Architecture.md` with the owner boundary, preserved boundaries, and compatibility implications.
2. WHEN an approved scheduling or profile rule changes, THE documentation system SHALL update `BusinessRules.md` with the rule, applicability, exceptions, and source decision.
3. WHEN a schema, constraint, index, relationship, or migration behavior changes, THE documentation system SHALL update `Database.md` with the canonical model and compatibility state.
4. WHEN a route, API payload, DTO, resource, or error contract changes, THE documentation system SHALL update `API.md` with the backward-compatible contract and migration notes.
5. WHEN a user-visible change is accepted, THE documentation system SHALL update both `History.md` and `CHANGELOG.md` with the date, scope, and verification status before implementation completion; WHEN an operational but not user-visible change is accepted, THE documentation system SHALL update `History.md` and SHALL not require a `CHANGELOG.md` entry; WHEN both change types occur together, THE documentation system SHALL update both documents, and implementation SHALL not proceed beyond the documentation gate until required updates succeed.
6. WHEN a non-trivial decision is made or a previous decision is superseded, THE documentation system SHALL update `DecisionLog.md` with the decision, reason, status, and affected boundaries.
7. THE implementation plan SHALL assign continuous maintenance of all seven documents—`Architecture.md`, `BusinessRules.md`, `Database.md`, `API.md`, `History.md`, `CHANGELOG.md`, and `DecisionLog.md`—to the owning implementation tasks and SHALL verify that no document is stale before completion.
8. IF an implementation has a clear contradiction with an active or frozen documented decision, THEN implementation SHALL stop immediately, regardless of implementation progress, until an explicit superseding decision is recorded; minor interpretation differences SHALL be recorded for resolution without stopping unrelated work.

### Requirement 20: Backward compatibility and rollout safety

**User Story:** As an academy operator, I want existing data and consumers to keep working while new capabilities roll out, so that the enhancement does not interrupt daily academy operations.

#### Acceptance Criteria

1. THE feature SHALL preserve existing route names, endpoint authentication behavior, API response compatibility, Blade contracts, DTO fields, policies, and existing session/profile ownership for consumers not using new fields.
2. WHEN existing records contain null, legacy, or imported identity values, THE Compatibility_Layer SHALL preserve their primary keys, relationships, scheduling references, user-avatar data, and historical display behavior while reporting data that requires remediation.
3. WHEN a new schema constraint cannot safely apply to legacy/imported records, THE migration SHALL use the approved nullable/backfill compatibility strategy, SHALL not invalidate unrelated existing records, and SHALL report any remaining inconsistency for controlled remediation.
4. WHEN a new profile or scheduling field is absent from a legacy request, THE canonical boundary SHALL apply the documented default or preserve the prior behavior, and SHALL reject the request if neither documented outcome is available.
5. WHEN the new feature is enabled, disabled, unavailable, rolled back, or a rollback encounters a technical failure, THE existing calendar projection, manual session operations, existing Student/Teacher pages, and authorized API consumers SHALL remain available through their existing contracts; IF the canonical calendar projection cannot remain available, THEN THE feature rollout SHALL be blocked or rolled back.
6. WHEN a backward-compatible adapter translates a legacy request or response, THE adapter SHALL preserve stable identifiers, authorization outcomes, error semantics, and persisted business meaning.
7. THE feature SHALL not alter the existing calendar-persisted-session projection into a generation, reconciliation, or synthetic-event system.

## Correctness Properties for Later Implementation

The following properties are selected for deterministic, input-varying logic. Database, filesystem, authorization, scheduler, browser, and PostgreSQL behavior SHALL additionally receive representative integration, concurrency, accessibility, and smoke coverage rather than being replaced by property tests.

### Property 1: Preferred-room resolution is deterministic and non-mandatory

For every generated instrument, active-room catalog, interval occupancy set, and preference mapping, the resolver SHALL choose the mapped Preferred_Room when it is active, compatible, owned by the Academy, and available; otherwise it SHALL choose the first Available_Room in the approved order, or return no room when none exists. A missing or unavailable preference SHALL never reject a proposal when another Available_Room exists.

**Validates:** Requirements 2.3–2.9.

### Property 2: Availability interval algebra

For every generated pair of intervals, resources, cancellation states, buffers, and explicit blocks, the Scheduling_Domain SHALL classify physical overlap as conflict, exact end-to-start adjacency as non-overlap when no buffer/block applies, cancelled sessions as non-blocking, completed historical sessions as blocking, and every accepted interval as satisfying all applicable resource and rule constraints.

**Validates:** Requirements 4.1–4.9.

### Property 3: No artificial scheduling gaps

For every generated sequence of operating intervals with no teacher, student, room, availability, or Manual_Admin_Block blocker, the Scheduling_Domain SHALL permit contiguous adjacent sessions and SHALL not invent an additional gap; every rejected adjacency SHALL identify an actual configured or persisted blocker.

**Validates:** Requirements 4.6–4.8 and 16.3.

### Property 4: Persisted calendar projection identity

For every generated set of persisted ClassSession records and approved inclusive calendar filters, the projection SHALL return exactly the matching records once, preserve ordered stable identifiers through query/resource/endpoint/normalization boundaries, perform no writes, and return zero events for an empty matching set.

**Validates:** Requirements 1.2, 6.1–6.3, 20.7.

### Property 5: Calendar mutation authority and rollback

For every generated authorized edit proposal and current session version, an accepted proposal SHALL be represented by the authoritative persisted session returned by the existing mutation owner, while every rejected authorization, validation, conflict, or stale-version proposal SHALL preserve the prior persisted session and authoritative calendar event.

**Validates:** Requirements 5.1–5.5 and 6.4–6.8.

### Property 6: Recurring generation idempotency

For every generated recurring schedule, eligible occurrence set, interruption point, and retry sequence, the final persisted set SHALL contain exactly one ClassSession per eligible schedule/enrollment/date/start-time identity, preserve successful prior commits, and produce no synthetic calendar records.

**Validates:** Requirements 7.1–7.8.

### Property 7: Iranian national-ID normalization and validation

For every generated representation of a ten-digit Iranian national ID, equivalent accepted representations SHALL normalize to one canonical value and pass only when the approved checksum is valid; invalid checksums, invalid lengths, and duplicate canonical values SHALL be rejected without persistence.

**Validates:** Requirements 9.4–9.9.

### Property 8: Cross-model email uniqueness

For every generated Student and Teacher profile pair, the canonical identity boundary SHALL accept distinct non-null Canonical_Email values, reject a duplicate across models, preserve null when email is omitted, and allow an update to retain its own existing canonical email without false duplication.

**Validates:** Requirements 9.1–9.2.

### Property 9: Profile identity and business-code stability

For every generated Profile_Record with valid identity data, create/backfill SHALL produce exactly one non-empty Business_Code, ordinary updates SHALL preserve the code and primary key byte-for-byte, and user-editable attempts to mutate the code SHALL have no accepted persistence effect.

**Validates:** Requirements 8.1–8.7 and 13.1–13.7.

### Property 10: Profile media lifecycle invariant

For every generated valid photo replacement sequence, the accepted profile contract SHALL reference only managed storage-resolved original/medium/thumbnail derivatives or the configured default avatar; failed processing SHALL preserve the previous valid media state, and replacement cleanup SHALL not remove unrelated files.

**Validates:** Requirements 10.1–10.8.

### Property 11: History ordering and separation

For every generated set of profile lifecycle events and operational actions, Timeline SHALL return only approved profile events in deterministic newest-first order, System Activity SHALL remain a separate action feed, and neither feed SHALL fabricate an event for an absent source record.

**Validates:** Requirements 11.1–11.7.

### Property 12: Accepted-mutation audit cardinality and immutability

For every generated sequence of accepted and rejected session/profile mutations, each accepted create/update/delete SHALL append exactly one immutable Audit_Record, each rejected or non-persisting operation SHALL append none, and later subject changes SHALL not alter stored before/after values.

**Validates:** Requirements 12.1–12.9.

### Property 13: Transactional failure atomicity

For every generated multi-record mutation and injected failure at each required persistence or audit step, the committed database state SHALL equal the pre-operation state, with no partial subject, media, code, recurring occurrence, or accepted-audit write.

**Validates:** Requirements 14.1–14.2 and 15.1–15.2.

### Property 14: Concurrency safety

For every generated pair or batch of concurrent operations targeting the same subject or overlapping scheduling resources, the final committed state SHALL satisfy database uniqueness, accepted conflict policy, and one-audit-per-accepted-mutation invariants; rejected operations SHALL return controlled conflict, stale, or retryable outcomes without duplicate records.

**Validates:** Requirements 13.5, 14.2, and 15.3–15.7.

### Property 15: Compatibility preservation

For every generated legacy request, existing profile, imported nullable identity, and existing calendar projection input that does not use new fields, the Compatibility_Layer SHALL preserve stable identifiers, relationships, authorization outcomes, prior error semantics, and persisted calendar membership while exposing no synthetic event or unauthorized new data.

**Validates:** Requirements 1.3–1.5 and 20.1–20.7.

## Later-Phase Verification Contract

- Property-based tests SHALL run at least 100 generated cases per deterministic property unless the repository convention requires more, and each test SHALL be tagged `Feature: academy-scheduling-and-profiles, Property N: <title>`.
- Generators SHALL cover Persian/Unicode identity text, Iranian digit variants, valid and invalid national-ID checksums, duplicate/null emails, room catalogs with inactive/missing/preferred rooms, adjacent and overlapping intervals, cancellation/completion states, retry interruption points, concurrent resource targets, optional profile fields, empty history, valid/invalid photo metadata, and legacy/imported records.
- Integration tests SHALL cover PostgreSQL row/advisory locking, transaction rollback, database constraints/indexes, Laravel Storage configuration, Intervention Image GD processing, scheduler invocation, authorization, CSRF, eager-loading/query counts, API/Blade compatibility, audit writes, and seeder repeatability.
- Browser/accessibility tests SHALL cover existing calendar editing, keyboard alternatives to drag/resize, RTL and Jalali rendering, dialogs/drawers, focus restoration, reduced motion, responsive widths 390, 430, 768, 1024, 1366, 1600, and 1920, and the required 44px touch targets.
- Parser, serializer, DTO, resource, and API contracts introduced in later phases SHALL include invalid-input/error-shape checks and round-trip equivalence whenever the representation is reversible.
- Documentation checks SHALL verify all seven required documents are updated with the implementation's accepted architecture, business, database, API, history, changelog, and decision evidence.
- The implementation phase SHALL not be considered complete until focused PHP tests, JavaScript/property tests, configured browser tests, static architecture checks, build validation, and applicable Laravel cache/config validation pass without changing the existing persisted-session projection contract.
