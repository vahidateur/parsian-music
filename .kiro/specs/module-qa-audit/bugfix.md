# Bugfix Requirements Document

## Introduction

This bugfix scope covers authenticated operational workflows: attendance, class scheduling, enrollments, and invoicing/payments. The defects allow invalid cross-record data, leave invoices in unusable states, or permit concurrent requests to violate financial and scheduling invariants. Application code is out of scope for this audit document.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN an administrator or the teacher who owns a session submits an existing `student_id` that is not the session's direct student or enrollment student THEN the system records attendance for that unrelated student against the session.
1.2 WHEN two requests create overlapping sessions for the same teacher, student, or room after both conflict checks complete but before either session is persisted THEN the system stores both conflicting sessions.
1.3 WHEN an invoice request supplies a valid `student_id` and an `enrollment_id` belonging to another student THEN the system creates or updates an invoice containing that inconsistent student/enrollment pair.
1.4 WHEN two administrators register payments whose individual amounts do not exceed the same invoice's pre-request outstanding balance THEN the system can persist payments whose combined completed amount exceeds the invoice total.
1.5 WHEN a completed payment is deleted from a fully paid invoice THEN the system leaves the invoice in the terminal `paid` status despite a positive outstanding balance.
1.6 WHEN an administrator changes an enrollment's teacher to an existing teacher who is not assigned to that enrollment's instrument THEN the system persists the incompatible teacher/instrument assignment.

### Expected Behavior (Correct)

2.1 WHEN an administrator or the teacher who owns a session submits attendance THEN the system SHALL accept only the student represented by that session and SHALL reject unrelated student identifiers without writing an attendance record.
2.2 WHEN concurrent session-creation requests would overlap for the same teacher, student, or room THEN the system SHALL persist at most one request and SHALL reject the conflicting request without incrementing subscription usage.
2.3 WHEN an invoice request includes an enrollment THEN the system SHALL verify that the enrollment belongs to the selected student and SHALL reject a mismatched pair without persisting an invoice change.
2.4 WHEN concurrent payment-registration requests target the same invoice THEN the system SHALL serialize balance evaluation and payment persistence so the completed-payment total never exceeds the invoice total.
2.5 WHEN deleting a completed payment causes an invoice to have a positive outstanding balance THEN the system SHALL transition the invoice to the applicable non-terminal unpaid status and SHALL allow subsequent valid payment registration.
2.6 WHEN an enrollment's teacher is changed THEN the system SHALL verify that the selected teacher is assigned to the enrollment's instrument and SHALL reject incompatible assignments without updating the enrollment.

### Unchanged Behavior (Regression Prevention)

3.1 WHEN the submitted attendance student is the session's direct student or enrollment student THEN the system SHALL CONTINUE TO create or update that single attendance record with the submitted valid status and note.
3.2 WHEN a requested session does not overlap any existing teacher, student, or room booking THEN the system SHALL CONTINUE TO create the session and increment the matching subscription usage once.
3.3 WHEN an invoice references no enrollment or references an enrollment owned by its selected student THEN the system SHALL CONTINUE TO persist valid line items, derived totals, and permitted invoice transitions.
3.4 WHEN sequential payments remain within an issued invoice's outstanding balance THEN the system SHALL CONTINUE TO record them and derive `partially_paid` or `paid` status from completed-payment totals.
3.5 WHEN the selected teacher is assigned to the enrollment's instrument THEN the system SHALL CONTINUE TO allow the authorized enrollment update.