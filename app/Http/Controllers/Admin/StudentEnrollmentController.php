<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $directSort = ['started_at', 'status', 'skill_level', 'created_at'];
        $joinSort = [
            'student_name'    => 'students.full_name',
            'teacher_name'    => 'teachers.full_name',
            'instrument_name' => 'instruments.name_fa',
        ];
        $allAllowed = array_merge($directSort, array_keys($joinSort));
        $sortKey = in_array($request->sort, $allAllowed, true) ? $request->sort : 'created_at';
        $sortDir = $request->direction === 'desc' ? 'desc' : 'asc';

        $query = StudentEnrollment::with(['student', 'teacher', 'instrument']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('instrument_id')) {
            $query->where('instrument_id', $request->instrument_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if (isset($joinSort[$sortKey])) {
            $dbColumn = $joinSort[$sortKey];
            $query->join('students',    'student_enrollments.student_id',    '=', 'students.id')
                  ->join('teachers',    'student_enrollments.teacher_id',    '=', 'teachers.id')
                  ->join('instruments', 'student_enrollments.instrument_id', '=', 'instruments.id')
                  ->select('student_enrollments.*')
                  ->orderBy($dbColumn, $sortDir);
        } else {
            $query->orderBy('student_enrollments.' . $sortKey, $sortDir);
        }

        $enrollments = $query->paginate(15)->withQueryString();
        $sortCol = $sortKey;

        $students = Student::orderBy('full_name')->get();
        $teachers = Teacher::orderBy('full_name')->get();
        $instruments = Instrument::orderBy('name_fa')->orderBy('name')->get();

        return view('admin.enrollments.index', compact('enrollments', 'students', 'teachers', 'instruments', 'sortCol', 'sortDir'));
    }

    public function create(): View
    {
        $students = Student::orderBy('full_name')->get();
        $teachers = Teacher::orderBy('full_name')->get();
        $instruments = Instrument::active()->orderBy('name_fa')->orderBy('name')->get();

        return view('admin.enrollments.create', compact('students', 'teachers', 'instruments'));
    }

    public function store(Request $request, EnrollmentService $service): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'instrument_id' => ['required', 'exists:instruments,id'],
            'skill_level' => ['required', 'string', Rule::in(\App\Enums\SkillLevelEnum::values())],
            'status' => ['nullable', 'string', Rule::in(\App\Enums\EnrollmentStatusEnum::values())],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $service->createEnrollment($validated);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('admin.enrollments.index')
            ->with('success', __('admin.enrollment_created_successfully'));
    }

    public function edit(StudentEnrollment $enrollment): View
    {
        $enrollment->load(['student', 'teacher', 'instrument']);
        $students = Student::orderBy('full_name')->get();
        $teachers = Teacher::orderBy('full_name')->get();
        $instruments = Instrument::active()->orderBy('name_fa')->orderBy('name')->get();

        return view('admin.enrollments.edit', compact('enrollment', 'students', 'teachers', 'instruments'));
    }

    public function update(Request $request, StudentEnrollment $enrollment, EnrollmentService $service): RedirectResponse
    {
        $validated = $request->validate([
            'instrument_id' => ['required', 'exists:instruments,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'skill_level' => ['required', 'string', Rule::in(\App\Enums\SkillLevelEnum::values())],
            'status' => ['required', 'string', Rule::in(\App\Enums\EnrollmentStatusEnum::values())],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $service->updateEnrollment($enrollment, $validated);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('admin.enrollments.index')
            ->with('success', __('admin.enrollment_updated_successfully'));
    }

    public function destroy(StudentEnrollment $enrollment): RedirectResponse
    {
        $enrollment->delete();

        return redirect()->route('admin.enrollments.index')
            ->with('success', __('admin.enrollment_deleted_successfully'));
    }
}
