# Implementation Plan: Payment Module

> **STATUS: SUPERSEDED (reconciled 2026-07-28).**
>
> This spec targeted a flat `payments` table hanging off `student_enrollments`.
> The repository has since shipped a proper billing domain (Sprint 19.4–19.5):
> `invoices` + `invoice_items` + `invoice_payments`, driven by
> `App\Services\InvoiceService` and the `InvoiceStatusEnum` state machine.
> The legacy `PaymentController` carried an explicit `@deprecated` marker
> (RC0 audit finding C-05) and "Do NOT add new routes pointing to this controller".
>
> Registering `admin.payments.*` routes would have resurrected the conflicting
> older architecture. Instead the **intent** of this spec was delivered on the
> newer architecture. See `Reconciliation` below for the task-by-task mapping.
>
> No further work should be scheduled from this file. Future billing work
> belongs in a new `admin-billing` spec.

## Reconciliation

| Spec task | Actual state | Resolution |
|---|---|---|
| 1. `payments` migration + `PaymentMethodEnum` | Already existed | `PaymentMethodEnum` retained and extended with `label()` / `color()`; it is now the shared method enum for `invoice_payments`. The `payments` table is left in place (data preserved). |
| 2.1 `Payment` model | Already existed | Kept, annotated `@deprecated`, read-only. No consumers. |
| 2.2 Payment status property test | Not applicable | Status is now owned by `InvoiceStatusEnum` + `Invoice::syncStatusFromPayments()`. Covered by `InvoiceAdminTest::test_issue_then_partial_then_full_payment_drives_status`. |
| 2.3 `StudentEnrollment::payments()` | Deliberately absent | Superseded by `StudentEnrollment::invoices()` (already present). Adding a second money relation would create two competing ledgers. |
| 2.4 relation unit test | Not applicable | Covered by the invoice relations. |
| 3. Translation keys | Was missing entirely | Delivered as billing keys in `lang/fa/admin.php` and `lang/en/admin.php` (invoices, payments, financial summary). |
| 4.x `PaymentController` validation / remaining balance | Superseded | `InvoiceController` validates the invoice header + line items; balance is derived (`Invoice::amountDue()`), never typed in. Overpayment is blocked by a `max:` rule against `amountDue()` plus `InvoiceService` domain guards. |
| 6.1 index/create/edit/destroy | Superseded | Implemented in `InvoiceController` (index with student/status filters + sortable columns, create, show, edit, update, destroy, issue, cancel, duplicate). |
| 6.3 `admin/payments` routes | Superseded | `admin/invoices` route group registered in `routes/web.php`, incl. nested `admin.invoices.payments.{store,destroy}`. |
| 7.x payment views | Superseded | `resources/views/admin/invoices/{index,create,edit,show,form}.blade.php`, plus reusable `admin/partials/flash.blade.php` and `<x-admin.status-badge>`. |
| 9.x StudentFinancialSummary | Was missing | Delivered: `StudentController::buildFinancialSummary()` (Invoice-based, excludes cancelled invoices) + `admin/partials/financial-summary.blade.php` rendered on the student show page. |
| — | Bug found during integration | `Invoice::recalculate()` double-counted line discounts (`subtotal` summed net line totals, then the saving hook subtracted `discount` again). Fixed to use gross line value, matching `DemoSeeder` and the migration semantics. |

## Retired artifacts

Removed as dead/duplicate code (all were unrouted):

- `app/Http/Controllers/Admin/PaymentController.php`
- `resources/views/admin/payments/{index,create,edit}.blade.php`
- `tests/Feature/Admin/PaymentControllerTest.php`

Retained, non-destructive:

- `app/Models/Payment.php` (deprecated, read-only)
- `database/migrations/*_create_payments_table.php` and the `payments` table — dropping
  the table needs an explicit data-migration decision from the product owner.

## Validation

`tests/Feature/Admin/InvoiceAdminTest.php` — 15 tests / 50 assertions, all passing:
invoice CRUD, derived totals, item replacement, issue → partial → full payment
status progression, overpayment rejection, draft-payment rejection, payment
deletion reopening the balance, cancel/duplicate, and the student financial summary
(including the cancelled-invoice exclusion).

`npm run build` passes. `php artisan route:list --path=admin/invoices` shows all 12 routes.
