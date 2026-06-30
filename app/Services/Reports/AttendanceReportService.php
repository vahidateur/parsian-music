<?php

namespace App\Services\Reports;

use App\Enums\AttendanceStatusEnum;
use App\Models\ClassAttendance;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Attendance report aggregation service.
 *
 * Extracted from AttendanceReportController. Owns the SQL-level aggregation
 * for per-student attendance breakdowns and overall totals within a date range.
 *
 * Hybrid strategy: SQL for aggregation/collection of totals, Collections
 * only for final presentation (student hydration).
 */
class AttendanceReportService
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
        $present = AttendanceStatusEnum::Present->value;
        $absent = AttendanceStatusEnum::Absent->value;
        $late = AttendanceStatusEnum::Late->value;
        $excused = AttendanceStatusEnum::Excused->value;

        return ClassAttendance::query()
            ->join('class_sessions', 'class_attendances.class_session_id', '=', 'class_sessions.id')
            ->whereBetween('class_sessions.session_date', $range)
            ->select(
                'class_attendances.student_id',
                DB::raw('COUNT(*) AS total'),
                DB::raw("SUM(CASE WHEN class_attendances.status = ? THEN 1 ELSE 0 END) AS present"),
                DB::raw("SUM(CASE WHEN class_attendances.status = ? THEN 1 ELSE 0 END) AS absent"),
                DB::raw("SUM(CASE WHEN class_attendances.status = ? THEN 1 ELSE 0 END) AS late"),
                DB::raw("SUM(CASE WHEN class_attendances.status = ? THEN 1 ELSE 0 END) AS excused")
            )
            ->addBinding([$present, $absent, $late, $excused], 'select')
            ->groupBy('class_attendances.student_id')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Compute overall totals via a single lightweight SQL query (one row).
     * Avoids loading all grouped rows into memory just to sum them.
     *
     * @param  array{0: string, 1: string}  $range
     * @return array{sessions: int, present: int, absent: int, late: int, excused: int}
     */
    public function buildTotals(array $range): array
    {
        $present = AttendanceStatusEnum::Present->value;
        $absent = AttendanceStatusEnum::Absent->value;
        $late = AttendanceStatusEnum::Late->value;
        $excused = AttendanceStatusEnum::Excused->value;

        $row = ClassAttendance::query()
            ->join('class_sessions', 'class_attendances.class_session_id', '=', 'class_sessions.id')
            ->whereBetween('class_sessions.session_date', $range)
            ->select(
                DB::raw('COUNT(*) AS sessions'),
                DB::raw("SUM(CASE WHEN class_attendances.status = ? THEN 1 ELSE 0 END) AS present"),
                DB::raw("SUM(CASE WHEN class_attendances.status = ? THEN 1 ELSE 0 END) AS absent"),
                DB::raw("SUM(CASE WHEN class_attendances.status = ? THEN 1 ELSE 0 END) AS late"),
                DB::raw("SUM(CASE WHEN class_attendances.status = ? THEN 1 ELSE 0 END) AS excused")
            )
            ->addBinding([$present, $absent, $late, $excused], 'select')
            ->first();

        return [
            'sessions' => (int) ($row->sessions ?? 0),
            'present'  => (int) ($row->present ?? 0),
            'absent'   => (int) ($row->absent ?? 0),
            'late'     => (int) ($row->late ?? 0),
            'excused'  => (int) ($row->excused ?? 0),
        ];
    }

    /**
     * Hydrate students and attach to aggregated rows.
     * Uses chunkById when the student set is large (>500 distinct ids) to keep
     * memory bounded; otherwise uses a single query for small datasets.
     *
     * @param  Collection<int, object>  $grouped
     * @return Collection<int, object>
     */
    public function buildRows(Collection $grouped): Collection
    {
        if ($grouped->isEmpty()) {
            return $grouped;
        }

        $studentIds = $grouped->pluck('student_id')->unique()->values();

        // Small result set — single query is faster.
        if ($studentIds->count() <= 500) {
            $students = Student::whereIn('id', $studentIds)->get()->keyBy('id');

            return $grouped->map(function (object $row) use ($students) {
                $row->student = $students->get($row->student_id);
                return $row;
            });
        }

        // Large result set — chunk by id to cap memory on the Student models.
        $students = collect();
        Student::whereIn('id', $studentIds)
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$students) {
                foreach ($chunk as $s) {
                    $students[$s->id] = $s;
                }
            });

        return $grouped->map(function (object $row) use ($students) {
            $row->student = $students->get($row->student_id);
            return $row;
        });
    }

    /**
     * Build the full report payload: per-student rows + overall totals.
     *
     * @param  array{0: string, 1: string}  $range
     * @return array{totals: array, rows: Collection<int, object>}
     */
    public function generate(array $range): array
    {
        $grouped = $this->buildGrouped($range);

        return [
            'totals' => $this->buildTotals($range),
            'rows'   => $this->buildRows($grouped),
        ];
    }
}
