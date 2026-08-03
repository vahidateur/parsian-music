<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Admin\InvoicePaymentAction;
use App\Enums\PaymentMethodEnum;
use App\Models\Invoice;
use Illuminate\Validation\Rule;

/**
 * Validation contract of the invoice payment form.
 *
 * The amount is bounded by the outstanding balance of the invoice bound to the
 * route, so a ledger entry can never exceed what the invoice still owes.
 *
 * Requirements: 6.5, 6.7
 */
class InvoicePaymentRequest extends AdminFormRequest
{
    /**
     * @return array<string, string>
     */
    public function normalizedFields(): array
    {
        return InvoicePaymentAction::NORMALIZED_FIELDS;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $invoice = $this->route('invoice');
        $maximum = $invoice instanceof Invoice ? max(0.01, $invoice->amountDue()) : 0.01;

        return [
            'amount'    => ['required', 'numeric', 'min:0.01', 'max:' . $maximum],
            'method'    => ['required', Rule::in(PaymentMethodEnum::values())],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ];
    }
}
