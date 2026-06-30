<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StudentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::query();

        if ($request->filled('full_name')) {
            $query->where('full_name', 'like', "%{$request->full_name}%");
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', "%{$request->phone}%");
        }

        $students = $query->latest()->paginate(15);

        return view('admin.students.index', compact('students'));
    }

    public function show(Student $student): View
    {
        $student->load([
            'enrollments.teacher',
            'enrollments.instrument',
        ]);

        return view('admin.students.show', compact('student'));
    }

    public function create(): View
    {
        return view('admin.students.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:students,phone'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'string', Rule::in(StudentStatusEnum::values())],
            'join_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        Student::create($validated);

        return redirect()->route('admin.students.index')
            ->with('success', 'Student created successfully.');
    }

    public function edit(Student $student): View
    {
        return view('admin.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('students', 'phone')->ignore($student->id)],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'string', Rule::in(StudentStatusEnum::values())],
            'join_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $student->update($validated);

        return redirect()->route('admin.students.index')
            ->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()->route('admin.students.index')
            ->with('success', 'Student deleted successfully.');
    }
}
