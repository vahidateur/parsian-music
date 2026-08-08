<?php

namespace Tests\Feature\Admin;

use App\DTOs\CreateInvoiceData;
use App\DTOs\InvoiceItemData;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Models\Instrument;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Services\InvoiceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Financial domain guard for the invoice money pipeline.
 *
 * Contract under test (source of truth: 2026_07_10_141438_create_billing_tables
 * migration columns + Invoice::$casts + the Invoice saving hook):
 *
 *   line:    net_line   = (quantity * unit_price) - line_discount
 *   header:  subtotal   = SUM(quantity * unit_price)        // GROSS
 *            discount   = SUM(line_discount)
 *            total      = subtotal - discount + tax          // == SUM(net_line) + tax
 *
 * The regression this locks down: `recalculate()` previously assigned the already
 * net `invoice_items.total` to `subtotal`, and the saving hook then subtracted
 * `discount` a second time, under-reporting every discounted invoice.
 */
class InvoiceCalculationTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;
    private StudentEnrollment $enrollment;
    private InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InvoiceService::class);

        $this->student = Student::forceCreate([
            'student_code' => 'S-95001',
            'full_name'    => 'Calc Student',
            'phone'        => '09120095001',
            'status'       => 'active',
            'join_date'    => now(),
        ]);

        $teacher = Teacher::forceCreate([
            'teacher_code' => 'T-95001',
            'full_name'    => 'Calc Teacher',
            'phone'        => '09120095002',
            'status'       => 'active',
        ]);

        $instrument = Instrument::create([
            'name'      => 'Tar',
            'name_fa'   => 'تار',
            'slug'      => 'tar-calc',
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

    /**
     * @param  array<int, array{0: int, 1: float, 2: float}>  $lines  [quantity, unitPrice, discount]
     */
    private function makeInvoice(array $lines, float $tax = 0): Invoice
    {
        $invoice = $this->service->createDraft($this->student, new CreateInvoiceData(
            issueDate: now(),
            dueDate: now()->addMonth(),
            enrollmentId: $this->enrollment->id,
            tax: $tax,
        ));

        foreach ($lines as $index => [$quantity, $unitPrice, $discount]) {
            $this->service->addItem($invoice, new InvoiceItemData(
                title: 'line ' . $index,
                quantity: $quantity,
                unitPrice: $unitPrice,
                discount: $discount,
                sortOrder: $index,
            ));
        }

        return $invoice->refresh()->load('items');
    }

    /**
     * Assert the full header identity for an invoice.
     */
    private function assertHeader(Invoice $invoice, float $gross, float $discount, float $tax): void
    {
        $invoice->refresh();

        $this->assertEquals($gross, (float) $invoice->subtotal, 'subtotal must be the GROSS line sum');
        $this->assertEquals($discount, (float) $invoice->discount, 'discount must be the line-discount sum');
        $this->assertEquals($gross - $discount + $tax, (float) $invoice->total, 'total must be gross - discount + tax');

        // The same identity expressed from the net line totals — this is the
        // assertion that fails if a line discount is applied twice.
        $netLineSum = (float) $invoice->items()->reorder()->sum('total');
        $this->assertEquals($netLineSum + $tax, (float) $invoice->total, 'total must equal SUM(net line) + tax');
    }

    public function test_line_net_is_gross_minus_line_discount(): void
    {
        $invoice = $this->makeInvoice([[3, 200000, 50000]]);
        $item = $invoice->items->first();

        $this->assertEquals(600000, (float) $item->quantity * (float) $item->unit_price);
        $this->assertEquals(550000, (float) $item->total);
    }

    public function test_no_discount(): void
    {
        $invoice = $this->makeInvoice([[4, 500000, 0]]);

        $this->assertHeader($invoice, gross: 2000000, discount: 0, tax: 0);
        $this->assertEquals(2000000, (float) $invoice->refresh()->total);
    }

    public function test_single_discounted_line_is_not_discounted_twice(): void
    {
        $invoice = $this->makeInvoice([[4, 500000, 100000]]);

        $this->assertHeader($invoice, gross: 2000000, discount: 100000, tax: 0);

        // Regression value: the old implementation produced 1,800,000 here.
        $this->assertEquals(1900000, (float) $invoice->refresh()->total);
    }

    public function test_multiple_line_items_without_discount(): void
    {
        $invoice = $this->makeInvoice([
            [1, 750000, 0],
            [2, 125000, 0],
            [3, 100000, 0],
        ]);

        $this->assertHeader($invoice, gross: 1300000, discount: 0, tax: 0);
    }

    public function test_mixed_discounted_and_undiscounted_lines(): void
    {
        $invoice = $this->makeInvoice([
            [2, 400000, 80000],   // gross 800000, net 720000
            [1, 300000, 0],       // gross 300000, net 300000
            [5,  60000, 25000],   // gross 300000, net 275000
        ]);

        $this->assertHeader($invoice, gross: 1400000, discount: 105000, tax: 0);
        $this->assertEquals(1295000, (float) $invoice->refresh()->total);
    }

    public function test_tax_is_added_after_discount(): void
    {
        $invoice = $this->makeInvoice([[2, 500000, 100000]], tax: 81000);

        $this->assertHeader($invoice, gross: 1000000, discount: 100000, tax: 81000);
        $this->assertEquals(981000, (float) $invoice->refresh()->total);
    }

    public function test_removing_an_item_recalculates_without_double_discount(): void
    {
        $invoice = $this->makeInvoice([
            [2, 400000, 80000],
            [1, 300000, 50000],
        ]);

        $this->assertHeader($invoice, gross: 1100000, discount: 130000, tax: 0);

        $this->service->removeItem($invoice, $invoice->items->last());

        $this->assertHeader($invoice, gross: 800000, discount: 80000, tax: 0);
        $this->assertEquals(720000, (float) $invoice->refresh()->total);
    }

    public function test_updating_an_item_recalculates_without_double_discount(): void
    {
        $invoice = $this->makeInvoice([[2, 400000, 80000]]);
        $item = $invoice->items->first();

        $this->service->updateItem($invoice, $item, new InvoiceItemData(
            title: 'updated',
            quantity: 3,
            unitPrice: 400000,
            discount: 0,
        ));

        $this->assertHeader($invoice, gross: 1200000, discount: 0, tax: 0);
    }

    public function test_zero_payment_leaves_full_balance_due(): void
    {
        $invoice = $this->makeInvoice([[4, 500000, 100000]]);
        $this->service->issue($invoice);

        $invoice->refresh();

        $this->assertEquals(0.0, $invoice->amountPaid());
        $this->assertEquals(1900000, $invoice->amountDue());
        $this->assertSame(InvoiceStatusEnum::Issued, $invoice->status);
    }

    public function test_partial_payment_balance_uses_discounted_total(): void
    {
        $invoice = $this->makeInvoice([[4, 500000, 100000]]);
        $this->service->issue($invoice);

        $this->service->registerPayment($invoice->refresh(), 900000, PaymentMethodEnum::Cash);

        $invoice->refresh();

        $this->assertEquals(900000, $invoice->amountPaid());
        $this->assertEquals(1000000, $invoice->amountDue());
        $this->assertSame(InvoiceStatusEnum::PartiallyPaid, $invoice->status);
    }

    public function test_paying_the_discounted_total_settles_the_invoice(): void
    {
        $invoice = $this->makeInvoice([[4, 500000, 100000]]);
        $this->service->issue($invoice);

        // Exactly the discounted total — under the old double-discount bug the
        // total was 1,800,000 and this payment would have been an overpayment.
        $this->service->registerPayment($invoice->refresh(), 1900000, PaymentMethodEnum::Card);

        $invoice->refresh();

        $this->assertEquals(1900000, $invoice->amountPaid());
        $this->assertEquals(0.0, $invoice->amountDue());
        $this->assertSame(InvoiceStatusEnum::Paid, $invoice->status);
    }

    public function test_stale_payment_attempt_cannot_overpay_invoice(): void
    {
        $invoice = $this->makeInvoice([[4, 500000, 0]]);
        $this->service->issue($invoice);

        // Models represent two requests that both passed form validation using the
        // same pre-payment balance. This is deterministic; it does not exercise
        // database lock-wait semantics.
        $firstAttempt = Invoice::findOrFail($invoice->id);
        $staleSecondAttempt = Invoice::findOrFail($invoice->id);

        $this->service->registerPayment($firstAttempt, 1200000, PaymentMethodEnum::Cash);

        try {
            $this->service->registerPayment($staleSecondAttempt, 1200000, PaymentMethodEnum::Card);
        } catch (DomainException) {
            // The current, locked invoice state correctly rejects the stale attempt.
        }

        $invoice->refresh();

        $this->assertEquals(1200000, $invoice->amountPaid());
        $this->assertLessThanOrEqual((float) $invoice->total, $invoice->amountPaid());
    }

    public function test_amount_due_never_goes_negative(): void
    {
        $invoice = $this->makeInvoice([[1, 100000, 0]]);
        $this->service->issue($invoice);

        $this->service->registerPayment($invoice->refresh(), 100000, PaymentMethodEnum::Cash);

        $this->assertEquals(0.0, $invoice->refresh()->amountDue());
    }

    public function test_empty_invoice_aggregates_to_zero(): void
    {
        $invoice = $this->service->createDraft($this->student, new CreateInvoiceData(
            issueDate: now(),
            dueDate: now()->addMonth(),
        ));

        $invoice->recalculate();

        $this->assertHeader($invoice, gross: 0, discount: 0, tax: 0);
    }
}
