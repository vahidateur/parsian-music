<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\InvoiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceRequest;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\InvoiceService;
use App\Services\Lists\InvoiceListQuery;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin billing surface for the Invoice domain (Sprint 19.4–19.5 architecture).
 *
 * Supersedes the legacy flat `payments` module: invoices own the amounts and
 * `invoice_payments` owns the ledger. All state changes go through InvoiceService
 * so the status machine stays authoritative.
 */
class InvoiceController extends Controller
{
    public function index(Request $request, InvoiceListQuery $listQuery): View
    {
        $this->authorize('viewAny', Invoice::class);

        return view('admin.invoices.index', [
            'list' => $listQuery->forInput($request->query(), $request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Invoice::class);

        $students = Student::orderBy('full_name')->get();
        $enrollments = StudentEnrollment::with(['student', 'instrument'])->get();
        $selectedStudentId = $request->integer('student_id') ?: null;

        return view('admin.invoices.create', compact('students', 'enrollments', 'selectedStudentId'));
    }

    public function store(InvoiceRequest $request, InvoiceAction $action): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $action->create($request->validated());

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', __('admin.invoice_created_successfully'));
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['student', 'enrollment.instrument', 'items', 'payments.creator']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View|RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status->isTerminal()) {
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('error', __('admin.invoice_not_editable'));
        }

        $invoice->load(['items', 'student']);
        $students = Student::orderBy('full_name')->get();
        $enrollments = StudentEnrollment::with(['student', 'instrument'])->get();

        return view('admin.invoices.edit', compact('invoice', 'students', 'enrollments'));
    }

    public function update(InvoiceRequest $request, Invoice $invoice, InvoiceAction $action): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status->isTerminal()) {
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('error', __('admin.invoice_not_editable'));
        }

        try {
            $action->update($invoice, $request->validated());
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', __('admin.invoice_updated_successfully'));
    }

    public function issue(Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        $this->authorize('issue', $invoice);

        try {
            $service->issue($invoice);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', __('admin.invoice_issued_successfully'));
    }

    public function cancel(Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        $this->authorize('cancel', $invoice);

        try {
            $service->cancel($invoice);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', __('admin.invoice_cancelled_successfully'));
    }

    public function duplicate(Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        $this->authorize('duplicate', $invoice);

        $copy = $service->duplicate($invoice);

        return redirect()->route('admin.invoices.show', $copy)
            ->with('success', __('admin.invoice_duplicated_successfully'));
    }

    public function destroy(Invoice $invoice, InvoiceAction $action): RedirectResponse
    {
        // InvoicePolicy::delete is deliberately super_admin only.
        $this->authorize('delete', $invoice);

        $action->delete($invoice);

        return redirect()->route('admin.invoices.index')
            ->with('success', __('admin.invoice_deleted_successfully'));
    }
}
