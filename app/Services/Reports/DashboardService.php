<?php

namespace App\Services\Reports;

use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\CarbonImmutable;

/**
 * Admin dashboard summary service.
 *
 * Extracted from DashboardController. Consolidates the previously inline
 * dashboard queries (counts + alert range) into a single cacheable method.
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
     *     missedSessions: int
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

        return [
            'totalStudents'    => $totalStudents,
            'activeTeachers'   => $activeTeachers,
            'todaySessions'    => $todaySessions,
            'recentSessions'   => $recentSessions,
            'cancelledSessions' => $cancelledSessions,
            'missedSessions'   => $missedSessions,
        ];
    }
}
