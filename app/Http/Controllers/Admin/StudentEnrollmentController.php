<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentEnrollment;
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

        $enrollments = $query->latest()->paginate(15);

        return view('admin.enrollments.index', compact('enrollments'));
    }

    public function create(): View
    {
        return view('admin.enrollments.create');
    }

    public function store(Request $request, EnrollmentService $service): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'instrument_id' => ['required', 'exists:instruments,id'],
            'status' => ['nullable', 'string', Rule::in(\App\Enums\EnrollmentStatusEnum::values())],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $service->createEnrollment($validated);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Enrollment created successfully.');
    }

    public function edit(StudentEnrollment $enrollment): View
    {
        $enrollment->load(['student', 'teacher', 'instrument']);
        return view('admin.enrollments.edit', compact('enrollment'));
    }

    public function update(Request $request, StudentEnrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'status' => ['required', 'string', Rule::in(\App\Enums\EnrollmentStatusEnum::values())],
            'notes' => ['nullable', 'string'],
        ]);

        $enrollment->update($validated);

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Enrollment updated successfully.');
    }

    public function destroy(StudentEnrollment $enrollment): RedirectResponse
    {
        $enrollment->delete();

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Enrollment deleted successfully.');
    }
}
