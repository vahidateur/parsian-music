<?php

namespace Tests\Feature\Admin;

use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\RoleEnum;
use App\Models\Instrument;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin billing surface: invoice CRUD, status transitions and the payment ledger.
 */
class InvoiceAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Student $student;
    private StudentEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $this->student = Student::forceCreate([
            'student_code' => 'S-90001',
            'full_name'    => 'Billing Student',
            'phone'        => '09120009001',
            'status'       => 'active',
            'join_date'    => now(),
        ]);

        $teacher = Teacher::forceCreate([
            'teacher_code' => 'T-90001',
            'full_name'    => 'Billing Teacher',
            'phone'        => '09120009002',
            'status'       => 'active',
        ]);

        $instrument = Instrument::create([
            'name'      => 'Setar',
            'name_fa'   => 'سه‌تار',
            'slug'      => 'setar-billing',
            'is_active' => true,
        ]);

        $this->enrollment = StudentEnrollment::create([
            'student_id'    => $this->student->id,
            'instrument_id' => $instrument->id,
            'teacher_id'    => $teacher->id,
            'skill_level'   => 'beginner',
            'status'        => 'active',
            'started_at'    => now(),
        ]);
    }

    private function enrollmentForAnotherStudent(): StudentEnrollment
    {
        $student = Student::forceCreate([
            'student_code' => 'S-90002',
            'full_name' => 'Other Billing Student',
            'phone' => '09120009003',
            'status' => 'active',
            'join_date' => now(),
        ]);

        return StudentEnrollment::create([
            'student_id' => $student->id,
            'instrument_id' => $this->enrollment->instrument_id,
            'teacher_id' => $this->enrollment->teacher_id,
            'skill_level' => 'beginner',
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function invoicePayload(array $overrides = []): array
    {
        return array_merge([
            'student_id'    => $this->student->id,
            'enrollment_id' => $this->enrollment->id,
            'issue_date'    => now()->format('Y-m-d'),
            'due_date'      => now()->addMonth()->format('Y-m-d'),
            'tax'           => 0,
            'items'         => [
                ['title' => 'شهریه مهر', 'quantity' => 4, 'unit_price' => 500000, 'discount' => 0],
            ],
        ], $overrides);
    }

    public function test_index_renders_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.invoices.index'))
            ->assertOk()
            ->assertViewIs('admin.invoices.index');
    }

    public function test_create_form_renders(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.invoices.create'))
            ->assertOk()
            ->assertViewIs('admin.invoices.create')
            ->assertViewHas('students');
    }

    public function test_store_creates_draft_with_derived_totals(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.invoices.store'), $this->invoicePayload(['tax' => 100000]));

        $invoice = Invoice::latest('id')->firstOrFail();

        $response->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertSame(InvoiceStatusEnum::Draft, $invoice->status);
        $this->assertEquals(2000000, (float) $invoice->subtotal);
        $this->assertEquals(2100000, (float) $invoice->total);
        $this->assertCount(1, $invoice->items);
    }

    public function test_store_rejects_an_enrollment_owned_by_another_student(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.invoices.store'), $this->invoicePayload([
                'enrollment_id' => $this->enrollmentForAnotherStudent()->id,
            ]))
            ->assertSessionHasErrors('enrollment_id');

        $this->assertSame(0, Invoice::count());
    }

    public function test_update_rejects_an_enrollment_owned_by_another_student(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $this->invoicePayload());
        $invoice = Invoice::latest('id')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.invoices.update', $invoice), $this->invoicePayload([
                'enrollment_id' => $this->enrollmentForAnotherStudent()->id,
            ]))
            ->assertSessionHasErrors('enrollment_id');

        $invoice->refresh();

        $this->assertSame($this->student->id, $invoice->student_id);
        $this->assertSame($this->enrollment->id, $invoice->enrollment_id);
    }

    public function test_store_and_update_allow_no_enrollment(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.invoices.store'), $this->invoicePayload(['enrollment_id' => null]));

        $invoice = Invoice::latest('id')->firstOrFail();

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertNull($invoice->enrollment_id);

        $this->actingAs($this->admin)
            ->put(route('admin.invoices.update', $invoice), $this->invoicePayload(['enrollment_id' => null]))
            ->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertNull($invoice->refresh()->enrollment_id);
    }

    public function test_store_requires_at_least_one_item(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.invoices.store'), $this->invoicePayload(['items' => []]))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Invoice::count());
    }

    public function test_due_date_cannot_precede_issue_date(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.invoices.store'), $this->invoicePayload([
                'issue_date' => now()->format('Y-m-d'),
                'due_date'   => now()->subDay()->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors('due_date');
    }

    public function test_update_replaces_items_and_recalculates(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $this->invoicePayload());
        $invoice = Invoice::latest('id')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.invoices.update', $invoice), $this->invoicePayload([
                'items' => [
                    ['title' => 'شهریه آبان', 'quantity' => 2, 'unit_price' => 300000, 'discount' => 50000],
                ],
            ]))
            ->assertRedirect(route('admin.invoices.show', $invoice));

        $invoice->refresh()->load('items');

        $this->assertCount(1, $invoice->items);
        $this->assertEquals(550000, (float) $invoice->items->first()->total);
        $this->assertEquals(550000, (float) $invoice->total);
    }

    public function test_issue_then_partial_then_full_payment_drives_status(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $this->invoicePayload());
        $invoice = Invoice::latest('id')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.invoices.issue', $invoice))
            ->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertSame(InvoiceStatusEnum::Issued, $invoice->refresh()->status);

        $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'amount' => 500000,
            'method' => PaymentMethodEnum::Cash->value,
        ]);

        $invoice->refresh();
        $this->assertSame(InvoiceStatusEnum::PartiallyPaid, $invoice->status);
        $this->assertEquals(1500000, $invoice->amountDue());

        $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'amount' => 1500000,
            'method' => PaymentMethodEnum::Card->value,
        ]);

        $invoice->refresh();
        $this->assertSame(InvoiceStatusEnum::Paid, $invoice->status);
        $this->assertEquals(0, $invoice->amountDue());
    }

    public function test_payment_cannot_exceed_amount_due(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $this->invoicePayload());
        $invoice = Invoice::latest('id')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.invoices.issue', $invoice));

        $this->actingAs($this->admin)
            ->post(route('admin.invoices.payments.store', $invoice), [
                'amount' => 2000001,
                'method' => PaymentMethodEnum::Cash->value,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertEquals(0.0, $invoice->refresh()->amountPaid());
    }

    public function test_payment_on_draft_invoice_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $this->invoicePayload());
        $invoice = Invoice::latest('id')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.invoices.payments.store', $invoice), [
                'amount' => 1000,
                'method' => PaymentMethodEnum::Cash->value,
            ])
            ->assertSessionHas('error');

        $this->assertEquals(0.0, $invoice->refresh()->amountPaid());
    }

    public function test_deleting_a_payment_reopens_the_balance(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $this->invoicePayload());
        $invoice = Invoice::latest('id')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.invoices.issue', $invoice));
        $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'amount' => 2000000,
            'method' => PaymentMethodEnum::Cash->value,
        ]);

        $payment = $invoice->refresh()->payments()->firstOrFail();
        $this->assertSame(InvoiceStatusEnum::Paid, $invoice->status);

        $this->actingAs($this->admin)
            ->delete(route('admin.invoices.payments.destroy', [$invoice, $payment]))
            ->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertEquals(2000000, $invoice->refresh()->amountDue());
    }

    public function test_cancelled_invoice_is_not_editable(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $this->invoicePayload());
        $invoice = Invoice::latest('id')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.invoices.cancel', $invoice));

        $this->assertSame(InvoiceStatusEnum::Cancelled, $invoice->refresh()->status);

        $this->actingAs($this->admin)
            ->get(route('admin.invoices.edit', $invoice))
            ->assertRedirect(route('admin.invoices.show', $invoice));
    }

    public function test_duplicate_creates_a_new_draft_copy(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $this->invoicePayload());
        $invoice = Invoice::latest('id')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.invoices.duplicate', $invoice));

        $this->assertSame(2, Invoice::count());

        $copy = Invoice::latest('id')->firstOrFail();
        $this->assertNotSame($invoice->id, $copy->id);
        $this->assertSame(InvoiceStatusEnum::Draft, $copy->status);
        $this->assertEquals((float) $invoice->total, (float) $copy->total);
    }

    public function test_student_show_page_exposes_financial_summary(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $this->invoicePayload());
        $invoice = Invoice::latest('id')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.invoices.issue', $invoice));
        $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'amount' => 800000,
            'method' => PaymentMethodEnum::Cash->value,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.students.show', $this->student))
            ->assertOk()
            ->assertViewHas('financialSummary', function (array $summary) {
                return $summary['invoice_count'] === 1
                    && (float) $summary['total_invoiced'] === 2000000.0
                    && (float) $summary['total_paid'] === 800000.0
                    && (float) $summary['total_outstanding'] === 1200000.0
                    && $summary['last_payment_at'] !== null;
            });
    }

    public function test_cancelled_invoices_are_excluded_from_financial_summary(): void
    {
        $this->actingAs($this->admin)->post(route('admin.invoices.store'), $this->invoicePayload());
        $invoice = Invoice::latest('id')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.invoices.cancel', $invoice));

        $this->actingAs($this->admin)
            ->get(route('admin.students.show', $this->student))
            ->assertOk()
            ->assertViewHas('financialSummary', function (array $summary) {
                return $summary['invoice_count'] === 0
                    && (float) $summary['total_outstanding'] === 0.0;
            });
    }

    public function test_guest_cannot_reach_billing(): void
    {
        $this->get(route('admin.invoices.index'))->assertRedirect(route('login'));
    }
}
