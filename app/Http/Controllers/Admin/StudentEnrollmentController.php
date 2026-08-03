<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\EnrollmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Http\Requests\Admin\UpdateEnrollmentRequest;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Services\Lists\EnrollmentListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Every action resolves its named EnrollmentPolicy ability through the
 * Authorization_Layer before any input is read or any record is written, so a
 * hidden UI control is never the only protection.
 */
class StudentEnrollmentController extends Controller
{
    public function index(Request $request, EnrollmentListQuery $listQuery): View
    {
        $this->authorize('viewAny', StudentEnrollment::class);

        return view('admin.enrollments.index', [
            'list' => $listQuery->forInput($request->query(), $request->user()),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', StudentEnrollment::class);

        $students = Student::orderBy('full_name')->get();
        $teachers = Teacher::orderBy('full_name')->get();
        $instruments = Instrument::orderBy('name_fa')->orderBy('name')->get();

        return view('admin.enrollments.create', compact('students', 'teachers', 'instruments'));
    }

    public function store(StoreEnrollmentRequest $request, EnrollmentAction $action): RedirectResponse
    {
        $this->authorize('create', StudentEnrollment::class);

        try {
            $action->create($request->validated());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('admin.enrollments.index')
            ->with('success', __('admin.enrollment_created_successfully'));
    }

    public function edit(StudentEnrollment $enrollment): View
    {
        $this->authorize('update', $enrollment);

        $enrollment->load(['student', 'teacher', 'instrument']);
        $students = Student::orderBy('full_name')->get();
        $teachers = Teacher::orderBy('full_name')->get();
        $instruments = Instrument::orderBy('name_fa')->orderBy('name')->get();

        return view('admin.enrollments.edit', compact('enrollment', 'students', 'teachers', 'instruments'));
    }

    public function update(UpdateEnrollmentRequest $request, StudentEnrollment $enrollment, EnrollmentAction $action): RedirectResponse
    {
        $this->authorize('update', $enrollment);

        $validated = $request->validated();

        // The edit form also reassigns the teacher and moves the status, so the
        // named abilities for those actions are evaluated before the write.
        if ((int) $validated['teacher_id'] !== (int) $enrollment->teacher_id) {
            $this->authorize('assign', $enrollment);
        }

        if ($validated['status'] !== $enrollment->status?->value) {
            $this->authorize('changeStatus', $enrollment);
        }

        $action->update($enrollment, $validated);

        return redirect()->route('admin.enrollments.index')
            ->with('success', __('admin.enrollment_updated_successfully'));
    }

    public function destroy(StudentEnrollment $enrollment, EnrollmentAction $action): RedirectResponse
    {
        $this->authorize('delete', $enrollment);

        $action->delete($enrollment);

        return redirect()->route('admin.enrollments.index')
            ->with('success', __('admin.enrollment_deleted_successfully'));
    }
}
