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

1. WHEN a successful event refresh completes for a Displayed_Range, THE Calendar_Rendering_Owner SHALL create exactly one Calendar_Event for each Renderable_Session with Displayed_Range_Membership and a successful Calendar_Event_Conversion.
2. WHEN a successful event refresh completes for a Displayed_Range, THE Calendar_Rendering_Owner SHALL omit each Persisted_Session identifier that lacks Displayed_Range_Membership or a successful Calendar_Event_Conversion.
3. WHEN an existing Renderable_Session has Displayed_Range_Membership and a successful Calendar_Event_Conversion, THE Calendar_Rendering_Owner SHALL include the Persisted_Session identifier in the Visible_Event_Set.
4. WHEN a Persisted_Session starts at the inclusive start instant of a Displayed_Range and has a successful Calendar_Event_Conversion, THE Calendar_Rendering_Owner SHALL include the Persisted_Session identifier in the Visible_Event_Set.
5. WHEN a Persisted_Session starts at the exclusive end instant of a Displayed_Range, THE Calendar_Rendering_Owner SHALL omit the Persisted_Session identifier from the Visible_Event_Set regardless of the Calendar_Event_Conversion outcome.

### Requirement 2: Deterministic New-Session Visibility

**User Story:** As an administrator, I want a newly persisted applicable session to appear after the first completed refresh, so that a successful save is reflected deterministically in the current calendar.

#### Acceptance Criteria

1. WHEN a newly Persisted_Session has Displayed_Range_Membership and a successful Calendar_Event_Conversion at the First_Post_Persistence_Successful_Refresh, THE Calendar_Rendering_Owner SHALL include the Persisted_Session identifier exactly once in the Visible_Event_Set.
2. WHEN the First_Post_Persistence_Successful_Refresh completes for a newly Persisted_Session without Displayed_Range_Membership, THE Calendar_Rendering_Owner SHALL omit the Persisted_Session identifier from the Visible_Event_Set.
3. IF Calendar_Event_Conversion fails for a newly Persisted_Session at the First_Post_Persistence_Successful_Refresh, THEN THE Calendar_Rendering_Owner SHALL omit the Persisted_Session identifier from the Visible_Event_Set and record the identifier in the Verification_Record.

### Requirement 3: Duration-Adaptive Readable Name Presentation

**User Story:** As an administrator, I want existing short and standard classes to show both names readably, so that I can identify each class without opening details.

#### Acceptance Criteria

1. WHEN a rendered Calendar_Event has a duration shorter than 60 minutes, THE Calendar_Rendering_Owner SHALL use the Compact_Event_Presentation.
2. WHEN a rendered Calendar_Event has a duration shorter than 60 minutes, THE Calendar_Rendering_Owner SHALL provide a Readable_Name_Presentation.
3. WHEN a rendered Calendar_Event has a duration of 60 minutes or longer, THE Calendar_Rendering_Owner SHALL use the Standard_Event_Presentation.
4. WHEN a rendered Calendar_Event has a duration of 60 minutes or longer, THE Calendar_Rendering_Owner SHALL provide a Readable_Name_Presentation.

### Requirement 4: Complete Overlapping-Event Discoverability

**User Story:** As an administrator, I want every overlapping class to remain discoverable, so that concurrent classes are not mistaken for missing sessions.

#### Acceptance Criteria

1. WHEN two or more Calendar_Events overlap in time within an active Displayed_Range, THE Calendar_Rendering_Owner SHALL include every overlapping Calendar_Event identifier in the Visible_Event_Set.
2. IF FullCalendar conceals one or more overlapping Calendar_Events behind an Event_Visibility_Affordance, THEN THE Calendar_Rendering_Owner SHALL provide an operable Event_Visibility_Affordance for every concealed Calendar_Event.
3. WHEN an Event_Visibility_Affordance is operated, THE Calendar_Rendering_Owner SHALL reveal every Calendar_Event identifier represented by that Event_Visibility_Affordance.
4. WHEN a successful event refresh completes for an active Displayed_Range, THE Calendar_Rendering_Owner SHALL expose every identifier with Displayed_Range_Membership and a successful Calendar_Event_Conversion through the Visible_Event_Set.

### Requirement 5: Month, Week, and Day View Equivalence

**User Story:** As an administrator, I want applicable sessions to be equivalently available in month, week, and day views, so that changing views does not change the schedule.

#### Acceptance Criteria

1. WHEN a Renderable_Session has Displayed_Range_Membership in an active month view and a successful Calendar_Event_Conversion, THE Calendar_Rendering_Owner SHALL include the Persisted_Session identifier in the month Visible_Event_Set.
2. WHEN a Renderable_Session has Displayed_Range_Membership in an active week view and a successful Calendar_Event_Conversion, THE Calendar_Rendering_Owner SHALL include the Persisted_Session identifier in the week Visible_Event_Set.
3. WHEN a Renderable_Session has Displayed_Range_Membership in an active day view and a successful Calendar_Event_Conversion, THE Calendar_Rendering_Owner SHALL include the Persisted_Session identifier in the day Visible_Event_Set.
4. WHEN month, week, and day views use identical Active_Filters and Calendar_Timezone for the same Calendar_Day, THE Calendar_Rendering_Owner SHALL expose the same identifiers for Renderable_Sessions that have that Calendar_Day as an Event_Touched_Day and have successful Calendar_Event_Conversion in each applicable Visible_Event_Set.

### Requirement 6: Jalali, Gregorian, and Calendar-Timezone Invariants

**User Story:** As an administrator, I want Jalali labels and event placement to identify the same day and time, so that calendar navigation neither hides nor moves sessions.

#### Acceptance Criteria

1. WHEN the Admin_Calendar displays a Jalali_Label for a Calendar_Day, THE Calendar_Rendering_Owner SHALL associate the Jalali_Label with that same Gregorian Calendar_Day in the Calendar_Timezone.
2. IF a Jalali_Label cannot be associated with a Gregorian Calendar_Day, THEN THE Calendar_Rendering_Owner SHALL display the Gregorian Calendar_Day without a Jalali_Label.
3. WHEN an administrator navigates from a Jalali_Label to its Calendar_Day, THE Calendar_Rendering_Owner SHALL request and render a Displayed_Range containing that same Gregorian Calendar_Day.
4. WHEN a Renderable_Session is transformed into a Calendar_Event, THE Calendar_Rendering_Owner SHALL preserve the Renderable_Session Calendar_Day, start time, and duration in the Calendar_Timezone.
5. WHEN a Timezone_Offset_Transition occurs in the Calendar_Timezone, THE Calendar_Rendering_Owner SHALL preserve the Gregorian Calendar_Day, start time, and duration for each unchanged Renderable_Session.
6. WHEN FullCalendar changes view with unchanged Active_Filters and Calendar_Timezone, THE Calendar_Rendering_Owner SHALL preserve the Gregorian Calendar_Day, start time, and duration for each unchanged Calendar_Event with Displayed_Range_Membership in the new view.

### Requirement 7: Idempotent FullCalendar Re-Rendering

**User Story:** As an administrator, I want repeated rendering of an unchanged calendar to preserve the schedule, so that navigation and refreshes do not make events disappear or duplicate.

#### Acceptance Criteria

1. WHEN FullCalendar re-renders an unchanged Displayed_Range with identical Active_Filters and Calendar_Timezone, THE Calendar_Rendering_Owner SHALL expose a Visible_Event_Set equal to the Visible_Event_Set from the immediately preceding successful render.
2. WHEN FullCalendar completes two or more consecutive event refreshes for an unchanged Displayed_Range with identical Active_Filters and Calendar_Timezone, THE Calendar_Rendering_Owner SHALL expose each Visible_Event_Set identifier exactly once.
3. WHEN FullCalendar renders a Displayed_Range in which a Calendar_Event lacks Displayed_Range_Membership, THE Calendar_Rendering_Owner SHALL omit the Calendar_Event identifier from the Visible_Event_Set.

### Requirement 8: Focused Regression Verification Records

**User Story:** As a maintainer, I want focused regression evidence for each rendering outcome, so that the correction can be accepted without informal visual inspection.

#### Acceptance Criteria

1. WHEN a Focused_Regression_Case completes, THE Calendar_Rendering_Owner SHALL create one Verification_Record for that Focused_Regression_Case.
2. WHEN a Focused_Regression_Case passes, THE Calendar_Rendering_Owner SHALL record the expected identifier set, observed identifier set, rendered identifiers, overflow-disclosed identifiers, and pass status in the Verification_Record.
3. IF a Focused_Regression_Case fails, THEN THE Calendar_Rendering_Owner SHALL record the expected identifier set, observed identifier set, fail status, and first failing ownership boundary in the Verification_Record.
4. WHEN the existing-session range-membership case executes, THE Calendar_Rendering_Owner SHALL verify the inclusive-start and exclusive-end outcomes in Requirement 1.
5. WHEN the newly-persisted-session case executes, THE Calendar_Rendering_Owner SHALL verify the First_Post_Persistence_Successful_Refresh outcome in Requirement 2.
6. WHEN the duration-presentation case executes with 30-minute and 60-minute fixtures, THE Calendar_Rendering_Owner SHALL verify the Readable_Name_Presentation outcome in Requirement 3.
7. WHEN the overlap case executes with two through ten fixtures sharing a time interval, THE Calendar_Rendering_Owner SHALL verify complete discoverability of every fixture identifier.
8. WHEN the month, week, day, Jalali, Gregorian, Calendar_Timezone, and re-rendering cases execute, THE Calendar_Rendering_Owner SHALL verify the invariants in Requirements 5 through 7.

### Requirement 9: Scope and Ownership Protection

**User Story:** As a maintainer, I want the regression correction to remain within Calendar_Rendering_Owner, so that unrelated product behavior remains unchanged.

#### Acceptance Criteria

1. WHEN a focused regression investigation identifies Calendar_Rendering_Owner as the first failing boundary, THE Calendar_Rendering_Owner SHALL limit correction and verification to the outcomes in Requirements 1 through 8.
2. IF a focused regression investigation identifies an External_Owner as the first failing boundary, THEN THE Calendar_Rendering_Owner SHALL record the external dependency and require a separately approved specification before scope expansion.
3. WHEN focused regression verification completes, THE Calendar_Rendering_Owner SHALL preserve the behaviors listed in the Non-Scope section.

## Focused Regression Tests

| Test ID | Regression fixture and action | Required observation |
|---|---|---|
| CRR-01 | Refresh a range with an eligible fixture crossing the inclusive start instant, an eligible fixture at the inclusive start instant, and an eligible fixture at the exclusive end instant. | The crossing and inclusive-start fixture identifiers are present exactly once; the exclusive-end fixture identifier is absent. |
| CRR-02 | Persist one eligible session in the active range and complete the First_Post_Persistence_Successful_Refresh. | The new fixture identifier is present exactly once. |
| CRR-03 | Render one existing 30-minute fixture and one existing 60-minute fixture with non-empty student and teacher names. | Each fixture uses its duration-adaptive presentation and displays both complete names simultaneously. |
| CRR-04 | Render fixture sets of 2, 3, 5, and 10 sessions sharing a time interval. | Every fixture identifier is directly discoverable or revealed by an operable Event_Visibility_Affordance. |
| CRR-05 | Inspect applicable month, week, and day views for the same Calendar_Day with identical Active_Filters and Calendar_Timezone. | Each applicable Visible_Event_Set contains the same fixture identifiers. |
| CRR-06 | Navigate Jalali month-boundary and year-boundary fixtures through month, week, and day views. | Each Jalali_Label and Calendar_Event refers to the same Gregorian Calendar_Day, start time, and duration in the Calendar_Timezone. |
| CRR-07 | Render applicable fixtures before, during, and after a Timezone_Offset_Transition. | Each unchanged fixture retains its Gregorian Calendar_Day, start time, and duration. |
| CRR-08 | Re-render an unchanged Displayed_Range with unchanged Active_Filters and Calendar_Timezone. | The before-and-after Visible_Event_Set values are equal and contain no duplicate identifier. |

## Verification Requirements

- Each Focused_Regression_Case uses isolated fixtures for only the calendar rendering outcomes covered by this specification.
- Each Verification_Record identifies the fixture identifiers, active FullCalendar view, Displayed_Range, Calendar_Timezone, and Active_Filters used by the Focused_Regression_Case.
- Completion of this requirements phase requires review and approval of this document before any design or task document is created.
