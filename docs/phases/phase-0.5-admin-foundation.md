# Phase 0.5 — Admin Foundation

**Status:** In progress — Task 1 complete; awaiting approval  
**Authority:** Official implementation contract for the Admin Foundation phase  
**Created:** 2026-07-23

## Purpose

Establish one reusable, token-driven, accessible admin UI foundation before any new domain feature is implemented. Existing domain pages are not redesigned in this phase; they may only be inspected for reuse requirements.

## Scope

- Design tokens and admin semantic aliases.
- Admin shell, sidebar, topbar, navigation system, page layout, page header, and breadcrumbs.
- Reusable buttons, status badges, form controls, tables, pagination, modal, drawer, toast, alert, and empty/loading/error states.
- Page-scoped FullCalendar and ApexCharts wrappers without adding dependencies unless already approved and installed.
- Responsive, keyboard, accessibility, performance, and reduced-motion review of the foundation.

## Explicit non-scope

Teacher, student, finance, backup, scheduling, reports, permissions, migrations, domain services, domain controllers, domain routes, and business rules are prohibited in this phase. No new feature page may be assembled as part of the foundation.

## Deliverables

1. Token-driven admin CSS entry and documented semantic aliases.
2. One admin layout owner with reusable shell regions.
3. Canonical navigation components with active and collapsed states.
4. Canonical form, data-display, overlay, feedback, and state components.
5. Lightweight calendar and chart wrappers with page-scoped loading contracts.
6. Updated component registry and usage map where required.
7. Responsive and accessibility review evidence for 390, 430, 768, 1024, 1366, 1600, and 1920 widths.

## Component list

| Area | Components |
|---|---|
| Shell | Admin Shell, Sidebar, Topbar, Navbar, Navigation System |
| Layout | Page Layout, Page Header, Breadcrumb |
| Actions | Button, Status Badge, Confirmation Action |
| Forms | Form Section, Input Group, Searchable Dropdown, Date Picker/Range, Tabs |
| Data | Card, KPI Card, Data Table, Pagination, Filter Toolbar, Filter Chips |
| Overlays | Modal, Drawer, Toast, Alert |
| States | Empty State, Loading State, Error State |
| Visualization | FullCalendar Wrapper, ApexCharts Wrapper, Progress Indicator |

## Canonical folder structure


```text
resources/views/layouts/admin.blade.php
resources/views/components/admin/{sidebar,topbar,navbar,page-header,status-badge,
  form-section,input-group,searchable-dropdown,date-picker,tabs,data-table,
  filter-toolbar,filter-chips,pagination,drawer,toast,loading-state,error-state,
  full-calendar,apex-chart}.blade.php
resources/css/admin-foundation.css
resources/css/admin/{tokens,shell,navigation,forms,data,overlays,states,visualization}.css
resources/js/admin-foundation.js
```

The exact existing project convention may be reused, but each component must have one clear owner and the registry path must be updated when a path changes.

## Rules

- Blade provides semantic structure only; no inline style or inline event handlers.
- CSS owns layout, appearance, responsive behavior, motion, and reduced-motion handling.
- Alpine owns UI state only; it must not contain business logic or styling calculations.
- All values use the primitive → semantic → component token chain.
- No raw color, radius, font, z-index, or page-layout magic numbers.
- RTL, logical properties, visible focus, keyboard operation, and minimum touch targets are mandatory.
- Drawer/modal must use dialog semantics, Escape, focus trap, focus return, and fail closed if required accessibility behavior cannot initialize.
- FullCalendar and ApexCharts are page-scoped wrappers; do not load them globally.
- No database, migration, domain query, authorization rule, or business logic belongs in this phase.
- No new dependency may be added without explicit approval and justification.

## Acceptance criteria

- [ ] All listed foundation components have a documented owner, API, and registry entry.
- [ ] Admin pages use canonical components instead of repeating equivalent HTML.
- [ ] Admin appearance is token-driven and does not alter the public fantasy theme.
- [ ] Sidebar, topbar, navigation, drawer, modal, and overlays are keyboard accessible.
- [ ] Drawer remains closed when focus trap, Escape handling, ARIA state, or focus restoration cannot be enabled.
- [ ] Components expose meaningful empty, loading, error, and disabled states.
- [ ] Tables and forms remain usable at every required breakpoint without horizontal overflow.
- [ ] Calendar/chart wrappers have stable containers, lazy/page-scoped loading, and no domain data logic.
- [ ] No Teacher, Student, Finance, Backup, Scheduling, Reports, Permission, migration, or domain route work is included.

## Exit criteria

- [ ] Production build passes.
- [ ] Relevant Blade/component checks pass.
- [ ] Accessibility review passes keyboard, focus, ARIA, reduced-motion, and contrast checks.
- [ ] Responsive review passes at 390, 430, 768, 1024, 1366, 1600, and 1920 widths.
- [ ] Performance review confirms no unnecessary global JavaScript or chart/calendar loading.
- [ ] Registry paths resolve for all `existing` entries; planned entries remain clearly marked.
- [ ] No duplicated foundation component or competing layout owner remains.
- [ ] Working tree baseline is clean or an explicit exception is documented and approved.
- [ ] Phase 0.5 is reviewed and frozen before Phase 1 domain work begins.

## Gate

This document is a planning and acceptance contract only. It does not authorize implementation, dependency installation, commit, deletion, or migration. Any scope change requires a Change Request or a new phase.


## Reference implementation policy

The existing dashboard HTML is a visual and interaction reference only. Its HTML, CSS, JavaScript, markup structure, inline styles, hardcoded data, and event-handling code must not be copied into the Laravel implementation. Each dashboard section must be rebuilt with the canonical Blade components, project tokens, approved Alpine state, and the Phase 0.5 design system.

The reference HTML must remain available until the Blade dashboard has demonstrated visual and functional parity, including responsive behavior, keyboard accessibility, loading/empty/error states, and approved interaction behavior. Removal or archival of the reference requires explicit approval after the parity review; it must not happen earlier.
