<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\CreateInvoiceData;
use App\DTOs\InvoiceItemData;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\InvoiceService;
use App\Support\PersianTextNormalizer;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Invoice mutations.
 *
 * An invoice is always written together with its line items and its derived
 * totals, so every operation here is a record-plus-relation change and runs in
 * one transaction with full rollback. The status machine and the totals stay
 * owned by InvoiceService: nothing in this action types an amount in.
 *
 * Requirements: 6.4, 6.6, 6.8, 6.9, 6.10, 6.13, 16.3
 */
final class InvoiceAction
{
    /**
     * Canonical form of every persisted invoice text field, shared with InvoiceRequest.
     *
     * @var array<string, string>
     */
    public const NORMALIZED_FIELDS = ['notes' => PersianTextNormalizer::MULTILINE];

    /**
     * Canonical form of every persisted line-item text field.
     *
     * @var array<string, string>
     */
    public const ITEM_NORMALIZED_FIELDS = [
        'title' => PersianTextNormalizer::TEXT,
        'description' => PersianTextNormalizer::MULTILINE,
    ];

    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when the student is gone.
     * @throws DomainException when a line item is rejected by the invoice state machine.
     */
    public function create(array $data): Invoice
    {
        $data = $this->attributes($data);

        // A missing student aborts with 404 instead of creating a substitute record.
        $student = Student::findOrFail($data['student_id']);

        return DB::transaction(function () use ($student, $data): Invoice {
            $invoice = $this->invoices->createDraft($student, new CreateInvoiceData(
                enrollmentId: $data['enrollment_id'] ?? null,
                issueDate: Carbon::parse($data['issue_date']),
                dueDate: Carbon::parse($data['due_date']),
                tax: (float) ($data['tax'] ?? 0),
                currency: 'IRR',
                notes: $data['notes'] ?? null,
            ));

            $this->syncItems($invoice, $data['items']);

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws DomainException when a line item is rejected by the invoice state machine.
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $data = $this->attributes($data);

        return DB::transaction(function () use ($invoice, $data): Invoice {
            $invoice->update([
                'student_id' => $data['student_id'],
                'enrollment_id' => $data['enrollment_id'] ?? null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'tax' => (float) ($data['tax'] ?? 0),
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->items()->delete();

            $this->syncItems($invoice, $data['items']);

            return $invoice;
        });
    }

    public function delete(Invoice $invoice): void
    {
        DB::transaction(static function () use ($invoice): void {
            $invoice->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $data = PersianTextNormalizer::fields($data, self::NORMALIZED_FIELDS);

        $data['items'] = array_map(
            static fn (mixed $item): mixed => is_array($item)
                ? PersianTextNormalizer::fields($item, self::ITEM_NORMALIZED_FIELDS)
                : $item,
            (array) ($data['items'] ?? [])
        );

        return $data;
    }

    /**
     * Persist line items through the service so totals stay derived, never typed in.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Invoice $invoice, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);

            // A line discount larger than the line value would silently clamp to zero.
            $discount = min($discount, $quantity * $unitPrice);

            $this->invoices->addItem($invoice, new InvoiceItemData(
                title: $item['title'],
                description: $item['description'] ?? null,
                quantity: $quantity,
                unitPrice: $unitPrice,
                discount: $discount,
                sortOrder: $index,
            ));
        }
    }
}
