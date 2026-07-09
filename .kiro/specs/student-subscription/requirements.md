# Student Subscription Module - Requirements

## Introduction

The Student Subscription Module manages monthly music lesson subscriptions for students at the academy. Each subscription ties a student to a specific teacher-instrument pair, tracks session usage with flexible overage allowance, and manages payment status through manual renewal and payment tracking.

## Glossary

- **Subscription**: A monthly contract between a Student, Teacher, and Instrument with a fixed session allocation (default 4 sessions/month at 3,000,000 toman)
- **Session Deduction**: The process of consuming allocated sessions when creating class sessions; permissive (allows overages)
- **Renewal**: Manual action to reset session count and payment status for the next billing period
- **Payment Status**: One of: paid, unpaid, overdue
- **Session Note**: Text annotation tracking overage sessions and session deductions
- **Double-booking**: Conflict when two sessions are scheduled in the same room at the same time
- **Room**: Fixed locations: A101, A102, A103

## Requirements

### Requirement 1: Subscription Model

**User Story:** As an academy manager, I want subscriptions tied to student+teacher+instrument combinations, so that I can track lesson allocations per student-teacher-instrument pair.

#### Acceptance Criteria

1. WHEN a student is assigned to a teacher for a specific instrument, THE System SHALL create one Subscription record per unique student-teacher-instrument pair
2. WHEN a Subscription is created, THE System SHALL set default values: 4 sessions allocated, 3,000,000 toman/month, payment_status = unpaid
3. WHEN a student has multiple teacher-instrument pairs, THE System SHALL maintain separate Subscription records for each pair
4. WHEN a Subscription exists, THE System SHALL store: student_id, teacher_id, instrument_id, sessions_allocated, sessions_used, monthly_fee, payment_status, renewal_date, created_date

### Requirement 2: Session Form Redesign

**User Story:** As an admin, I want to create class sessions with a redesigned form including student autocomplete, so that I can quickly schedule lessons with better UX.

#### Acceptance Criteria

1. WHEN creating a class session, THE Session_Form SHALL display a student autocomplete field (searchable by name/id) instead of selecting active enrollment
2. WHEN a student is selected via autocomplete, THE System SHALL load all available teacher-instrument-room combinations for that student's active subscriptions
3. WHEN the teacher field is populated, THE System SHALL display only rooms available in the fixed set: A101, A102, A103
4. WHEN a room is selected, THE System SHALL validate against existing sessions to warn of double-booking conflicts (optional)
5. WHEN all fields are filled and the form is submitted, THE System SHALL create the ClassSession and decrement the Subscription's sessions_used by 1

### Requirement 3: Manual Teacher and Instrument Selection

**User Story:** As an admin, I want to manually select teacher and instrument during session creation, so that I maintain flexibility in scheduling.

#### Acceptance Criteria

1. WHEN creating a class session, THE Session_Form SHALL provide dropdown/select fields for manual Teacher selection
2. WHEN creating a class session, THE Session_Form SHALL provide dropdown/select fields for manual Instrument selection
3. WHEN a Teacher and Instrument are manually selected, THE System SHALL validate that a Subscription exists for the selected Student-Teacher-Instrument triple
4. IF no Subscription exists for the selected triple, THEN THE System SHALL display an error and prevent session creation

### Requirement 4: Room Selection and Double-booking Prevention

**User Story:** As an admin, I want to select from fixed rooms and optionally prevent double-booking, so that I can manage physical space efficiently.

#### Acceptance Criteria

1. WHEN creating a class session, THE Session_Form SHALL display room selection as a dropdown restricted to: A101, A102, A103
2. WHEN a room and date/time are selected, THE System SHALL check if any existing session occupies that room at the same time
3. WHERE double-booking prevention is enabled, IF a conflict is detected, THEN THE System SHALL warn the user but allow override and creation
4. WHEN a session is created with an overage, THE System SHALL store a note indicating overage sessions were booked

### Requirement 5: Session Deduction and Overage Tracking

**User Story:** As an admin, I want to allow flexible session creation with overage tracking, so that I can accommodate student needs while monitoring usage.

#### Acceptance Criteria

1. WHEN a class session is created for a student, THE System SHALL decrement the associated Subscription's sessions_used by 1
2. IF sessions_used would exceed sessions_allocated, THEN THE System SHALL allow the session creation and mark the session with an overage note
3. WHEN a session is created with overage, THE System SHALL record the overage in Subscription's notes field with timestamp and reason (if provided)
4. WHEN viewing a Subscription, THE Admin SHALL see current sessions_used, sessions_allocated, and overage count

### Requirement 6: Payment Tracking and Status

**User Story:** As an academy manager, I want to track payment status for each subscription, so that I can manage revenue and identify delinquent accounts.

#### Acceptance Criteria

1. THE Subscription model SHALL store payment_status with values: paid, unpaid, overdue
2. WHEN a Subscription is created, THE System SHALL set payment_status to unpaid
3. WHEN payment is received for a Subscription, THE Admin SHALL manually update payment_status to paid via the payment tracking interface
4. WHEN a Subscription's renewal_date passes without payment, THE System SHALL mark payment_status as overdue
5. WHEN viewing any payment-related interface, THE System SHALL display a clear indicator of the Subscription's current payment_status

### Requirement 7: Manual Renewal and Session Reset

**User Story:** As an academy manager, I want to manually renew subscriptions, so that I can reset session counts and track recurring revenue.

#### Acceptance Criteria

1. WHEN a Subscription is displayed, THE System SHALL show a "Renew Subscription" button
2. WHEN the Renew button is clicked, THE Admin SHALL confirm renewal before processing
3. WHEN renewal is confirmed, THE System SHALL: reset sessions_used to 0, set new renewal_date (current_date + 30 days), reset payment_status to unpaid
4. THE System SHALL record the renewal action with timestamp in an audit log or notes field

### Requirement 8: Student Profile Subscription Summary

**User Story:** As a student, I want to see my subscription summary in my profile, so that I can track my remaining sessions, payment status, and renewal dates.

#### Acceptance Criteria

1. WHEN a student views their profile, THE System SHALL display a Subscription Summary section
2. WHEN the Subscription Summary is rendered, THE System SHALL show a list of all active Subscriptions for that student
3. FOR each Subscription in the list, THE System SHALL display: Teacher name, Instrument, Remaining sessions (sessions_allocated - sessions_used), Payment status (paid/unpaid/overdue), Renewal date, Monthly fee
4. WHEN a Subscription has overages, THE System SHALL display the overage count and a note indicator
5. WHEN payment_status is overdue, THE System SHALL highlight the Subscription with a visual indicator (color, badge, or icon)

### Requirement 9: Remove Active Enrollment Field

**User Story:** As an admin, I want the session form to no longer use active enrollment field, so that I can reduce redundancy and use the new subscription model.

#### Acceptance Criteria

1. WHEN the session creation form is rendered, THE System SHALL NOT display the active_enrollment field
2. WHEN an existing session form references active_enrollment, THE System SHALL remove this field from the form UI
3. THE System SHALL use student autocomplete + subscription lookup instead of enrollment status validation

### Requirement 10: Data Model Updates

**User Story:** As a developer, I want new database fields and relationships, so that I can support subscription and payment tracking.

#### Acceptance Criteria

1. THE Subscription model SHALL have fields: id, student_id (FK), teacher_id (FK), instrument_id (FK), sessions_allocated, sessions_used, monthly_fee, payment_status, renewal_date, notes, created_at, updated_at
2. THE ClassSession model SHALL have an optional: subscription_overage_notes field to track overage sessions
3. WHEN a Subscription is created or renewed, THE System SHALL automatically set renewal_date to 30 days from creation/renewal date
4. THE System SHALL support querying Subscriptions by: student_id, payment_status, renewal_date (for overdue detection)
