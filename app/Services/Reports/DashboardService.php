<?php

namespace App\Services\Reports;

use App\Enums\SessionStatusEnum;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Admin dashboard summary service.
 *
 * Extracted from DashboardController. Consolidates the previously inline
 * dashboard queries (counts + alert range) into a single cacheable method.
 * Extended with real data sources for analytics widgets.
 */
class DashboardService
{
    /**
     * Build the full dashboard summary payload for a given day.
     *
     * @return array{
     *     totalStudents: int,
     *     activeTeachers: int,
     *     todaySessions: int,
     *     recentSessions: \Illuminate\Database\Eloquent\Collection<int, ClassSession>,
     *     cancelledSessions: int,
     *     missedSessions: int,
     *     recentStudents: \Illuminate\Database\Eloquent\Collection,
     *     enrollmentTrend: array,
     *     attendanceStats: array
     * }
     */
    public function getSummary(CarbonImmutable $today): array
    {
        $todayString = $today->toDateString();

        $totalStudents = Student::count();
        $activeTeachers = Teacher::active()->count();
        $todaySessions = ClassSession::where('session_date', $todayString)->count();

        $recentSessions = ClassSession::with([
            'enrollment.student',
            'enrollment.teacher',
        ])
            ->where('session_date', $todayString)
            ->orderBy('start_time')
            ->get();

        $alertStart = $today->subDays(7)->toDateString();
        $alertRange = [$alertStart, $todayString];

        $cancelledSessions = ClassSession::whereBetween('session_date', $alertRange)
            ->where('status', SessionStatusEnum::Cancelled->value)
            ->count();

        $missedSessions = ClassSession::whereBetween('session_date', $alertRange)
            ->where('status', SessionStatusEnum::Missed->value)
            ->count();

        // Recent Students: latest 5 with enrollment details
        $recentStudents = Student::with(['enrollments.instrument', 'enrollments.teacher'])
            ->latest()
            ->take(5)
            ->get();

        // Enrollment Trend: last 6 months of enrollment counts
        $enrollmentTrend = $this->getEnrollmentTrend($today);

        // Attendance Stats: aggregated by status
        $attendanceStats = $this->getAttendanceStats();

        return [
            'totalStudents'    => $totalStudents,
            'activeTeachers'   => $activeTeachers,
            'todaySessions'    => $todaySessions,
            'recentSessions'   => $recentSessions,
            'cancelledSessions' => $cancelledSessions,
            'missedSessions'   => $missedSessions,
            'recentStudents'   => $recentStudents,
            'enrollmentTrend'  => $enrollmentTrend,
            'attendanceStats'  => $attendanceStats,
        ];
    }

    /**
     * Get enrollment counts for the last 6 months, grouped by month.
     *
     * @return array Months with enrollment counts
     */
    private function getEnrollmentTrend(CarbonImmutable $today): array
    {
        $sixMonthsAgo = $today->copy()->subMonths(6)->startOfMonth();
        $endDate = $today->endOfMonth();

        $enrollments = StudentEnrollment::where('started_at', '>=', $sixMonthsAgo->toDateString())
            ->where('started_at', '<=', $endDate->toDateString())
            ->selectRaw('DATE_FORMAT(started_at, \'%Y-%m-01\') as month, COUNT(*) as count')
            ->groupBy(DB::raw('DATE_FORMAT(started_at, \'%Y-%m-01\')'))
            ->orderBy('month')
            ->get();

        // Build array with all 6 months (fill gaps with 0)
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $monthDate = $today->copy()->subMonths($i)->startOfMonth();
            $monthKey = $monthDate->toDateString();

            $enrollment = $enrollments->firstWhere('month', $monthKey);
            $count = $enrollment ? $enrollment->count : 0;

            $trend[] = [
                'month' => $monthDate->format('m'),  // 01-12
                'label' => $this->monthNamePersian($monthDate->month),
                'count' => $count,
            ];
        }

        return $trend;
    }

    /**
     * Get attendance statistics aggregated by status.
     *
     * @return array Attendance counts by status
     */
    private function getAttendanceStats(): array
    {
        $stats = ClassAttendance::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = array_sum($stats);

        return [
            'present'  => $stats['present'] ?? 0,
            'absent'   => $stats['absent'] ?? 0,
            'late'     => $stats['late'] ?? 0,
            'excused'  => $stats['excused'] ?? 0,
            'total'    => $total,
        ];
    }

    /**
     * Get Persian month name.
     *
     * @param int $monthNumber 1-12
     * @return string
     */
    private function monthNamePersian(int $monthNumber): string
    {
        $months = [
            1 => 'فروردین',
            2 => 'اردیبهشت',
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند',
        ];

        return $months[$monthNumber] ?? '';
    }
}
