<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SessionStatusEnum;
use App\Http\Controllers\Controller;
use App\Helpers\Jalalian;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Services\Reports\DashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $service): View
    {
        $today = CarbonImmutable::today();

        $summary = $service->getSummary($today);

        return view('admin.dashboard', [
            'totalStudents'          => $summary['totalStudents'],
            'activeTeachers'         => $summary['activeTeachers'],
            'todaySessions'          => $summary['todaySessions'],
            'recentSessions'         => $summary['recentSessions'],
            'cancelledSessions'      => $summary['cancelledSessions'],
            'missedSessions'         => $summary['missedSessions'],
            'recentStudents'         => $summary['recentStudents'],
            'enrollmentTrend'        => $summary['enrollmentTrend'],
            'attendanceStats'        => $summary['attendanceStats'],
            'overdueSubscriptions'   => Subscription::where('payment_status', 'overdue')->count(),
            'recentActivities'       => $this->buildRecentActivities(),
            'notifications'          => $this->buildNotifications($today),
            'calendarSessionDates'   => $this->monthSessionDates($today),
            // Chart datasets — consumed via data-chart-data attributes in the view
            'chartAttendanceTrend'   => $this->attendanceTrend($today),
            'chartTeacherWorkload'   => $this->teacherWorkload(),
            'chartSessionStatus'     => $this->sessionStatusSummary(),
            'chartMonthlyRevenue'    => $this->monthlyRevenuePlaceholder(),
        ]);
    }

    /** Last 30 days of attendance counts broken down by status per day. */
    private function attendanceTrend(CarbonImmutable $today): array
    {
        $start = $today->subDays(29);

        $rows = ClassAttendance::selectRaw('DATE(marked_at) as `date`, status, COUNT(*) as cnt')
            ->whereDate('marked_at', '>=', $start->toDateString())
            ->groupBy(DB::raw('DATE(marked_at)'), 'status')
            ->get()
            ->groupBy('date');

        $trend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $today->subDays($i)->toDateString();
            $day  = $rows->get($date, collect());
            $trend[] = [
                'date'    => $date,
                'present' => (int) ($day->firstWhere('status', 'present')?->cnt  ?? 0),
                'absent'  => (int) ($day->firstWhere('status', 'absent')?->cnt   ?? 0),
                'late'    => (int) ($day->firstWhere('status', 'late')?->cnt     ?? 0),
                'excused' => (int) ($day->firstWhere('status', 'excused')?->cnt  ?? 0),
            ];
        }

        return $trend;
    }

    /** Sessions per teacher (top 10), using the direct teacher_id column. */
    private function teacherWorkload(): array
    {
        return ClassSession::selectRaw('teacher_id, COUNT(*) as total')
            ->whereNotNull('teacher_id')
            ->groupBy('teacher_id')
            ->with('teacher')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'teacher' => $s->teacher?->full_name ?? '—',
                'total'   => (int) $s->total,
            ])
            ->toArray();
    }

    /** All-time session counts grouped by status. */
    private function sessionStatusSummary(): array
    {
        $counts = ClassSession::selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        return [
            'completed' => (int) ($counts[SessionStatusEnum::Completed->value] ?? 0),
            'scheduled' => (int) ($counts[SessionStatusEnum::Scheduled->value] ?? 0),
            'cancelled' => (int) ($counts[SessionStatusEnum::Cancelled->value] ?? 0),
            'missed'    => (int) ($counts[SessionStatusEnum::Missed->value]    ?? 0),
        ];
    }

    /**
     * Placeholder revenue dataset.
     * TODO: Replace with real data once the Payment module is complete.
     */
    private function monthlyRevenuePlaceholder(): array
    {
        return [
            ['month' => 'فروردین',   'revenue' => 0],
            ['month' => 'اردیبهشت', 'revenue' => 0],
            ['month' => 'خرداد',     'revenue' => 0],
            ['month' => 'تیر',       'revenue' => 0],
            ['month' => 'مرداد',     'revenue' => 0],
            ['month' => 'شهریور',    'revenue' => 0],
        ];
    }

    /** Date strings in the current month that have at least one session. */
    private function monthSessionDates(CarbonImmutable $today): array
    {
        return ClassSession::whereBetween('session_date', [
                $today->startOfMonth()->toDateString(),
                $today->endOfMonth()->toDateString(),
            ])
            ->distinct()
            ->pluck('session_date')
            ->map(fn ($d) => is_string($d) ? $d : (string) $d)
            ->toArray();
    }

    /** Notification feed: upcoming sessions, overdue subscriptions, cancellations, new students. */
    private function buildNotifications(CarbonImmutable $today): Collection
    {
        $items = collect();

        // Upcoming scheduled sessions (next 24 h)
        ClassSession::with(['enrollment.student', 'student', 'enrollment.teacher', 'teacher'])
            ->whereIn('session_date', [$today->toDateString(), $today->addDay()->toDateString()])
            ->where('status', SessionStatusEnum::Scheduled->value)
            ->orderBy('session_date')->orderBy('start_time')
            ->take(5)->get()
            ->each(function ($s) use ($items) {
                $student = $s->enrollment?->student?->full_name ?? $s->student?->full_name ?? '—';
                $teacher = $s->enrollment?->teacher?->full_name ?? $s->teacher?->full_name ?? '—';
                $items->push([
                    'type'     => 'upcoming',
                    'title'    => $student.' · '.$teacher,
                    'message'  => 'جلسه برنامه‌ریزی‌شده · '.($s->start_time?->format('H:i') ?? '—'),
                    'priority' => 'info',
                    'meta'     => Jalalian::fromCarbon($s->session_date, 'Y/m/d'),
                ]);
            });

        // Overdue subscriptions
        Subscription::with('student')
            ->where('payment_status', 'overdue')
            ->latest('renewal_date')
            ->take(4)->get()
            ->each(function ($sub) use ($items) {
                $items->push([
                    'type'     => 'overdue',
                    'title'    => $sub->student?->full_name ?? '—',
                    'message'  => 'اشتراک معوق · '.number_format($sub->monthly_fee).' تومان',
                    'priority' => 'high',
                    'meta'     => $sub->renewal_date ? Jalalian::fromCarbon($sub->renewal_date, 'Y/m/d') : '—',
                ]);
            });

        // Cancelled sessions (last 7 days)
        ClassSession::with(['enrollment.student', 'student'])
            ->where('status', SessionStatusEnum::Cancelled->value)
            ->whereBetween('session_date', [$today->subDays(7)->toDateString(), $today->toDateString()])
            ->latest('session_date')
            ->take(3)->get()
            ->each(function ($s) use ($items) {
                $items->push([
                    'type'     => 'cancelled',
                    'title'    => $s->enrollment?->student?->full_name ?? $s->student?->full_name ?? '—',
                    'message'  => 'جلسه لغو شد',
                    'priority' => 'mid',
                    'meta'     => Jalalian::fromCarbon($s->session_date, 'Y/m/d'),
                ]);
            });

        // Newly registered students
        Student::latest()->take(3)->get()
            ->each(function ($student) use ($items) {
                $items->push([
                    'type'     => 'new_student',
                    'title'    => $student->full_name,
                    'message'  => 'هنرجوی جدید ثبت شد',
                    'priority' => 'success',
                    'meta'     => Jalalian::fromCarbon($student->created_at, 'Y/m/d'),
                ]);
            });

        return $items;
    }

    /**
     * Assemble a lightweight activity feed for the dashboard view.
     * Does not alter core dashboard business logic — view data only.
     */
    private function buildRecentActivities(): Collection
    {
        $activities = collect();

        Student::latest()->take(5)->get()->each(function (Student $student) use ($activities) {
            $activities->push([
                'type'        => 'new_student',
                'title'       => $student->full_name,
                'description' => 'هنرجوی جدید ثبت شد',
                'badge'       => 'New student',
                'tone'        => 'sky',
                'time'        => Jalalian::fromCarbon($student->created_at, 'Y/m/d H:i'),
                'sort_at'     => $student->created_at,
            ]);
        });

        StudentEnrollment::with(['student', 'instrument'])
            ->latest()
            ->take(5)
            ->get()
            ->each(function (StudentEnrollment $enrollment) use ($activities) {
                $instrument = $enrollment->instrument?->display_name ?? '—';
                $activities->push([
                    'type'        => 'new_enrollment',
                    'title'       => $enrollment->student?->full_name ?? '—',
                    'description' => 'ثبت‌نام جدید · '.$instrument,
                    'badge'       => 'New enrollment',
                    'tone'        => 'violet',
                    'time'        => Jalalian::fromCarbon($enrollment->created_at, 'Y/m/d H:i'),
                    'sort_at'     => $enrollment->created_at,
                ]);
            });

        ClassAttendance::with('student')
            ->latest('marked_at')
            ->take(5)
            ->get()
            ->each(function (ClassAttendance $attendance) use ($activities) {
                $status = $attendance->status instanceof \BackedEnum
                    ? $attendance->status->value
                    : (string) $attendance->status;

                $activities->push([
                    'type'        => 'attendance_marked',
                    'title'       => $attendance->student?->full_name ?? '—',
                    'description' => 'حضور ثبت شد · '.$status,
                    'badge'       => 'Attendance marked',
                    'tone'        => 'orange',
                    'time'        => Jalalian::fromCarbon($attendance->marked_at ?? $attendance->created_at, 'Y/m/d H:i'),
                    'sort_at'     => $attendance->marked_at ?? $attendance->created_at,
                ]);
            });

        ClassSession::with(['enrollment.student', 'student'])
            ->where('status', SessionStatusEnum::Completed->value)
            ->latest('session_date')
            ->take(5)
            ->get()
            ->each(function (ClassSession $session) use ($activities) {
                $studentName = $session->enrollment?->student?->full_name
                    ?? $session->student?->full_name
                    ?? '—';

                $activities->push([
                    'type'        => 'session_completed',
                    'title'       => $studentName,
                    'description' => 'جلسه برگزار شد · '.($session->room ?? '—'),
                    'badge'       => 'Session completed',
                    'tone'        => 'emerald',
                    'time'        => Jalalian::fromCarbon($session->session_date, 'Y/m/d'),
                    'sort_at'     => $session->session_date,
                ]);
            });

        Teacher::latest()->take(4)->get()
            ->each(function (Teacher $teacher) use ($activities) {
                $activities->push([
                    'type'        => 'new_teacher',
                    'title'       => $teacher->full_name,
                    'description' => 'استاد جدید اضافه شد',
                    'badge'       => 'New teacher',
                    'tone'        => 'amber',
                    'time'        => Jalalian::fromCarbon($teacher->created_at, 'Y/m/d H:i'),
                    'sort_at'     => $teacher->created_at,
                ]);
            });

        return $activities
            ->sortByDesc('sort_at')
            ->take(15)
            ->values();
    }
}
