# Requirements Document

## Introduction

This document defines the phased roadmap for completing the Laravel admin panel in `c:\laragon\www\parsian-music`. The repository is the source of truth. The roadmap covers database architecture, domain logic, CRUD, authorization, validation, security, workflows, reporting, and a later admin-only UI stabilization phase. Public website UI and teacher-profile-page implementation are out of scope.

Implementation is **blocked pending explicit user approval**. This document is a planning artifact only; it does not authorize application-code or database changes. No frontend redesign work is included.

## Verified Baseline

The current repository contains Laravel 13.8/PHP 8.3 application layers for models, controllers, requests, services, DTOs, enums, policies, middleware, Blade views, migrations, factories, seeders, and tests. Admin routes are primarily in `routes/web.php`; `routes/admin.php` is empty; the dashboard route is in `routes/dashboard.php`; and `bootstrap/app.php` registers `routes/web.php`.

The current schema includes users, teachers, students, instruments, teacher-instrument assignments, enrollments, recurring schedules, class sessions, attendance, rooms, subscriptions, legacy payments, invoices, invoice items, invoice payments, leads, notifications, settings, institute profile, login logs, and password-reset records. The current source also contains both a deprecated legacy payment controller and an unrouted invoice billing domain.

The current implementation provides substantial CRUD, scheduling, lead, settings, dashboard-service, policy, and authentication infrastructure, but has verified gaps in billing ownership, direct-session attendance support, granular authorization, audit logging, password-policy enforcement, authentication-test alignment, schema/content-model alignment, route organization, and dashboard revenue rendering.

## Glossary

- **Admin_Panel**: Authenticated Laravel administration surface for institute staff and privileged operators.
- **Implementation_Process**: Any code, migration, configuration, or test change that changes application behavior.
- **Approval_Gate**: Explicit written user approval required before the Implementation_Process starts.
- **Phase_0_Baseline**: Read-only audit, contract inventory, migration-risk register, and decision record for the existing repository.
- **Core_Domain**: Teachers, instruments, teacher-instrument assignments, teacher schedules, students, enrollments, class sessions, attendance, rooms, subscriptions, and billing records.
- **Teacher_Schedule**: Persisted recurring or availability schedule associated with a teacher and used by lesson planning.
- **Lesson**: A class session represented by `class_sessions`, whether linked to an enrollment or to direct student, teacher, and instrument references.
- **Canonical_Billing**: The single billing model selected from the existing legacy `payments` model and the invoice/item/payment domain.
- **Audit_Event**: Immutable record of an administrative create, update, delete, status, assignment, authentication, or security action.
- **Login_Attempt**: A recorded successful or failed authentication attempt with security-safe metadata.
- **Granular_Permission**: Named capability evaluated through Laravel Policy/Gate authorization rather than only a broad role check.
- **Dashboard_Metric**: A named, documented value returned by the dashboard reporting service and rendered by an admin view.
- **Admin_UI_Stabilization**: Limited integration work that connects approved backend contracts to existing admin Blade views without redesigning the public site or visual system.
- **Content_Model**: The entity guidance in `.kiro/steering/19_CONTENT_MODEL.md`; it is authoritative for future content fields but does not authorize changing frozen teacher-profile work in this roadmap.

## Requirements

### Requirement 1: Audit baseline and approval control

**User Story:** As a project owner, I want a verified baseline and approval boundary, so that implementation decisions are traceable and safe.

#### Acceptance Criteria

1. WHEN Phase_0_Baseline is completed, THE Admin_Panel SHALL document every required baseline element, including project structure, schema, migrations, models, relationships, routes, views, services, policies, middleware, business rules, reusable infrastructure, completed features, gaps, technical debt, security concerns, and migration risks with concrete repository paths.
2. WHILE the Approval_Gate is pending, THE Implementation_Process SHALL not modify application code, migrations, database data, routes, views, configuration, or tests.
3. WHEN the Approval_Gate is granted, THE Implementation_Process SHALL record the approved phase and scope before implementation formally begins, and SHALL permit only the approved implementation changes after formal start.
4. IF the repository conflicts with an existing specification, THEN THE Admin_Panel SHALL record the repository behavior as the verified baseline and identify the specification as an inconsistent dependency.

### Requirement 2: Database architecture and migration safety

**User Story:** As a maintainer, I want a coherent schema plan, so that future admin functionality can evolve without unsafe or conflicting migrations.

#### Acceptance Criteria

1. WHEN a schema change is approved, THE Admin_Panel SHALL implement the change through a new migration that includes applicable explicit foreign-key actions, indexes, timestamps, and soft deletes required by the affected domain.
2. WHEN Phase_1 data design is approved, THE Admin_Panel SHALL document ownership and lifecycle rules for teacher schedules, direct and enrollment-linked lessons, attendance, rooms, enrollments, subscriptions, and Canonical_Billing.
3. IF an existing table or column conflicts with the Content_Model or a workspace database rule, THEN THE Admin_Panel SHALL document a backward-compatible migration path and data-conversion decision before implementation.
4. WHEN monetary data is persisted by Canonical_Billing, THE Admin_Panel SHALL store values as integer smallest-unit amounts and document the unit used by the application.
5. IF migration-history ambiguity is detected, THEN THE Admin_Panel SHALL register the ambiguity as a maintenance risk and SHALL not rewrite historical migrations as part of this roadmap.

### Requirement 3: Teacher, instrument, schedule, and student domain

**User Story:** As an administrator, I want complete core records and relationships, so that the institute can manage teaching operations reliably.

#### Acceptance Criteria

1. WHEN an administrator creates or updates a teacher, THE Admin_Panel SHALL validate the teacher record and persist the approved operational fields, lifecycle status, ordering data, and Content_Model fields selected for the admin scope.
2. WHEN an administrator assigns an instrument to a teacher, THE Admin_Panel SHALL prevent duplicate assignments and SHALL maintain at most one primary assignment per teacher.
3. WHEN an administrator creates or updates a Teacher_Schedule, THE Admin_Panel SHALL validate weekday, time, duration, and room data against the approved scheduling rules.
4. WHEN an administrator creates or updates a student, THE Admin_Panel SHALL validate the student record and preserve the relationship between the student record and its user account when an account exists.
5. IF a teacher is assigned to a Lesson for an instrument the teacher does not teach, THEN THE Admin_Panel SHALL reject the assignment with a validation error.

### Requirement 4: Lessons, attendance, and operational workflows

**User Story:** As an administrator, I want lesson and attendance workflows to support current scheduling paths, so that operational records remain consistent.

#### Acceptance Criteria

1. WHEN an administrator creates a Lesson, THE Admin_Panel SHALL validate direct student, teacher, and instrument references or the enrollment reference according to the approved lesson contract.
2. WHEN an administrator creates a Lesson that overlaps an existing teacher, student, or room booking, THE Admin_Panel SHALL reject the conflicting booking and identify the conflicting resource.
3. WHEN an administrator records attendance for a Lesson, THE Admin_Panel SHALL require the Lesson to have either an enrollment reference or direct student, teacher, and instrument references before accepting the attendance record.
4. WHEN a Lesson changes status or attendance, THE Admin_Panel SHALL update related subscription usage and reporting data through a transaction when multiple records change.
5. IF a Lesson lacks a required approved relationship, THEN THE Admin_Panel SHALL return a validation error and SHALL not create a partial operational record.

### Requirement 5: Canonical billing direction

**User Story:** As an institute operator, I want one reliable billing workflow, so that payment records and dashboard revenue use the same source of truth.

#### Acceptance Criteria

1. WHEN Phase_1 billing design begins, THE Admin_Panel SHALL select either the legacy `payments` domain or the invoice/item/payment domain as Canonical_Billing and SHALL document the rejection or migration plan for the other domain.
2. WHEN Canonical_Billing is selected, THE Admin_Panel SHALL expose approved admin workflows for billing records, invoice items where applicable, payment registration, lifecycle transitions, and authorization.
3. WHEN a billing record is created or changed, THE Admin_Panel SHALL validate monetary units, status transitions, ownership references, and duplicate-payment rules defined by the approved billing contract.
4. WHEN the dashboard requests revenue, THE Admin_Panel SHALL calculate the metric from Canonical_Billing and SHALL render the same value in the approved dashboard contract.
5. IF the billing direction is not approved, THEN THE Implementation_Process SHALL not add billing migrations, controllers, routes, or views.

### Requirement 6: Roles, permissions, and validation

**User Story:** As a security-conscious administrator, I want consistent authorization and input validation, so that each admin action is limited to an approved capability.

#### Acceptance Criteria

1. WHEN an authenticated user requests an Admin_Panel action, THE Admin_Panel SHALL evaluate the action through the applicable Policy or Gate and SHALL apply route middleware as a complementary boundary.
2. WHEN a user attempts an action outside the user’s Granular_Permission, THE Admin_Panel SHALL deny the action and SHALL preserve the protected record.
3. WHEN an admin form receives input, THE Admin_Panel SHALL validate the input through a dedicated Form Request or an approved reusable validator before invoking domain services.
4. IF an administrator attempts to manage an equal-rank or higher-rank user, THEN THE Admin_Panel SHALL deny the action according to the approved role hierarchy.
5. WHEN an admin list loads related records, THE Admin_Panel SHALL eager-load required relationships and SHALL return a bounded, authorized result set.

### Requirement 7: Security, audit events, login attempts, and password policy

**User Story:** As a system owner, I want security events and sensitive operations to be traceable, so that abuse and account compromise can be investigated.

#### Acceptance Criteria

1. WHEN an administrative mutation succeeds, THE Admin_Panel SHALL create an Audit_Event containing the actor, action, target type, target identifier, timestamp, and security-safe change summary.
2. WHEN a login succeeds or fails, THE Admin_Panel SHALL create a Login_Attempt with security-safe metadata and SHALL exclude passwords, reset tokens, and unnecessary sensitive values.
3. WHEN a configured failed-login threshold is reached, THE Admin_Panel SHALL apply the approved account lockout and rate-limit policy and SHALL expose a generic authentication response.
4. WHEN an administrator or reset flow sets a password, THE Admin_Panel SHALL enforce the approved password policy and SHALL enforce a forced-password-change state when the account requires it.
5. WHEN an upload is accepted, THE Admin_Panel SHALL validate file type, size, storage location, and replacement behavior before persisting the file reference.
6. IF an unexpected security or persistence error occurs, THEN THE Admin_Panel SHALL record a safe diagnostic event and SHALL return a non-sensitive error response.

### Requirement 8: Dashboard metrics and data contracts

**User Story:** As an institute operator, I want accurate operational metrics, so that the dashboard supports decisions using current data.

#### Acceptance Criteria

1. WHEN the dashboard loads, THE Admin_Panel SHALL obtain students, teachers, lessons, attendance, leads, subscriptions, notifications, and revenue metrics from documented service contracts.
2. WHEN a Dashboard_Metric has no source data, THE Admin_Panel SHALL return an explicit zero or empty-state value defined by the metric contract.
3. WHEN a Dashboard_Metric is displayed, THE Admin_Panel SHALL render the returned value rather than a placeholder label.
4. WHEN dashboard data requires multiple aggregates, THE Admin_Panel SHALL use bounded queries and eager loading or cached reporting data according to the approved performance contract.
5. IF Canonical_Billing is not approved, THEN THE Admin_Panel SHALL label revenue as unavailable and SHALL not present an inferred revenue value.

### Requirement 9: Admin UI stabilization boundary

**User Story:** As an administrator, I want existing admin screens to reflect stable backend contracts, so that the panel is usable without introducing redesign risk.

#### Acceptance Criteria

1. WHERE Admin_UI_Stabilization is approved, THE Admin_Panel SHALL connect existing admin Blade screens to approved backend contracts after Phase 1 through Phase 3 are stable.
2. WHERE Admin_UI_Stabilization is approved, THE Admin_Panel SHALL remove backend-query and business-logic responsibilities from Blade views without changing the public website visual design.
3. WHERE Admin_UI_Stabilization is approved, THE Admin_Panel SHALL replace inline admin styles, inline event handlers, and inline scripts with the existing approved component and asset patterns.
4. IF a requested UI change is a public website redesign or teacher-profile-page implementation, THEN THE Admin_Panel SHALL exclude the change from this roadmap and require a separate approved phase.
5. WHEN an admin view exposes a form or action, THE Admin_Panel SHALL preserve CSRF protection, semantic controls, validation feedback, and authorized route visibility.

## Scope and Dependencies

- Phase 0 is read-only and must precede implementation.
- Phase 1 depends on the billing direction decision and the approved lesson/schedule data contract.
- Phase 2 depends on stable actor and domain identifiers from Phase 1.
- Phase 3 depends on Canonical_Billing and stable reporting contracts.
- Phase 4 is explicitly deferred until the preceding phases stabilize and is limited to admin integration; it is not frontend redesign.
- Existing specs such as `admin-settings-module`, `payment-module`, `student-history`, `student-subscription`, and scheduling specs are dependencies to reconcile, not sources of truth when they conflict with the repository.
