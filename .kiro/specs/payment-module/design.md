# Design Document: Payment Module

## Overview

MVP tuition payment tracking for the admin panel. Adds a `payments` table (many-per-`student_enrollment_id`, installment-style), a thin `PaymentController` (CRUD, mirrors `StudentEnrollmentController`), a `PaymentMethodEnum`, computed `payment_status` accessor, sortable/paginated index, and a `StudentFinancialSummary` block on `admin.students.show`. No repository layer, no gateway abstraction, no queues/events. Payment timeline integration is explicitly out of scope this phase.

Resolved decisions (per finalized requirements.md):
- Overpayment (`amount_paid > amount_total - discount`) → hard validation rejection, no partial allowance.
- No `StudentHistoryService`/timeline integration in this phase.
- Multiple `Payment` rows per `StudentEnrollment` allowed (installments).

## Architecture

Standard Laravel MVC, same shape as the Enrollment module:

```
routes/web.php (admin/payments prefix, role:admin)
  → PaymentController (index/create/store/edit/update/destroy)
    → Payment model (belongsTo StudentEnrollment, casts, payment_status accessor)
  → views/admin/payments/{index,create,edit}.blade.php

StudentController@show
  → aggregates Payment rows via StudentEnrollment relation → StudentFinancialSummary
  → views/admin/students/show.blade.php (new summary partial block)
```

No new service class is introduced — `remaining_balance` computation and validation live directly in the controller (mirrors `StudentController`'s inline validation style), since the logic is a single arithmetic formula, not multi-step orchestration like `EnrollmentService`.

## Components and Interfaces

### PaymentMethodEnum (`app/Enums/PaymentMethodEnum.php`)

```php
enum PaymentMethodEnum: string
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### Payment model (`app/Models/Payment.php`)

- `belongsTo(StudentEnrollment::class)`
- Fillable: `student_enrollment_id, amount_total, discount, amount_paid, remaining_balance, payment_date, payment_method, notes`
- Casts: `amount_total`/`discount`/`amount_paid`/`remaining_balance` → `decimal:2`; `payment_date` → `date`; `payment_method` → `PaymentMethodEnum::class`
- Accessor `getPaymentStatusAttribute()` (not persisted):
  - `remaining_balance == 0` → `'fully_paid'`
  - `remaining_balance > 0 && amount_paid > 0` → `'partial'`
  - else (`remaining_balance > 0 && amount_paid == 0`) → `'owing'`

### PaymentController (`app/Http/Controllers/Admin/PaymentController.php`)

Mirrors `StudentEnrollmentController` structure exactly:

- `index(Request)`: sortable (`amount_total, discount, amount_paid, remaining_balance, payment_date`), default `payment_date` desc, `with('enrollment.student')`, `paginate(15)->withQueryString()`.
- `create()`: passes `StudentEnrollment::with('student')->get()` for the select dropdown.
- `store(Request)`: validates per Requirement 1 (rules below), computes `remaining_balance = amount_total - discount - amount_paid`, creates, redirects with `admin.payment_created_successfully`.
- `edit(Payment)`: pre-fills form.
- `update(Request, Payment)`: same validation as store, recomputes `remaining_balance`, redirects with `admin.payment_updated_successfully`.
- `destroy(Payment)`: deletes, redirects with `admin.payment_deleted_successfully`.

Validation rules (store/update, shared):

```php
'student_enrollment_id' => ['required', 'exists:student_enrollments,id'],
'amount_total' => ['required', 'numeric', 'min:0'],
'discount' => ['nullable', 'numeric', 'min:0', 'lte:amount_total'],
'amount_paid' => ['required', 'numeric', 'min:0'],
'payment_date' => ['required', 'date'],
'payment_method' => ['required', Rule::in(PaymentMethodEnum::values())],
'notes' => ['nullable', 'string'],
```

The overpayment rule (`amount_paid <= amount_total - discount`) cannot be expressed as a single Laravel rule string across three fields, so it's checked manually after base validation, adding an error to `amount_paid` via `$validator->after()` or a manual `throw ValidationException::withMessages(...)` — following the same pattern `EnrollmentService` uses for custom business-rule errors surfaced as `ValidationException`.

### Routes (`routes/web.php`)

```php
Route::middleware(['auth', 'role:admin'])->prefix('admin/payments')->name('admin.payments.')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::get('/create', [PaymentController::class, 'create'])->name('create');
    Route::post('/', [PaymentController::class, 'store'])->name('store');
    Route::get('/{payment}/edit', [PaymentController::class, 'edit'])->name('edit');
    Route::put('/{payment}', [PaymentController::class, 'update'])->name('update');
    Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('destroy');
});
```

### Views

- `resources/views/admin/payments/index.blade.php`: same shell as `enrollments/index.blade.php` — sortable headers via `admin.partials.sort-th` for the 5 sortable columns, status badge (3 Tailwind color variants keyed by `payment_status`), delete form with `admin.delete_payment_confirm`, empty state `admin.no_payments_found`.
- `resources/views/admin/payments/create.blade.php`, `edit.blade.php`: form fields for all 7 inputs, enrollment select shows student + instrument for disambiguation.
- `resources/views/admin/students/show.blade.php`: insert a new **StudentFinancialSummary** block between Section 1 (Student Info) and Section 2 (Enrollments), same card styling as other sections.

### StudentFinancialSummary computation

In `StudentController@show`, alongside existing `$timeline` computation:

```php
$enrollmentIds = $student->enrollments()->withTrashed()->pluck('id');
$payments = Payment::whereIn('student_enrollment_id', $enrollmentIds)->get();

$financialSummary = [
    'total_owing' => $payments->sum('remaining_balance'),
    'total_paid'  => $payments->sum('amount_paid'),
    'last_payment_date' => $payments->max('payment_date'),
];
```

`withTrashed()` is required because Requirement 7.5 mandates payments for soft-deleted enrollments still count. Passed to the view as `$financialSummary`; view renders `admin.no_payments_yet` when `$payments->isEmpty()`.

## Data Models

### `payments` table migration

New file: `database/migrations/2026_07_04_000000_create_payments_table.php`

```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();
    $table->decimal('amount_total', 10, 2);
    $table->decimal('discount', 10, 2)->default(0);
    $table->decimal('amount_paid', 10, 2);
    $table->decimal('remaining_balance', 10, 2);
    $table->date('payment_date');
    $table->string('payment_method', 20);
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['student_enrollment_id', 'payment_date']);
});
```

Note: `cascadeOnDelete()` on the FK matches the existing convention (e.g. `student_enrollments.student_id`), but since `student_enrollments` uses soft deletes and Requirement 7.5 requires payments to survive enrollment soft-delete, this is safe — `cascadeOnDelete` only fires on a hard/forced delete of the enrollment row, which doesn't happen via the app's `SoftDeletes` flow.

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Remaining balance computation

For any numeric `amount_total >= 0`, `discount` with `0 <= discount <= amount_total`, and `amount_paid` with `0 <= amount_paid <= amount_total - discount`, creating or updating a Payment SHALL store `remaining_balance` exactly equal to `amount_total - discount - amount_paid`.

**Validates: Requirements 1.12, 2.4**

### Property 2: Valid payment persists with correct types

For any valid combination of `student_enrollment_id` (existing), `amount_total`, `discount`, `amount_paid`, `payment_date`, `payment_method`, and optional `notes`, submitting the create (or edit) form SHALL persist a Payment whose stored attributes equal the submitted values, with `payment_method` retrievable as a `PaymentMethodEnum` instance and `payment_date` retrievable as a date.

**Validates: Requirements 1.2, 2.2, 7.2, 7.3, 7.4**

### Property 3: Invalid numeric fields are rejected

For any submission where `amount_total`, `discount`, or `amount_paid` is missing, non-numeric, or negative, the PaymentController SHALL reject the request with a validation error and SHALL NOT create or modify a Payment record.

**Validates: Requirements 1.4, 1.5, 1.6, 2.3**

### Property 4: Relational amount constraints are enforced

For any submission where `discount > amount_total`, or where `amount_paid > amount_total - discount`, the PaymentController SHALL reject the request with a validation error and SHALL NOT create or modify a Payment record.

**Validates: Requirements 1.7, 1.8, 2.3**

### Property 5: Invalid enrollment, date, or method are rejected

For any submission with a non-existent `student_enrollment_id`, a missing/invalid `payment_date`, or a `payment_method` outside `{cash, card, bank_transfer}`, the PaymentController SHALL reject the request with a validation error.

**Validates: Requirements 1.3, 1.9, 1.10, 2.3**

### Property 6: Payment status classification

For any Payment with computed `remaining_balance` and stored `amount_paid`: if `remaining_balance == 0` the status SHALL be `fully_paid`; if `remaining_balance > 0 && amount_paid > 0` the status SHALL be `partial`; if `remaining_balance > 0 && amount_paid == 0` the status SHALL be `owing`.

**Validates: Requirements 5.2, 5.3, 5.4**

### Property 7: Index sorting

For any collection of Payments and any of the sortable columns (`amount_total, discount, amount_paid, remaining_balance, payment_date`) with either direction, the PaymentsIndex results SHALL be ordered by that column and direction; when no sort is specified, results SHALL default to `payment_date` descending.

**Validates: Requirements 4.2, 4.3, 4.4**

### Property 8: Financial summary aggregation

For any Student with any set of Payment records across any of that Student's enrollments (including soft-deleted enrollments), the StudentFinancialSummary SHALL report `total_owing` equal to the sum of `remaining_balance`, `total_paid` equal to the sum of `amount_paid`, and `last_payment_date` equal to the maximum `payment_date` across those Payments; when the set is empty, `total_owing` and `total_paid` SHALL be zero.

**Validates: Requirements 6.1, 6.2, 6.3**

### Property 9: Payments survive enrollment soft-delete

For any StudentEnrollment with associated Payment records, soft-deleting the enrollment SHALL NOT delete, modify, or orphan-error its associated Payment records.

**Validates: Requirements 7.5**

## Error Handling

- All validation failures return via Laravel's standard `ValidationException` → `back()->withInput()->withErrors(...)`, consistent with `StudentEnrollmentController`.
- The overpayment/discount relational checks are enforced via `$validator->after()` closure (since they span multiple fields) rather than a single string rule, then surfaced as normal field errors on `amount_paid`/`discount`.
- Non-existent `Payment`/`StudentEnrollment` route-model bindings 404 automatically (implicit binding), same as existing controllers.
- No custom exception types introduced.

## Testing Strategy

**Unit tests** (specific examples/edge cases):
- Create form renders enrollment options (1.1).
- Edit form pre-fills existing values (2.1).
- Successful create/update/delete redirect with correct translation-keyed flash message (1.13, 2.5, 3.1, 3.2).
- Delete confirmation prompt uses correct key (3.3, view-only).
- Index empty state shows `admin.no_payments_found` (4.6).
- Index pagination is 15/page (4.5).
- Payment belongs to StudentEnrollment relationship resolves correctly (7.1).

**Property tests** (PBT is appropriate here — pure validation/computation logic with a large input space):
- Library: PHP `Eris` (or `giorgiosironi/eris`) is not currently in the project; since it's not already a dependency, use plain randomized data generation with PHPUnit's data-driven loops seeded via `fake()->numberBetween`/`fake()->randomFloat` executed in a loop of 100+ iterations inside a single test method (avoids adding a new PBT library dependency for an MVP). Each property test runs a minimum of 100 iterations internally.
- Tag format in each test's doc-comment: **Feature: payment-module, Property {number}: {property text}**
- Properties 1–9 above each map to exactly one property-based test method.
- Mocks are not needed — all logic is pure DB/Eloquent, tests run against the SQLite in-memory test DB already used by the project's test suite.

## Files to Create

- `app/Enums/PaymentMethodEnum.php`
- `app/Models/Payment.php`
- `app/Http/Controllers/Admin/PaymentController.php`
- `database/migrations/2026_07_04_000000_create_payments_table.php`
- `resources/views/admin/payments/index.blade.php`
- `resources/views/admin/payments/create.blade.php`
- `resources/views/admin/payments/edit.blade.php`
- `tests/Feature/Admin/PaymentControllerTest.php` (or equivalent, per project's existing test layout)

## Files to Modify

- `routes/web.php` — add `admin/payments` route group.
- `app/Models/StudentEnrollment.php` — add `payments(): HasMany` relation.
- `app/Http/Controllers/Admin/StudentController.php` — compute `$financialSummary` in `show()`.
- `resources/views/admin/students/show.blade.php` — add StudentFinancialSummary block.
- `lang/fa/admin.php` — new keys (see below).
- `lang/en/admin.php` — new keys (mirrored).

## Translation Keys

Add to both `lang/fa/admin.php` and `lang/en/admin.php`:

```
payments, create_payment, edit_payment, new_payment,
payment_created_successfully, payment_updated_successfully, payment_deleted_successfully,
delete_payment_confirm, no_payments_found, no_payments_yet,
student_enrollment, amount_total, discount, amount_paid, remaining_balance,
payment_date, payment_method, financial_summary, total_owing, total_paid, last_payment_date,
payment_methods.cash, payment_methods.card, payment_methods.bank_transfer,
payment_statuses.fully_paid, payment_statuses.partial, payment_statuses.owing,
```

Persian values for `payment_statuses` are fixed by Requirement 8.6–8.8: `fully_paid` → «پرداخت کامل», `partial` → «ناقص», `owing` → «بدهکار».

## Migration Risk Notes

- Purely additive: one new table (`payments`), zero `ALTER` statements on any existing table.
- FK references `student_enrollments.id` only (read-only reference); no changes to `students`, `class_sessions`, `recurring_schedules`, `teachers`, or any scheduling/attendance table.
- `StudentEnrollment` model gets one new `HasMany` relation method — additive, no existing method signatures change.
- `StudentController@show` gains one new query + one new view variable — existing `$student`/`$timeline` logic untouched.
- No changes to `StudentHistoryService`, `ConflictDetectionService`, `SessionGeneratorService`, or any session/attendance/scheduling code — satisfies Requirement 9.1–9.2.
- Rollback (`down()`) simply drops the `payments` table — fully reversible, no data loss to other modules.
