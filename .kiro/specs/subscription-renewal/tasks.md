# Implementation Plan: Subscription Renewal

## Overview

این برنامه، طراحی فنی تأییدشده را به promptهای افزایشی برای یک code-generation LLM تبدیل می‌کند. پیاده‌سازی با PHP 8.3/Laravel 13، Eloquent، Blade/Alpine و PHPUnit/Eris انجام می‌شود؛ هر prompt فقط کد یا تست می‌نویسد، به خروجی مراحل قبل متکی است و در پایان route، UI، action، generator و آزمون‌های پذیرش را به هم متصل می‌کند.

دامنه فقط renewal موجود Subscription است: همان Linked Enrollment حفظ می‌شود، فقط تاریخ‌های مجاز تغییر می‌کنند، یک RenewalRecord immutable ساخته می‌شود و جلسات آینده بدون duplicate تولید می‌شوند. Calendar، Dashboard، Bulk، پرداخت، invoice، attendance، completed sessions و گزارش‌های تاریخی خارج از تغییر هستند و فقط regression coverage می‌گیرند.

## Tasks

- [ ] 1. Establish renewal contracts and persistence foundations
  - [ ] 1.1 Add the additive `renewal_records` migration, `RenewalRecord` model and `Subscription::renewalRecords()` relation.
    - Add the unique `request_uuid`, restrictive foreign keys, required date/actor fields, indexes, immutable mass-assignment/cast rules, and model safeguards that reject update/delete paths.
    - Add the non-unique `class_sessions(enrollment_id, session_date, start_time)` lookup index without changing existing historical/manual rows or adding a uniqueness constraint.
    - _Requirements: 3.4–3.7, 4.2–4.3, 5.2, 5.4–5.5_
  - [ ] 1.2 Implement immutable renewal DTOs/value objects and the pure `RenewalPeriodCalculator`.
    - Create `RenewalPeriodData`, `RenewalResult`, `OccurrenceOutcome`, typed rejection data and stable status/reason representations; calculate explicit timezone-aware confirmation dates, null candidates, month-by-month clamping, inclusive periods and next renewal dates without mutating Eloquent dates.
    - _Requirements: 1.3, 1.5–1.8, 4.4–4.5, 5.1, 6.1–6.4_
  - [ ]* 1.3 Add isolated factories, generators, snapshots and an independently implemented occurrence oracle for renewal verification.
    - Cover subscriptions, exact-tuple enrollments, schedules, existing sessions, conflicts, historical records, actors, dates, valid/invalid counts, prior RenewalRecords and injected recoverable/persistent failures; normalize snapshots by table, ID, foreign keys and stored values.
    - _Requirements: 2.1–2.3, 3.1–3.3, 4.1–4.8, 5.1–5.7, 6.1–6.4_

- [ ] 2. Implement request, authorization and renewal orchestration boundaries
  - [ ] 2.1 Implement `SubscriptionRenewalRequest`, `SubscriptionPolicy::renew`, named `admin.subscriptions.renew` routes and a thin `SubscriptionController::renew`.
    - Validate integer `months >= 1`, UUID `request_uuid` and permitted confirmation-date input before mutation; normalize business timezone values; map guest/invalid actors and policy failures to `UNAUTHORIZED` without leaking target details.
    - Preserve existing subscription-management response/redirect contracts and enforce auth/admin middleware plus the dedicated policy ability.
    - _Requirements: 1.1–1.4, 3.5, 5.3, 6.2–6.3_
  - [ ] 2.2 Implement the locked `LinkedEnrollmentResolver` and typed `NO_ENROLLMENT`/`MULTIPLE_ENROLLMENTS` diagnostics.
    - Match the exact student/teacher/instrument tuple, active non-deleted rows, and `lockForUpdate()`; require exactly one result and return all matching IDs for administrator resolution without changing state.
    - _Requirements: 2.1–2.5, 5.1, 6.2_
  - [ ] 2.3 Implement transactional `RenewSubscriptionAction::execute` with request idempotency and write ordering.
    - Re-check boundary invariants, lock Subscription then the linked enrollment, return an existing committed `request_uuid` result without extending twice, calculate from locked current dates, update only allowed dates, insert one immutable RenewalRecord, and rethrow required-write failures for outer rollback.
    - Keep usage/allocation/fee/payment status/notes unchanged; never call invoice, payment, attendance, manual-session or historical mutation paths.
    - _Requirements: 1.5–1.8, 2.4–2.8, 3.1–3.7, 4.6–4.7, 5.1–5.7_
  - [ ] 2.4 Wire the Subscription Management renewal form, confirmation state and result presentation.
    - Offer positive whole-month input, CSRF and idempotency key; render dates and created/skipped/blocked/failed counts plus per-occurrence reasons on success, and stable failure code/message with unchanged state on rejection/failure.
    - Keep business logic out of Blade, use existing UI variants and semantic/RTL/accessibility conventions, and make no Calendar, Dashboard or Bulk UI changes.
    - _Requirements: 1.1–1.4, 1.7–1.8, 2.2–2.3, 3.5, 4.4–4.5_

- [ ] 3. Implement bounded future-session generation
  - [ ] 3.1 Implement lazy inclusive occurrence enumeration for active schedules and canonical session-identity lookup.
    - Enumerate only matching weekdays in `[Renewal Start Date, Renewal End Date]`, support large month counts without an arbitrary cap or full in-memory period list, load schedules once, and use the indexed `(enrollment_id, session_date, start_time)` identity.
    - Preserve the existing `SessionGeneratorService::generateForSchedule($schedule, 8)` behavior for all current callers.
    - _Requirements: 4.1–4.3, 4.7–4.8, 5.4, 6.1_
  - [ ] 3.2 Implement `RenewalSessionGenerator` occurrence outcomes, conflicts and savepoint handling.
    - Lock/check duplicates before creation; report `DUPLICATE_SESSION_IDENTITY` as skipped; run existing teacher/room/enrollment conflict semantics as stable blocked reasons; create one Scheduled enrollment-backed session with the source schedule; catch only recoverable pre-write failures and rethrow persistence failures.
    - Return `created|skipped|blocked|failed` `OccurrenceOutcome` entries and preserve each failed occurrence’s pre-attempt state while continuing remaining occurrences.
    - _Requirements: 4.1–4.6, 5.4–5.5, 6.1–6.4_
  - [ ] 3.3 Integrate the generator into the outer action transaction and result DTO.
    - Process every active schedule under the enrollment lock, handle no active schedules with zero counts, keep per-occurrence details, and ensure any required write failure rolls back dates, sessions and audit insertion together.
    - _Requirements: 1.7–1.8, 4.1–4.8, 5.5–5.7_

- [ ] 4. Add core correctness and state-integrity verification
  - [ ]* 4.1 Add unit tests for `RenewalPeriodCalculator` and DTO/reason-code contracts.
    - Cover one/multiple months, leap years, short months, month-end clamping on every step, null date candidates, inclusive boundaries, next-date calculation, immutable input dates and invalid count rejection.
    - _Requirements: 1.3, 1.5–1.6, 4.8, 5.1_
  - [ ]* 4.2 Write the Eris PBT for **Property 1: Duration and Same-Enrollment State Preservation** with the exact tag `Feature: subscription-renewal, Property 1: Duration and Same-Enrollment State Preservation`.
    - Run at least 100 isolated generated cases; assert same enrollment identity/tuple/start/active state, documented dates, unchanged allocation/usage/fee/payment status/notes, and session dates in the computed period.
    - **Validates: Requirements 1.5–1.6, 2.1, 2.4, 2.6–2.8**
  - [ ]* 4.3 Write the Eris PBT for **Property 2: Historical Preservation and Report Stability** with the exact tag `Feature: subscription-renewal, Property 2: Historical Preservation and Report Stability`.
    - Snapshot invoices, payments, attendance, completed sessions, report-source records and pre-confirmation report results; compare IDs, foreign keys, values and report output after both successful and failed renewals.
    - **Validates: Requirements 3.1–3.3**
  - [ ]* 4.4 Write the Eris PBT for **Property 6: Rejection and Persistent-Write Atomicity** with the exact tag `Feature: subscription-renewal, Property 6: Rejection and Persistent-Write Atomicity`.
    - Generate every invalid count/actor condition and inject failures at each required write boundary; compare complete pre-request snapshots of Subscription, enrollment, historical records, RenewalRecords and ClassSessions, including no audit row for rejection.
    - **Validates: Requirements 1.3–1.4, 2.5, 3.2, 3.5, 4.6, 5.3, 5.5, 6.2–6.3**

- [ ] 5. Add session-generation correctness and failure coverage
  - [ ]* 5.1 Write the Eris PBT for **Property 3: Complete Bounded Future Occurrence Evaluation** with the exact tag `Feature: subscription-renewal, Property 3: Complete Bounded Future Occurrence Evaluation`.
    - Run at least 100 conflict-free, no-injected-failure cases; independently compute expected schedule occurrences and assert the persisted identity union, one enrollment/source-schedule link per new session, and period-boundary inclusion.
    - **Validates: Requirements 4.1, 4.3, 4.8**
  - [ ]* 5.2 Write the Eris PBT for **Property 4: Session Identity Idempotency** with the exact tag `Feature: subscription-renewal, Property 4: Session Identity Idempotency`.
    - Generate arbitrary existing sessions and repeated requests; assert duplicates are skipped/rejected, grouped identity count remains one, and existing session IDs and values never change.
    - **Validates: Requirements 4.2, 5.4**
  - [ ]* 5.3 Write the Eris PBT for **Property 7: Recoverable Per-Occurrence Failure Continuation** with the exact tag `Feature: subscription-renewal, Property 7: Recoverable Per-Occurrence Failure Continuation`.
    - Inject arbitrary recoverable failures by occurrence; assert pre-attempt state and reason for each failed occurrence, continuation through all remaining occurrences, and at-most-once persistence for eligible occurrences.
    - **Validates: Requirements 4.4–4.5**
  - [ ]* 5.4 Add focused generator/action feature tests for no schedules, conflicts, duplicate identities, boundary dates, safe failure messages, per-occurrence counts and no mutation of historical/manual-session paths.
    - Include persistent failure rollback at session, date and RenewalRecord writes and verify the existing eight-week generator contract remains unchanged.
    - _Requirements: 4.1–4.8, 5.4–5.5, 6.1–6.4_

- [ ] 6. Verify repeated renewals, idempotency and concurrency
  - [ ]* 6.1 Add feature tests for linked-enrollment resolution, rejection diagnostics, immutable audit records and request retries.
    - Cover zero/multiple exact active enrollments with matching IDs, one enrollment retention, two deliberate request UUIDs creating two records, same UUID returning the existing result, and failed requests preserving prior RenewalRecords.
    - _Requirements: 2.1–2.7, 3.4–3.7, 5.2–5.3_
  - [ ]* 6.2 Write the Eris PBT for **Property 5: Sequential Renewal Composition and Audit Distinctness** with the exact tag `Feature: subscription-renewal, Property 5: Sequential Renewal Composition and Audit Distinctness`.
    - Generate two positive month counts, perform two successful renewals in order, and assert one linked enrollment, dates based on the first committed result, two distinct immutable records and the sequential reference date result.
    - **Validates: Requirements 3.4, 3.6, 5.1–5.2**
  - [ ]* 6.3 Add MySQL integration tests for same-subscription serialization, different-subscription parallel progress, lock timeout/deadlock handling, retryable unchanged-state errors and composite identity locking.
    - Assert the later same-subscription request reads the preceding committed dates while unrelated subscriptions do not share a long-running lock.
    - _Requirements: 5.6–5.7, 4.2, 4.6_

- [ ] 7. Add protected-module regression coverage without changing protected modules
  - [ ]* 7.1 Add deterministic **Property 8: Protected-Module Regression Examples** integration coverage after a successful renewal.
    - Use existing Calendar event feed/render/resource contracts, Dashboard response/report/render contracts and Bulk authorization/dependency/audit contracts; assert they remain unchanged and future enrollment-backed sessions are naturally visible through existing queries.
    - This is example-based, not PBT, and has no generated renewal-property requirement.
    - **Validates: Requirements 3.3, 4.1, 6.5; Property 8 in `requirements.md`**
  - [ ]* 7.2 Add explicit non-goal regression tests for historical and protected behavior.
    - Assert renewal does not reset subscription fields, create/mutate payments or invoices, alter attendance/completed sessions/report-source rows, create an enrollment, add protected routes, or introduce inline presentation/business logic in the renewal view.
    - _Requirements: 2.4, 2.8, 3.1–3.3, 4.6, 5.5, Out of Scope_

- [ ] 8. Wire the complete feature and run the acceptance suite
  - [ ] 8.1 Register migrations, model/relation bindings, policy mapping, named routes, action/generator services, DTO/resource presentation and renewal view integration.
    - Confirm the final path is Form Request → Policy → Controller → Action → locked resolver/calculator/generator → RenewalRecord/result, with no orphaned class or unreferenced route.
    - _Requirements: 1.1–1.8, 2.1–2.8, 3.4–3.7, 4.1–4.8_
  - [ ]* 8.2 Add the final automated acceptance command/configuration for unit, feature, Eris PBT, MySQL integration and protected-module regression suites.
    - Require at least 100 cases for each P1–P7, isolated persistent state, exact property tags, unified counterexample output containing generated state/count/date/schedules/seed, and smoke checks for unique request UUID and immutable RenewalRecord mutation paths.
    - _Requirements: 6.1–6.5_
  - [ ] 8.3 Checkpoint - Ensure all tests pass, ask the user if questions arise.
    - Run the relevant PHPUnit/Laravel test suites and static checks; do not modify `requirements.md` or `design.md`, protected modules, or application behavior outside the approved renewal scope.

## Notes

- Tasks marked with `*` are optional test tasks and may be skipped for a faster MVP; core implementation tasks are not optional.
- Every P1–P7 property has its own Eris task, at least 100 generated applicable cases, isolated persistent state, and the exact design traceability tag. P8 is intentionally deterministic example-based regression coverage.
- Test generators must use an independent occurrence oracle and normalized database snapshots; failure output must preserve the generated state and seed needed for reproduction.
- Preserve existing Laravel architecture: thin controllers, Form Requests, Policy/Gate authorization, Eloquent/query-builder bindings, `$fillable`, eager loading, transactions, no raw SQL concatenation, no business logic in Blade, and no inline style/handler.
- Do not change Calendar, Dashboard or Bulk implementation. Do not add invoice/payment/attendance side effects, new enrollments, historical rewrites, destructive database commands, deployment steps or documentation-only tasks.
- The implementation language is PHP/Laravel as specified by `design.md`; no language-selection question is required.
- After this file is created, open `tasks.md` and click **Start task** next to an item to begin implementation.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3"] },
    { "id": 1, "tasks": ["2.1", "2.2", "4.1"] },
    { "id": 2, "tasks": ["2.3", "3.1", "6.1"] },
    { "id": 3, "tasks": ["2.4", "3.2"] },
    { "id": 4, "tasks": ["3.3", "4.2", "4.3", "4.4", "5.1", "5.2", "5.3", "6.1"] },
    { "id": 5, "tasks": ["5.4", "6.2", "7.1", "7.2"] },
    { "id": 6, "tasks": ["6.3", "8.1"] },
    { "id": 7, "tasks": ["8.2"] }
  ]
}
```
