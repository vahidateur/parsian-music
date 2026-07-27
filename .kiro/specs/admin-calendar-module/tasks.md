# Implementation Plan: Admin Calendar Module

## Overview

Replace the server-rendered admin calendar with the designed FullCalendar `timeGridDay` module while preserving the existing `ClassSession` model, scopes, Jalali conventions, admin authorization, RTL behavior, and design-system decisions. Tasks are incremental code-generation prompts: each step builds on earlier work and the final wiring task integrates the complete module.

The implementation must use PHP/Laravel, Blade, JavaScript ES modules, Alpine.js, CSS, Vite, PHPUnit, fast-check, and Playwright as specified by the design. Do not add migrations or change the existing `ClassSession` schema/scopes unless a failing implementation proves the design assumptions incorrect.

## Tasks

- [x] 1. Implement the calendar backend contract and protected API
  - [x] 1.1 Create `app/Http/Requests/Admin/CalendarEventRequest.php`
    - Validate required `start` and `end` values as `Y-m-d` dates, `after_or_equal:start`, and a maximum inclusive span of 92 days.
    - Validate optional `teacher_id`, `student_id`, `room`, and `instrument_id` with the existing database/configuration sources.
    - Return field-specific JSON validation errors with HTTP 422 for API requests.
    - _Requirements: 4.2, 4.6, 4.7, 4.8_

  - [x] 1.2 Create `app/Http/Resources/CalendarEventResource.php`
    - Transform direct or enrollment-backed relations into the exact FullCalendar event schema from the design.
    - Compute `end` from the session start and `duration_minutes`; preserve null room/notes safely and expose all required `extendedProps`.
    - Use `SessionStatusEnum` values only and avoid query work inside the resource.
    - _Requirements: 1.9, 4.4, 5.1–5.5, 6.2–6.4_

  - [x] 1.3 Create `app/Http/Controllers/Admin/CalendarController.php`
    - Implement `index(): View` with eager-loaded/server-provided teachers, students, instruments, and configured rooms for filter options.
    - Implement `events(CalendarEventRequest $request): JsonResponse` using `forDateRange`, optional filter scopes/room matching, and `withEnrollmentDetails` in one query batch.
    - Return a resource collection with a safe generic JSON 500 response path and no sensitive error details.
    - Keep controller orchestration thin; do not add business logic to Blade or raw SQL.
    - _Requirements: 4.1–4.5, 4.9, 7.1, Security/Architecture decisions in design_

  - [x] 1.4 Register named admin calendar routes in the established route loading path
    - Add `admin.calendar.index` and `admin.calendar.events` for `/admin/calendar` and `/admin/calendar/events`.
    - Apply `auth` and `role:admin` middleware and preserve the project’s route grouping conventions.
    - _Requirements: 4.1, Error Handling (401/403), 3.1_

  - [x] 1.5 Add `tests/Unit/Http/Resources/CalendarEventResourceTest.php`
    - Cover complete enrollment-backed and direct-relation sessions, null notes/room, Western ISO datetimes, status values, and duration-based end-time calculation.
    - Reuse existing factories/builders where available; add test-only fixtures without changing the production schema.
    - _Requirements: 1.9, 4.4, Property 1_

  - [x] 1.6 Add `tests/Feature/Admin/CalendarControllerTest.php`
    - Cover authenticated admin success, unauthenticated/unauthorized access, valid event structure, eager-loaded relation paths, all four filters, missing/invalid dates, reversed ranges, and ranges over 92 days.
    - Assert JSON status codes and field-specific `errors` responses, including direct sessions with nullable enrollment.
    - _Requirements: 4.1–4.8, Error Handling_

  - [x] 1.7 Add the fast-check contract property for session transformation
    - Target `tests/js/properties/session-transformation.property.test.js` and generate valid session payloads covering direct/enrollment relation data and optional fields.
    - Assert every required resource field, valid ISO datetimes, `end = start + duration_minutes`, valid status, and complete `extendedProps`.
    - Tag the test `Feature: admin-calendar-module, Property 1` and run at least 100 iterations.
    - _Requirements: Property 1; 1.9, 4.4_

  - [x] 1.8 Add the fast-check property for invalid API date parameters
    - Target `tests/js/properties/api-invalid-date.property.test.js` and generate empty, partial, nonnumeric, malformed, and non-`Y-m-d` values for both `start` and `end`.
    - Assert the API contract returns HTTP 422 and a field-specific validation error.
    - Tag as Property 4 and run at least 100 iterations.
    - _Requirements: Property 4; 4.2, 4.6_

  - [x] 1.9 Add the fast-check property for reversed date ranges
    - Target `tests/js/properties/api-reversed-range.property.test.js` and generate valid `start > end` date pairs.
    - Assert HTTP 422 and an error stating that start must be before or equal to end.
    - Tag as Property 5 and run at least 100 iterations.
    - _Requirements: Property 5; 4.7_

  - [x] 1.10 Add the fast-check property for oversized date ranges
    - Target `tests/js/properties/api-oversized-range.property.test.js` and generate valid ranges greater than 92 days.
    - Assert HTTP 422 and the maximum-range validation error.
    - Tag as Property 6 and run at least 100 iterations.
    - _Requirements: Property 6; 4.8_

  - [x] 1.11 Add the fast-check property for filter scoping
    - Target `tests/js/properties/filter-scoping.property.test.js` and generate sessions with known teacher, student, room, and instrument identities.
    - Assert each independent filter produces only matching event objects and does not accidentally apply absent filters.
    - Tag as Property 7 and run at least 100 iterations.
    - _Requirements: Property 7; 4.3_

- [x] 2. Build the modular Blade surface and server-rendered filter data
  - [x] 2.1 Create `resources/views/admin/calendar/index.blade.php` and `components/calendar-layout.blade.php`
    - Extend the dashboard layout, expose one semantic page heading, set the calendar root to `dir="rtl"`, and provide stable mount/skeleton/error regions.
    - Compose the header, filters, sidebar, timeline, and drawer through Blade component syntax; pass server data through explicit props.
    - Keep layout ownership in the wrapper and include no inline style, inline JavaScript, query, or business logic.
    - _Requirements: 2.3, 3.1, 8.2, 9.1, 10.1, 12.3, 12.5_

  - [x] 2.2 Create `components/calendar-header.blade.php`, `week-sidebar.blade.php`, and `day-timeline.blade.php`
    - Provide semantic header/navigation controls, a keyboard-reachable seven-day sidebar, FullCalendar mount point, loading skeleton, empty state, retry state, and stable minimum timeline dimensions.
    - Expose data attributes/IDs needed by the orchestrator without embedding presentation logic.
    - Ensure selected-day and today semantics can be updated without a page reload.
    - _Requirements: 1.11, 3.1–3.9, 9.3, 9.5, 10.1–10.4, 12.3, 12.5_

  - [x] 2.3 Create `components/event-filters.blade.php` and `components/event-drawer.blade.php`
    - Render labeled teacher, student, room, and instrument selects from controller props, plus clear-all and mobile toggle controls.
    - Render the drawer with `role="dialog"`, `aria-modal`, `aria-labelledby`, close button label, overlay, `x-data`, `x-show`, `x-transition`, and `x-trap` hooks for `@alpinejs/focus`.
    - Include safe placeholders such as `بدون یادداشت` and `—`, status badge hooks, and responsive bottom-sheet hooks.
    - _Requirements: 6.1–6.11, 7.1–7.6, 10.5–10.7, 11.2–11.3_

  - [x] 2.4 Add calendar localization keys to the existing admin language files
    - Update `lang/fa/admin.php` and the corresponding supported locale file(s) for labels, errors, retry/empty states, status labels, placeholders, aria labels, and filter controls.
    - Keep visible strings translatable and preserve the required Persian day/month names and Western numeric output.
    - _Requirements: 3.1–3.3, 6.3, 7.6, 9.2–9.6, Error Handling_

- [x] 3. Implement client-side calendar modules and date utilities
  - [x] 3.1 Create `resources/js/calendar/utils/jalali.js`
    - Export the date/week/day/month/full-date and Western-digit/time-format helpers required by the design.
    - Compute Saturday–Friday weeks, Jalali labels, 24-hour Western-digit times, and date navigation without external locale packages.
    - Keep pure helpers independent of DOM, sibling modules, and presentation state.
    - _Requirements: 2.1, 3.1–3.7, 9.2–9.6, Properties 2, 3, 8, 11, 12, 14_

  - [x] 3.2 Create `resources/js/calendar/fullcalendar.js`
    - Lazy-load FullCalendar through dynamic `import()` and configure `timeGridDay`, 30-minute slots, 08:00–22:00 bounds, no all-day slot, now indicator, expanded rows, auto height, Persian locale, RTL, Saturday first day, and toolbar controls.
    - Implement the JSON event feed with selected date/filter parameters, custom `eventContent`, event click callback, loading skeleton, empty/error/retry behavior, previous-content preservation, and bounded retry backoff.
    - Skip malformed event payloads without crashing and expose calendar lifecycle methods needed by the orchestrator.
    - _Requirements: 1.1–1.11, 3.8, 5.1–5.9, 12.1, 12.3–12.5_

  - [x] 3.3 Create `resources/js/calendar/sidebar.js`
    - Render the current Persian week from `jalali.js`, mark selected/today days with non-color-only state, and keep the selected item visible in the responsive horizontal strip.
    - Handle click, Enter/Space, arrow-key navigation, focus indication, and callbacks to the orchestrator without sibling imports.
    - _Requirements: 2.1, 3.2–3.9, 9.3, 9.5, 10.1–10.4, 11.1, Property 2_

  - [x] 3.4 Create `resources/js/calendar/filters.js`
    - Maintain the four filter values, active-filter count, clear-all behavior, mobile expand/collapse state, and 300ms debounced callback.
    - Preserve state across day navigation, serialize only non-default values, and prevent duplicate requests during rapid changes.
    - _Requirements: 2.1, 7.1–7.6, 12.4, Property 10_

  - [x] 3.5 Create `resources/js/calendar/drawer.js`
    - Implement event-detail population, open/close state, trigger-focus capture/restore, overlay/close/Escape handling, responsive width/bottom-sheet behavior, and Alpine integration.
    - Map all four statuses to the specified badge classes and expose the exact student/status aria-label format for event triggers.
    - Respect reduced motion and avoid direct imports from sibling modules.
    - _Requirements: 2.1, 5.6, 6.1–6.11, 10.3, 10.7, 11.2, Property 9, Property 13_

  - [x] 3.6 Create `resources/js/calendar/calendar-app.js` as the sole orchestrator
    - Import each sibling module exactly once, initialize them in a deterministic order, and wire callbacks for date changes, filter changes, event clicks, navigation, retry, drawer close, and focus restoration.
    - Keep all sibling communication callback-based with no sibling-to-sibling imports or circular dependencies.
    - Handle initialization failure with an inline retryable error while leaving content outside the calendar untouched.
    - _Requirements: 1.10, 2.1, 2.4–2.5, 3.4–3.8, 6.6–6.9, 7.2–7.4_

  - [x] 3.7 Add the fast-check test harness and JavaScript module test setup
    - Add `fast-check` as an exact pinned development dependency in `package.json` and `package-lock.json`, and create the project-compatible test command/config without introducing watch-mode commands.
    - Establish reusable request, date, DOM, and event generators under `tests/js/support/`.
    - _Requirements: Testing Strategy; all design properties_

  - [x] 3.8 Add `tests/js/properties/persian-week.property.test.js`
    - Generate arbitrary valid dates and assert exactly seven consecutive dates, Saturday start, Friday end, and inclusion of the selected date.
    - Tag as Property 2 and run at least 100 iterations.
    - _Requirements: Property 2; 3.2, 3.7, 9.5_

  - [x] 3.9 Add `tests/js/properties/day-navigation.property.test.js`
    - Generate arbitrary calendar dates and assert next/previous navigation changes by exactly one day.
    - Tag as Property 3 and run at least 100 iterations.
    - _Requirements: Property 3; 3.5_

  - [x] 3.10 Add `tests/js/properties/time-range.property.test.js`
    - Generate valid 24-hour start times and durations from 1–480 minutes and assert `HH:MM–HH:MM` Western-digit output with the correct end time.
    - Tag as Property 8 and run at least 100 iterations.
    - _Requirements: Property 8; 5.3, 9.4_

  - [x] 3.11 Add `tests/js/properties/status-style.property.test.js`
    - Assert every `SessionStatusEnum` value maps to a defined non-empty sky/emerald/red/orange class or token with no fallback path.
    - Tag as Property 9 and run at least 100 iterations or exhaustive status cases as appropriate.
    - _Requirements: Property 9; 5.6, 6.4_

  - [x] 3.12 Add `tests/js/properties/active-filter-count.property.test.js`
    - Generate all combinations of the four default/non-default filter states and assert the exact count from 0 through 4.
    - Tag as Property 10 and run at least 100 iterations.
    - _Requirements: Property 10; 7.5_

  - [x] 3.13 Add `tests/js/properties/western-digits.property.test.js`
    - Generate valid dates and times and assert numeric formatter output contains Western digits only, never Persian/Arabic numerals.
    - Tag as Property 11 and run at least 100 iterations.
    - _Requirements: Property 11; 9.2, 9.4_

  - [x] 3.14 Add `tests/js/properties/persian-locale-names.property.test.js`
    - Exhaustively verify all seven canonical Persian weekday names and twelve canonical Jalali month names.
    - Tag as Property 12.
    - _Requirements: Property 12; 9.3, 9.6_

  - [x] 3.15 Add `tests/js/properties/event-aria-label.property.test.js`
    - Generate student names and each valid status and assert `{studentName} – {statusLabel}` exactly.
    - Tag as Property 13 and run at least 100 iterations.
    - _Requirements: Property 13; 10.3_

  - [x] 3.16 Add `tests/js/properties/jalali-full-date.property.test.js`
    - Generate valid dates and assert weekday, Western numeric day, Jalali month, and four-digit Western numeric year appear in that order.
    - Tag as Property 14 and run at least 100 iterations.
    - _Requirements: Property 14; 3.1_

  - [x] 3.17 Add `tests/js/unit/calendar-modules.test.js`
    - Unit-test FullCalendar configuration/callback contracts, sidebar state updates, filter serialization/debounce/clear behavior, drawer state transitions, and malformed-event handling.
    - Assert no module imports a sibling directly and initialization remains retryable.
    - _Requirements: 1.1–1.11, 2.1–2.5, 3.4–3.9, 6.5–6.10, 7.2–7.6_

- [x] 4. Implement design-system CSS and responsive calendar presentation
  - [x] 4.1 Create `resources/css/admin/calendar.css`
    - Define all calendar layout, FullCalendar overrides, event cards, filters, sidebar, skeleton, empty/error states, drawer, focus states, and responsive breakpoints with BEM selectors.
    - Use only existing primitive/semantic/component tokens for color, spacing, radius, shadows, typography, z-index, and motion; do not add duplicate tokens, hardcoded colors/rgba, raw presentation magic numbers, or `!important`.
    - Use Vazirmatn, CSS logical properties, glass treatment, `--shadow-md`/`--shadow-lg`, button variants, status border-inline-start mapping, stable minimum dimensions, and reduced-motion rules.
    - Implement mobile-first behavior for 390/430/768/1024/1366/1600/1920 widths: horizontal sidebar below 1024px, filter overlay and full-width bottom-sheet drawer below 768px, no horizontal overflow, and 44px coarse-pointer targets.
    - _Requirements: 5.6–5.8, 6.1, 6.10, 8.1–8.9, 9.1, 10.2, 10.6, 11.1–11.5, 12.3, 12.5_

  - [x] 4.2 Add automated CSS/design-system compliance checks in `tests/js/unit/calendar-css.test.js`
    - Assert calendar CSS exists, uses BEM selectors and logical properties, contains no hardcoded hex/rgba colors or duplicate token definitions, includes reduced-motion behavior, and references the required glass/button/shadow/motion tokens.
    - Assert the compiled page has no horizontal overflow and preserves the calendar minimum-height contract at required viewport sizes.
    - _Requirements: 8.1–8.9, 9.1, 11.1–11.5, 12.5_

- [x] 5. Integrate the entry point, page boot, and browser behavior
  - [x] 5.1 Update `vite.config.js` and the established page boot path for the calendar entry
    - Add `resources/js/calendar/calendar-app.js` as a dedicated Vite input and import `resources/css/admin/calendar.css` through the established CSS entry architecture without duplicating global tokens.
    - Mount the app only on the calendar page, ensure Alpine focus support is initialized through the existing `resources/js/app.js` boot path, and keep FullCalendar in a separate dynamic chunk.
    - Verify the generated manifest references the calendar entry and separate FullCalendar chunk.
    - _Requirements: 2.2, 2.4–2.5, 6.8, 10.7, 12.1_

  - [x] 5.2 Add browser integration coverage in `tests/browser/admin-calendar.spec.js`
    - Using the project’s existing Playwright setup, verify page load and FullCalendar render, previous/next/today navigation, sidebar day selection within 300ms, week rollover, loading skeleton/error retry/empty states, and preserved filters.
    - Verify selecting each filter issues one debounced API request, clear-all restores all sessions, event cards render required content, and clicking an event opens the drawer.
    - Verify Escape, overlay, close button, and focus restoration behavior, plus responsive sidebar/filter/drawer behavior at the defined breakpoints.
    - _Requirements: 1.1–1.11, 3.4–3.8, 6.1–6.10, 7.2–7.6, 11.1–11.5_

  - [x] 5.3 Add automated accessibility coverage in `tests/browser/admin-calendar-accessibility.spec.js`
    - Assert logical Tab order sidebar → filters → grid → drawer, Enter/Space activation, visible token-based focus rings, associated select labels, `aria-current="date"`, event role/aria-label, dialog semantics, and `x-trap` focus cycling.
    - Assert Escape closes the drawer and returns focus to the triggering card; check reduced-motion behavior and status/focus contrast against the dark surfaces.
    - _Requirements: 3.9, 6.5–6.11, 9.1, 10.1–10.7, 11.5_

  - [x] 5.4 Add automated performance coverage in `tests/Feature/Admin/CalendarPerformanceTest.php` and `tests/browser/admin-calendar-performance.spec.js`
    - Benchmark the events endpoint with up to 50 sessions and assert the normal-load target is below 200ms using a stable test threshold/method.
    - Assert FullCalendar is code-split/lazy-loaded, filter bursts produce one request after 300ms, loading uses a stable skeleton/minimum height, and no measurable CLS is introduced.
    - _Requirements: 12.1–12.5_

- [x] 6. Checkpoint — validate backend and module foundations
  - Ensure all focused PHP and JavaScript tests pass, the API schema matches the design, and no questions or requirement conflicts remain before final wiring verification.

- [x] 7. Complete cross-layer verification and quality gates
  - [x] 7.1 Add final verification coverage and scripts without changing production behavior
    - Target `tests/Feature/Admin/CalendarPageTest.php`, `tests/browser/admin-calendar.spec.js`, and existing project quality-check entry points as appropriate.
    - Verify named routes, admin authorization, Blade component resolution, Vite manifest entry/chunking, no circular sibling imports, no inline style/JS, no raw query in Blade, no N+1 relation loading, no console logging/debug statements, and safe generic API failures.
    - Cover graceful degradation when JavaScript or the event feed fails while content outside the calendar remains intact.
    - _Requirements: 1.10, 2.1–2.5, 4.1–4.5, 6.5–6.11, Error Handling, Graceful Degradation, all steering decisions_

  - [x] 7.2 Run the repository verification commands and record only implementation-relevant failures
    - Run `php artisan test --filter=Calendar` (including property/integration suites through their configured command), `npm run build`, and the relevant static/accessibility/performance checks.
    - After Blade/config changes, run `php artisan optimize:clear`; do not start a development server or watcher as part of automated verification.
    - Confirm responsive automated checks at 390, 430, 768, 1024, 1366, 1600, and 1920 pixels and confirm no application code outside the feature is modified.
    - _Requirements: 8.1–8.9, 10.1–10.7, 11.1–11.5, 12.1–12.5_

- [x] 8. Final checkpoint — ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional test tasks and may be skipped for a faster MVP; core implementation tasks are not optional.
- The plan preserves the design decisions: existing `ClassSession` data/scopes, resource-based API responses, thin controllers, Form Request validation, Alpine `x-trap`, orchestrator-only module imports, client-side Jalali helpers, FullCalendar dynamic import, token-only BEM CSS, RTL logical properties, and no inline presentation/behavior.
- Test-related tasks are intentionally placed under implementation parents and must be implemented only when selected. Every property test explicitly maps to its design property and uses at least 100 iterations where the design calls for fast-check.
- Convert the feature design into a series of prompts for a code-generation LLM that will implement each step with incremental progress. Make sure that each prompt builds on the previous prompts, and ends with wiring things together. There should be no hanging or orphaned code that isn't integrated into a previous step. Focus ONLY on tasks that involve writing, modifying, or testing code.
- No production application code has been changed by this planning task.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "2.1", "2.2", "2.3", "2.4", "3.1", "4.1"] },
    { "id": 1, "tasks": ["1.3", "1.4", "3.2", "3.3", "3.4", "3.5"] },
    { "id": 2, "tasks": ["1.5", "1.6", "1.7", "1.8", "1.9", "1.10", "1.11", "3.6", "3.7", "4.2"] },
    { "id": 3, "tasks": ["3.8", "3.9", "3.10", "3.11", "3.12", "3.13", "3.14", "3.15", "3.16", "3.17", "5.1"] },
    { "id": 4, "tasks": ["5.2", "5.3", "5.4"] },
    { "id": 5, "tasks": ["7.1"] },
    { "id": 6, "tasks": ["7.2"] }
  ]
}
```
