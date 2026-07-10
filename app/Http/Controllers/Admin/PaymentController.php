<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethodEnum;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\StudentEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * @deprecated No routes are registered for this controller (RC0 audit finding C-05).
 *
 * The legacy Payment model conflicts with the new billing domain introduced in Sprint 19.4–19.5.
 * This controller will be either removed or migrated to use {@see \App\Services\InvoiceService}
 * in a dedicated billing sprint before RC1.
 *
 * Do NOT add new routes pointing to this controller.
 */
class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $sortable = ['amount_total', 'discount', 'amount_paid', 'remaining_balance', 'payment_date'];
        $sortKey = in_array($request->sort, $sortable, true) ? $request->sort : 'payment_date';
        $sortDir = $request->direction === 'asc' ? 'asc' : 'desc';

        $payments = Payment::with('enrollment.student')
            ->orderBy($sortKey, $sortDir)
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments.index', compact('payments', 'sortKey', 'sortDir'));
    }

    public function create(): View
    {
        $enrollments = StudentEnrollment::with('student')->get();

        return view('admin.payments.create', compact('enrollments'));
    }

    public function edit(Payment $payment): View
    {
        $enrollments = StudentEnrollment::with('student')->get();

        return view('admin.payments.edit', compact('payment', 'enrollments'));
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()->route('admin.payments.index')
            ->with('success', __('admin.payment_deleted_successfully'));
    }

    public function store(Request $request): RedirectResponse
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

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->hasAny(['amount_total', 'discount', 'amount_paid'])) {
                return;
            }

            $amountTotal = (float) $request->input('amount_total', 0);
            $discount = (float) $request->input('discount', 0);
            $amountPaid = (float) $request->input('amount_paid', 0);

            if ($amountPaid > ($amountTotal - $discount)) {
                $validator->errors()->add('amount_paid', __('validation.lte.numeric', [
                    'attribute' => __('validation.attributes.amount_paid'),
                    'value' => $amountTotal - $discount,
                ]));
            }
        });

        $validated = $validator->validate();

        $amountTotal = (float) ($validated['amount_total'] ?? 0);
        $discount = (float) ($validated['discount'] ?? 0);
        $amountPaid = (float) ($validated['amount_paid'] ?? 0);

        $validated['remaining_balance'] = $amountTotal - $discount - $amountPaid;

        Payment::create($validated);

        return redirect()->route('admin.payments.index')
            ->with('success', __('admin.payment_created_successfully'));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
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

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->hasAny(['amount_total', 'discount', 'amount_paid'])) {
                return;
            }

            $amountTotal = (float) $request->input('amount_total', 0);
            $discount = (float) $request->input('discount', 0);
            $amountPaid = (float) $request->input('amount_paid', 0);

            if ($amountPaid > ($amountTotal - $discount)) {
                $validator->errors()->add('amount_paid', __('validation.lte.numeric', [
                    'attribute' => __('validation.attributes.amount_paid'),
                    'value' => $amountTotal - $discount,
                ]));
            }
        });

        $validated = $validator->validate();

        $amountTotal = (float) ($validated['amount_total'] ?? 0);
        $discount = (float) ($validated['discount'] ?? 0);
        $amountPaid = (float) ($validated['amount_paid'] ?? 0);

        $validated['remaining_balance'] = $amountTotal - $discount - $amountPaid;

        $payment->update($validated);

        return redirect()->route('admin.payments.index')
            ->with('success', __('admin.payment_updated_successfully'));
    }
}
