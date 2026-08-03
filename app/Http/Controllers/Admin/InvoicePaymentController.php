<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\InvoicePaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoicePaymentRequest;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use DomainException;
use Illuminate\Http\RedirectResponse;

/**
 * Payment ledger entries against an invoice.
 *
 * Both directions are delegated to InvoicePaymentAction so the invoice status is
 * always recomputed from the actual payment total inside one transaction.
 */
class InvoicePaymentController extends Controller
{
    public function store(InvoicePaymentRequest $request, Invoice $invoice, InvoicePaymentAction $action): RedirectResponse
    {
        $this->authorize('registerPayment', $invoice);

        try {
            $action->register($invoice, $request->validated(), $request->user()->id);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', __('admin.payment_registered_successfully'));
    }

    public function destroy(Invoice $invoice, InvoicePayment $payment, InvoicePaymentAction $action): RedirectResponse
    {
        $this->authorize('deletePayment', $invoice);

        // A ledger entry of another invoice is not found here, and nothing is written.
        abort_unless($payment->invoice_id === $invoice->id, 404);

        $action->delete($invoice, $payment);

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', __('admin.payment_deleted_successfully'));
    }
}
