<?php

namespace App\Services\Reports;

use App\Enums\AttendanceStatusEnum;
use App\Enums\SessionStatusEnum;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Teacher;
use Carbon\CarbonImmutable;

/**
 * Per-teacher panel summary service.
 *
 * Extracted from TeacherPanelController. Owns the weekly session + attendance
 * stat assembly for a single teacher's panel view.
 */
class TeacherPanelService
{
    /**
     * Build the panel payload for a teacher over a week range.
     *
     * @return array{
     *     sessions: \Illuminate\Database\Eloquent\Collection<int, ClassSession>,
     *     weeklySessions: int,
     *     completedSessions: int,
     *     missedSessions: int,
     *     totalStudents: int,
     *     presentCount: int,
     *     absentCount: int
     * }
     */
    public function getPanelData(Teacher $teacher, CarbonImmutable $weekStart, CarbonImmutable $weekEnd): array
    {
        $teacher->load('instruments');

        // Eager-load enrollment + relations to avoid N+1 in the view.
        $sessions = ClassSession::with([
            'enrollment.student',
            'enrollment.teacher',
            'enrollment.instrument',
        ])
            ->forTeacher($teacher->id)
            ->forDateRange($weekStart->toDateString(), $weekEnd->toDateString())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        $weeklySessions = $sessions->count();
        $completedSessions = $sessions->where('status', SessionStatusEnum::Completed->value)->count();
        $missedSessions = $sessions->where('status', SessionStatusEnum::Missed->value)->count();

        // Distinct student count via the model helper (no raw DB query).
        $totalStudents = $teacher->enrolledStudentsCount();

        // Attendance stats across this teacher's sessions (this week).
        $sessionIds = $sessions->pluck('id')->all();
        $presentCount = ClassAttendance::whereIn('class_session_id', $sessionIds)
            ->where('status', AttendanceStatusEnum::Present->value)
            ->count();
        $absentCount = ClassAttendance::whereIn('class_session_id', $sessionIds)
            ->where('status', AttendanceStatusEnum::Absent->value)
            ->count();

        return [
            'sessions'         => $sessions,
            'weeklySessions'   => $weeklySessions,
            'completedSessions' => $completedSessions,
            'missedSessions'   => $missedSessions,
            'totalStudents'    => $totalStudents,
            'presentCount'     => $presentCount,
            'absentCount'      => $absentCount,
        ];
    }
}
