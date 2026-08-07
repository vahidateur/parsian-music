<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AttendanceStatusEnum;
use App\Enums\SessionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Teacher;
use App\Services\Reports\TeacherPanelService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    /**
     * Resolve the Teacher profile for the authenticated user.
     * Aborts with 403 if no linked teacher record exists.
     */
    private function resolveTeacher(): Teacher
    {
        $teacher = auth()->user()->teacher;

        abort_unless($teacher, 403, 'حساب استاد به پروفایل معلم متصل نشده است. با مدیر تماس بگیرید.');

        return $teacher;
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function index(TeacherPanelService $service): View
    {
        $teacher   = $this->resolveTeacher();
        $today     = CarbonImmutable::today();
        $weekStart = $today->startOfWeek();
        $weekEnd   = $today->endOfWeek();

        // Reuse existing service for week stats + sessions
        $panel = $service->getPanelData($teacher, $weekStart, $weekEnd);

        // Today's sessions — scoped to this teacher only
        $todaySessions = ClassSession::with(['enrollment.student', 'enrollment.instrument', 'student', 'instrument'])
            ->forTeacher($teacher->id)
            ->whereDate('session_date', $today->toDateString())
            ->orderBy('start_time')
            ->get();

        // Upcoming sessions (next 7 days, excluding today)
        $upcomingSessions = ClassSession::with(['enrollment.student', 'enrollment.instrument', 'student', 'instrument'])
            ->forTeacher($teacher->id)
            ->whereDate('session_date', '>', $today->toDateString())
            ->whereDate('session_date', '<=', $today->addDays(7)->toDateString())
            ->where('status', SessionStatusEnum::Scheduled)
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        // Sessions waiting for attendance (past + today, status completed, no attendance yet)
        $waitingAttendance = ClassSession::with(['enrollment.student', 'student'])
            ->forTeacher($teacher->id)
            ->whereDate('session_date', '<=', $today->toDateString())
            ->where('status', SessionStatusEnum::Completed)
            ->whereDoesntHave('attendances')
            ->orderByDesc('session_date')
            ->limit(5)
            ->get();

        // Assigned students (distinct, via enrollments)
        $students = $teacher->enrollments()
            ->active()
            ->with('student.enrollments')
            ->get()
            ->pluck('student')
            ->unique('id')
            ->sortBy('full_name')
            ->values();

        return view('teacher.dashboard', compact(
            'teacher',
            'panel',
            'todaySessions',
            'upcomingSessions',
            'waitingAttendance',
            'students',
            'today',
        ));
    }

    // ── Weekly Schedule ───────────────────────────────────────────────────────

    public function schedule(TeacherPanelService $service): View
    {
        $teacher   = $this->resolveTeacher();
        $today     = CarbonImmutable::today();
        $weekStart = $today->startOfWeek();
        $weekEnd   = $today->endOfWeek();

        $panel = $service->getPanelData($teacher, $weekStart, $weekEnd);

        return view('teacher.schedule', compact('teacher', 'panel', 'weekStart', 'weekEnd'));
    }

    // ── Students ──────────────────────────────────────────────────────────────

    public function students(Request $request): View
    {
        $teacher = $this->resolveTeacher();

        $query = $teacher->enrollments()->active()->with(['student', 'instrument']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('student', fn ($q) => $q
                ->where('full_name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")
            );
        }

        $enrollments = $query->paginate(20)->withQueryString();

        return view('teacher.students', compact('teacher', 'enrollments'));
    }

    // ── Attendance ────────────────────────────────────────────────────────────

    public function attendance(ClassSession $session): View
    {
        $teacher = $this->resolveTeacher();

        // Scope: teacher can only mark attendance for their own sessions
        abort_unless(
            (int) $session->teacher_id === $teacher->id ||
            optional($session->enrollment)->teacher_id === $teacher->id,
            403
        );

        $session->load(['enrollment.student', 'student', 'attendances']);

        $students = collect();
        if ($session->student) {
            $students->push($session->student);
        } elseif ($session->enrollment?->student) {
            $students->push($session->enrollment->student);
        }

        $attendanceMap = $session->attendances->keyBy('student_id');

        return view('teacher.attendance', compact('teacher', 'session', 'students', 'attendanceMap'));
    }

    public function saveAttendance(Request $request, ClassSession $session): RedirectResponse
    {
        $teacher = $this->resolveTeacher();

        abort_unless(
            (int) $session->teacher_id === $teacher->id ||
            optional($session->enrollment)->teacher_id === $teacher->id,
            403
        );

        $statusValues = implode(',', AttendanceStatusEnum::values());

        $validated = $request->validate([
            'attendance'              => ['required', 'array', 'min:1'],
            'attendance.*.student_id' => [
                'bail',
                'required',
                'integer',
                'exists:students,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($session): void {
                    if (! $session->representsStudent((int) $value)) {
                        $fail('The selected student is invalid.');
                    }
                },
            ],
            'attendance.*.status'     => ['required', 'string', "in:{$statusValues}"],
            'attendance.*.note'       => ['nullable', 'string', 'max:500'],
        ]);

        $markedBy = auth()->id();
        $markedAt = now();

        foreach ($validated['attendance'] as $record) {
            ClassAttendance::updateOrCreate(
                ['class_session_id' => $session->id, 'student_id' => $record['student_id']],
                ['status' => $record['status'], 'note' => $record['note'] ?? null, 'marked_by' => $markedBy, 'marked_at' => $markedAt]
            );
        }

        return redirect()->route('teacher.dashboard')->with('success', 'حضور و غیاب با موفقیت ثبت شد.');
    }

    // ── Notifications ─────────────────────────────────────────────────────────

    public function notifications(Request $request): View
    {
        $user = auth()->user();

        if ($request->query('mark_read') === 'all') {
            $user->unreadNotifications->markAsRead();

            return redirect()->route('teacher.notifications');
        }

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('teacher.notifications', compact('notifications'));
    }

    // ── Calendar ─────────────────────────────────────────────────────────────

    public function calendar(Request $request): View
    {
        $teacher  = $this->resolveTeacher();
        $today    = CarbonImmutable::today();

        $weekStart = $request->filled('week')
            ? CarbonImmutable::parse($request->week)->startOfWeek()
            : $today->startOfWeek();
        $weekEnd   = $weekStart->endOfWeek();

        $sessions = ClassSession::with(['enrollment.student', 'enrollment.instrument', 'student', 'instrument'])
            ->forTeacher($teacher->id)
            ->forDateRange($weekStart->toDateString(), $weekEnd->toDateString())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($s) => $s->session_date->toDateString());

        $days = collect();
        for ($d = $weekStart; $d->lte($weekEnd); $d = $d->addDay()) {
            $days->push($d);
        }

        return view('teacher.calendar', compact('teacher', 'sessions', 'days', 'weekStart', 'weekEnd', 'today'));
    }
}
