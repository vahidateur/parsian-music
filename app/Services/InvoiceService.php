<?php

namespace App\Services;

use App\DTOs\CreateInvoiceData;
use App\DTOs\InvoiceItemData;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Student;
use DomainException;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    // ── Write operations ─────────────────────────────────────────────────────

    /**
     * Create a new Draft invoice for a student.
     */
    public function createDraft(Student $student, CreateInvoiceData $data): Invoice
    {
        return DB::transaction(function () use ($student, $data): Invoice {
            return Invoice::create([
                'student_id'    => $student->id,
                'enrollment_id' => $data->enrollmentId,
                'issue_date'    => $data->issueDate,
                'due_date'      => $data->dueDate,
                'tax'           => $data->tax,
                'currency'      => $data->currency,
                'notes'         => $data->notes,
                'status'        => InvoiceStatusEnum::Draft,
                'subtotal'      => 0,
                'discount'      => 0,
                'total'         => 0,
            ]);
        });
    }

    /**
     * Append a line item and recalculate the invoice totals.
     */
    public function addItem(Invoice $invoice, InvoiceItemData $data): InvoiceItem
    {
        $this->assertEditable($invoice);

        return DB::transaction(function () use ($invoice, $data): InvoiceItem {
            $item = $invoice->items()->create([
                'title'       => $data->title,
                'description' => $data->description,
                'quantity'    => $data->quantity,
                'unit_price'  => $data->unitPrice,
                'discount'    => $data->discount,
                'sort_order'  => $data->sortOrder,
            ]);

            $invoice->recalculate();

            return $item;
        });
    }

    /**
     * Remove a line item and recalculate.
     */
    public function removeItem(Invoice $invoice, InvoiceItem $item): void
    {
        $this->assertEditable($invoice);
        $this->assertItemBelongs($invoice, $item);

        DB::transaction(function () use ($invoice, $item): void {
            $item->delete();
            $invoice->recalculate();
        });
    }

    /**
     * Update a line item and recalculate.
     */
    public function updateItem(Invoice $invoice, InvoiceItem $item, InvoiceItemData $data): InvoiceItem
    {
        $this->assertEditable($invoice);
        $this->assertItemBelongs($invoice, $item);

        return DB::transaction(function () use ($invoice, $item, $data): InvoiceItem {
            $item->update([
                'title'       => $data->title,
                'description' => $data->description,
                'quantity'    => $data->quantity,
                'unit_price'  => $data->unitPrice,
                'discount'    => $data->discount,
                'sort_order'  => $data->sortOrder,
            ]);

            $invoice->recalculate();

            return $item->fresh();
        });
    }

    // ── Status transitions ───────────────────────────────────────────────────

    /**
     * Issue the invoice (Draft → Issued).
     * Sets issue_date to today if not already set.
     */
    public function issue(Invoice $invoice): Invoice
    {
        $this->assertTransition($invoice, InvoiceStatusEnum::Issued);

        return DB::transaction(function () use ($invoice): Invoice {
            $invoice->update([
                'status'     => InvoiceStatusEnum::Issued,
                'issue_date' => $invoice->issue_date ?? now(),
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Cancel an invoice (any non-terminal status → Cancelled).
     */
    public function cancel(Invoice $invoice): Invoice
    {
        $this->assertTransition($invoice, InvoiceStatusEnum::Cancelled);

        return DB::transaction(function () use ($invoice): Invoice {
            $invoice->update(['status' => InvoiceStatusEnum::Cancelled]);

            return $invoice->fresh();
        });
    }

    /**
     * Mark an issued invoice as overdue.
     * Safe to call in a scheduled command — skips gracefully if not applicable.
     */
    public function markOverdue(Invoice $invoice): Invoice
    {
        if (! $invoice->status->canTransitionTo(InvoiceStatusEnum::Overdue)) {
            return $invoice; // already paid or cancelled — no-op
        }

        if (! $invoice->isOverdue()) {
            return $invoice; // due date not reached yet — no-op
        }

        return DB::transaction(function () use ($invoice): Invoice {
            $invoice->update(['status' => InvoiceStatusEnum::Overdue]);

            return $invoice->fresh();
        });
    }

    // ── Extension points ─────────────────────────────────────────────────────

    /**
     * Register a payment against an invoice.
     *
     * Supports full and partial payments — multiple calls accumulate. The invoice
     * row is locked while its current status and balance are checked so concurrent
     * registrations cannot overpay it. Invoice status is automatically synced:
     *   paid < total  → PartiallyPaid
     *   paid >= total → Paid
     *
     * @throws DomainException if the invoice cannot accept the payment.
     */
    public function registerPayment(
        Invoice           $invoice,
        float             $amount,
        PaymentMethodEnum $method,
        ?string           $reference  = null,
        ?string           $notes      = null,
        ?int              $createdBy  = null,
    ): InvoicePayment {
        if ($amount <= 0) {
            throw new DomainException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $reference, $notes, $createdBy): InvoicePayment {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            if ($invoice->status === InvoiceStatusEnum::Draft) {
                throw new DomainException(
                    "Invoice #{$invoice->invoice_number} must be issued before payments can be registered."
                );
            }

            if ($invoice->status === InvoiceStatusEnum::Cancelled) {
                throw new DomainException(
                    "Invoice #{$invoice->invoice_number} is cancelled — payments cannot be registered."
                );
            }

            if ($invoice->status === InvoiceStatusEnum::Paid) {
                throw new DomainException(
                    "Invoice #{$invoice->invoice_number} is already fully paid."
                );
            }

            if ($amount > $invoice->amountDue()) {
                throw new DomainException(
                    "Payment amount exceeds the outstanding balance for invoice #{$invoice->invoice_number}."
                );
            }

            $payment = $invoice->payments()->create([
                'amount'     => $amount,
                'paid_at'    => now(),
                'method'     => $method,
                'status'     => PaymentStatusEnum::Completed,
                'reference'  => $reference,
                'notes'      => $notes,
                'created_by' => $createdBy,
            ]);

            $invoice->syncStatusFromPayments();

            return $payment;
        });
    }

    /**
     * Verify a payment originating from an external gateway.
     *
     * TODO: Implement when a payment gateway (Zarinpal, PayPing, etc.) is integrated.
     * Expected flow:
     *   1. Gateway redirects back with a reference/authority token.
     *   2. This method calls the gateway SDK to verify the transaction.
     *   3. On success, calls registerPayment() with status = Completed.
     *   4. On failure, creates a payment record with status = Failed.
     *
     * @throws \RuntimeException until implemented.
     */
    public function verifyGatewayPayment(Invoice $invoice, string $gatewayReference): never
    {
        throw new \RuntimeException(
            'InvoiceService::verifyGatewayPayment() requires a payment gateway integration. ' .
            'Implement in the gateway Sprint.'
        );
    }

    /**
     * Refund a paid invoice (full or partial).
     *
     * TODO: Requires a refund_payments table and gateway refund API.
     *
     * @throws \RuntimeException until implemented.
     */
    public function refund(Invoice $invoice, float $amount): never
    {
        throw new \RuntimeException(
            'InvoiceService::refund() is not yet implemented. ' .
            'Awaiting refund ledger and gateway integration.'
        );
    }

    /**
     * Duplicate an invoice as a new Draft, cloning all items.
     * Useful for recurring monthly invoices.
     */
    public function duplicate(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $copy = Invoice::create([
                'student_id'    => $invoice->student_id,
                'enrollment_id' => $invoice->enrollment_id,
                'issue_date'    => now(),
                'due_date'      => now()->addMonth(),
                'tax'           => $invoice->tax,
                'currency'      => $invoice->currency,
                'notes'         => $invoice->notes,
                'status'        => InvoiceStatusEnum::Draft,
                'subtotal'      => 0,
                'discount'      => 0,
                'total'         => 0,
            ]);

            foreach ($invoice->items as $item) {
                $copy->items()->create($item->only([
                    'title', 'description', 'quantity',
                    'unit_price', 'discount', 'sort_order',
                ]));
            }

            $copy->recalculate();

            return $copy->fresh(['items']);
        });
    }

    // ── Guards ───────────────────────────────────────────────────────────────

    /**
     * Throw if the invoice is in a terminal / non-editable status.
     */
    private function assertEditable(Invoice $invoice): void
    {
        if ($invoice->status->isTerminal()) {
            throw new DomainException(
                "Invoice #{$invoice->invoice_number} cannot be edited — " .
                "status is '{$invoice->status->label()}'."
            );
        }
    }

    /**
     * Throw if the status transition is not allowed by the enum's state machine.
     */
    private function assertTransition(Invoice $invoice, InvoiceStatusEnum $next): void
    {
        if (! $invoice->status->canTransitionTo($next)) {
            throw new DomainException(
                "Invoice #{$invoice->invoice_number}: cannot transition from " .
                "'{$invoice->status->label()}' to '{$next->label()}'."
            );
        }
    }

    /**
     * Throw if an item does not belong to the given invoice.
     */
    private function assertItemBelongs(Invoice $invoice, InvoiceItem $item): void
    {
        if ($item->invoice_id !== $invoice->id) {
            throw new DomainException(
                "InvoiceItem #{$item->id} does not belong to Invoice #{$invoice->invoice_number}."
            );
        }
    }
}
