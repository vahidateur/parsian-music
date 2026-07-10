<?php

namespace App\Http\Controllers\Student;

use App\Enums\AttendanceStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\SessionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\InvoicePayment;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    /**
     * Resolve the Student profile for the authenticated user.
     * Aborts with 403 if the user has no linked student record.
     */
    private function resolveStudent(): Student
    {
        $student = auth()->user()->student;

        abort_unless($student, 403, 'حساب هنرجو به پروفایل متصل نشده است. با مدیر تماس بگیرید.');

        return $student;
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function index(): View
    {
        $student = $this->resolveStudent();
        $today   = CarbonImmutable::today();

        // Today's sessions — scoped to this student only
        $todaySessions = ClassSession::withEnrollmentDetails()
            ->forStudent($student->id)
            ->whereDate('session_date', $today)
            ->orderBySchedule()
            ->get();

        // Upcoming (next 7 days, scheduled only)
        $upcomingSessions = ClassSession::withEnrollmentDetails()
            ->forStudent($student->id)
            ->whereDate('session_date', '>', $today)
            ->whereDate('session_date', '<=', $today->addDays(7))
            ->where('status', SessionStatusEnum::Scheduled)
            ->orderBySchedule()
            ->limit(5)
            ->get();

        // Attendance summary
        $attendances      = $student->attendances()->get();
        $totalAttendances = $attendances->count();
        $presentCount     = $attendances->where('status', AttendanceStatusEnum::Present)->count();
        $attendanceRate   = $totalAttendances > 0 ? round(($presentCount / $totalAttendances) * 100) : 0;

        // Active subscriptions — remaining sessions
        $subscriptions = $student->subscriptions()
            ->with(['instrument', 'teacher'])
            ->where('sessions_allocated', '>', 0)
            ->orderByDesc('renewal_date')
            ->get();

        $remainingSessions = $subscriptions->sum('remaining_sessions');

        // Outstanding balance: unpaid invoice totals minus completed payments
        $invoices = $student->invoices()
            ->with('payments')
            ->whereNotIn('status', [InvoiceStatusEnum::Paid->value, InvoiceStatusEnum::Cancelled->value])
            ->get();

        $outstandingBalance = $invoices->sum(fn ($inv) => $inv->amountDue());

        // Recent payments (last 5)
        $recentPayments = InvoicePayment::whereHas('invoice', fn ($q) => $q->where('student_id', $student->id))
            ->with(['invoice'])
            ->where('status', PaymentStatusEnum::Completed)
            ->orderByDesc('paid_at')
            ->limit(5)
            ->get();

        // Last paid invoice
        $lastPayment = $recentPayments->first();

        // Recent notifications
        $notifications = auth()->user()->notifications()->limit(5)->get();
        $unreadCount   = auth()->user()->unreadNotifications->count();

        return view('student.dashboard', compact(
            'student',
            'today',
            'todaySessions',
            'upcomingSessions',
            'attendanceRate',
            'totalAttendances',
            'presentCount',
            'subscriptions',
            'remainingSessions',
            'outstandingBalance',
            'recentPayments',
            'lastPayment',
            'notifications',
            'unreadCount',
        ));
    }

    // ── My Classes ────────────────────────────────────────────────────────────

    public function classes(Request $request): View
    {
        $student = $this->resolveStudent();
        $today   = CarbonImmutable::today();

        $range   = $request->input('range', 'upcoming');
        $search  = $request->input('search', '');
        $status  = $request->input('status', '');

        $query = ClassSession::withEnrollmentDetails()
            ->forStudent($student->id)
            ->orderBySchedule();

        match ($range) {
            'today'    => $query->whereDate('session_date', $today),
            'week'     => $query->forDateRange($today->startOfWeek()->toDateString(), $today->endOfWeek()->toDateString()),
            'past'     => $query->whereDate('session_date', '<', $today),
            default    => $query->whereDate('session_date', '>=', $today), // upcoming
        };

        if ($status) {
            $query->where('status', $status);
        }

        $sessions = $query->paginate(20)->withQueryString();

        return view('student.classes', compact('student', 'sessions', 'range', 'search', 'status'));
    }

    // ── Calendar ─────────────────────────────────────────────────────────────

    public function calendar(Request $request): View
    {
        $student   = $this->resolveStudent();
        $today     = CarbonImmutable::today();

        $weekStart = $request->filled('week')
            ? CarbonImmutable::parse($request->week)->startOfWeek()
            : $today->startOfWeek();
        $weekEnd   = $weekStart->endOfWeek();

        $sessions = ClassSession::withEnrollmentDetails()
            ->forStudent($student->id)
            ->forDateRange($weekStart->toDateString(), $weekEnd->toDateString())
            ->orderBySchedule()
            ->get()
            ->groupBy(fn ($s) => $s->session_date->toDateString());

        $days = collect();
        for ($d = $weekStart; $d->lte($weekEnd); $d = $d->addDay()) {
            $days->push($d);
        }

        return view('student.calendar', compact('student', 'sessions', 'days', 'weekStart', 'weekEnd', 'today'));
    }

    // ── Attendance ────────────────────────────────────────────────────────────

    public function attendance(Request $request): View
    {
        $student = $this->resolveStudent();

        $attendances = $student->attendances()
            ->with(['classSession.enrollment.instrument', 'classSession.instrument'])
            ->orderByDesc('marked_at')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            AttendanceStatusEnum::Present->value  => $student->attendances()->where('status', AttendanceStatusEnum::Present)->count(),
            AttendanceStatusEnum::Absent->value   => $student->attendances()->where('status', AttendanceStatusEnum::Absent)->count(),
            AttendanceStatusEnum::Late->value     => $student->attendances()->where('status', AttendanceStatusEnum::Late)->count(),
            AttendanceStatusEnum::Excused->value  => $student->attendances()->where('status', AttendanceStatusEnum::Excused)->count(),
        ];
        $total = array_sum($summary);

        return view('student.attendance', compact('student', 'attendances', 'summary', 'total'));
    }

    // ── Invoices ─────────────────────────────────────────────────────────────

    public function invoices(): View
    {
        $student  = $this->resolveStudent();

        $invoices = $student->invoices()
            ->with(['items', 'payments'])
            ->orderByDesc('issue_date')
            ->paginate(15)
            ->withQueryString();

        return view('student.invoices', compact('student', 'invoices'));
    }

    // ── Payments ─────────────────────────────────────────────────────────────

    public function payments(): View
    {
        $student  = $this->resolveStudent();

        $payments = InvoicePayment::whereHas('invoice', fn ($q) => $q->where('student_id', $student->id))
            ->with(['invoice'])
            ->orderByDesc('paid_at')
            ->paginate(15)
            ->withQueryString();

        return view('student.payments', compact('student', 'payments'));
    }

    // ── My Teachers ───────────────────────────────────────────────────────────

    public function teachers(): View
    {
        $student = $this->resolveStudent();

        $teachers = $student->enrollments()
            ->active()
            ->with(['teacher.instruments', 'instrument'])
            ->get()
            ->map(fn ($e) => $e->teacher)
            ->filter()
            ->unique('id')
            ->values();

        return view('student.teachers', compact('student', 'teachers'));
    }

    // ── Notifications ─────────────────────────────────────────────────────────

    public function notifications(Request $request): View
    {
        $user = auth()->user();

        if ($request->query('mark_read') === 'all') {
            $user->unreadNotifications->markAsRead();

            return redirect()->route('student.notifications');
        }

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('student.notifications', compact('notifications'));
    }
}
