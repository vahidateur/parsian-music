<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\StudentAction;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\Details\StudentDetailQuery;
use App\Services\Lists\StudentListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every action resolves its named StudentPolicy ability through the
 * Authorization_Layer before any input is read or any record is written, so a
 * hidden UI control is never the only protection.
 */
class StudentController extends Controller
{
    public function index(Request $request, StudentListQuery $listQuery): View
    {
        $this->authorize('viewAny', Student::class);

        return view('admin.students.index', [
            'list' => $listQuery->forInput($request->query(), $request->user()),
        ]);
    }

    public function show(Request $request, Student $student, StudentDetailQuery $detailQuery): View
    {
        $this->authorize('view', $student);

        $student->load([
            'enrollments.teacher',
            'enrollments.instrument',
            'subscriptions.teacher',
            'subscriptions.instrument',
        ]);

        $detail = $detailQuery->forRecord($student, $request->user());
        $financialSummary = $this->buildFinancialSummary($student);

        return view('admin.students.show', compact('student', 'detail', 'financialSummary'));
    }

    public function create(): View
    {
        $this->authorize('create', Student::class);

        return view('admin.students.create');
    }

    public function store(StudentRequest $request, StudentAction $action): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $action->create($request->validated());

        return redirect()->route('admin.students.index')
            ->with('success', __('admin.student_created_successfully'));
    }

    public function edit(Student $student): View
    {
        $this->authorize('update', $student);

        return view('admin.students.edit', compact('student'));
    }

    public function update(StudentRequest $request, Student $student, StudentAction $action): RedirectResponse
    {
        $this->authorize('update', $student);

        $action->update($student, $request->validated());

        return redirect()->route('admin.students.index')
            ->with('success', __('admin.student_updated_successfully'));
    }

    public function destroy(Student $student, StudentAction $action): RedirectResponse
    {
        $this->authorize('delete', $student);

        $action->delete($student);

        return redirect()->route('admin.students.index')
            ->with('success', __('admin.student_deleted_successfully'));
    }

    /**
     * Aggregate the student's billing position from the Invoice domain.
     *
     * Cancelled invoices are excluded from the invoiced total so a voided
     * invoice never inflates the outstanding balance.
     *
     * @return array{invoice_count: int, total_invoiced: float, total_paid: float, total_outstanding: float, last_payment_at: ?\Illuminate\Support\Carbon}
     */
    private function buildFinancialSummary(Student $student): array
    {
        $invoices = $student->invoices()
            ->where('status', '!=', InvoiceStatusEnum::Cancelled->value)
            ->with('payments')
            ->get();

        $totalInvoiced = (float) $invoices->sum(fn (Invoice $invoice) => (float) $invoice->total);
        $totalPaid = (float) $invoices->sum(fn (Invoice $invoice) => $invoice->amountPaid());
        $totalOutstanding = (float) $invoices->sum(fn (Invoice $invoice) => $invoice->amountDue());

        $lastPaymentAt = $invoices
            ->flatMap(fn (Invoice $invoice) => $invoice->payments)
            ->where('status', PaymentStatusEnum::Completed)
            ->max('paid_at');

        return [
            'invoice_count'     => $invoices->count(),
            'total_invoiced'    => $totalInvoiced,
            'total_paid'        => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'last_payment_at'   => $lastPaymentAt,
        ];
    }
}
