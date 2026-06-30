<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SkillLevelEnum;
use App\Enums\TeacherStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Instrument;
use App\Models\Teacher;
use App\Services\TeacherInstrumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $query = Teacher::query();

        if ($request->filled('full_name')) {
            $query->where('full_name', 'like', '%' . trim($request->full_name) . '%');
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . trim($request->phone) . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', trim($request->status));
        }

        $teachers = $query->latest()->paginate(15)->withQueryString();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        return view('admin.teachers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:teachers,phone'],
            'status' => ['required', 'string', Rule::in(TeacherStatusEnum::values())],
            'bio' => ['nullable', 'string'],
            'hire_date' => ['nullable', 'date'],
        ]);

        Teacher::create($validated);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher created successfully.');
    }

    public function edit(Teacher $teacher): View
    {
        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('teachers', 'phone')->ignore($teacher->id)],
            'status' => ['required', 'string', Rule::in(TeacherStatusEnum::values())],
            'bio' => ['nullable', 'string'],
            'hire_date' => ['nullable', 'date'],
        ]);

        $teacher->update($validated);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher updated successfully.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $teacher->delete();

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher deleted successfully.');
    }

    public function instruments(Teacher $teacher): View
    {
        $teacher->load('instruments');
        $assignedIds = $teacher->instruments()->pluck('instruments.id');

        $allInstruments = Instrument::active()
            ->whereNotIn('id', $assignedIds)
            ->orderBy('name')
            ->get();

        return view('admin.teachers.instruments', compact('teacher', 'allInstruments'));
    }

    public function attachInstrument(Request $request, Teacher $teacher, TeacherInstrumentService $service): RedirectResponse
    {
        $validated = $request->validate([
            'instrument_id' => ['required', 'exists:instruments,id', Rule::unique('teacher_instruments', 'instrument_id')->where(fn ($q) => $q->where('teacher_id', $teacher->id))],
            'skill_level' => ['required', 'string', Rule::in(SkillLevelEnum::values())],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $service->attachInstrument(
            $teacher,
            $validated['instrument_id'],
            $validated['skill_level'],
            (bool) ($validated['is_primary'] ?? false),
        );

        return redirect()->route('admin.teachers.instruments', $teacher)
            ->with('success', 'Instrument assigned successfully.');
    }

    public function detachInstrument(Request $request, Teacher $teacher, TeacherInstrumentService $service): RedirectResponse
    {
        $validated = $request->validate([
            'instrument_id' => ['required', 'exists:instruments,id'],
        ]);

        $service->detachInstrument($teacher, $validated['instrument_id']);

        return redirect()->route('admin.teachers.instruments', $teacher)
            ->with('success', 'Instrument removed successfully.');
    }
}
