<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\PaymentMethodEnum;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\InvoiceService;
use App\Support\PersianTextNormalizer;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Payment ledger mutations.
 *
 * A ledger entry always moves the invoice status with it, so both directions are
 * a record-plus-relation change and run in one transaction: registration is
 * owned by InvoiceService, and deletion recomputes the invoice status from the
 * remaining payments inside the same transaction.
 *
 * Requirements: 6.4, 6.6, 6.9, 6.10, 16.3
 */
final class InvoicePaymentAction
{
    /**
     * Canonical form of every persisted text field, shared with InvoicePaymentRequest.
     *
     * @var array<string, string>
     */
    public const NORMALIZED_FIELDS = [
        'reference' => PersianTextNormalizer::TEXT,
        'notes' => PersianTextNormalizer::MULTILINE,
    ];

    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws DomainException when the invoice cannot accept a payment.
     */
    public function register(Invoice $invoice, array $data, int $createdBy): InvoicePayment
    {
        $data = PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS);

        return $this->invoices->registerPayment(
            invoice: $invoice,
            amount: (float) $data['amount'],
            method: PaymentMethodEnum::from($data['method']),
            reference: $data['reference'] ?? null,
            notes: $data['notes'] ?? null,
            createdBy: $createdBy,
        );
    }

    public function delete(Invoice $invoice, InvoicePayment $payment): void
    {
        DB::transaction(static function () use ($invoice, $payment): void {
            $payment->delete();

            // Balance changed — let the invoice recompute its own status.
            $invoice->refresh()->syncStatusFromPayments();
        });
    }
}
