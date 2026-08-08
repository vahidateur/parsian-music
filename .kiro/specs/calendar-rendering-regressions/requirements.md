# Requirements Document

## Introduction

This specification defines acceptance outcomes for current rendering regressions in the existing Admin_Calendar. The scope is limited to making existing persisted sessions discoverable and readable in the existing FullCalendar month, week, and day views. This document defines observable calendar outcomes and focused verification evidence; it does not define architecture, design, implementation, code, schema, or new user capabilities.

## Glossary

- **Admin_Calendar**: The existing calendar available to authorized administrators.
- **Calendar_Rendering_Owner**: The Admin_Calendar boundary that transforms Renderable_Sessions into FullCalendar Calendar_Events and presents the results.
- **FullCalendar**: The existing calendar view component used by the Admin_Calendar.
- **Persisted_Session**: An existing session that has been successfully saved and is available to the Admin_Calendar.
- **Renderable_Session**: A Persisted_Session with an identifier, date, start time, a duration of 30 or 60 minutes, Student_Display_Name, and Teacher_Display_Name required by the current Admin_Calendar contract.
- **Calendar_Event**: The FullCalendar representation of one Renderable_Session, identified by the Persisted_Session identifier.
- **Calendar_Event_Conversion**: The existing transformation that determines whether a Renderable_Session produces a Calendar_Event.
- **Calendar_Timezone**: The single configured IANA timezone used by the Admin_Calendar to interpret session dates and times.
- **Calendar_Day**: The Gregorian day containing a Calendar_Event start time when interpreted in the Calendar_Timezone.
- **Event_Touched_Day**: A Calendar_Day whose interval intersects a Calendar_Event time interval in the Calendar_Timezone.
- **Displayed_Range**: The interval reported by FullCalendar for the active month, week, or day view, with an inclusive start instant and an exclusive end instant in the Calendar_Timezone.
- **Displayed_Range_Membership**: The condition in which a Renderable_Session time interval intersects the Displayed_Range interval, including the Displayed_Range start instant and excluding the Displayed_Range end instant.
- **Active_Filters**: The unchanged set of existing calendar filters applied to a FullCalendar view.
- **Visible_Event_Set**: The set of Calendar_Event identifiers discoverable in the active view without changing the Displayed_Range or Active_Filters.
- **Event_Visibility_Affordance**: A directly rendered Calendar_Event or an operable existing FullCalendar overflow control that reveals a Calendar_Event.
- **Student_Display_Name**: The student name supplied for a Renderable_Session.
- **Teacher_Display_Name**: The teacher name supplied for a Renderable_Session.
- **Compact_Event_Presentation**: The existing duration-adaptive presentation used for a rendered Calendar_Event with a duration shorter than 60 minutes.
- **Standard_Event_Presentation**: The existing duration-adaptive presentation used for a rendered Calendar_Event with a duration of 60 minutes or longer.
- **Readable_Name_Presentation**: Simultaneous visual presentation of the complete Student_Display_Name and complete Teacher_Display_Name without clipping, truncation, ellipsis, pointer hover, keyboard focus, or opening event details.
- **Jalali_Label**: The Jalali calendar date shown by the Admin_Calendar for a Calendar_Day.
- **Confirmed_Jalali_Association**: A confirmed relationship between a Jalali_Label and the corresponding Gregorian Calendar_Day in the Calendar_Timezone.
- **Timezone_Offset_Transition**: A change to the UTC offset applicable to the Calendar_Timezone.
- **First_Post_Persistence_Successful_Refresh**: The earliest event refresh that completes successfully after a Persisted_Session is saved.
- **External_Owner**: The team responsible for a dependency outside the Calendar_Rendering_Owner boundary.
- **Focused_Regression_Case**: A named case in the Focused Regression Tests section.
- **Verification_Record**: The result for one Focused_Regression_Case, containing the test ID, fixture identifiers, active view, Displayed_Range, Calendar_Timezone, Active_Filters, expected identifier set, observed identifier set, overflow-disclosed identifiers, pass or fail status, and first failing ownership boundary when the case fails.

## Scope

The scope includes only current Admin_Calendar rendering regressions: inclusive-start and exclusive-end Displayed_Range membership, deterministic visibility of a newly persisted session after the First_Post_Persistence_Successful_Refresh, overlapping-event discoverability, month/week/day equivalence, Jalali/Gregorian/Calendar_Timezone day-and-time invariants, idempotent FullCalendar re-rendering, and duration-adaptive readable presentation of existing 30-minute and 60-minute Calendar_Events with student and teacher names.

## Non-Scope

The scope excludes architecture, design, implementation, source-code changes, schema changes, subscription logic, Session business rules, session creation or persistence rules, Bulk functionality, Dashboard behavior, authorization-policy changes, unrelated user-interface work, visual redesign, and new user capabilities.

## Ownership Boundaries

Calendar_Rendering_Owner owns observable conversion, discovery, and readable presentation outcomes for Renderable_Sessions in the active Displayed_Range. Session persistence, subscription decisions, Session business rules, Bulk workflows, Dashboard workflows, authorization policy, and unrelated interface ownership remain external dependencies. An issue whose first failing boundary is outside Calendar_Rendering_Owner requires a separate approved specification before this scope expands.

## Requirements

### Requirement 1: Inclusive-Start and Exclusive-End Session Visibility

**User Story:** As an administrator, I want each applicable existing session to appear once in the calendar range, so that the displayed schedule is complete and has unambiguous range boundaries.

#### Acceptance Criteria

1. WHEN a successful event refresh completes for a Displayed_Range, THE Calendar_Rendering_Owner SHALL create exactly one Calendar_Event, identified by the Persisted_Session identifier, for each Renderable_Session whose time interval has Displayed_Range_Membership and whose Calendar_Event_Conversion succeeds, and SHALL not create duplicate Calendar_Events for one Persisted_Session identifier.
2. WHEN a successful event refresh completes for a Displayed_Range, THE Calendar_Rendering_Owner SHALL omit from the Visible_Event_Set every Persisted_Session identifier whose time interval lacks Displayed_Range_Membership or whose Calendar_Event_Conversion fails, and SHALL not create a Calendar_Event for an identifier whose conversion fails.
3. WHEN an existing Renderable_Session has Displayed_Range_Membership and a successful Calendar_Event_Conversion, THE Calendar_Rendering_Owner SHALL include the Persisted_Session identifier exactly once in the Visible_Event_Set.
4. WHEN a Persisted_Session starts at the inclusive start instant of a Displayed_Range, THE Calendar_Rendering_Owner SHALL include the Persisted_Session identifier exactly once in the Visible_Event_Set when Calendar_Event_Conversion succeeds, and SHALL omit the identifier and SHALL not create its Calendar_Event when Calendar_Event_Conversion fails.
5. WHEN a zero-duration Displayed_Range has equal start and end instants, THE Calendar_Rendering_Owner SHALL omit every Renderable_Session identifier from the Visible_Event_Set and SHALL not create a Calendar_Event, because no session interval has Displayed_Range_Membership in an empty half-open range.
6. WHEN a Persisted_Session starts at the exclusive end instant of a Displayed_Range, THE Calendar_Rendering_Owner SHALL omit the Persisted_Session identifier from the Visible_Event_Set and SHALL not create its Calendar_Event regardless of the Calendar_Event_Conversion outcome.

### Requirement 2: Deterministic New-Session Visibility

**User Story:** As an administrator, I want a newly persisted applicable session to appear after the first completed refresh, so that a successful save is reflected deterministically in the current calendar.

#### Acceptance Criteria

1. WHEN a newly Persisted_Session is saved and the First_Post_Persistence_Successful_Refresh completes, THE Calendar_Rendering_Owner SHALL include the Persisted_Session identifier exactly once in the Visible_Event_Set if the session has Displayed_Range_Membership and Calendar_Event_Conversion succeeds.
2. WHEN the First_Post_Persistence_Successful_Refresh completes for a newly Persisted_Session without Displayed_Range_Membership, THE Calendar_Rendering_Owner SHALL omit the Persisted_Session identifier from the Visible_Event_Set and SHALL not create its Calendar_Event.
3. IF Calendar_Event_Conversion fails for a newly Persisted_Session at the First_Post_Persistence_Successful_Refresh, THEN THE Calendar_Rendering_Owner SHALL atomically remove the Persisted_Session identifier from the Visible_Event_Set, SHALL not expose the identifier as a Calendar_Event during that failed-conversion refresh, SHALL record the identifier and failed-conversion status in the Verification_Record for auditing and manual review, and SHALL retain that identifier and status in the Verification_Record even if a later conversion succeeds.
4. IF any failed-conversion handling change cannot complete, including when rollback of that handling fails, THEN THE Calendar_Rendering_Owner SHALL attempt to roll back all failed-conversion handling changes and SHALL record a FAIL status for the affected Focused_Regression_Case.
5. WHEN a later successful refresh converts a previously failed-conversion Persisted_Session and the session has Displayed_Range_Membership, THE Calendar_Rendering_Owner SHALL include the Persisted_Session identifier exactly once in the Visible_Event_Set while retaining the earlier failed-conversion identifier and status in the Verification_Record.

### Requirement 3: Duration-Adaptive Readable Name Presentation

**User Story:** As an administrator, I want existing short and standard classes to show both names readably, so that I can identify each class without opening details.

#### Acceptance Criteria

1. WHEN a rendered Calendar_Event has a duration greater than 0 and shorter than 60 minutes, THE Calendar_Rendering_Owner SHALL use only the Compact_Event_Presentation.
2. WHEN a rendered Calendar_Event has a duration greater than 0 and shorter than 60 minutes, THE Calendar_Rendering_Owner SHALL provide a Readable_Name_Presentation containing the complete Student_Display_Name and complete Teacher_Display_Name simultaneously.
3. WHEN a rendered Calendar_Event has a duration of 60 minutes or longer, THE Calendar_Rendering_Owner SHALL use the Standard_Event_Presentation, including when the duration is exactly 60 minutes.
4. WHEN a rendered Calendar_Event has a duration of 60 minutes or longer, THE Calendar_Rendering_Owner SHALL provide a Readable_Name_Presentation containing the complete Student_Display_Name and complete Teacher_Display_Name simultaneously.

### Requirement 4: Complete Overlapping-Event Discoverability

**User Story:** As an administrator, I want every overlapping class to remain discoverable, so that concurrent classes are not mistaken for missing sessions.

#### Acceptance Criteria

1. WHEN two or more successfully converted Calendar_Events form an overlap group by sharing any time interval and at least one Calendar_Event in the group has Displayed_Range_Membership, THE Calendar_Rendering_Owner SHALL include exactly once in the Visible_Event_Set every successfully converted Calendar_Event identifier in that overlap group, including identifiers for events whose intervals extend outside the Displayed_Range, without a performance or selective-visibility exception.
2. IF FullCalendar conceals one or more overlapping Calendar_Events behind an Event_Visibility_Affordance, THEN THE Calendar_Rendering_Owner SHALL provide one operable Event_Visibility_Affordance that represents each concealed Calendar_Event, without requiring a prior request for concealed events.
3. WHEN an Event_Visibility_Affordance is operated, THE Calendar_Rendering_Owner SHALL reveal every Calendar_Event identifier represented by that Event_Visibility_Affordance and SHALL make each revealed identifier discoverable.
4. WHEN a successful event refresh completes for an active Displayed_Range, THE Calendar_Rendering_Owner SHALL expose through the Visible_Event_Set every identifier whose Calendar_Event has Displayed_Range_Membership and successful Calendar_Event_Conversion exactly once, including identifiers in an overlap group, without a performance or selective-visibility exception.

### Requirement 5: Month, Week, and Day View Equivalence

**User Story:** As an administrator, I want applicable sessions to be equivalently available in month, week, and day views, so that changing views does not change the schedule.

#### Acceptance Criteria

1. WHEN a successful event refresh completes for an active month view, THE Calendar_Rendering_Owner SHALL include exactly once in the month Visible_Event_Set the Persisted_Session identifier for each Renderable_Session that has Displayed_Range_Membership and a successful Calendar_Event_Conversion.
2. WHEN a successful event refresh completes for an active week view, THE Calendar_Rendering_Owner SHALL include exactly once in the week Visible_Event_Set the Persisted_Session identifier for each Renderable_Session that has Displayed_Range_Membership and a successful Calendar_Event_Conversion.
3. WHEN a successful event refresh completes for an active day view, THE Calendar_Rendering_Owner SHALL include exactly once in the day Visible_Event_Set the Persisted_Session identifier for each Renderable_Session that has Displayed_Range_Membership and a successful Calendar_Event_Conversion.
4. WHEN the month, week, and day views each display the same Calendar_Day with identical Active_Filters and Calendar_Timezone, THE Calendar_Rendering_Owner SHALL, at completion of each view's successful event refresh, expose the same set of Persisted_Session identifiers in all three applicable Visible_Event_Sets for Renderable_Sessions whose Event_Touched_Day is that Calendar_Day and whose Calendar_Event_Conversion succeeds.
5. WHEN Calendar_Event_Conversion fails for a Renderable_Session while the month, week, and day views display the same Calendar_Day with identical Active_Filters and Calendar_Timezone, THE Calendar_Rendering_Owner SHALL remove the Persisted_Session identifier from all three applicable Visible_Event_Sets in one atomic observable update and SHALL not expose that identifier in any of those views during an intermediate update.

### Requirement 6: Jalali, Gregorian, and Calendar-Timezone Invariants

**User Story:** As an administrator, I want Jalali labels and event placement to identify the same day and time, so that calendar navigation neither hides nor moves sessions.

#### Acceptance Criteria

1. WHEN the Admin_Calendar displays a Jalali_Label for a Calendar_Day, THE Calendar_Rendering_Owner SHALL associate that Jalali_Label with exactly one Gregorian Calendar_Day having the same local date in the Calendar_Timezone.
2. IF a Jalali_Label does not have exactly one Gregorian Calendar_Day association, THEN THE Calendar_Rendering_Owner SHALL display the Gregorian Calendar_Day without a Jalali_Label.
3. WHEN an administrator navigates from a Jalali_Label with a Confirmed_Jalali_Association, THE Calendar_Rendering_Owner SHALL request and render a Displayed_Range whose interval intersects the complete local-day interval of the associated Gregorian Calendar_Day in the Calendar_Timezone.
4. IF a Jalali_Label lacks a Confirmed_Jalali_Association or identifies more than one Gregorian Calendar_Day, THEN THE Calendar_Rendering_Owner SHALL block navigation from that Jalali_Label and SHALL not request or render a Displayed_Range as a result of that navigation.
5. WHEN a Renderable_Session is transformed into a Calendar_Event and Calendar_Event_Conversion succeeds, THE Calendar_Rendering_Owner SHALL preserve exactly the Renderable_Session Gregorian Calendar_Day, local start time, and duration when interpreted in the Calendar_Timezone.
6. IF a Renderable_Session cannot be transformed while preserving its Gregorian Calendar_Day, local start time, or duration in the Calendar_Timezone, THEN THE Calendar_Rendering_Owner SHALL fail the Calendar_Event_Conversion, SHALL not create or render a Calendar_Event for that Renderable_Session, and SHALL leave the Renderable_Session unchanged.
7. WHEN a Timezone_Offset_Transition occurs in the Calendar_Timezone, THE Calendar_Rendering_Owner SHALL preserve each unchanged Renderable_Session’s local Gregorian Calendar_Day, local start time, and duration in the Calendar_Timezone; IF the original local start time is invalid after the transition, THE Calendar_Rendering_Owner SHALL use a valid local start time on the same Gregorian Calendar_Day and SHALL preserve the duration.
8. IF a Timezone_Offset_Transition makes a Renderable_Session’s original local start time invalid and THE Calendar_Rendering_Owner cannot determine a valid local start time on the same Gregorian Calendar_Day, THEN THE Calendar_Rendering_Owner SHALL not create or render a Calendar_Event for that Renderable_Session and SHALL record a fail status for the affected Focused_Regression_Case.
9. WHEN FullCalendar changes view with unchanged Active_Filters and Calendar_Timezone, THE Calendar_Rendering_Owner SHALL preserve the same Gregorian Calendar_Day, local start time, and duration for each unchanged Calendar_Event that has Displayed_Range_Membership in the new view.

### Requirement 7: Idempotent FullCalendar Re-Rendering

**User Story:** As an administrator, I want repeated rendering of an unchanged calendar to preserve the schedule, so that navigation and refreshes do not make events disappear or duplicate.

#### Acceptance Criteria

1. WHEN FullCalendar completes a successful re-render of an unchanged Displayed_Range with identical Active_Filters and Calendar_Timezone, THE Calendar_Rendering_Owner SHALL expose a Visible_Event_Set equal to the Visible_Event_Set exposed by the immediately preceding successful render of that same Displayed_Range with those same Active_Filters and Calendar_Timezone.
2. WHEN FullCalendar completes each of two or more consecutive successful event refreshes for an unchanged Displayed_Range with identical Active_Filters and Calendar_Timezone, THE Calendar_Rendering_Owner SHALL expose no more than one Calendar_Event for each identifier in the Visible_Event_Set.
3. WHEN FullCalendar renders a Displayed_Range, THE Calendar_Rendering_Owner SHALL assign each Calendar_Event exactly one visibility state: visible if the Calendar_Event has Displayed_Range_Membership and satisfies every Active_Filter, or hidden otherwise.
4. WHEN a Calendar_Event time interval partially overlaps a Displayed_Range interval, THE Calendar_Rendering_Owner SHALL include the Calendar_Event identifier in the Visible_Event_Set if and only if the Calendar_Event has Displayed_Range_Membership and satisfies every Active_Filter, and SHALL omit the identifier otherwise.
5. IF a Calendar_Event has Displayed_Range_Membership and fails at least one Active_Filter, THEN THE Calendar_Rendering_Owner SHALL assign the Calendar_Event the hidden visibility state and SHALL omit its identifier from the Visible_Event_Set.

### Requirement 8: Focused Regression Verification Records

**User Story:** As a maintainer, I want focused regression evidence for each rendering outcome, so that the correction can be accepted without informal visual inspection.

#### Acceptance Criteria

1. WHEN execution of a Focused_Regression_Case reaches a pass or fail outcome, THE Calendar_Rendering_Owner SHALL create exactly one Verification_Record for that Focused_Regression_Case.
2. WHEN all applicable assertions for a Focused_Regression_Case succeed and its expected identifier set equals its observed identifier set, THE Calendar_Rendering_Owner SHALL record the expected identifier set, observed identifier set, rendered identifiers, overflow-disclosed identifiers, and PASS status in the Verification_Record.
3. IF any applicable assertion fails or the expected identifier set differs from the observed identifier set, THEN THE Calendar_Rendering_Owner SHALL record the expected identifier set, observed identifier set, FAIL status, and first failing ownership boundary in the Verification_Record, and SHALL not record PASS status for that case.
4. WHEN the existing-session range-membership Focused_Regression_Case executes, THE Calendar_Rendering_Owner SHALL verify both the inclusive-start and exclusive-end outcomes specified in Requirement 1 before assigning the case a pass or fail outcome.
5. WHEN the newly-persisted-session Focused_Regression_Case executes, THE Calendar_Rendering_Owner SHALL independently verify the First_Post_Persistence_Successful_Refresh outcome specified in Requirement 2, and failure of any other Focused_Regression_Case SHALL not prevent verification or recording of this case.
6. WHEN the duration-presentation Focused_Regression_Case executes with one 30-minute fixture and one 60-minute fixture, THE Calendar_Rendering_Owner SHALL verify the Readable_Name_Presentation outcome specified in Requirement 3 for both fixtures before assigning the case a pass or fail outcome.
7. WHEN the overlap Focused_Regression_Case executes with each fixture count from 2 through 10 sharing one time interval, THE Calendar_Rendering_Owner SHALL verify complete discoverability of every fixture identifier for each count before assigning the case a pass or fail outcome.
8. WHEN any month, week, day, Jalali, Gregorian, Calendar_Timezone, or re-rendering Focused_Regression_Case completes all applicable invariant assertions successfully, THE Calendar_Rendering_Owner SHALL verify the applicable invariants in Requirements 5 through 7 and record PASS status for that case before completing its Verification_Record.
9. WHEN a Focused_Regression_Case executes, THE Calendar_Rendering_Owner SHALL verify and record that case independently of every other Focused_Regression_Case, and failure of another Focused_Regression_Case SHALL not prevent verification or recording of the current case.
10. IF execution of an invariant Focused_Regression_Case does not complete or any applicable invariant assertion fails, THEN THE Calendar_Rendering_Owner SHALL record FAIL status for the affected Focused_Regression_Case and SHALL not record PASS status for that case.

### Requirement 9: Scope and Ownership Protection

**User Story:** As a maintainer, I want the regression correction to remain within Calendar_Rendering_Owner, so that unrelated product behavior remains unchanged.

#### Acceptance Criteria

1. WHEN a focused regression investigation identifies Calendar_Rendering_Owner as the first failing boundary, THE Calendar_Rendering_Owner SHALL allow correction and verification only for the observable outcomes defined in Requirements 1 through 8 and SHALL prevent execution when either the proposed correction or verification includes behavior outside those outcomes.
2. IF a focused regression investigation identifies an External_Owner as the first failing boundary, THEN THE Calendar_Rendering_Owner SHALL record the external dependency and External_Owner in the Verification_Record, SHALL prevent correction or verification outside the current scope, and SHALL permit scope expansion only after a separately approved specification is available.
3. WHEN focused regression verification completes, THE Calendar_Rendering_Owner SHALL compare each exercised behavior listed in the Non-Scope section with its pre-correction result, SHALL record each comparison as unchanged or changed, and SHALL not mark the scope verification as passing if any recorded comparison is changed.

## Focused Regression Tests

| Test ID | Regression fixture and action | Required observation |
|---|---|---|
| CRR-01 | Refresh a range with an eligible fixture crossing the inclusive start instant, an eligible fixture at the inclusive start instant with successful conversion, an eligible fixture at the inclusive start instant with failed conversion, and an eligible fixture at the exclusive end instant; repeat with a zero-duration Displayed_Range whose start equals its end. | The crossing and successful inclusive-start fixture identifiers are present exactly once; the failed-conversion and exclusive-end fixture identifiers are absent from the Visible_Event_Set; the failed-conversion identifier is recorded in the Verification_Record for auditing and manual review; every session starting at the zero-duration boundary has no Calendar_Event and is absent. |
| CRR-02 | Persist one eligible session in the active range and complete the First_Post_Persistence_Successful_Refresh. | The new fixture identifier is present exactly once. |
| CRR-03 | Render one existing 30-minute fixture and one existing 60-minute fixture with non-empty student and teacher names. | Each fixture uses its duration-adaptive presentation and displays both complete names simultaneously. |
| CRR-04 | Render fixture sets of 2, 3, 5, and 10 sessions sharing a time interval. | Every fixture identifier is directly discoverable or revealed by an operable Event_Visibility_Affordance. |
| CRR-05 | Inspect applicable month, week, and day views for the same Calendar_Day with identical Active_Filters and Calendar_Timezone. | Immediately after each view is displayed, each applicable Visible_Event_Set contains the same successfully converted fixture identifiers and omits failed-conversion identifiers. |
| CRR-06 | Navigate Jalali month-boundary and year-boundary fixtures through month, week, and day views, including a Jalali_Label with a broken Gregorian association. | Each valid Jalali_Label and Calendar_Event refers to the same Gregorian Calendar_Day, start time, and duration in the Calendar_Timezone; navigation from the broken association is blocked and does not request or render a Displayed_Range. |
| CRR-07 | Render applicable fixtures before, during, and after a Timezone_Offset_Transition, including a fixture whose original local time becomes invalid. | Each unchanged fixture retains its local Gregorian Calendar_Day, start time, and duration meaning in the Calendar_Timezone, and any invalid transition time is adjusted to a valid value. |
| CRR-08 | Re-render an unchanged Displayed_Range with unchanged Active_Filters and Calendar_Timezone. | The before-and-after Visible_Event_Set values are equal and contain no duplicate identifier. |

## Verification Requirements

- Each Focused_Regression_Case uses isolated fixtures for only the calendar rendering outcomes covered by this specification.
- Each Focused_Regression_Case proceeds independently; failure of another Focused_Regression_Case does not prevent verification of the current Focused_Regression_Case.
- Each Verification_Record identifies the fixture identifiers, active FullCalendar view, Displayed_Range, Calendar_Timezone, and Active_Filters used by the Focused_Regression_Case.
- Completion of this requirements phase requires review and approval of this document before any design or task document is created.
