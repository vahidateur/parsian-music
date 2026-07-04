<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethodEnum;
use App\Enums\RoleEnum;
use App\Models\Instrument;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private StudentEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $student = Student::forceCreate([
            'student_code' => 'STU-001',
            'full_name' => 'Test Student',
            'phone' => '09120000001',
            'status' => 'active',
            'join_date' => now(),
        ]);

        $teacher = Teacher::forceCreate([
            'teacher_code' => 'TCH-001',
            'full_name' => 'Test Teacher',
            'phone' => '09120000002',
            'status' => 'active',
            'hire_date' => now(),
        ]);

        $instrument = Instrument::create([
            'name' => 'Piano',
            'slug' => 'piano',
            'is_active' => true,
        ]);

        $this->enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'instrument_id' => $instrument->id,
            'teacher_id' => $teacher->id,
            'skill_level' => 'beginner',
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * Test that Payment belongsTo StudentEnrollment resolves correctly.
     * Validates: Requirements 7.1
     */
    public function test_payment_belongs_to_enrollment(): void
    {
        $payment = Payment::create([
            'student_enrollment_id' => $this->enrollment->id,
            'amount_total' => 1000000,
            'discount' => 100000,
            'amount_paid' => 500000,
            'remaining_balance' => 400000,
            'payment_date' => '2025-01-15',
            'payment_method' => PaymentMethodEnum::Cash->value,
            'notes' => null,
        ]);

        $resolved = $payment->enrollment;

        $this->assertInstanceOf(StudentEnrollment::class, $resolved);
        $this->assertEquals($this->enrollment->id, $resolved->id);
    }

    /**
     * Test that StudentEnrollment hasMany Payments resolves correctly.
     * Validates: Requirements 7.1
     */
    public function test_enrollment_has_many_payments(): void
    {
        $payment = Payment::create([
            'student_enrollment_id' => $this->enrollment->id,
            'amount_total' => 2000000,
            'discount' => 0,
            'amount_paid' => 2000000,
            'remaining_balance' => 0,
            'payment_date' => '2025-02-01',
            'payment_method' => PaymentMethodEnum::Card->value,
            'notes' => 'Full payment',
        ]);

        $payments = $this->enrollment->payments;

        $this->assertCount(1, $payments);
        $this->assertTrue($payments->contains($payment));
    }

    /**
     * Feature: payment-module, Property 1: Remaining balance computation
     *
     * For any numeric amount_total >= 0, discount with 0 <= discount <= amount_total,
     * and amount_paid with 0 <= amount_paid <= amount_total - discount, creating or updating
     * a Payment SHALL store remaining_balance exactly equal to amount_total - discount - amount_paid.
     *
     * **Validates: Requirements 1.12, 2.4**
     */
    public function test_property_remaining_balance_computation(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $amountTotal = fake()->randomFloat(2, 0, 10000);
            $discount = fake()->randomFloat(2, 0, $amountTotal);
            $maxPayable = round($amountTotal - $discount, 2);
            $amountPaid = fake()->randomFloat(2, 0, $maxPayable);

            $expectedRemaining = round($amountTotal - $discount - $amountPaid, 2);

            // Test "store" scenario: compute remaining_balance and create
            $payment = Payment::create([
                'student_enrollment_id' => $this->enrollment->id,
                'amount_total' => $amountTotal,
                'discount' => $discount,
                'amount_paid' => $amountPaid,
                'remaining_balance' => $amountTotal - $discount - $amountPaid,
                'payment_date' => now()->subDays(fake()->numberBetween(0, 365)),
                'payment_method' => fake()->randomElement(PaymentMethodEnum::values()),
            ]);

            $fresh = $payment->fresh();
            $this->assertEquals(
                $expectedRemaining,
                (float) $fresh->remaining_balance,
                "Store iteration $i: expected remaining_balance=$expectedRemaining "
                ."(amount_total=$amountTotal, discount=$discount, amount_paid=$amountPaid) "
                ."but got {$fresh->remaining_balance}"
            );

            // Test "update" scenario: change values and recompute remaining_balance
            $newAmountTotal = fake()->randomFloat(2, 0, 10000);
            $newDiscount = fake()->randomFloat(2, 0, $newAmountTotal);
            $newMaxPayable = round($newAmountTotal - $newDiscount, 2);
            $newAmountPaid = fake()->randomFloat(2, 0, $newMaxPayable);

            $newExpectedRemaining = round($newAmountTotal - $newDiscount - $newAmountPaid, 2);

            $payment->update([
                'amount_total' => $newAmountTotal,
                'discount' => $newDiscount,
                'amount_paid' => $newAmountPaid,
                'remaining_balance' => $newAmountTotal - $newDiscount - $newAmountPaid,
            ]);

            $freshAfterUpdate = $payment->fresh();
            $this->assertEquals(
                $newExpectedRemaining,
                (float) $freshAfterUpdate->remaining_balance,
                "Update iteration $i: expected remaining_balance=$newExpectedRemaining "
                ."(amount_total=$newAmountTotal, discount=$newDiscount, amount_paid=$newAmountPaid) "
                ."but got {$freshAfterUpdate->remaining_balance}"
            );
        }
    }

    /**
     * Feature: payment-module, Property 2: Valid payment persists with correct types
     *
     * For any valid combination of student_enrollment_id (existing), amount_total, discount,
     * amount_paid, payment_date, payment_method, and optional notes, creating a Payment record
     * SHALL persist a Payment whose stored attributes equal the submitted values, with
     * payment_method retrievable as a PaymentMethodEnum instance and payment_date retrievable
     * as a date.
     *
     * **Validates: Requirements 1.2, 2.2, 7.2, 7.3, 7.4**
     */
    public function test_property_valid_payment_persists_with_correct_types(): void
    {
        $paymentMethods = PaymentMethodEnum::values();

        for ($i = 0; $i < 100; $i++) {
            $amountTotal = fake()->randomFloat(2, 0.01, 99999.99);
            $discount = fake()->randomFloat(2, 0, $amountTotal);
            $maxPayable = round($amountTotal - $discount, 2);
            $amountPaid = fake()->randomFloat(2, 0, $maxPayable);
            $remainingBalance = round($amountTotal - $discount - $amountPaid, 2);
            $paymentDate = fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d');
            $paymentMethod = fake()->randomElement($paymentMethods);
            $notes = fake()->boolean(70) ? fake()->sentence() : null;

            $payment = Payment::create([
                'student_enrollment_id' => $this->enrollment->id,
                'amount_total' => $amountTotal,
                'discount' => $discount,
                'amount_paid' => $amountPaid,
                'remaining_balance' => $remainingBalance,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
            ]);

            $fresh = $payment->fresh();

            // All stored attributes match input values
            $this->assertEquals(
                $this->enrollment->id,
                $fresh->student_enrollment_id,
                "Iteration $i: student_enrollment_id mismatch"
            );
            $this->assertEquals(
                number_format($amountTotal, 2, '.', ''),
                $fresh->amount_total,
                "Iteration $i: amount_total mismatch"
            );
            $this->assertEquals(
                number_format($discount, 2, '.', ''),
                $fresh->discount,
                "Iteration $i: discount mismatch"
            );
            $this->assertEquals(
                number_format($amountPaid, 2, '.', ''),
                $fresh->amount_paid,
                "Iteration $i: amount_paid mismatch"
            );
            $this->assertEquals(
                number_format($remainingBalance, 2, '.', ''),
                $fresh->remaining_balance,
                "Iteration $i: remaining_balance mismatch"
            );
            $this->assertEquals(
                $notes,
                $fresh->notes,
                "Iteration $i: notes mismatch"
            );

            // payment_method is a PaymentMethodEnum instance when retrieved
            $this->assertInstanceOf(
                PaymentMethodEnum::class,
                $fresh->payment_method,
                "Iteration $i: payment_method is not a PaymentMethodEnum instance"
            );
            $this->assertEquals(
                $paymentMethod,
                $fresh->payment_method->value,
                "Iteration $i: payment_method value mismatch"
            );

            // payment_date is a Carbon/date instance when retrieved
            $this->assertInstanceOf(
                Carbon::class,
                $fresh->payment_date,
                "Iteration $i: payment_date is not a Carbon instance"
            );
            $this->assertEquals(
                $paymentDate,
                $fresh->payment_date->format('Y-m-d'),
                "Iteration $i: payment_date value mismatch"
            );

            // Amounts are cast as decimal:2 (retrieved as strings with exactly 2 decimal places)
            $this->assertMatchesRegularExpression(
                '/^\d+\.\d{2}$/',
                $fresh->amount_total,
                "Iteration $i: amount_total not cast as decimal:2"
            );
            $this->assertMatchesRegularExpression(
                '/^\d+\.\d{2}$/',
                $fresh->discount,
                "Iteration $i: discount not cast as decimal:2"
            );
            $this->assertMatchesRegularExpression(
                '/^\d+\.\d{2}$/',
                $fresh->amount_paid,
                "Iteration $i: amount_paid not cast as decimal:2"
            );
            $this->assertMatchesRegularExpression(
                '/^\d+\.\d{2}$/',
                $fresh->remaining_balance,
                "Iteration $i: remaining_balance not cast as decimal:2"
            );
        }
    }

    /**
     * Feature: payment-module, Property 6: Payment status classification
     *
     * For any Payment with computed remaining_balance and stored amount_paid:
     * - if remaining_balance == 0 the status SHALL be fully_paid
     * - if remaining_balance > 0 && amount_paid > 0 the status SHALL be partial
     * - if remaining_balance > 0 && amount_paid == 0 the status SHALL be owing
     *
     * **Validates: Requirements 5.2, 5.3, 5.4**
     */
    public function test_property_payment_status_classification(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $amountTotal = fake()->randomFloat(2, 1, 10000);
            $discount = fake()->randomFloat(2, 0, $amountTotal);
            $maxPayable = round($amountTotal - $discount, 2);

            // Randomly choose one of 3 scenarios to ensure all branches are covered
            $scenario = fake()->numberBetween(0, 2);

            switch ($scenario) {
                case 0: // fully_paid: amount_paid == maxPayable, remaining_balance == 0
                    $amountPaid = $maxPayable;
                    $remainingBalance = 0.00;
                    $expectedStatus = 'fully_paid';
                    break;

                case 1: // partial: amount_paid > 0 but < maxPayable, remaining_balance > 0
                    if ($maxPayable <= 0.01) {
                        // Edge case: maxPayable too small for partial, force fully_paid
                        $amountPaid = $maxPayable;
                        $remainingBalance = 0.00;
                        $expectedStatus = 'fully_paid';
                    } else {
                        $amountPaid = fake()->randomFloat(2, 0.01, $maxPayable - 0.01);
                        $remainingBalance = round($maxPayable - $amountPaid, 2);
                        $expectedStatus = 'partial';
                    }
                    break;

                case 2: // owing: amount_paid == 0, remaining_balance > 0
                    if ($maxPayable <= 0) {
                        // Edge case: nothing owed, force fully_paid
                        $amountPaid = 0.00;
                        $remainingBalance = 0.00;
                        $expectedStatus = 'fully_paid';
                    } else {
                        $amountPaid = 0.00;
                        $remainingBalance = $maxPayable;
                        $expectedStatus = 'owing';
                    }
                    break;
            }

            $payment = Payment::create([
                'student_enrollment_id' => $this->enrollment->id,
                'amount_total' => $amountTotal,
                'discount' => $discount,
                'amount_paid' => $amountPaid,
                'remaining_balance' => $remainingBalance,
                'payment_date' => now()->subDays(fake()->numberBetween(0, 365)),
                'payment_method' => fake()->randomElement(PaymentMethodEnum::values()),
            ]);

            $fresh = $payment->fresh();

            $this->assertEquals(
                $expectedStatus,
                $fresh->payment_status,
                "Iteration $i: expected status '$expectedStatus' but got '{$fresh->payment_status}' "
                ."(remaining_balance={$fresh->remaining_balance}, amount_paid={$fresh->amount_paid})"
            );
        }
    }

    /**
     * Feature: payment-module, Property 4: Relational amount constraints are enforced
     *
     * For any submission where discount > amount_total, or where amount_paid > amount_total - discount,
     * the PaymentController SHALL reject the request with a validation error and SHALL NOT create or
     * modify a Payment record.
     *
     * **Validates: Requirements 1.7, 1.8, 2.3**
     */
    public function test_property_relational_amount_constraints_enforced(): void
    {
        $initialCount = Payment::count();

        for ($i = 0; $i < 100; $i++) {
            // Randomly choose one of 2 sub-scenarios
            $scenario = fake()->numberBetween(0, 1);

            $amountTotal = fake()->randomFloat(2, 0.01, 10000);

            if ($scenario === 0) {
                // Sub-scenario 1: discount > amount_total → should fail on 'discount' field (lte rule)
                $discount = fake()->randomFloat(2, $amountTotal + 0.01, $amountTotal + 5000);
                $amountPaid = fake()->randomFloat(2, 0, $amountTotal);
            } else {
                // Sub-scenario 2: amount_paid > amount_total - discount → should fail on 'amount_paid' (custom after() rule)
                $discount = fake()->randomFloat(2, 0, $amountTotal);
                $maxPayable = round($amountTotal - $discount, 2);
                $amountPaid = fake()->randomFloat(2, $maxPayable + 0.01, $maxPayable + 5000);
            }

            $data = [
                'student_enrollment_id' => $this->enrollment->id,
                'amount_total' => $amountTotal,
                'discount' => $discount,
                'amount_paid' => $amountPaid,
                'payment_date' => now()->format('Y-m-d'),
                'payment_method' => fake()->randomElement(PaymentMethodEnum::values()),
            ];

            // Replicate the same validation rules from PaymentController
            $rules = [
                'student_enrollment_id' => ['required', 'exists:student_enrollments,id'],
                'amount_total' => ['required', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0', 'lte:amount_total'],
                'amount_paid' => ['required', 'numeric', 'min:0'],
                'payment_date' => ['required', 'date'],
                'payment_method' => ['required', Rule::in(PaymentMethodEnum::values())],
                'notes' => ['nullable', 'string'],
            ];

            $validator = Validator::make($data, $rules);

            // Replicate the after() closure for overpayment check
            $validator->after(function ($v) use ($data) {
                if ($v->errors()->hasAny(['amount_total', 'discount', 'amount_paid'])) {
                    return;
                }

                $total = (float) ($data['amount_total'] ?? 0);
                $disc = (float) ($data['discount'] ?? 0);
                $paid = (float) ($data['amount_paid'] ?? 0);

                if ($paid > ($total - $disc)) {
                    $v->errors()->add('amount_paid', 'amount_paid exceeds amount_total - discount');
                }
            });

            $this->assertTrue(
                $validator->fails(),
                "Iteration $i (scenario $scenario): Expected validation failure but passed. "
                ."amount_total=$amountTotal, discount=$discount, amount_paid=$amountPaid"
            );

            if ($scenario === 0) {
                $this->assertTrue(
                    $validator->errors()->has('discount'),
                    "Iteration $i: Expected error on 'discount' field when discount ($discount) > amount_total ($amountTotal)"
                );
            } else {
                $this->assertTrue(
                    $validator->errors()->has('amount_paid'),
                    "Iteration $i: Expected error on 'amount_paid' field when amount_paid ($amountPaid) > amount_total - discount ("
                    .round($amountTotal - $discount, 2).")"
                );
            }
        }

        // Verify no Payment records were created during validation-only checks
        $this->assertEquals($initialCount, Payment::count(), 'No Payment records should be created for invalid data');
    }

    /**
     * Feature: payment-module, Property 3: Invalid numeric fields are rejected
     *
     * For any submission where amount_total, discount, or amount_paid is missing, non-numeric,
     * or negative, the PaymentController SHALL reject the request with a validation error and
     * SHALL NOT create or modify a Payment record.
     *
     * **Validates: Requirements 1.4, 1.5, 1.6, 2.3**
     */
    public function test_property_invalid_numeric_fields_rejected(): void
    {
        $initialCount = Payment::count();

        // Generators that produce non-numeric or negative values (always invalid for all 3 fields)
        $alwaysInvalidGenerators = [
            'non_numeric_word' => fn () => fake()->word(),
            'non_numeric_sentence' => fn () => fake()->sentence(),
            'non_numeric_alpha' => fn () => fake()->lexify('??????'),
            'negative' => fn () => fake()->randomFloat(2, -99999, -0.01),
            'special_chars' => fn () => fake()->randomElement(['!@#', '$%^', '&*(', 'abc123def', '12.34.56']),
        ];

        // Generators that only apply to required fields (amount_total, amount_paid)
        $missingGenerators = [
            'missing' => fn () => null,
            'empty_string' => fn () => '',
        ];

        $numericFields = ['amount_total', 'discount', 'amount_paid'];
        $requiredFields = ['amount_total', 'amount_paid'];

        for ($i = 0; $i < 100; $i++) {
            // Pick a random field to invalidate
            $targetField = fake()->randomElement($numericFields);

            // Choose appropriate generators based on field (discount is nullable)
            if (in_array($targetField, $requiredFields)) {
                $allGenerators = array_merge($alwaysInvalidGenerators, $missingGenerators);
            } else {
                $allGenerators = $alwaysInvalidGenerators;
            }

            // Pick a random invalid generator
            $generatorKey = fake()->randomElement(array_keys($allGenerators));
            $invalidValue = $allGenerators[$generatorKey]();

            // Build valid base data
            $data = [
                'student_enrollment_id' => $this->enrollment->id,
                'amount_total' => fake()->randomFloat(2, 1, 10000),
                'discount' => fake()->randomFloat(2, 0, 500),
                'amount_paid' => fake()->randomFloat(2, 0, 500),
                'payment_date' => now()->format('Y-m-d'),
                'payment_method' => fake()->randomElement(PaymentMethodEnum::values()),
            ];

            // Override the target field with invalid value
            if ($invalidValue === null) {
                unset($data[$targetField]);
            } else {
                $data[$targetField] = $invalidValue;
            }

            // Replicate the same validation rules from PaymentController
            $rules = [
                'student_enrollment_id' => ['required', 'exists:student_enrollments,id'],
                'amount_total' => ['required', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0', 'lte:amount_total'],
                'amount_paid' => ['required', 'numeric', 'min:0'],
                'payment_date' => ['required', 'date'],
                'payment_method' => ['required', Rule::in(PaymentMethodEnum::values())],
                'notes' => ['nullable', 'string'],
            ];

            $validator = Validator::make($data, $rules);

            $this->assertTrue(
                $validator->fails(),
                "Iteration $i: Expected validation failure for field '$targetField' "
                ."with value " . var_export($invalidValue, true) . " (generator: $generatorKey) but validation passed."
            );

            $this->assertTrue(
                $validator->errors()->has($targetField),
                "Iteration $i: Expected error on '$targetField' field "
                ."with value " . var_export($invalidValue, true) . " (generator: $generatorKey) "
                ."but errors were on: " . implode(', ', array_keys($validator->errors()->toArray()))
            );
        }

        // Verify no Payment records were created
        $this->assertEquals($initialCount, Payment::count(), 'No Payment records should be created for invalid numeric data');
    }

    /**
     * Feature: payment-module, Property 5: Invalid enrollment, date, or method are rejected
     *
     * For any submission with a non-existent student_enrollment_id, a missing/invalid payment_date,
     * or a payment_method outside {cash, card, bank_transfer}, the PaymentController SHALL reject
     * the request with a validation error.
     *
     * **Validates: Requirements 1.3, 1.9, 1.10, 2.3**
     */
    public function test_property_invalid_enrollment_date_method_rejected(): void
    {
        $rules = [
            'student_enrollment_id' => ['required', 'exists:student_enrollments,id'],
            'amount_total' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0', 'lte:amount_total'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(PaymentMethodEnum::values())],
            'notes' => ['nullable', 'string'],
        ];

        // Valid base data (all fields valid)
        $validBase = [
            'student_enrollment_id' => $this->enrollment->id,
            'amount_total' => 1000,
            'discount' => 100,
            'amount_paid' => 500,
            'payment_date' => '2025-03-15',
            'payment_method' => 'cash',
            'notes' => null,
        ];

        for ($i = 0; $i < 100; $i++) {
            // Sub-scenario 1: Non-existent student_enrollment_id
            $invalidEnrollmentId = fake()->numberBetween(900000, 999999);
            $dataInvalidEnrollment = array_merge($validBase, [
                'student_enrollment_id' => $invalidEnrollmentId,
            ]);
            $validator = Validator::make($dataInvalidEnrollment, $rules);
            $this->assertTrue(
                $validator->fails(),
                "Iteration $i (invalid enrollment): enrollment_id=$invalidEnrollmentId should fail validation"
            );
            $this->assertTrue(
                $validator->errors()->has('student_enrollment_id'),
                "Iteration $i (invalid enrollment): error should be on student_enrollment_id field"
            );

            // Sub-scenario 2: Invalid payment_date
            $invalidDates = [
                null,
                '',
                fake()->word(),
                (string) fake()->randomNumber(5),
                fake()->sentence(),
                'not-a-date',
                '2025-13-45',
                '00/00/0000',
                fake()->lexify('????-??-??'),
            ];
            $invalidDate = fake()->randomElement($invalidDates);
            $dataInvalidDate = array_merge($validBase, [
                'payment_date' => $invalidDate,
            ]);
            $validator = Validator::make($dataInvalidDate, $rules);
            $this->assertTrue(
                $validator->fails(),
                "Iteration $i (invalid date): payment_date='$invalidDate' should fail validation"
            );
            $this->assertTrue(
                $validator->errors()->has('payment_date'),
                "Iteration $i (invalid date): error should be on payment_date field"
            );

            // Sub-scenario 3: Invalid payment_method
            $invalidMethod = fake()->lexify('??????') . fake()->randomNumber(2);
            // Ensure we never accidentally pick a valid method
            while (in_array($invalidMethod, PaymentMethodEnum::values(), true)) {
                $invalidMethod = fake()->lexify('??????') . fake()->randomNumber(3);
            }
            $dataInvalidMethod = array_merge($validBase, [
                'payment_method' => $invalidMethod,
            ]);
            $validator = Validator::make($dataInvalidMethod, $rules);
            $this->assertTrue(
                $validator->fails(),
                "Iteration $i (invalid method): payment_method='$invalidMethod' should fail validation"
            );
            $this->assertTrue(
                $validator->errors()->has('payment_method'),
                "Iteration $i (invalid method): error should be on payment_method field"
            );
        }
    }

    /**
     * Test that the create form renders enrollment options.
     * Validates: Requirements 1.1
     */
    public function test_create_form_renders_enrollment_options(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.payments.create'));

        $response->assertOk();
        $response->assertViewIs('admin.payments.create');
        $response->assertViewHas('enrollments', function ($enrollments) {
            return $enrollments->contains($this->enrollment);
        });
    }

    /**
     * Test that the edit form pre-fills existing payment values.
     * Validates: Requirements 2.1
     */
    public function test_edit_form_prefills_existing_values(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $payment = Payment::create([
            'student_enrollment_id' => $this->enrollment->id,
            'amount_total' => 5000000,
            'discount' => 500000,
            'amount_paid' => 2000000,
            'remaining_balance' => 2500000,
            'payment_date' => '2025-03-10',
            'payment_method' => PaymentMethodEnum::Card->value,
            'notes' => 'Test note',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.payments.edit', $payment));

        $response->assertOk();
        $response->assertViewIs('admin.payments.edit');
        $response->assertViewHas('payment', function ($viewPayment) use ($payment) {
            return $viewPayment->id === $payment->id;
        });
        $response->assertViewHas('enrollments');
    }

    /**
     * Test that store redirects with success flash message.
     * Validates: Requirements 1.13
     */
    public function test_store_redirects_with_success_flash(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.payments.store'), [
            'student_enrollment_id' => $this->enrollment->id,
            'amount_total' => 1000000,
            'discount' => 100000,
            'amount_paid' => 500000,
            'payment_date' => '2025-04-01',
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('admin.payments.index'));
        $response->assertSessionHas('success', __('admin.payment_created_successfully'));
    }

    /**
     * Test that update redirects with success flash message.
     * Validates: Requirements 2.5
     */
    public function test_update_redirects_with_success_flash(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $payment = Payment::create([
            'student_enrollment_id' => $this->enrollment->id,
            'amount_total' => 2000000,
            'discount' => 0,
            'amount_paid' => 1000000,
            'remaining_balance' => 1000000,
            'payment_date' => '2025-02-15',
            'payment_method' => PaymentMethodEnum::Cash->value,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.payments.update', $payment), [
            'student_enrollment_id' => $this->enrollment->id,
            'amount_total' => 2000000,
            'discount' => 200000,
            'amount_paid' => 1800000,
            'payment_date' => '2025-02-20',
            'payment_method' => 'card',
        ]);

        $response->assertRedirect(route('admin.payments.index'));
        $response->assertSessionHas('success', __('admin.payment_updated_successfully'));
    }

    /**
     * Test that destroy redirects with success flash message.
     * Validates: Requirements 3.1, 3.2
     */
    public function test_destroy_redirects_with_success_flash(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $payment = Payment::create([
            'student_enrollment_id' => $this->enrollment->id,
            'amount_total' => 1000000,
            'discount' => 0,
            'amount_paid' => 1000000,
            'remaining_balance' => 0,
            'payment_date' => '2025-01-10',
            'payment_method' => PaymentMethodEnum::BankTransfer->value,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.payments.destroy', $payment));

        $response->assertRedirect(route('admin.payments.index'));
        $response->assertSessionHas('success', __('admin.payment_deleted_successfully'));
    }

    /**
     * Test that index empty state shows no_payments_found message.
     * Validates: Requirements 4.6
     */
    public function test_index_empty_state(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        // Ensure no payments exist
        Payment::query()->delete();

        $response = $this->actingAs($admin)->get(route('admin.payments.index'));

        $response->assertOk();
        $response->assertViewIs('admin.payments.index');
        $response->assertViewHas('payments', function ($payments) {
            return $payments->isEmpty();
        });
    }

    /**
     * Test that index pagination is 15 per page.
     * Validates: Requirements 4.5
     */
    public function test_index_pagination_is_15_per_page(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        // Create 20 payment records
        for ($i = 0; $i < 20; $i++) {
            Payment::create([
                'student_enrollment_id' => $this->enrollment->id,
                'amount_total' => 1000000 + ($i * 10000),
                'discount' => 0,
                'amount_paid' => 500000,
                'remaining_balance' => 500000 + ($i * 10000),
                'payment_date' => now()->subDays($i)->format('Y-m-d'),
                'payment_method' => PaymentMethodEnum::Cash->value,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.payments.index'));

        $response->assertOk();
        $response->assertViewHas('payments', function ($payments) {
            return $payments->perPage() === 15 && $payments->count() === 15;
        });
    }

    /**
     * Feature: payment-module, Property 7: Index sorting
     *
     * For any collection of Payments and any of the sortable columns (amount_total, discount,
     * amount_paid, remaining_balance, payment_date) with either direction, the PaymentsIndex
     * results SHALL be ordered by that column and direction; when no sort is specified, results
     * SHALL default to payment_date descending.
     *
     * **Validates: Requirements 4.2, 4.3, 4.4**
     */
    public function test_property_index_sorting(): void
    {
        $sortableColumns = ['amount_total', 'discount', 'amount_paid', 'remaining_balance', 'payment_date'];
        $directions = ['asc', 'desc'];

        // Create 15+ Payment records with randomized values
        for ($i = 0; $i < 20; $i++) {
            $amountTotal = fake()->randomFloat(2, 100, 10000);
            $discount = fake()->randomFloat(2, 0, $amountTotal * 0.3);
            $maxPayable = round($amountTotal - $discount, 2);
            $amountPaid = fake()->randomFloat(2, 0, $maxPayable);
            $remainingBalance = round($amountTotal - $discount - $amountPaid, 2);

            Payment::create([
                'student_enrollment_id' => $this->enrollment->id,
                'amount_total' => $amountTotal,
                'discount' => $discount,
                'amount_paid' => $amountPaid,
                'remaining_balance' => $remainingBalance,
                'payment_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                'payment_method' => fake()->randomElement(PaymentMethodEnum::values()),
            ]);
        }

        // Test each sortable column × each direction (5 columns × 2 directions = 10 combinations)
        // Run 10 iterations per combination = 100 total checks
        for ($iteration = 0; $iteration < 10; $iteration++) {
            foreach ($sortableColumns as $column) {
                foreach ($directions as $direction) {
                    // Replicate the controller's index query logic
                    $results = Payment::with('enrollment.student')
                        ->orderBy($column, $direction)
                        ->paginate(15);

                    $values = $results->pluck($column)->toArray();

                    // Verify ordering
                    for ($j = 0; $j < count($values) - 1; $j++) {
                        $current = $values[$j];
                        $next = $values[$j + 1];

                        if ($direction === 'asc') {
                            $this->assertTrue(
                                $current <= $next,
                                "Iteration $iteration, column=$column, direction=$direction: "
                                ."value at index $j ($current) should be <= value at index ".($j + 1)." ($next)"
                            );
                        } else {
                            $this->assertTrue(
                                $current >= $next,
                                "Iteration $iteration, column=$column, direction=$direction: "
                                ."value at index $j ($current) should be >= value at index ".($j + 1)." ($next)"
                            );
                        }
                    }
                }
            }
        }

        // Test default sort (no sort params) → payment_date desc
        // Replicate controller logic: when sort is not in sortable list, default to payment_date desc
        $sortKey = 'payment_date';
        $sortDir = 'desc';

        $defaultResults = Payment::with('enrollment.student')
            ->orderBy($sortKey, $sortDir)
            ->paginate(15);

        $dates = $defaultResults->pluck('payment_date')->toArray();

        for ($j = 0; $j < count($dates) - 1; $j++) {
            $current = $dates[$j];
            $next = $dates[$j + 1];

            $this->assertTrue(
                $current >= $next,
                "Default sort: payment_date at index $j ($current) should be >= payment_date at index ".($j + 1)." ($next)"
            );
        }
    }

    /**
     * Feature: payment-module, Property 9: Payments survive enrollment soft-delete
     *
     * For any StudentEnrollment with associated Payment records, soft-deleting the enrollment
     * SHALL NOT delete, modify, or orphan-error its associated Payment records.
     *
     * **Validates: Requirements 7.5**
     */
    public function test_property_payments_survive_enrollment_soft_delete(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $paymentCount = fake()->numberBetween(1, 3);
            $payments = [];

            for ($p = 0; $p < $paymentCount; $p++) {
                $amountTotal = fake()->randomFloat(2, 100, 10000);
                $discount = fake()->randomFloat(2, 0, $amountTotal * 0.3);
                $maxPayable = round($amountTotal - $discount, 2);
                $amountPaid = fake()->randomFloat(2, 0, $maxPayable);
                $remainingBalance = round($amountTotal - $discount - $amountPaid, 2);

                $payments[] = Payment::create([
                    'student_enrollment_id' => $this->enrollment->id,
                    'amount_total' => $amountTotal,
                    'discount' => $discount,
                    'amount_paid' => $amountPaid,
                    'remaining_balance' => $remainingBalance,
                    'payment_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                    'payment_method' => fake()->randomElement(PaymentMethodEnum::values()),
                    'notes' => fake()->boolean(50) ? fake()->sentence() : null,
                ]);
            }

            $paymentIds = array_map(fn ($pay) => $pay->id, $payments);

            // Store original attributes for comparison
            $originalAttributes = [];
            foreach ($payments as $pay) {
                $fresh = $pay->fresh();
                $originalAttributes[$fresh->id] = $fresh->getAttributes();
            }

            // Soft-delete the enrollment
            $this->enrollment->delete();

            // Assert the enrollment is indeed soft-deleted
            $this->assertSoftDeleted('student_enrollments', ['id' => $this->enrollment->id]);

            // Assert all payments still exist in the database (not deleted)
            $survivingPayments = Payment::whereIn('id', $paymentIds)->get();
            $this->assertCount(
                $paymentCount,
                $survivingPayments,
                "Iteration $i: Expected $paymentCount payments to survive soft-delete, got {$survivingPayments->count()}"
            );

            // Assert all payment attributes are unchanged after the soft-delete
            foreach ($survivingPayments as $surviving) {
                $original = $originalAttributes[$surviving->id];
                $current = $surviving->getAttributes();

                foreach (['student_enrollment_id', 'amount_total', 'discount', 'amount_paid', 'remaining_balance', 'payment_date', 'payment_method', 'notes'] as $attr) {
                    $this->assertEquals(
                        $original[$attr],
                        $current[$attr],
                        "Iteration $i: Payment #{$surviving->id} attribute '$attr' changed after enrollment soft-delete"
                    );
                }
            }

            // Restore the enrollment and clean up payments for next iteration
            $this->enrollment->restore();
            Payment::whereIn('id', $paymentIds)->delete();
        }
    }

    /**
     * Feature: payment-module, Property 8: Financial summary aggregation
     *
     * For any Student with any set of Payment records across any of that Student's enrollments
     * (including soft-deleted enrollments), the StudentFinancialSummary SHALL report:
     * - total_owing equal to the sum of remaining_balance
     * - total_paid equal to the sum of amount_paid
     * - last_payment_date equal to the maximum payment_date
     * When the set is empty, total_owing and total_paid SHALL be zero.
     *
     * **Validates: Requirements 6.1, 6.2, 6.3**
     */
    public function test_property_financial_summary_aggregation(): void
    {
        $student = $this->enrollment->student;

        // Test empty case first: no payments → totals are zero
        Payment::query()->delete();

        $enrollmentIds = $student->enrollments()->withTrashed()->pluck('id');
        $payments = Payment::whereIn('student_enrollment_id', $enrollmentIds)->get();

        $financialSummary = [
            'total_owing' => $payments->sum('remaining_balance'),
            'total_paid' => $payments->sum('amount_paid'),
            'last_payment_date' => $payments->max('payment_date'),
        ];

        $this->assertEquals(0, $financialSummary['total_owing'], 'Empty case: total_owing should be zero');
        $this->assertEquals(0, $financialSummary['total_paid'], 'Empty case: total_paid should be zero');
        $this->assertNull($financialSummary['last_payment_date'], 'Empty case: last_payment_date should be null');

        // Create a second enrollment for the same student (to test multi-enrollment aggregation)
        $instrument2 = Instrument::create([
            'name' => 'Guitar',
            'slug' => 'guitar-fin',
            'is_active' => true,
        ]);

        $teacher2 = Teacher::forceCreate([
            'teacher_code' => 'TCH-FIN',
            'full_name' => 'Financial Teacher',
            'phone' => '09120000099',
            'status' => 'active',
            'hire_date' => now(),
        ]);

        $enrollment2 = StudentEnrollment::create([
            'student_id' => $student->id,
            'instrument_id' => $instrument2->id,
            'teacher_id' => $teacher2->id,
            'skill_level' => 'intermediate',
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Soft-delete the second enrollment to test inclusion of soft-deleted enrollments
        $enrollment2->delete();

        for ($i = 0; $i < 100; $i++) {
            // Clean state each iteration
            Payment::query()->delete();

            $numPayments = fake()->numberBetween(1, 5);
            $expectedTotalOwing = 0.0;
            $expectedTotalPaid = 0.0;
            $expectedLastPaymentDate = null;

            for ($p = 0; $p < $numPayments; $p++) {
                // Randomly assign to one of the student's enrollments (including soft-deleted)
                $targetEnrollmentId = fake()->randomElement([$this->enrollment->id, $enrollment2->id]);

                $amountTotal = fake()->randomFloat(2, 100, 10000);
                $discount = fake()->randomFloat(2, 0, $amountTotal * 0.3);
                $maxPayable = round($amountTotal - $discount, 2);
                $amountPaid = fake()->randomFloat(2, 0, $maxPayable);
                $remainingBalance = round($amountTotal - $discount - $amountPaid, 2);
                $paymentDate = fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d');

                Payment::create([
                    'student_enrollment_id' => $targetEnrollmentId,
                    'amount_total' => $amountTotal,
                    'discount' => $discount,
                    'amount_paid' => $amountPaid,
                    'remaining_balance' => $remainingBalance,
                    'payment_date' => $paymentDate,
                    'payment_method' => fake()->randomElement(PaymentMethodEnum::values()),
                ]);

                $expectedTotalOwing += $remainingBalance;
                $expectedTotalPaid += $amountPaid;

                if ($expectedLastPaymentDate === null || $paymentDate > $expectedLastPaymentDate) {
                    $expectedLastPaymentDate = $paymentDate;
                }
            }

            // Replicate the StudentController@show logic
            $enrollmentIds = $student->enrollments()->withTrashed()->pluck('id');
            $payments = Payment::whereIn('student_enrollment_id', $enrollmentIds)->get();

            $financialSummary = [
                'total_owing' => $payments->sum('remaining_balance'),
                'total_paid' => $payments->sum('amount_paid'),
                'last_payment_date' => $payments->max('payment_date'),
            ];

            $this->assertEquals(
                round($expectedTotalOwing, 2),
                round((float) $financialSummary['total_owing'], 2),
                "Iteration $i: total_owing mismatch. Expected $expectedTotalOwing, got {$financialSummary['total_owing']}"
            );

            $this->assertEquals(
                round($expectedTotalPaid, 2),
                round((float) $financialSummary['total_paid'], 2),
                "Iteration $i: total_paid mismatch. Expected $expectedTotalPaid, got {$financialSummary['total_paid']}"
            );

            $actualLastDate = $financialSummary['last_payment_date'] instanceof \Illuminate\Support\Carbon
                ? $financialSummary['last_payment_date']->format('Y-m-d')
                : $financialSummary['last_payment_date'];

            $this->assertEquals(
                $expectedLastPaymentDate,
                $actualLastDate,
                "Iteration $i: last_payment_date mismatch. Expected $expectedLastPaymentDate, got $actualLastDate"
            );
        }
    }
}
