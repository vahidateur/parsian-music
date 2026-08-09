# Implementation Plan: Interactive Session Scheduling

## Overview

Implement the approved PHP 8.3/Laravel scheduling domain incrementally. Begin by locking down the proven boundaries: `CalendarQueryService → CalendarEventResource → admin.calendar.events` remains a zero-write persisted-`ClassSession` projection; `SessionEditService`, `SessionGeneratorService`, `ConflictDetectionService`, `SessionPolicy`, and existing DTOs become compatibility adapters/fact providers, never parallel decision owners. Use the installed pinned `fast-check` 4.3.0 and a deterministic PHP test helper for executable generated checks; add no dependency or replacement calendar.

## Tasks

- [x] 1. Establish regression guards and test infrastructure before scheduling production changes
  - [x] 1.1 Add a required architecture-characterization suite and deterministic scheduling-case support
    - Encode the proven feed boundary, named route compatibility, DTO/query-free view contracts, policy seam, and the prohibition on `CalendarQueryService`/`CalendarEventResource` importing command, generation, cache, or decision code.
    - Create test-only deterministic interval, relation-path, rule, room, version, and concurrency-case builders (minimum 100 generated cases per property) with seed and first-failure diagnostics; reuse existing `tests/Feature/Admin/CalendarControllerTest.php`, `tests/js/properties/`, and the installed test runners.
    - Affected: `tests/Feature/Admin/`, `tests/Unit/`, `tests/Support/`, `tests/js/properties/` only. Prerequisite for every production task; rollback is removal of added test-only files.
    - _Requirements: 1.1-1.8, 17.1-17.7, 18.5-18.7, 20.5, 21.1-21.8_
  - [x]* 1.2 Write the executable property test for persisted calendar projection preservation
    - **Property 15: Persisted calendar projection count-and-ID preservation invariant.** Generate inclusive ranges and approved filters, then assert source IDs/counts survive query, resource, endpoint, normalization, and rendering once only with zero feed writes or synthesis.
    - **Validates: Requirements 13.4, 13.9, 15.7, 17.1, 17.2, 17.3, 17.7, 21.5.**
  - [x]* 1.3 Write the executable property test for first-boundary diagnostics
    - **Property 16: First-boundary diagnostic invariant.** Inject earliest representation mismatches and assert the suite reports the correct boundary, fixture/seed, expected, and observed result without a downstream adjustment.
    - **Validates: Requirements 1.1, 17.1, 21.5, 21.6.**

- [ ] 2. Build the canonical pure scheduling decision contracts
  - [x] 2.1 Create immutable scheduling values, enums, proposal normalization, and the transport-neutral `SchedulingDomain` façade
    - Add only approved `app/Domain/Scheduling` value objects/contracts; map existing `ClassSession`, `SessionEditResource`, `SessionEditViewData`, and `SessionDisplayData` compatibly rather than replacing their public fields. Reject protected enrollment, financial, recurrence-identity, and BusinessCode fields before a mutation path.
    - Do not select a new package or add a second persistence abstraction. Use one relation-path resolution route for direct and enrollment-backed sessions.
    - Affected: `app/Domain/Scheduling/`, existing enums/DTOs only where a backward-compatible server-owned field is required.
    - _Requirements: 2.1-2.7, 4.1-4.3, 5.7, 8.4, 11.1, 16.3-16.5, 18.1-18.4, 21.3_
  - [x] 2.2 Implement the sole availability evaluator, conflict classifier, rules provider, and room-suitability facts boundary
    - Compose interval/resource facts from `ConflictDetectionService`, `RoomResolver`, `RoomOptionProvider`, and `RelationPathResolver`; do not copy their queries or overlap predicate. Only `SchedulingDomain` may turn those facts into an `AvailabilityResult`.
    - Return exactly one state with complete authorized conflict ranges, hard/soft classification, effective buffers/rules, localized-safe codes, and deterministic outcomes.
    - Affected: `app/Domain/Scheduling/` and only proven compatibility adapters.
    - _Requirements: 4.1-4.10, 5.1, 9.1-9.9, 10.1-10.6, 18.1, 18.6_
  - [x] 2.3 Add centralized scheduling authorization and extend `SessionPolicy` only with evidence-backed named abilities
    - Gate protected facts before evaluation; distinguish update, preview, suggestion, audit-history, recurrence, rules, and override authority without record-existence disclosure. Preserve all existing `SessionPolicy` behavior and named route middleware.
    - _Requirements: 3.5-3.6, 5.3-5.4, 8.6, 12.5-12.6, 16.1-16.2, 16.7_
  - [x]* 2.4 Write the executable property test for canonical decision ownership
    - **Property 1: Canonical decision ownership invariant.** Generate source-equivalent form, drag/resize, recurrence, and Busy Seed proposals; assert they use one normalized domain decision before source presentation.
    - **Validates: Requirements 4.1, 10.5, 13.1, 15.1, 18.1, 18.2, 18.6.**
  - [x]* 2.5 Write the executable property test for proposal integrity and no-write rejection
    - **Property 2: Proposal integrity and rejection preservation invariant.** Generate permitted, protected, malformed, mixed-path, unauthorized, and disallowed fields; assert stable rejections preserve session, code, recurrence, version, counters, and audit state.
    - **Validates: Requirements 2.3, 2.4, 2.5, 2.7, 8.3, 8.4, 16.3, 16.4, 21.3.**
  - [x]* 2.6 Write the executable property test for complete availability and conflict classification
    - **Property 3: Scheduling consistency and complete conflict invariant.** Cover physical/adjacent intervals, buffers, cancelled/completed sessions, every resource category, and hard constraints; assert exactly one safe decision state.
    - **Validates: Requirements 4.2-4.8, 9.3-9.7, 10.1-10.3.**
  - [x]* 2.7 Write the executable property test for effective rules and room suitability
    - **Property 10: Effective rules and room suitability invariant.** Generate complete/contradictory rules and room/resource facts; assert only authorized active compatible available rooms are ordered and violations name their constraint.
    - **Validates: Requirements 9.1-9.7, 9.9, 10.1-10.5.**
  - [x]* 2.8 Write the executable property test for immutable contract round trips
    - **Property 14: Immutable contract round-trip invariant.** Generate valid/malformed proposal, availability, rules, suggestion, audit, and resource representations; assert reversible fields/enums or stable safe errors that never become mutations.
    - **Validates: Requirements 2.6, 5.7, 17.5, 18.2, 21.3.**

- [x] 3. Add proven additive persistence, effective rules, and immutable operational-code support
  - [x] 3.1 Implement only evidence-backed reversible migrations, models, repositories, factories, and rule validation
    - After the Task 1 preflight confirms actual schema/consumers, add the minimum indexed persistent state for effective rules, resource versions/locks, room requirements, and immutable audit records. Preserve `ClassSession` direct/enrollment paths and existing `teacher_code`/`student_code`; never create duplicate BusinessCode columns or a destructive rewrite.
    - Validate complete rule configurations atomically, preserve the last valid configuration, advance effective versions, and scope all new state to the proven academy owner.
    - Affected: migration/model/factory paths discovered by the preflight, `ClassSession` only for additive compatible relations/casts/fillable fields.
    - _Requirements: 8.1-8.5, 9.1-9.9, 10.1-10.5, 12.1-12.4, 14.1-14.3, 18.5, 21.1_
  - [x] 3.2 Implement one `BusinessCodeOwner` and wire approved teacher/student create and backfill paths through it
    - Allocate/backfill canonical `teacher_code` and `student_code` uniquely under transaction/database enforcement; reject user-editable or scheduling-payload code changes and preserve primary keys/relations byte-for-byte. Limit disclosure to authorized DTO/resource fields with localized, escaped labels.
    - Reuse existing teacher/student actions, models, policies, and code columns; do not add another identity generator.
    - _Requirements: 8.1-8.7, 16.1-16.6, 18.2, 21.1_
  - [x]* 3.3 Write the executable property test for BusinessCode allocation and immutability
    - **Property 9: BusinessCode uniqueness and immutability invariant.** Generate create, approved backfill, ordinary update, and forbidden payload sequences; assert one non-empty unique canonical code and no ordinary mutation.
    - **Validates: Requirements 8.1-8.4.**

- [x] 4. Coordinate authoritative mutation, auditing, concurrency, and recurrence through existing entry points
  - [x] 4.1 Implement the transaction-only mutation coordinator, lock manager, version manager, and append-only audit writer
    - Lock the session and affected resources deterministically, compare the opaque current `SessionVersion`, re-evaluate under lock, atomically persist the accepted session/version/resource-version changes and exactly one audit snapshot, then roll back all writes for every failure.
    - Retain legacy `updated_at` compatibility only in an adapter; audit writer failure, cache/infrastructure failure, stale version, hard constraint, and authorization failure must never yield partial state.
    - _Requirements: 2.2, 2.6-2.7, 5.2-5.7, 11.1-11.7, 12.1-12.7, 14.3-14.5, 16.5-16.6, 20.4_
  - [ ] 4.2 Adapt `SessionCreateService`, `SessionEditService`, and `SessionGeneratorService` to the one domain coordinator
    - Preserve existing routes, redirects, result/DTO fields, relation-path protection, notes behavior, subscriptions, and `ClassSession` identity. Refactor only duplicated acceptance decisions into domain delegation; keep `ConflictDetectionService` as an interval fact provider and never move generation into the calendar query/feed.
    - _Requirements: 2.1-2.7, 3.1-3.7, 4.1, 13.1-13.9, 17.2-17.6, 18.1-18.7_
  - [ ] 4.3 Implement occurrence identity, expansion, and lifecycle guards behind the preserved `SessionGeneratorService`
    - Generate/reconcile only through canonical proposals and the mutation coordinator; enforce one persisted occurrence/audit per recurring-schedule/date/start identity, explicit series scope confirmation, active/inactive deletion guards, and explanation-only blocked results.
    - Calendar discovery remains an ordinary later read through the existing named feed after commit.
    - _Requirements: 13.1-13.9, 17.2-17.4, 18.1-18.2_
  - [x]* 4.4 Write the executable property test for narrow force overrides
    - **Property 4: Narrow override invariant.** Generate blocking reports and override instructions; assert only fully authorized, confirmed, reasoned soft-conflict overrides commit one correctly attributed audit and no hard-constraint bypass.
    - **Validates: Requirements 5.1-5.7, 11.5.**
  - [ ]* 4.5 Write the executable property test for accepted-transition atomicity
    - **Property 7: Accepted scheduling transition atomicity invariant.** Generate valid/current and failing create, edit, drag, resize, recurrence, and Busy Seed transitions; assert all-or-nothing session/version/resource-version/audit state.
    - **Validates: Requirements 2.2, 2.6, 11.2, 12.1, 12.2, 12.4, 15.2, 15.4.**
  - [ ]* 4.6 Write the executable property test for locks and optimistic concurrency
    - **Property 8: Locking and optimistic concurrency invariant.** Generate versions and deterministic mutation interleavings; assert only locked-current proposals commit, stale results return authorized latest state, and accepted conflicts never survive.
    - **Validates: Requirements 11.1, 11.3, 11.6, 11.7.**
  - [ ]* 4.7 Write the executable property test for immutable audit history
    - **Property 12: Audit immutability and history consistency invariant.** Generate accepted transitions and later edits; assert one corresponding immutable snapshot and deterministic authorized history order.
    - **Validates: Requirements 12.1-12.5.**
  - [ ]* 4.8 Write the executable property test for recurrence identity and lifecycle idempotency
    - **Property 13: Recurrence identity and lifecycle idempotency invariant.** Generate active/inactive schedules, identities, repeated expansions, scopes, and interleavings; assert at most one persisted occurrence/audit and no synthetic event.
    - **Validates: Requirements 13.1-13.8.**

- [ ] 5. Add non-persisting suggestions/previews, safe caching, and deterministic busy fixtures
  - [ ] 5.1 Implement advisory availability-cache keys, freshness checks, version bypass, single-flight protection, and safe instrumentation
    - Key every required academy/resource/interval/room/excluded-session/rules/resource/recurrence/authorization dimension. Re-evaluate every final mutation from locked authoritative data; cache failure/incomplete data must fall back safely or fail closed.
    - Add bounded retention and outcome-only metrics without proposal, names, notes, codes, snapshots, or override reasons.
    - _Requirements: 4.9-4.10, 14.1-14.8, 20.1, 20.3-20.5_
  - [ ] 5.2 Implement fully evaluated, deterministic, non-persisting `SuggestionService`
    - Search only within the approved bounded window; evaluate each candidate through the canonical domain, return safe fields/reasons, and rank temporal distance, resource preservation, room suitability, then rule compliance. Empty sets must distinguish authorized-unavailable/filter-window reasons.
    - _Requirements: 6.1-6.6, 10.4-10.5, 16.1-16.2, 20.1-20.5_
  - [ ] 5.3 Implement a preview-only domain path with zero write, version, cache-invalidation, lock, generation, or audit effects
    - Return discardable server presentation and a non-authoritative availability result for current authorized proposals. Never permit preview or suggestion data to authorize a later write.
    - _Requirements: 3.5-3.6, 7.1-7.7, 11.2-11.4, 14.4-14.5, 16.1-16.7_
  - [ ] 5.4 Implement named deterministic Busy Seed fixture groups via canonical domain proposals
    - Support targeted idempotent groups for hours/lunch/daily/consecutive/buffer/room/cancelled/historical/competing-resource cases. Refuse production without the approved explicit safety control; when allowed, authorize/audit it and never create direct calendar-only data.
    - _Requirements: 15.1-15.7, 16.1-16.7, 17.1-17.4_
  - [ ]* 5.5 Write the executable property test for suggestions
    - **Property 5: Suggestions are evaluated, ordered, and side-effect free.** Generate conflicting proposals and candidate permutations; assert fully evaluated available alternatives, deterministic ordering, safe fields, and zero persistence.
    - **Validates: Requirements 6.1-6.4.**
  - [ ]* 5.6 Write the executable property test for preview non-authority
    - **Property 6: Preview is non-authoritative and non-mutating.** Generate valid, invalid, stale, cancelled, and unauthorized previews; assert only discardable decision data with zero session, generation, version, invalidation, or audit effects.
    - **Validates: Requirements 7.1-7.4, 7.7, 14.4.**
  - [ ]* 5.7 Write the executable property test for cache decision equivalence and safety
    - **Property 11: Cache safety and decision equivalence invariant.** Generate equivalent facts and cache-key/freshness changes; assert safe identical decisions when reusable and non-reuse/fail-closed behavior otherwise.
    - **Validates: Requirements 4.9-4.10, 14.1-14.7.**

- [ ] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Expose additive scheduling contracts and wire the approved interactive surfaces
  - [ ] 7.1 Add thin, named scheduling request/controller/resource adapters and compatible route registration
    - Add validation requests and named endpoints for preview, suggestion, mutation, history, rules, recurrence expansion, and safe Busy Seed. Apply existing auth/CSRF/middleware/limiter conventions, `SessionPolicy` abilities, localized stable codes, safe error logging, and current-version behavior before domain invocation.
    - Do not alter the existing `admin.calendar.events`, session CRUD, note, attendance, or feed response meanings; additive endpoints delegate only to `SchedulingDomain`.
    - Affected: additive requests/resources/controller plus `routes/web.php`, `AppServiceProvider` only if the preflight proves a compatible binding/limiter need.
    - _Requirements: 3.1-3.7, 5.7, 6.5-6.6, 7.2, 11.3-11.4, 12.5-12.6, 16.1-16.7, 17.2-17.6, 20.3-20.4, 21.3-21.4_
  - [ ] 7.2 Extend the existing edit DTO/resource/service/view contract for server-owned scheduling metadata
    - Carry allowed fields, authoritative `SessionVersion`, recurrence association state, room/options, effective rules, policy flags, BusinessCode visibility, and stale-review data through existing `SessionEditResource`/`SessionEditViewData`, the named edit route, and query-free Blade. Preserve normal edit, notes, room, relation, and redirect contracts.
    - Blade may render labels, escaped values, CSRF form structure, and accessibility attributes only; it must not query models or evaluate scheduling rules.
    - _Requirements: 2.1-2.7, 8.6-8.7, 11.1-11.4, 13.5-13.8, 16.1-16.4, 18.4, 19.1-19.2, 19.8_
  - [ ] 7.3 Add a calendar interaction adapter to the existing `calendar-app.js`/`fullcalendar.js` composition
    - Enable authorized pointer drag/drop/resize and keyboard-equivalent edits only when server metadata permits. Submit canonical proposals with version/relation/room context; suppress duplicate in-flight requests, debounce/cancel stale previews, discard ambiguous authorization, render only accepted authoritative state, and revert on any rejection.
    - Keep FullCalendar rendering/read-feed logic intact. JavaScript may orchestrate requests, pending/preview/rollback and presentation only—never overlap, buffers, rules, rooms, recurrence, or override policy.
    - _Requirements: 3.1-3.7, 6.6, 7.1-7.7, 10.6, 11.1-11.4, 17.2-17.4, 18.3, 19.2-19.7, 20.2_
  - [ ] 7.4 Add accessible, localized scheduling presentation using the established calendar/dialog design system
    - Add only required dialog/template/CSS hooks and translation keys for available/conflict/invalid/stale/pending/review/override/suggestion states. Retain RTL, Jalali display, 24-hour machine values, non-color state indication, 44px touch targets, reduced motion, visible focus, `x-trap`, Escape, and focus restoration.
    - Do not use inline JS/styles, raw translation keys, hard-coded visual tokens, or duplicate calendar/drawer components.
    - _Requirements: 3.3-3.7, 5.7, 6.3-6.6, 7.3-7.6, 8.7, 11.4, 16.6, 19.1-19.8_
  - [ ]* 7.5 Write focused JavaScript unit/property tests for interaction orchestration
    - Exercise canonical request construction, pending suppression, latest-preview-wins cancellation, authoritative success rendering, rejection rollback, stale review/reload, suggestion re-evaluation, and pointer/keyboard equivalence with fake transport; assert no business-rule implementation in client modules.
    - _Requirements: 3.1-3.7, 6.6, 7.1-7.7, 11.4, 17.2-17.4, 18.3, 19.3-19.8, 21.4_

- [ ] 8. Verify integration boundaries, accessibility, security, and operations
  - [ ]* 8.1 Add focused PHP feature/integration tests for persistent scheduling boundaries
    - Cover named contracts, validation/error shape, policies/CSRF/rate limits, cross-resource secrecy, rule/room outcomes, force override, database lock/rollback/audit failure, cache failure/invalidation, recurrence/Busy Seed idempotency and safety, and legacy session/edit/generator compatibility.
    - Use representative database/cache/authorization cases rather than duplicating pure-property assertions. Report first responsible boundary and seed/fixture on failure.
    - _Requirements: 1.1-1.8, 2.1-2.7, 4.1-4.10, 5.1-5.7, 8.1-8.7, 9.1-9.9, 10.1-10.6, 11.1-11.7, 12.1-12.7, 13.1-13.9, 14.1-14.8, 15.1-15.7, 16.1-16.7, 17.1-17.7, 20.1-20.5, 21.1-21.8_
  - [ ]* 8.2 Add browser and accessibility coverage for the interactive calendar/edit surfaces
    - Test form edit, pointer drag/resize, keyboard equivalent, pending/rollback, stale/override/suggestion flows, dialog focus behavior, Persian/RTL labels, 390/430/768/1024/1366/1600/1920 viewports, touch sizing, no horizontal overflow, and reduced motion. Use configured Playwright tests; never start a development server or watcher from the task.
    - _Requirements: 3.1-3.7, 6.6, 7.1-7.7, 11.4, 17.2-17.4, 19.1-19.8, 20.2, 21.4_
  - [ ]* 8.3 Add architecture/observability regression checks and run focused validation
    - Assert one decision owner, no scheduling imports/logic in Blade or client code, read-only calendar projection, stable routes/IDs, safe log payloads, and performance-baseline measurements for availability/conflict/suggestion/mutation/concurrency/cache/recurrence/error paths.
    - Execute `php artisan test --filter=Scheduling`, `npm run test:calendar`, configured browser/a11y tests, `npm run build`, and applicable `php artisan optimize:clear`/cache-config checks; record failures at their first owner boundary.
    - _Requirements: 1.5-1.8, 14.6-14.8, 16.5-16.6, 17.1-17.7, 18.1-18.7, 19.1-19.8, 20.1-20.5, 21.1-21.8_

- [ ] 9. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- No production code was modified while creating this plan. Task 1.1 is a required proof gate before any scheduling production change.
- Tasks marked `*` are optional test tasks; they remain required in the dependency graph and are the executable verification specified by the approved design.
- The existing calendar feed remains `CalendarQueryService` → `CalendarEventResource` → `admin.calendar.events`: persisted `ClassSession` records only, stable IDs, inclusive filters, and zero scheduling writes.
- Do not modify the completed `calendar-persisted-session-projection` spec, install dependencies, replace FullCalendar, add plugin/microservice architecture, or bypass existing policies/DTOs/services without an approved investigation-record boundary change.
- Each implementation task must use the preflight-derived affected-file inventory, fixture set, acceptance criteria, focused command, and reversible migration/rollback point before code is changed.
- **Implementation sequencing:** Execute complete waves in ascending order. Within a wave, start only tasks that do not contend for a file identified by Task 1.1; serialize such work in the listed dependency order. Complete checkpoint 6 before beginning wave 8 and checkpoint 9 after wave 12; checkpoints are human review gates, not graph leaves.
- **Rollback:** Preserve the additive/reversible migration boundary in Task 3.1. Before each mutation, capture the affected-file inventory and a rollback path; stop and revert the current task’s isolated change when a focused check fails. Never use destructive database commands, rewrite existing `ClassSession` identity paths, or roll back a shared migration without an approved recovery plan.
- **Testing:** Implement all non-optional tasks. `*` tasks are optional for scoped MVP execution but remain scheduled verification work: property tests require deterministic seeds and first-failure diagnostics; feature, browser, accessibility, and architecture checks run after their corresponding implementation. Task 8.3 is the final automated validation gate and must report the first responsible boundary.
- **Concurrency:** Treat Task 4.1 as the sole commit authority. Mutations must lock deterministically, compare the current opaque version, re-evaluate under lock, and commit session, resource-version, cache-invalidation, and audit effects atomically. Preview, suggestion, cache, and client interaction work never authorizes a write; stale or duplicate requests must be rejected or safely reverted.
- **Safe execution:** Keep the existing calendar feed read-only and preserve named routes, DTOs, policy seams, and FullCalendar composition. Use authorized fixtures only, never log protected scheduling payloads, and do not add dependencies or infrastructure without an approved requirement/design change. No task may bypass validation, CSRF, policy authorization, transaction rollback, or the task’s focused automated check.
- **Graph conventions:** `blockedBy` lists direct prerequisite leaves; wave barriers additionally require every earlier wave to finish. Therefore the graph contains every incomplete decimal task exactly once, while parent tasks and checkpoints are documented but intentionally excluded from wave execution.

## Task Dependency Graph

```json
{
  "executionSemantics": {
    "waveBarrier": "A wave starts only after every earlier wave completes.",
    "parallelism": "Tasks in one wave are parallelizable only when the Task 1.1 inventory confirms that they do not write the same file.",
    "blocking": "Each blockedBy entry is a direct blocker in addition to the wave barrier.",
    "criticalPathBasis": "Dependency-derived path; duration estimates are not available."
  },
  "taskHierarchy": {
    "1": ["1.1", "1.2", "1.3"],
    "2": ["2.1", "2.2", "2.3", "2.4", "2.5", "2.6", "2.7", "2.8"],
    "3": ["3.1", "3.2", "3.3"],
    "4": ["4.1", "4.2", "4.3", "4.4", "4.5", "4.6", "4.7", "4.8"],
    "5": ["5.1", "5.2", "5.3", "5.4", "5.5", "5.6", "5.7"],
    "6": [],
    "7": ["7.1", "7.2", "7.3", "7.4", "7.5"],
    "8": ["8.1", "8.2", "8.3"],
    "9": []
  },
  "checkpoints": [
    { "task": "6", "afterWave": 7, "beforeWave": 8, "purpose": "Ensure all tests pass, ask the user if questions arise." },
    { "task": "9", "afterWave": 12, "purpose": "Ensure all tests pass, ask the user if questions arise." }
  ],
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3", "2.1"] },
    { "id": 2, "tasks": ["2.2", "2.3", "3.1"] },
    { "id": 3, "tasks": ["2.4", "2.5", "2.6", "2.7", "2.8", "3.2"] },
    { "id": 4, "tasks": ["3.3", "4.1"] },
    { "id": 5, "tasks": ["4.2", "4.4", "4.6", "4.7"] },
    { "id": 6, "tasks": ["4.3"] },
    { "id": 7, "tasks": ["4.5", "4.8", "5.1", "5.2", "5.3", "5.4"] },
    { "id": 8, "tasks": ["5.5", "5.6", "5.7", "7.1"] },
    { "id": 9, "tasks": ["7.2", "7.3"] },
    { "id": 10, "tasks": ["7.4", "7.5"] },
    { "id": 11, "tasks": ["8.1", "8.2"] },
    { "id": 12, "tasks": ["8.3"] }
  ],
  "blockedBy": {
    "1.1": [],
    "1.2": ["1.1"],
    "1.3": ["1.1"],
    "2.1": ["1.1"],
    "2.2": ["2.1"],
    "2.3": ["2.1"],
    "2.4": ["2.2", "2.3"],
    "2.5": ["2.1", "2.3"],
    "2.6": ["2.2"],
    "2.7": ["2.2"],
    "2.8": ["2.1"],
    "3.1": ["1.1"],
    "3.2": ["3.1"],
    "3.3": ["3.2"],
    "4.1": ["2.2", "2.3", "3.1"],
    "4.2": ["4.1"],
    "4.3": ["4.2"],
    "4.4": ["4.1"],
    "4.5": ["4.2", "4.3"],
    "4.6": ["4.1"],
    "4.7": ["4.1"],
    "4.8": ["4.3"],
    "5.1": ["2.2", "3.1", "4.1"],
    "5.2": ["2.2", "2.3"],
    "5.3": ["2.2", "2.3"],
    "5.4": ["2.3", "4.3"],
    "5.5": ["5.2"],
    "5.6": ["5.3"],
    "5.7": ["5.1"],
    "7.1": ["4.2", "4.3", "5.1", "5.2", "5.3", "5.4"],
    "7.2": ["3.2", "4.2", "7.1"],
    "7.3": ["7.1"],
    "7.4": ["7.2", "7.3"],
    "7.5": ["7.3"],
    "8.1": ["5.4", "7.1", "7.2", "7.3"],
    "8.2": ["7.3", "7.4"],
    "8.3": ["1.2", "1.3", "2.4", "2.5", "2.6", "2.7", "2.8", "3.3", "4.4", "4.5", "4.6", "4.7", "4.8", "5.5", "5.6", "5.7", "7.5", "8.1", "8.2"]
  },
  "blockingTasks": {
    "proofGate": ["1.1"],
    "domainAndPersistenceGates": ["2.1", "2.2", "2.3", "3.1"],
    "authoritativeMutationGates": ["4.1", "4.2", "4.3"],
    "surfaceIntegrationGates": ["5.1", "5.2", "5.3", "5.4", "7.1", "7.2", "7.3", "7.4"],
    "finalVerificationGates": ["8.1", "8.2", "8.3"]
  },
  "parallelizable": [
    { "wave": 1, "tasks": ["1.2", "1.3", "2.1"] },
    { "wave": 2, "tasks": ["2.2", "2.3", "3.1"] },
    { "wave": 3, "tasks": ["2.4", "2.5", "2.6", "2.7", "2.8", "3.2"] },
    { "wave": 5, "tasks": ["4.2", "4.4", "4.6", "4.7"] },
    { "wave": 7, "tasks": ["4.5", "4.8", "5.1", "5.2", "5.3", "5.4"] },
    { "wave": 8, "tasks": ["5.5", "5.6", "5.7", "7.1"] },
    { "wave": 9, "tasks": ["7.2", "7.3"] },
    { "wave": 10, "tasks": ["7.4", "7.5"] },
    { "wave": 11, "tasks": ["8.1", "8.2"] }
  ],
  "criticalPath": {
    "implementation": ["1.1", "2.1", "2.2", "3.1", "4.1", "4.2", "4.3", "5.4", "7.1", "7.3", "7.4"],
    "releaseVerification": ["1.1", "2.1", "2.2", "3.1", "4.1", "4.2", "4.3", "5.4", "7.1", "7.3", "7.4", "8.2", "8.3"],
    "finalGate": "9"
  }
}
```