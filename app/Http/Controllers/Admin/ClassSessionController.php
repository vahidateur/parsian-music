<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SessionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\RecurringSchedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ConflictDetectionService;
use App\Services\SessionGeneratorService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClassSessionController extends Controller
{
    public function index(Request $request): View
    {
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

    public function generate(SessionGeneratorService $generator): RedirectResponse
    {
        $schedules = RecurringSchedule::active()->get();

        $totalCreated = 0;

        foreach ($schedules as $schedule) {
            $created = $generator->generateForSchedule($schedule, 8);
            $totalCreated += $created->count();
        }

        return redirect()->route('admin.sessions.index')
            ->with('success', __('admin.sessions_generated_successfully', ['count' => $totalCreated]));
    }

    public function create(): View
    {
        // Phase 1: manual session creation no longer depends on enrollment.
        // Student, teacher, and instrument are all selected independently.
        $students = Student::orderBy('full_name')->get();
        $teachers = Teacher::orderBy('full_name')->get();
        $instruments = Instrument::active()->orderBy('name_fa')->orderBy('name')->get();

        // Temporary hardcoded room list per business rule — no rooms table
        // query for this form.
        $rooms = ['A101', 'A102', 'A103'];

        return view('admin.sessions.create', compact('students', 'teachers', 'instruments', 'rooms'));
    }

    public function store(Request $request, ConflictDetectionService $conflictDetector): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'instrument_id' => ['required', 'exists:instruments,id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:15:00', 'before_or_equal:21:30'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:120'],
            'room' => ['required', 'string', 'in:A101,A102,A103'],
            'notes' => ['nullable', 'string'],
        ]);

        $hasConflict = $conflictDetector->checkTeacherConflict(
            $validated['teacher_id'], $validated['session_date'], $validated['start_time'], $validated['duration_minutes']
        ) || $conflictDetector->checkRoomConflict(
            $validated['room'], $validated['session_date'], $validated['start_time'], $validated['duration_minutes']
        ) || $conflictDetector->checkStudentOverlap(
            $validated['student_id'], $validated['session_date'], $validated['start_time'], $validated['duration_minutes']
        );

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'start_time' => __('admin.session_conflict_error'),
            ]);
        }

        ClassSession::create($validated);

        return redirect()->route('admin.sessions.index')
            ->with('success', __('admin.session_created_successfully'));
    }

    public function calendar(Request $request): View
    {
        // Week navigation: default to the current Persian week (Sat-Fri)
        $weekStart = $request->filled('week')
            ? Carbon::parse($request->week)->startOfWeek(Carbon::SATURDAY)
            : Carbon::now()->startOfWeek(Carbon::SATURDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::FRIDAY);

        // Build the 7-day column range
        $days = CarbonPeriod::create($weekStart, $weekEnd)->toArray();

        // Time slots: 08:00 → 20:00 (inclusive, hourly)
        $hours = range(8, 20);

        // Base query with relations + week range (centralized scopes)
        $query = ClassSession::withEnrollmentDetails()
            ->forDateRange($weekStart->toDateString(), $weekEnd->toDateString());

        if ($request->filled('teacher_id')) {
            $query->forTeacher($request->teacher_id);
        }

        if ($request->filled('student_id')) {
            $query->forStudent($request->student_id);
        }

        if ($request->filled('room')) {
            $query->where('room', $request->room);
        }

        $sessions = $query->orderBy('start_time')->get();

        // Group sessions by [date][hour] for O(1) grid lookup
        $grid = [];
        foreach ($sessions as $session) {
            $dateKey = $session->session_date->toDateString();
            $hourKey = (int) $session->start_time->format('G');
            $grid[$dateKey][$hourKey][] = $session;
        }

        $prevWeek = $weekStart->copy()->subWeek()->toDateString();
        $nextWeek = $weekStart->copy()->addWeek()->toDateString();

        $students = Student::orderBy('full_name')->get();
        $teachers = Teacher::orderBy('full_name')->get();

        return view('admin.calendar', compact(
            'weekStart',
            'weekEnd',
            'days',
            'hours',
            'grid',
            'prevWeek',
            'nextWeek',
            'students',
            'teachers'
        ));
    }

    public function destroy(ClassSession $session): RedirectResponse
    {
        $session->delete();

        return redirect()->route('admin.sessions.index')
            ->with('success', __('admin.session_deleted_successfully'));
    }
}
