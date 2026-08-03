# Requirements Document

## Introduction

This feature changes **Subscription Management** renewal only. An authorized administrator can renew an existing Subscription for a positive whole number of calendar months, extend the same Linked Enrollment, and create only newly required future ClassSessions. The feature preserves historical payments, attendance, completed ClassSessions, invoices, and report results.

### Investigated Baseline

- A Subscription is unique per Student, Teacher, and Instrument and has no enrollment identifier.
- A StudentEnrollment owns recurring schedules, enrollment-backed ClassSessions, and invoices.
- `SessionGeneratorService` skips an existing `(enrollment_id, session_date, start_time)` occurrence and skips scheduling conflicts.
- Historical reports and student history resolve completed sessions, attendance, and invoices through the existing enrollment and existing session identifiers.
- The codebase has no documented upper limit for a renewal-month count.

### Scope Boundaries

- This feature changes Subscription Management and the renewal workflow only.
- The feature extends the existing Linked Enrollment; it does not replace the Linked Enrollment with a newly created enrollment.
- Calendar rendering, Dashboard behavior or rendering, and Bulk functionality are unchanged. Targeted regression examples may verify their existing contracts after a successful renewal.

## Glossary

- **Subscription Management**: The authorized administrative area that displays and renews Subscriptions.
- **Subscription**: The existing unique Student-Teacher-Instrument subscription record.
- **Linked Enrollment**: The one non-deleted active StudentEnrollment whose Student, Teacher, and Instrument identifiers equal the Subscription identifiers.
- **Renewal Request**: An administrator-confirmed request to renew one Subscription for a Positive Whole-Month Count.
- **Positive Whole-Month Count**: An integer greater than or equal to one. Subscription Management defines no upper limit for this count.
- **Renewal Month**: One calendar month. Calendar arithmetic retains the day number when valid and uses the last valid day of the target month otherwise; the same rule applies for every month advanced in a multi-month calculation.
- **Confirmation Date**: The calendar date, in the application business timezone, on which an authorized administrator confirms a Renewal Request. The confirmation timestamp records the corresponding instant.
- **Current Subscription Renewal Date**: The Subscription `renewal_date` value persisted immediately before the Renewal Request; a null value contributes no candidate to the maximum.
- **Current Linked Enrollment End Date**: The Linked Enrollment `ended_at` value persisted immediately before the Renewal Request; a null value contributes no candidate to the maximum.
- **Renewal Start Date**: The maximum of the Confirmation Date, the Current Subscription Renewal Date, and the day after the Current Linked Enrollment End Date, considering only non-null candidates.
- **Renewal End Date**: The calendar day immediately before the Renewal Start Date advanced by the Positive Whole-Month Count using the Renewal Month rule.
- **Next Renewal Date**: The calendar day immediately after the Renewal End Date.
- **Renewal Period**: The inclusive calendar-date interval from the Renewal Start Date through the Renewal End Date.
- **Recurring Schedule**: An active weekly lesson schedule belonging to the Linked Enrollment.
- **Future Session Occurrence**: A scheduled lesson date and start time produced from an active Recurring Schedule within the Renewal Period.
- **ClassSession**: An existing or newly scheduled lesson record associated with an enrollment.
- **Session Identity**: The combination `(enrollment_id, session_date, start_time)`.
- **Scheduling Conflict**: A teacher, room, or enrollment-time overlap detected for a Future Session Occurrence.
- **Blocking Condition**: A Scheduling Conflict or validated rule that prevents a Future Session Occurrence from being created.
- **Recoverable Generation Failure**: A validation, business-rule, availability, or unexpected generation failure that occurs before the Future Session Occurrence changes persistent state.
- **Persistent Write Failure**: A failure to create or update a required Subscription, Linked Enrollment, Renewal Record, or ClassSession in durable storage.
- **Pre-Attempt State**: The persisted state immediately before creating one Future Session Occurrence. A recoverable occurrence failure leaves this state unchanged for that occurrence.
- **Successful Renewal**: A Renewal Request that commits the Subscription and Linked Enrollment date changes and one Renewal Record. A Successful Renewal may report skipped occurrences, blocking conditions, and Recoverable Generation Failures; those per-occurrence outcomes do not roll back committed renewal dates.
- **Persistent-State Rollback**: Restoration of the complete persisted Subscription, Linked Enrollment, Historical Record Set, Renewal Record set, and ClassSession set to the state immediately before the Renewal Request.
- **Rejection Reason Code**: A machine-readable explanation of a rejected Renewal Request. This feature defines `INVALID_MONTH_COUNT`, `UNAUTHORIZED`, `NO_ENROLLMENT`, and `MULTIPLE_ENROLLMENTS` for the corresponding rejection conditions.
- **Renewal Record**: An immutable audit entry that identifies one successful Renewal Request and its resulting dates.
- **Historical Record Set**: The payment entries, attendance entries, completed ClassSessions, invoices, and report-source records associated with the Linked Enrollment before a Renewal Request.
- **Renewal Verification Suite**: The automated property-based and example-based tests for Subscription Management renewal.

## Requirements

### Requirement 1: Renewal Request and Duration

**User Story:** As an authorized administrator, I want to renew a Subscription for one or more calendar months, so that a student’s current lesson arrangement can continue without re-enrollment.

#### Acceptance Criteria

1. WHEN an authorized administrator selects a Subscription and provides a Positive Whole-Month Count in Subscription Management, THE Subscription Management SHALL offer a Renewal Request for that count.
2. WHEN an authorized administrator confirms a Renewal Request with a Positive Whole-Month Count, THE Subscription Management SHALL submit the selected Subscription and count for renewal.
3. IF a Renewal Request has a missing, zero, negative, fractional, or non-numeric month count, THEN THE Subscription Management SHALL reject the Renewal Request with the Rejection Reason Code `INVALID_MONTH_COUNT` before changing persistent records.
4. IF a Renewal Request is submitted by an unauthenticated or unauthorized administrator, THEN THE Subscription Management SHALL reject the Renewal Request with the Rejection Reason Code `UNAUTHORIZED` before changing persistent records.
5. WHEN Subscription Management calculates dates for a valid Renewal Request, THE Subscription Management SHALL set the Renewal Start Date to the maximum of all non-null values among the Confirmation Date, Current Subscription Renewal Date, and the day after the Current Linked Enrollment End Date.
6. WHEN Subscription Management calculates dates for a valid Renewal Request, THE Subscription Management SHALL calculate the Renewal End Date by advancing the Renewal Start Date by the Positive Whole-Month Count using the Renewal Month rule and subtracting one calendar day, and SHALL set the Next Renewal Date to the following calendar day.
7. WHEN a Successful Renewal completes, THE Subscription Management SHALL display the Renewal Start Date, Renewal End Date, Next Renewal Date, and counts of Future Session Occurrences created, skipped, blocked, and failed.
8. IF a Renewal Request fails, THEN THE Subscription Management SHALL display the failure reason and the unchanged renewal state.

### Requirement 2: Existing Enrollment Extension

**User Story:** As an authorized administrator, I want a renewal to extend the current enrollment, so that the student continues under the same academic and financial history.

#### Acceptance Criteria

1. WHEN Subscription Management processes a valid Renewal Request, THE Subscription Management SHALL identify exactly one Linked Enrollment.
2. IF no Linked Enrollment exists for a Renewal Request, THEN THE Subscription Management SHALL reject the Renewal Request and set the Rejection Reason Code to `NO_ENROLLMENT`.
3. IF more than one Linked Enrollment exists for a Renewal Request, THEN THE Subscription Management SHALL reject the Renewal Request, set the Rejection Reason Code to `MULTIPLE_ENROLLMENTS`, and identify each matching enrollment record requiring administrator resolution.
4. WHEN a Linked Enrollment is identified for a successful Renewal Request, THE Subscription Management SHALL retain the Linked Enrollment identifier, Student identifier, Teacher identifier, Instrument identifier, start date, and active status.
5. IF Subscription Management cannot retain a required Linked Enrollment identifier, relationship, start date, or active status during a Renewal Request, THEN THE Subscription Management SHALL fail the Renewal Request and restore the pre-request persistent state.
6. WHEN a Renewal Request succeeds, THE Subscription Management SHALL set the Linked Enrollment end date to the Renewal End Date.
7. WHEN a Renewal Request succeeds, THE Subscription Management SHALL set the Subscription renewal date to the Next Renewal Date.
8. WHEN a Renewal Request succeeds, THE Subscription Management SHALL retain the Subscription session allocation, session usage, fee, payment status, and existing notes.

### Requirement 3: Historical Record Preservation and Auditability

**User Story:** As an authorized administrator, I want renewed Subscriptions to retain prior operational and financial history, so that renewal does not corrupt student records or historical reporting.

#### Acceptance Criteria

1. WHEN a Renewal Request succeeds, THE Subscription Management SHALL retain every Historical Record Set record with its existing identifier, relationships, and stored values.
2. IF a Renewal Request would fail to retain a Historical Record Set record, THEN THE Subscription Management SHALL block renewal completion and restore the pre-request persistent state.
3. WHEN a historical report query is limited to dates before the Confirmation Date and a Renewal Request is processing, succeeds, or fails, THE Subscription Management SHALL return the same report result before and after the Renewal Request.
4. WHEN a Renewal Request succeeds, THE Subscription Management SHALL create one Renewal Record containing the Subscription identifier, Linked Enrollment identifier, Positive Whole-Month Count, Renewal Start Date, Renewal End Date, confirmation timestamp, and confirming administrator identifier.
5. IF a confirming administrator identifier is missing or invalid, THEN THE Subscription Management SHALL block the Renewal Request with the Rejection Reason Code `UNAUTHORIZED` before creating a Renewal Record or changing persistent records.
6. WHILE a Renewal Record exists, THE Subscription Management SHALL retain the Renewal Record values as an audit history of the successful Renewal Request.
7. IF a Renewal Request fails, THEN THE Subscription Management SHALL retain every preceding Renewal Record as audit history.

### Requirement 4: Future Session Generation Without Duplication

**User Story:** As an authorized administrator, I want renewal to create the required upcoming lessons, so that the extended enrollment has an operational schedule without changing prior lessons.

#### Acceptance Criteria

1. WHEN a Successful Renewal completes and the Linked Enrollment has an active Recurring Schedule, THE Subscription Management SHALL evaluate every Future Session Occurrence in the Renewal Period.
2. WHEN a Future Session Occurrence has a Session Identity matching an existing ClassSession, THE Subscription Management SHALL retain the existing ClassSession and mark the Future Session Occurrence as skipped.
3. WHEN a Future Session Occurrence has no existing Session Identity, has no Blocking Condition, and does not produce a Recoverable Generation Failure, THE Subscription Management SHALL create one scheduled ClassSession associated with the existing Linked Enrollment and source Recurring Schedule.
4. WHEN a Future Session Occurrence has a Blocking Condition, THE Subscription Management SHALL leave the Future Session Occurrence uncreated and include the blocking reason in the renewal result.
5. IF a Recoverable Generation Failure occurs for a Future Session Occurrence, THEN THE Subscription Management SHALL retain the Pre-Attempt State, include the failure reason in the renewal result, and continue evaluating every remaining Future Session Occurrence.
6. IF a Persistent Write Failure occurs during a Renewal Request, THEN THE Subscription Management SHALL perform Persistent-State Rollback for every change made during the Renewal Request.
7. WHEN the Linked Enrollment has no active Recurring Schedule, THE Subscription Management SHALL complete the enrollment extension and report zero Future Session Occurrences created.
8. WHEN Subscription Management creates a ClassSession for a Renewal Request, THE Subscription Management SHALL assign a session date in the Renewal Period.

### Requirement 5: Repeated Renewal and Transactional Consistency

**User Story:** As an authorized administrator, I want to renew the same Subscription repeatedly, so that continuous enrollment can be managed without duplicate schedules or fragmented history.

#### Acceptance Criteria

1. WHEN an authorized administrator confirms a later Renewal Request for a previously renewed Subscription, THE Subscription Management SHALL calculate the Renewal Start Date as the documented maximum of the Confirmation Date, existing Subscription renewal date, and day after the Current Linked Enrollment End Date.
2. WHEN a later Renewal Request succeeds, THE Subscription Management SHALL retain the same Linked Enrollment identifier and create a distinct Renewal Record.
3. IF a Renewal Request fails, THEN THE Subscription Management SHALL create no Renewal Record and retain the pre-request Linked Enrollment identifier.
4. WHEN a Renewal Request would create a ClassSession whose Session Identity matches an existing ClassSession, THE Subscription Management SHALL reject the duplicate creation and retain the existing ClassSession.
5. IF a Persistent Write Failure occurs during a Renewal Request, THEN THE Subscription Management SHALL restore the pre-request Subscription, Linked Enrollment, Historical Record Set, Renewal Record set, and ClassSession set.
6. WHEN concurrent Renewal Requests target the same Subscription, THE Subscription Management SHALL serialize the requests so that each successful Renewal Request uses the dates persisted by the preceding successful Renewal Request.
7. WHEN concurrent Renewal Requests target different Subscriptions, THE Subscription Management SHALL permit the requests to proceed in parallel and calculate each Renewal Start Date from the latest persisted dates of the corresponding Subscription and Linked Enrollment.

### Requirement 6: Automated Correctness Verification

**User Story:** As a maintainer, I want executable renewal checks, so that date handling, data preservation, repeated renewals, and generated-session behavior remain correct across varied inputs.

#### Acceptance Criteria

1. WHEN the Renewal Verification Suite runs property-based tests, THE Renewal Verification Suite SHALL execute Properties P1 through P7 with at least 100 generated applicable cases per property in isolated persistent state.
2. WHEN the Renewal Verification Suite generates an invalid Renewal Request, THE Renewal Verification Suite SHALL verify that the rejected operation preserves the pre-request persistent state.
3. WHEN the Renewal Verification Suite simulates a failure while rejecting an invalid Renewal Request, THE Renewal Verification Suite SHALL verify that the rejection preserves the pre-request persistent state.
4. WHEN a property-based test fails, THE Renewal Verification Suite SHALL produce one unified counterexample report containing the generated Subscription state, Linked Enrollment state, Positive Whole-Month Count, Confirmation Date, schedule set, and random seed needed to reproduce the counterexample.
5. WHEN targeted regression examples run after a successful Renewal Request, THE Renewal Verification Suite SHALL verify that Calendar rendering, Dashboard behavior and rendering, and Bulk functionality retain their existing contracts.

## Executable Correctness Properties

The Renewal Verification Suite must implement the following properties against a transaction-isolated database with factories or generators for Subscriptions, Linked Enrollments, Recurring Schedules, ClassSessions, invoices, payments, attendance, dates, and Positive Whole-Month Counts. P1 through P7 require at least 100 runs each. P8 is deterministic because it verifies fixed module contracts rather than input-variable renewal logic.

### P1: Duration and Same-Enrollment Extension

For every valid Subscription `s`, Linked Enrollment `e`, Confirmation Date `d`, and Positive Whole-Month Count `m`, a successful renewal retains `e.id`, sets `start` to the maximum of `d`, the existing `s.renewal_date`, and the day after the current `e.ended_at` when `e.ended_at` exists, sets `e.ended_at` to the calendar day immediately before `start` advanced by `m` Renewal Months, and sets `s.renewal_date` to the day after `e.ended_at`. The renewal retains `s.sessions_allocated`, `s.sessions_used`, `s.monthly_fee`, `s.payment_status`, and existing notes.

### P2: Historical Preservation

For every valid renewal input, snapshot the Historical Record Set and the results of report queries whose date ranges end before the Confirmation Date. During and after renewal, every snapshot record has the same identity, foreign keys, and stored values, and every snapshotted report result is equal.

### P3: Future Session Completeness

For every valid renewal input with conflict-free active Recurring Schedules and no injected generation failures, the persisted future Session Identities after renewal equal the union of the pre-existing Session Identities and exactly one identity for every schedule occurrence in the Renewal Period.

### P4: Duplicate Prevention

For every valid renewal input with arbitrary pre-existing ClassSessions and repeated renewal operations, each attempted duplicate Session Identity is rejected, the count of persisted ClassSessions grouped by Session Identity equals one for every group, and existing ClassSession identifiers and values remain unchanged.

### P5: Sequential Renewal Composition

For every valid Subscription and two Positive Whole-Month Counts `m1` and `m2`, applying renewals sequentially for `m1` and then `m2` retains one Linked Enrollment identifier, creates two distinct Renewal Records, and produces a final Next Renewal Date equal to the date obtained by applying the documented Renewal Start Date and Renewal End Date rules twice in order.

### P6: Rejection and Required-Write Atomicity

For every invalid Renewal Request, every injected failure while rejecting an invalid Renewal Request, and every injected Persistent Write Failure during a valid Renewal Request, the persisted Subscription, Linked Enrollment, Historical Record Set, Renewal Record set, and ClassSession set equal the state captured immediately before the operation.

### P7: Recoverable Per-Occurrence Failure Continuation

For every valid renewal input with an arbitrary subset of conflict-free Future Session Occurrences configured to have a Recoverable Generation Failure, the renewal retains the Pre-Attempt State for each failed occurrence, reports each failure reason, and persists every remaining eligible occurrence exactly once.

### P8: Protected-Module Regression Examples

For representative existing Calendar, Dashboard, and Bulk workflows, executing a successful renewal leaves established response or rendering contracts unchanged. These are targeted example-based integration tests, not property tests, because these workflows do not vary meaningfully with renewal inputs.

## Out of Scope

- Modifying Calendar rendering.
- Modifying Dashboard behavior or rendering.
- Modifying Bulk functionality.
- Creating, editing, deleting, or reconciling payments or invoices as a renewal side effect.
- Replacing a Linked Enrollment or modifying completed ClassSessions, attendance entries, invoices, or historical report-source records.
