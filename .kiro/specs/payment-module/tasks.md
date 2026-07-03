# Implementation Plan: Payment Module

## Overview

MVP tuition payment tracking: `payments` table, `PaymentMethodEnum`, `Payment` model, `PaymentController` (CRUD), routes, views, StudentFinancialSummary on student show page, and translation keys. Mirrors existing `StudentEnrollmentController` patterns.

## Tasks

- [x] 1. Create payments table migration and PaymentMethodEnum
  - Create `database/migrations/2026_07_04_000000_create_payments_table.php` per design schema
  - Create `app/Enums/PaymentMethodEnum.php` with `Cash, Card, BankTransfer` cases and `values()` helper
  - Run migration against test DB
  - _Requirements: 7.2, 7.4_

- [ ] 2. Implement Payment model
  - [ ] 2.1 Create `app/Models/Payment.php`
    - `belongsTo(StudentEnrollment::class)`, fillable fields, casts (`decimal:2` for amounts, `date` for `payment_date`, `PaymentMethodEnum::class` for `payment_method`)
    - `getPaymentStatusAttribute()` accessor implementing fully_paid/partial/owing classification
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 5.1, 5.2, 5.3, 5.4_

  - [ ]* 2.2 Write property test for Payment status classification
    - **Property 6: Payment status classification**
    - **Validates: Requirements 5.2, 5.3, 5.4**

  - [ ] 2.3 Add `payments(): HasMany` relation to `app/Models/StudentEnrollment.php`
    - _Requirements: 7.1_

  - [ ]* 2.4 Write unit test for Payment-StudentEnrollment relationship
    - Test relation resolves correctly (7.1)
    - _Requirements: 7.1_

- [ ] 3. Add translation keys
  - Add all new keys to `lang/fa/admin.php` and `lang/en/admin.php` per design's Translation Keys section (payments, create_payment, edit_payment, financial_summary, payment_statuses.*, payment_methods.*, etc.)
  - Persian values for `payment_statuses`: fully_paid=«پرداخت کامل», partial=«ناقص», owing=«بدهکار»
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8_

- [ ] 4. Implement PaymentController validation and create/update logic
  - [ ] 4.1 Create `app/Http/Controllers/Admin/PaymentController.php` with `store` and `update` methods
    - Base validation rules (student_enrollment_id exists, amount_total/discount/amount_paid numeric+min:0, discount lte:amount_total, payment_date date, payment_method in enum values, notes nullable string)
    - `$validator->after()` closure for overpayment check (`amount_paid <= amount_total - discount`)
    - Compute `remaining_balance = amount_total - discount - amount_paid` on create/update
    - Redirect with `admin.payment_created_successfully` / `admin.payment_updated_successfully`
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 1.11, 1.12, 1.13, 2.2, 2.3, 2.4, 2.5_

  - [ ]* 4.2 Write property test for remaining balance computation
    - **Property 1: Remaining balance computation**
    - **Validates: Requirements 1.12, 2.4**

  - [ ]* 4.3 Write property test for valid payment persistence with correct types
    - **Property 2: Valid payment persists with correct types**
    - **Validates: Requirements 1.2, 2.2, 7.2, 7.3, 7.4**

  - [ ]* 4.4 Write property test for invalid numeric field rejection
    - **Property 3: Invalid numeric fields are rejected**
    - **Validates: Requirements 1.4, 1.5, 1.6, 2.3**

  - [ ]* 4.5 Write property test for relational amount constraints
    - **Property 4: Relational amount constraints are enforced**
    - **Validates: Requirements 1.7, 1.8, 2.3**

  - [ ]* 4.6 Write property test for invalid enrollment/date/method rejection
    - **Property 5: Invalid enrollment, date, or method are rejected**
    - **Validates: Requirements 1.3, 1.9, 1.10, 2.3**

- [ ] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Implement PaymentController read/delete actions and routes
  - [ ] 6.1 Add `index`, `create`, `edit`, `destroy` methods to `PaymentController`
    - `index`: sortable columns (amount_total, discount, amount_paid, remaining_balance, payment_date), default `payment_date` desc, `with('enrollment.student')`, `paginate(15)->withQueryString()`
    - `create`: pass `StudentEnrollment::with('student')->get()`
    - `edit`: pre-fill form with existing Payment values
    - `destroy`: delete Payment, redirect with `admin.payment_deleted_successfully`
    - _Requirements: 1.1, 2.1, 3.1, 3.2, 4.2, 4.3, 4.4, 4.5_

  - [ ]* 6.2 Write property test for index sorting
    - **Property 7: Index sorting**
    - **Validates: Requirements 4.2, 4.3, 4.4**

  - [ ] 6.3 Add `admin/payments` route group to `routes/web.php`
    - _Requirements: 1.1, 2.1, 3.1_

  - [ ]* 6.4 Write unit tests for form rendering and flash messages
    - Create form renders enrollment options (1.1)
    - Edit form pre-fills existing values (2.1)
    - Successful create/update/delete redirect with correct flash message (1.13, 2.5, 3.1, 3.2)
    - Index empty state shows `admin.no_payments_found` (4.6)
    - Index pagination is 15/page (4.5)
    - _Requirements: 1.1, 1.13, 2.1, 2.5, 3.1, 3.2, 4.5, 4.6_

- [ ] 7. Implement payment views
  - [ ] 7.1 Create `resources/views/admin/payments/index.blade.php`
    - Sortable headers via `admin.partials.sort-th`, status badge with 3 Tailwind color variants, delete confirmation using `admin.delete_payment_confirm`, empty state
    - _Requirements: 3.3, 4.1, 4.6, 5.5_

  - [ ] 7.2 Create `resources/views/admin/payments/create.blade.php` and `edit.blade.php`
    - Form fields for all 7 inputs, enrollment select shows student + instrument
    - _Requirements: 1.1, 1.11, 2.1_

- [ ] 8. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 9. Implement StudentFinancialSummary
  - [ ] 9.1 Compute `$financialSummary` in `StudentController@show`
    - Query payments via `withTrashed()` enrollments, compute `total_owing`, `total_paid`, `last_payment_date`
    - _Requirements: 6.1, 6.2, 6.3, 7.5_

  - [ ]* 9.2 Write property test for financial summary aggregation
    - **Property 8: Financial summary aggregation**
    - **Validates: Requirements 6.1, 6.2, 6.3**

  - [ ]* 9.3 Write property test for payments surviving enrollment soft-delete
    - **Property 9: Payments survive enrollment soft-delete**
    - **Validates: Requirements 7.5**

  - [ ] 9.4 Add StudentFinancialSummary block to `resources/views/admin/students/show.blade.php`
    - Position between Student Info and Enrollments sections, render `admin.no_payments_yet` when empty
    - _Requirements: 6.1, 6.3, 6.4, 8.5_

- [ ] 10. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP.
- Each task references specific requirement clauses for traceability.
- Properties 1-9 map to the design's Correctness Properties section; each is its own sub-task.
- No changes are made to `ClassSession`, `RecurringSchedule`, `SessionGeneratorService`, `ConflictDetectionService`, or `StudentHistoryService` (Requirements 9.1, 9.2).

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1"] },
    { "id": 1, "tasks": ["2.1", "2.3", "3"] },
    { "id": 2, "tasks": ["2.2", "2.4", "4.1"] },
    { "id": 3, "tasks": ["4.2", "4.3", "4.4", "4.5", "4.6"] },
    { "id": 4, "tasks": ["6.1", "6.3"] },
    { "id": 5, "tasks": ["6.2", "6.4", "7.1", "7.2"] },
    { "id": 6, "tasks": ["9.1"] },
    { "id": 7, "tasks": ["9.2", "9.3", "9.4"] }
  ]
}
```
