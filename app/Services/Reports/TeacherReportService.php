<?php

namespace App\Services\Reports;

use App\Enums\SessionStatusEnum;
use App\Enums\TeacherStatusEnum;
use App\Models\ClassSession;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Teacher performance report service.
 *
 * Extracted from TeacherReportController. Owns the SQL-level aggregation
 * of completed vs missed sessions per teacher and the attendance-rate
 * computation (presentation layer).
 *
 * Uses the canonical relation path: teacher → enrollments → class_sessions.
 */
class TeacherReportService
{
    /**
     * Run the cross-DB-safe grouped aggregation once.
     * Uses parameter bindings instead of string interpolation.
     *
     * @param  array{0: string, 1: string}  $range
     * @return Collection<int, object>
     */
    public function buildGrouped(array $range): Collection
    {
        $completed = SessionStatusEnum::Completed->value;
        $missed = SessionStatusEnum::Missed->value;

        return ClassSession::query()
            ->join('student_enrollments', 'class_sessions.enrollment_id', '=', 'student_enrollments.id')
            ->whereBetween('class_sessions.session_date', $range)
            ->select(
                'student_enrollments.teacher_id',
                DB::raw('COUNT(*) AS total'),
                DB::raw("SUM(CASE WHEN class_sessions.status = ? THEN 1 ELSE 0 END) AS completed"),
                DB::raw("SUM(CASE WHEN class_sessions.status = ? THEN 1 ELSE 0 END) AS missed")
            )
            ->addBinding([$completed, $missed], 'select')
            ->groupBy('student_enrollments.teacher_id')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Hydrate teachers in one query and compute the attendance rate.
     *
     * @param  Collection<int, object>  $grouped
     * @return Collection<int, array{teacher: Teacher, total: int, completed: int, missed: int, rate: int}>
     */
    public function buildRows(Collection $grouped): Collection
    {
        if ($grouped->isEmpty()) {
            return $grouped;
        }

        $teacherIds = $grouped->pluck('teacher_id')->unique()->values();

        // Teacher count is always small (<100), so single query is optimal.
        $teachers = Teacher::whereIn('id', $teacherIds)
            ->active()
            ->orderBy('full_name')
            ->get()
            ->keyBy('id');

        return $grouped->map(function (object $row) use ($teachers) {
            $teacher = $teachers->get($row->teacher_id);

            // Skip rows whose teacher is no longer active.
            if (! $teacher) {
                return null;
            }

            $completed = (int) $row->completed;
            $missed = (int) $row->missed;

            // Attendance rate = delivered sessions over deliverable sessions
            // (completed + missed). Cancelled/scheduled excluded from base.
            $deliverable = $completed + $missed;
            $rate = $deliverable > 0 ? (int) round(($completed / $deliverable) * 100) : 0;

            return [
                'teacher'   => $teacher,
                'total'     => (int) $row->total,
                'completed' => $completed,
                'missed'    => $missed,
                'rate'      => $rate,
            ];
        })->filter()->values();
    }

    /**
     * Build the full teacher report payload.
     *
     * @param  array{0: string, 1: string}  $range
     * @return Collection<int, array{teacher: Teacher, total: int, completed: int, missed: int, rate: int}>
     */
    public function generate(array $range): Collection
    {
        return $this->buildRows($this->buildGrouped($range));
    }
}
