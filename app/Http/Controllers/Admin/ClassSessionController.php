<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SessionStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SessionCreateRequest;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\RecurringSchedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Subscription;
use App\Services\ConflictDetectionService;
use App\Services\SessionCreateOptionsProvider;
use App\Services\SessionCreateService;
use App\Services\SessionGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Session list/create/generate surface.
 *
 * The calendar surface is owned by `CalendarController` and the single canonical
 * view `resources/views/admin/calendar/index.blade.php`; this controller renders
 * no calendar view.
 *
 * Session edit/update behavior stays owned by `admin-bulk-selection-actions`;
 * this controller only resolves the named SessionPolicy ability before each
 * action so no mutation relies on a hidden UI control.
 */
class ClassSessionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClassSession::class);

        // Columns on class_sessions itself
        $directSort = ['session_date', 'start_time', 'duration_minutes', 'room', 'status'];
        // Columns via JOIN
        $joinSort   = ['student_name' => 'students.full_name', 'teacher_name' => 'teachers.full_name', 'instrument_name' => 'instruments.name_fa'];

        $allAllowed = array_merge($directSort, array_keys($joinSort));
        $sortKey = in_array($request->sort, $allAllowed, true) ? $request->sort : 'session_date';
        $sortDir = $request->direction === 'desc' ? 'desc' : 'asc';

        $query = ClassSession::withEnrollmentDetails();

        if ($request->filled('student_id')) {
            $query->forStudent($request->student_id);
        }
        if ($request->filled('teacher_id')) {
            $query->forTeacher($request->teacher_id);
        }
        if ($request->filled('instrument_id')) {
            $query->forInstrument($request->instrument_id);
        }
        if ($request->filled('room')) {
            $query->where('room', $request->room);
        }
        if ($request->filled('status')) {
            abort_unless(
                in_array($request->status, SessionStatusEnum::values(), true),
                422,
                'Invalid session status filter.'
            );
            $query->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $query->whereDate('session_date', $request->date);
        }

        // Apply sort — join-based columns need explicit joins
        if (isset($joinSort[$sortKey])) {
            $dbColumn = $joinSort[$sortKey];
            $query->join('student_enrollments', 'class_sessions.enrollment_id', '=', 'student_enrollments.id')
                  ->join('students',    'student_enrollments.student_id',    '=', 'students.id')
                  ->join('teachers',    'student_enrollments.teacher_id',    '=', 'teachers.id')
                  ->join('instruments', 'student_enrollments.instrument_id', '=', 'instruments.id')
                  ->select('class_sessions.*')
                  ->orderBy($dbColumn, $sortDir);
        } else {
            $query->orderBy('class_sessions.' . $sortKey, $sortDir);
        }

        $sessions = $query->paginate(20)->withQueryString();

        $students    = Student::orderBy('full_name')->get();
        $teachers    = Teacher::orderBy('full_name')->get();
        $instruments = Instrument::orderBy('name_fa')->orderBy('name')->get();
        $sortCol     = $sortKey;

        return view('admin.sessions.index', compact('sessions', 'students', 'teachers', 'instruments', 'sortCol', 'sortDir'));
    }

    public function generate(Request $request, SessionGeneratorService $generator): RedirectResponse
    {
        $this->authorize('generate', ClassSession::class);

        /** @var \App\Models\User $actor */
        $actor = $request->user();
        $schedules = RecurringSchedule::active()->get();

        $totalCreated = 0;

        foreach ($schedules as $schedule) {
            $created = $generator->generateForSchedule($schedule, 8, $actor);
            $totalCreated += $created->count();
        }

        return redirect()->route('admin.sessions.index')
            ->with('success', __('admin.sessions_generated_successfully', ['count' => $totalCreated]));
    }

    public function create(SessionCreateOptionsProvider $optionsProvider): View
    {
        $this->authorize('create', ClassSession::class);

        return view('admin.sessions.create', $optionsProvider->prepare());
    }

    public function store(SessionCreateRequest $request, SessionCreateService $createService): RedirectResponse
    {
        $this->authorize('create', ClassSession::class);

        /** @var \App\Models\User $actor */
        $actor = $request->user();
        $createService->create($request->validated(), $actor);

        return redirect()->route('admin.sessions.index')
            ->with('success', __('admin.session_created_successfully'));
    }

    public function destroy(ClassSession $session): RedirectResponse
    {
        $this->authorize('delete', $session);

        $session->delete();

        return redirect()->route('admin.sessions.index')
            ->with('success', __('admin.session_deleted_successfully'));
    }
}
