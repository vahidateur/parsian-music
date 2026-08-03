# Requirements Document: Calendar Owner Regression Fix

## Introduction

This narrowly scoped bugfix restores correct rendering of valid admin calendar sessions. It covers the Calendar Owner path only: event query, `CalendarEventResource`, FullCalendar mapping and date handling, the custom event renderer, calendar CSS, and FullCalendar visibility behavior. It creates no application-code change in this planning phase.

## Glossary

- **Calendar_Owner**: The isolated server-to-client event pipeline and calendar presentation files that own calendar event retrieval and rendering.
- **Valid_Session**: A persisted `ClassSession` with a valid `session_date`, `start_time`, positive duration, supported status, and either direct or enrollment-backed relations.
- **Calendar_Event**: The FullCalendar-compatible object derived from a Valid_Session.
- **Active_Range**: The Gregorian date range FullCalendar requests for the displayed month, week, or day view.
- **Visibility_Path**: The rendering steps that can show, stack, collapse, clip, or hide a Calendar_Event.
- **Baseline_Counterexample**: A repeatable pre-fix fixture, request, view, and observed defect record.

## Scope and Constraints

- Scope is functional calendar regressions in the Calendar_Owner only.
- The production fix SHALL be minimal and SHALL not redesign the calendar or alter Session business rules.
- Explicit non-goals: calendar redesign; unrelated UI changes; subscription logic; Session business-rule changes; Bulk; Dashboard.

## Requirements

### Requirement 1: Root-Cause Investigation

**User Story:** As a maintainer, I want reproducible evidence for each calendar regression, so that the production fix addresses the actual failing boundary.

#### Acceptance Criteria
1. WHEN a Baseline_Counterexample is prepared, THE Calendar_Owner SHALL record the query request and result, CalendarEventResource output, FullCalendar normalization result, timezone/date conversion result, Jalali/Gregorian conversion result, event-height result, eventContent output, CSS overflow result, and stacking or hidden-event result.
2. WHEN a Valid_Session is absent from a calendar view, THE Calendar_Owner SHALL classify the first failing boundary as query exclusion, resource mapping, client normalization, date-range conversion, stacking or hiding, or visual clipping.
3. WHEN root-cause evidence is recorded, THE Calendar_Owner SHALL preserve the fixture identifier, requested Active_Range, active view, viewport, timezone, and observed versus expected event count for before-and-after comparison.

### Requirement 2: Valid Session Retrieval and Mapping

**User Story:** As an admin, I want every valid new or existing session in the displayed range to reach the calendar, so that schedules are complete.

#### Acceptance Criteria
1. WHEN a Valid_Session is persisted through a direct or enrollment-backed relation path within an Active_Range, THE Calendar_Owner SHALL return exactly one Calendar_Event with the persisted session identifier.
2. WHEN CalendarEventResource transforms a Valid_Session, THE Calendar_Owner SHALL produce a start and end datetime whose local calendar date and time equal `session_date`, `start_time`, and `duration_minutes` without an unintended timezone shift.
3. IF a Valid_Session reaches client mapping with an invalid Calendar_Event contract, THEN THE Calendar_Owner SHALL expose the contract failure to the focused regression harness instead of silently treating the session as absent.

### Requirement 3: Date and View Consistency

**User Story:** As an admin, I want date navigation and Persian date labels to request the displayed Gregorian range, so that month, week, and day views show the correct sessions.

#### Acceptance Criteria
1. WHEN FullCalendar changes the Active_Range, THE Calendar_Owner SHALL derive the event-feed request range from the FullCalendar callback range rather than a stale selected-date value.
2. WHEN a Gregorian date is formatted as Jalali and converted back for calendar navigation, THE Calendar_Owner SHALL preserve the original Gregorian calendar day.
3. WHEN a Valid_Session falls within the Active_Range of the month, week, or day view, THE Calendar_Owner SHALL make the Calendar_Event available to that view.

### Requirement 4: Event Visibility and Stacking

**User Story:** As an admin, I want every returned event to be discoverable, so that concurrent sessions are not mistaken for missing sessions.

#### Acceptance Criteria
1. WHEN multiple Calendar_Events occupy an overlapping slot, THE Calendar_Owner SHALL render every event directly or provide an accessible FullCalendar overflow affordance whose disclosed event count equals the hidden-event count.
2. IF FullCalendar applies an event stack limit, THEN THE Calendar_Owner SHALL preserve an operable overflow affordance for every hidden Calendar_Event.
3. WHEN a Calendar_Event is returned by the feed, THE Calendar_Owner SHALL not remove the Calendar_Event solely because the event is in a month, week, or day presentation.

### Requirement 5: Duration-Aware Event Content

**User Story:** As an admin, I want readable short and standard session cards, so that I can identify students and teachers without opening each event.

#### Acceptance Criteria
1. WHEN a 30-minute Calendar_Event renders, THE Calendar_Owner SHALL use a compact eventContent layout that visibly presents the complete student name and teacher name beside the student name without clipping either identity.
2. WHEN a 60-minute Calendar_Event renders, THE Calendar_Owner SHALL use a standard eventContent layout that visibly presents the complete student name and teacher name beside the student name and retains the time range.
3. WHILE a 30-minute or 60-minute Calendar_Event is rendered, THE Calendar_Owner SHALL allocate event-card height and overflow behavior that do not crop the required identity content.

### Requirement 6: Minimal Production Fix

**User Story:** As a maintainer, I want a contained repair, so that unrelated modules remain unchanged.

#### Acceptance Criteria
1. WHERE a production change is required, THE Calendar_Owner SHALL modify only the smallest set of calendar query, resource, JavaScript, Blade, CSS, and focused-test files needed to satisfy Requirements 1 through 5.
2. WHEN the Calendar_Owner fix is implemented, THE Calendar_Owner SHALL preserve existing authorization, session persistence, subscription updates, Bulk behavior, Dashboard behavior, and session conflict or validation rules.
3. IF an investigation points outside the Calendar_Owner, THEN THE Calendar_Owner SHALL document the dependency and stop scope expansion pending a separate approved specification.

### Requirement 7: Focused Regression Verification

**User Story:** As a maintainer, I want targeted before-and-after automated verification, so that the regressions do not return.

#### Acceptance Criteria
1. WHEN the focused regression suite runs before the production fix, THE Calendar_Owner SHALL record the Baseline_Counterexample result without changing application behavior.
2. WHEN the production fix is complete, THE Calendar_Owner SHALL verify newly created and pre-existing direct and enrollment-backed 30-minute and 60-minute Valid_Sessions across month, week, and day views.
3. WHEN post-fix verification completes, THE Calendar_Owner SHALL confirm the returned, normalized, directly rendered, overflow-disclosed, and visible event counts reconcile for each Baseline_Counterexample.
