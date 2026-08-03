<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\TeacherAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttachTeacherInstrumentRequest;
use App\Http\Requests\Admin\DetachTeacherInstrumentRequest;
use App\Http\Requests\Admin\TeacherRequest;
use App\Models\Instrument;
use App\Models\Teacher;
use App\Services\Details\TeacherDetailQuery;
use App\Services\Lists\TeacherListQuery;
use App\Services\TeacherInstrumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every action resolves its named TeacherPolicy ability through the
 * Authorization_Layer before any input is read or any record is written, so a
 * hidden UI control is never the only protection.
 */
class TeacherController extends Controller
{
    public function index(Request $request, TeacherListQuery $listQuery): View
    {
        $this->authorize('viewAny', Teacher::class);

        return view('admin.teachers.index', [
            'list' => $listQuery->forInput($request->query(), $request->user()),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Teacher::class);

        return view('admin.teachers.create');
    }

    public function store(TeacherRequest $request, TeacherAction $action): RedirectResponse
    {
        $this->authorize('create', Teacher::class);

        $action->create($request->validated());

        return redirect()->route('admin.teachers.index')
            ->with('success', __('admin.teacher_created_successfully'));
    }

    /**
     * Record_Detail screen. Route model binding returns the shared not-found
     * response for an unknown identifier before the ability is evaluated.
     */
    public function show(Request $request, Teacher $teacher, TeacherDetailQuery $detailQuery): View
    {
        $this->authorize('view', $teacher);

        return view('admin.teachers.show', [
            'detail' => $detailQuery->forRecord($teacher, $request->user()),
        ]);
    }

    public function edit(Teacher $teacher): View
    {
        $this->authorize('update', $teacher);

        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(TeacherRequest $request, Teacher $teacher, TeacherAction $action): RedirectResponse
    {
        $this->authorize('update', $teacher);

        $action->update($teacher, $request->validated());

        return redirect()->route('admin.teachers.index')
            ->with('success', __('admin.teacher_updated_successfully'));
    }

    public function destroy(Teacher $teacher, TeacherAction $action): RedirectResponse
    {
        $this->authorize('delete', $teacher);

        $action->delete($teacher);

        return redirect()->route('admin.teachers.index')
            ->with('success', __('admin.teacher_deleted_successfully'));
    }

    public function instruments(Teacher $teacher): View
    {
        $this->authorize('manageInstruments', $teacher);

        $teacher->load('instruments');
        $assignedIds = $teacher->instruments()->pluck('instruments.id');

        $allInstruments = Instrument::active()
            ->whereNotIn('id', $assignedIds)
            ->orderBy('name_fa')
            ->orderBy('name')
            ->get();

        return view('admin.teachers.instruments', compact('teacher', 'allInstruments'));
    }

    public function attachInstrument(AttachTeacherInstrumentRequest $request, Teacher $teacher, TeacherInstrumentService $service): RedirectResponse
    {
        $this->authorize('attachInstrument', $teacher);

        $validated = $request->validated();

        $service->attachInstrument(
            $teacher,
            $validated['instrument_id'],
            $validated['skill_level'],
            (bool) ($validated['is_primary'] ?? false),
        );

        return redirect()->route('admin.teachers.instruments', $teacher)
            ->with('success', __('admin.instrument_updated_successfully'));
    }

    public function detachInstrument(DetachTeacherInstrumentRequest $request, Teacher $teacher, TeacherInstrumentService $service): RedirectResponse
    {
        $this->authorize('detachInstrument', $teacher);

        $service->detachInstrument($teacher, $request->validated('instrument_id'));

        return redirect()->route('admin.teachers.instruments', $teacher)
            ->with('success', __('admin.instrument_updated_successfully'));
    }
}
