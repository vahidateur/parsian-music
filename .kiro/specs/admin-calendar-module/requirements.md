# Requirements Document

## Introduction

This specification defines the rebuild of the admin calendar page from a server-rendered HTML table into a professional, modular FullCalendar-based calendar module. The module replaces the current `admin/calendar` page with a client-side rendered day view using FullCalendar (timeGridDay), a modular file architecture, custom event cards, a detail drawer, and full RTL/Design System compliance. The module retains existing backend data (ClassSession model) and filtering capabilities while delivering a premium Apple-inspired user experience.

## Glossary

- **Calendar_Module**: The complete FullCalendar-based calendar system including layout, sidebar, timeline, drawer, and filters
- **FullCalendar**: The JavaScript calendar library (@fullcalendar/core with daygrid, timegrid, interaction, list plugins)
- **Day_Timeline**: The main FullCalendar timeGridDay view showing hourly slots for a single day
- **Week_Sidebar**: The left-side panel listing the 7 days of the Persian week (Saturday to Friday) for quick day navigation
- **Event_Card**: A custom-rendered card inside the calendar representing a single ClassSession
- **Event_Drawer**: A right-side sliding panel showing full details of a selected session
- **Filter_Panel**: The UI section containing teacher, student, room, and instrument filter controls
- **Calendar_API**: The JSON endpoint providing session data to FullCalendar
- **Design_System**: The project's token-driven design system (dark premium theme, warm gold accent, glass effects)
- **Persian_Week**: A week starting on Saturday (شنبه) and ending on Friday (جمعه)
- **Session_Status**: One of scheduled, completed, cancelled, or missed (from SessionStatusEnum)

## Requirements

### Requirement 1: FullCalendar Integration

**User Story:** As an admin, I want a professional client-side calendar powered by FullCalendar, so that I get smooth interactions, proper time rendering, and a modern calendar experience.

#### Acceptance Criteria

1. THE Calendar_Module SHALL render using FullCalendar timeGridDay as the default view
2. THE Calendar_Module SHALL configure slotDuration to 30 minutes
3. THE Calendar_Module SHALL configure slotMinTime to 08:00 and slotMaxTime to 22:00
4. THE Calendar_Module SHALL disable the allDaySlot
5. THE Calendar_Module SHALL enable the nowIndicator to show the current time
6. THE Calendar_Module SHALL set expandRows to true and height to auto
7. THE Calendar_Module SHALL set the calendar locale to Persian (fa) with RTL direction
8. THE Calendar_Module SHALL set the first day of the week to Saturday (6)
9. WHEN the calendar view is initialized, THE Calendar_Module SHALL fetch class session events from the server as a JSON feed and render each event displaying the session title, teacher name, and time range within the corresponding time slot
10. IF the FullCalendar library fails to initialize or the event data feed returns an error, THEN THE Calendar_Module SHALL display an inline error message indicating the calendar could not be loaded and SHALL preserve any previously visible page content outside the calendar area
11. THE Calendar_Module SHALL provide header toolbar navigation controls allowing the admin to move to the previous day, next day, and return to today

### Requirement 2: Modular File Architecture

**User Story:** As a developer, I want the calendar module split into dedicated files with single responsibilities, so that maintenance, testing, and future extensions are straightforward.

#### Acceptance Criteria

1. THE Calendar_Module SHALL organize JavaScript into separate ES modules with the following files, each exporting a single default initialization function: calendar-app.js (orchestrator that imports and initializes all other modules), fullcalendar.js (FullCalendar library initialization and configuration), sidebar.js (week sidebar rendering and interaction logic), drawer.js (event drawer open/close and content logic), and filters.js (filter state management and application logic)
2. THE Calendar_Module SHALL maintain a dedicated calendar.css file in resources/css/admin/ for all calendar-specific styles, using BEM naming (.calendar-block__element--modifier) and referencing only existing design tokens (CSS custom properties) for colors, spacing, radius, and shadows
3. THE Calendar_Module SHALL organize Blade templates as modular components under resources/views/admin/calendar/components/: calendar-layout.blade.php, calendar-header.blade.php, week-sidebar.blade.php, day-timeline.blade.php, event-drawer.blade.php, and event-filters.blade.php, each resolvable via Blade's x-component syntax
4. THE Calendar_Module SHALL register a dedicated Vite entry point (resources/js/calendar/calendar-app.js) in vite.config.js, where calendar-app.js imports all sibling calendar modules and no circular dependencies exist between modules
5. WHEN a new calendar JS module is added, THE Calendar_Module SHALL require it to be imported only through calendar-app.js (orchestrator pattern), ensuring no direct cross-imports between sibling modules (sidebar.js, drawer.js, filters.js, fullcalendar.js)

### Requirement 3: Layout and Navigation

**User Story:** As an admin, I want a clear layout with a header, week sidebar, and main timeline area, so that I can navigate days quickly and see the full schedule at a glance.

#### Acceptance Criteria

1. THE Calendar_Module SHALL display a header section containing the current Jalali date in full format (weekday name, day number, month name, and year — e.g., "شنبه ۲۳ تیر ۱۴۰۴"), navigation arrows (previous day, next day), and a "today" button
2. THE Week_Sidebar SHALL display all 7 days of the current Persian_Week (Saturday through Friday) with Jalali day names and day-of-month numbers
3. THE Week_Sidebar SHALL distinguish the currently selected day by applying a visually distinct gold-colored background or border indicator that differentiates it from unselected days without relying on color alone (e.g., combining background highlight with a bold text weight or border)
4. WHEN the admin clicks a day in the Week_Sidebar, THE Day_Timeline SHALL update to show sessions for that selected day without a full page reload within 300ms of the click event
5. WHEN the admin clicks the previous-day or next-day navigation arrows, THE Day_Timeline SHALL update to the adjacent day and THE Week_Sidebar SHALL update its selected state accordingly
6. WHEN the admin clicks the "today" button, THE Day_Timeline SHALL navigate to the current day and THE Week_Sidebar SHALL select it; IF the current day is already selected, THEN THE Calendar_Module SHALL remain in its current state with no visible change
7. WHEN the selected day changes to a day outside the currently displayed week, THE Week_Sidebar SHALL update to show the new week containing that day
8. WHILE the Day_Timeline is fetching session data for a newly selected day, THE Calendar_Module SHALL display a loading skeleton in the timeline area; IF the data fetch fails, THEN THE Calendar_Module SHALL display an error indication with a retry option and preserve the previously displayed day's content until dismissed
9. THE Calendar_Module SHALL support keyboard navigation such that the admin can move between days using arrow keys when the Week_Sidebar or header arrows are focused, and all interactive elements (navigation arrows, today button, day items) SHALL be reachable via Tab key with a visible focus indicator

### Requirement 4: Calendar API Endpoint

**User Story:** As a developer, I want a dedicated JSON API endpoint for session data, so that FullCalendar can fetch events dynamically without full page reloads.

#### Acceptance Criteria

1. WHEN a GET request is made to the route named admin.calendar.events, THE Calendar_API SHALL return a JSON array of session objects in FullCalendar-compatible format with HTTP 200 status
2. THE Calendar_API SHALL require start and end query parameters as ISO 8601 date strings (YYYY-MM-DD) and use the forDateRange scope to fetch sessions within the specified inclusive date range
3. THE Calendar_API SHALL accept optional teacher_id, student_id, room, and instrument_id query parameters, applying the corresponding filter scopes (forTeacher, forStudent, forInstrument) and a where clause on the room column only when a parameter is present
4. THE Calendar_API SHALL return each session object containing: id (integer), title (string, max 255 characters), start (ISO 8601 datetime), end (ISO 8601 datetime computed from start_time + duration_minutes), status (one of: scheduled, completed, cancelled, missed), studentName, teacherName, instrumentName, room, and an extendedProps object containing enrollment_id and session_fee
5. THE Calendar_API SHALL use the withEnrollmentDetails scope to eager-load enrollment.student, enrollment.teacher, enrollment.instrument, and direct student, teacher, instrument relations in a single query batch
6. IF the start or end query parameter is missing or is not a valid date in YYYY-MM-DD format, THEN THE Calendar_API SHALL return a 422 JSON response containing a validation errors object with field-specific error messages
7. IF start is after end, THEN THE Calendar_API SHALL return a 422 JSON response with a validation error indicating the start date must be before or equal to the end date
8. IF the date range span exceeds 92 days, THEN THE Calendar_API SHALL return a 422 JSON response with a validation error indicating the maximum allowed range

### Requirement 5: Custom Event Cards

**User Story:** As an admin, I want visually rich event cards showing key session info at a glance, so that I can identify sessions without clicking each one.

#### Acceptance Criteria

1. THE Event_Card SHALL display the student name as the primary text with font-weight bold
2. THE Event_Card SHALL display the teacher name as secondary text with reduced opacity
3. THE Event_Card SHALL display the session time range in monospace format (e.g., "09:00–09:30")
4. THE Event_Card SHALL display the room identifier
5. THE Event_Card SHALL display an instrument icon or name
6. THE Event_Card SHALL apply a border-inline-start color based on the Session_Status: sky for scheduled, emerald for completed, red for cancelled, orange for missed
7. THE Event_Card SHALL use Design_System glass card styling with tokens for background (--glass-bg), border (--glass-border), and radius (--radius-sm)
8. WHEN the admin hovers over an Event_Card, THE Event_Card SHALL show a subtle elevation change using Design_System motion tokens (--duration-fast, --ease-standard)
9. THE Event_Card SHALL render inside FullCalendar time slots via the eventContent callback, replacing FullCalendar's default event markup

### Requirement 6: Event Detail Drawer

**User Story:** As an admin, I want to click a session and see full details in a side drawer, so that I can review and manage sessions without leaving the calendar view.

#### Acceptance Criteria

1. WHEN the admin clicks an Event_Card, THE Event_Drawer SHALL open from the inline-end side of the viewport, with a maximum width of 400px on viewports ≥768px and full viewport width on viewports <768px
2. THE Event_Drawer SHALL display: student name, teacher name, instrument, session date (Jalali formatted), start time, duration (in minutes), room, status badge, and session notes
3. IF a displayed field (session notes) has no value, THEN THE Event_Drawer SHALL display a localized placeholder text (e.g., "بدون یادداشت") instead of leaving the area blank
4. THE Event_Drawer SHALL display a status badge color-coded per Session_Status: scheduled (neutral/default), completed (success/green), cancelled (danger/red), missed (warning/amber)
5. THE Event_Drawer SHALL include a close button in the header with an aria-label attribute describing its action
6. WHEN the admin presses the Escape key while the Event_Drawer is open, THE Event_Drawer SHALL close within 300ms
7. WHEN the admin clicks the overlay backdrop, THE Event_Drawer SHALL close within 300ms
8. WHILE the Event_Drawer is open, THE Event_Drawer SHALL trap keyboard focus so that Tab and Shift+Tab cycle only through focusable elements within the drawer
9. WHEN the Event_Drawer closes, THE Event_Drawer SHALL return focus to the Event_Card that triggered the opening
10. THE Event_Drawer SHALL animate open and closed with a slide-in/slide-out transition using Design_System motion tokens (--duration-normal: 300ms, --ease-standard) and respect prefers-reduced-motion by disabling the animation
11. THE Event_Drawer SHALL use role="dialog", aria-modal="true", and aria-labelledby referencing the drawer heading element

### Requirement 7: Filters

**User Story:** As an admin, I want to filter the calendar by teacher, student, room, and instrument, so that I can focus on specific schedules.

#### Acceptance Criteria

1. THE Filter_Panel SHALL provide dropdown selects for teacher, student, room, and instrument, each populated with options passed from the server (teachers and students from database, rooms from the configured room list, instruments from database)
2. WHEN the admin selects or changes any filter value, THE Day_Timeline SHALL re-fetch events from the Calendar_API with the selected filter parameters after a 300ms debounce delay, without a full page reload
3. WHEN the admin activates the clear-all-filters control, THE Day_Timeline SHALL reset all filter dropdowns to their default "all" option and display all sessions for the selected day
4. WHILE the admin navigates between days within the same browser page load (no full page refresh or tab close), THE Filter_Panel SHALL preserve the previously selected filter values
5. WHEN at least one filter has a non-default value selected, THE Filter_Panel SHALL display a badge indicator showing the count of active filters (1 to 4); WHEN all filters are at their default "all" value, THE Filter_Panel SHALL hide the badge indicator
6. IF the applied filters match zero sessions for the selected day, THEN THE Day_Timeline SHALL display an empty-state message indicating no sessions match the current filters

### Requirement 8: Design System Compliance

**User Story:** As a user, I want the calendar to match the premium dark theme of the rest of the admin panel, so that the experience feels unified and professional.

#### Acceptance Criteria

1. THE Calendar_Module SHALL use only Design_System color tokens (primitive or semantic) for all surfaces, text, and borders, containing zero hardcoded hex or rgba values in its CSS
2. THE Calendar_Module SHALL apply glass card styling to the main calendar container using --glass-bg for background, backdrop-filter: blur(var(--glass-blur)), --glass-border for border color, and --radius-md for border radius
3. THE Calendar_Module SHALL style time slot labels, day headers, and grid lines using Design_System semantic tokens (--color-text, --color-text-muted, --color-border-light)
4. THE Calendar_Module SHALL use Vazirmatn font-family with type scale tokens (--text-sm for slot labels, --text-base for day headers, --text-lg for toolbar title)
5. THE Calendar_Module SHALL style the FullCalendar toolbar and navigation buttons to match the Design_System button variants by applying --color-primary for active state background, --radius-sm for border radius, and --duration-fast with --ease-standard for hover transitions
6. THE Calendar_Module SHALL override FullCalendar default styles with custom CSS using BEM naming convention (.calendar__element--modifier)
7. THE Calendar_Module SHALL use Design_System shadow tokens (--shadow-md for card containers, --shadow-lg for the drawer) for elevation
8. THE Calendar_Module SHALL support RTL layout using CSS logical properties (margin-inline, padding-inline) for all calendar spacing and alignment
9. IF the user has enabled prefers-reduced-motion: reduce, THEN THE Calendar_Module SHALL disable all calendar transition animations

### Requirement 9: RTL and Persian Locale

**User Story:** As a Persian-speaking admin, I want the calendar fully localized with proper RTL layout and Jalali dates, so that the interface feels natural in my language.

#### Acceptance Criteria

1. THE Calendar_Module SHALL render with dir="rtl" on its container and use logical CSS properties (margin-inline, padding-block) for all spacing
2. THE Calendar_Module SHALL display all date numerals in the Jalali (Persian) calendar system using Western digits (e.g., 1404/04/25, not ۱۴۰۴/۰۴/۲۵)
3. THE Calendar_Module SHALL display Persian day names in the Week_Sidebar (شنبه، یکشنبه، دوشنبه، سه‌شنبه، چهارشنبه، پنجشنبه، جمعه)
4. THE Calendar_Module SHALL display time values in 24-hour format using Western digits (e.g., 08:00, 14:30)
5. THE Week_Sidebar SHALL order days from Saturday (first) to Friday (last) following Persian_Week convention
6. THE Calendar_Module SHALL display Jalali month names (فروردین through اسفند) and the Jalali year in the month/week navigation header

### Requirement 10: Accessibility

**User Story:** As a keyboard-only user, I want the calendar to be fully navigable with keyboard and screen reader compatible, so that I can manage the schedule without a mouse.

#### Acceptance Criteria

1. THE Calendar_Module SHALL allow all interactive elements (day buttons, event cards, filter controls, drawer trigger) to receive focus via the Tab key in a logical reading order (sidebar → filters → calendar grid → drawer), and each focusable element SHALL be activatable via Enter or Space key
2. THE Calendar_Module SHALL display a focus ring on all focused interactive elements using the Design_System --shadow-input-focus token with a minimum 3:1 contrast ratio against adjacent colors per WCAG 2.4.7
3. THE Event_Card SHALL use role="button" and an aria-label containing the student name and session status in the format "{student name} – {session status}" where session status is one of: scheduled, completed, cancelled, missed
4. THE Week_Sidebar day buttons SHALL use aria-current="date" on today's date
5. THE Filter_Panel select elements SHALL have associated <label> elements linked via matching for and id attributes
6. IF the user's system has prefers-reduced-motion: reduce enabled, THEN THE Calendar_Module SHALL disable all CSS transitions and animations (duration set to 0ms)
7. WHEN the event detail drawer opens, THE Calendar_Module SHALL trap focus within the drawer using @alpinejs/focus (x-trap), close the drawer on Escape key press, and return focus to the triggering Event_Card upon close

### Requirement 11: Responsive Behavior

**User Story:** As an admin using different devices, I want the calendar to adapt gracefully to smaller screens, so that I can check schedules on a tablet.

#### Acceptance Criteria

1. WHILE the viewport width is less than 1024px, THE Week_Sidebar SHALL collapse into a horizontal scrollable strip above the Day_Timeline with the selected day scrolled into view
2. WHILE the viewport width is less than 768px, THE Event_Drawer SHALL open as a full-width bottom sheet with a maximum height of 80vh and a drag handle for dismissal
3. WHILE the viewport width is less than 768px, THE Filter_Panel SHALL collapse behind a toggle button showing the active filter count; WHEN the toggle button is activated, THE Filter_Panel SHALL expand as a dropdown overlay
4. THE Calendar_Module SHALL not produce horizontal overflow at any viewport width from 390px to 1920px
5. THE Event_Card SHALL maintain touch targets of at least 44x44px on touch devices (pointer: coarse media query)

### Requirement 12: Performance

**User Story:** As an admin, I want the calendar to load quickly and respond immediately to interactions, so that managing the schedule does not feel sluggish.

#### Acceptance Criteria

1. THE Calendar_Module SHALL lazy-load FullCalendar JavaScript via Vite dynamic import() to produce a separate chunk that does not block initial page render
2. THE Calendar_API SHALL respond within 200ms for a typical day's sessions (up to 50 sessions) under normal database load
3. THE Calendar_Module SHALL display a skeleton loading state matching the Design_System dark theme while events are being fetched from the Calendar_API
4. THE Calendar_Module SHALL debounce filter changes by 300ms before issuing a new Calendar_API request to prevent excessive API calls
5. THE Calendar_Module SHALL not cause Cumulative Layout Shift (CLS) during event loading by maintaining stable container dimensions with a minimum height set before data arrives
