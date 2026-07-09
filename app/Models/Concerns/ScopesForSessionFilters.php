<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Centralized query scopes for ClassSession filtering.
 *
 * Replaces repeated whereHas / with / whereBetween patterns that were
 * copy-pasted across 5+ controllers. Every scope uses the canonical
 * enrollment → session relationship path.
 */
trait ScopesForSessionFilters
{
    /**
     * Filter sessions within a date range (inclusive).
     */
    public function scopeForDateRange(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('session_date', [$start, $end]);
    }

    /**
     * Filter sessions belonging to a specific teacher.
     * Checks both the enrollment path and the direct teacher_id column
     * (set on manually created sessions that have no enrollment).
     */
    public function scopeForTeacher(Builder $query, int $teacherId): Builder
    {
        return $query->where(function (Builder $q) use ($teacherId) {
            $q->whereHas('enrollment', fn (Builder $inner) => $inner->where('teacher_id', $teacherId))
              ->orWhere('class_sessions.teacher_id', $teacherId);
        });
    }

    /**
     * Filter sessions belonging to a specific student.
     * Checks both the enrollment path and the direct student_id column.
     */
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where(function (Builder $q) use ($studentId) {
            $q->whereHas('enrollment', fn (Builder $inner) => $inner->where('student_id', $studentId))
              ->orWhere('class_sessions.student_id', $studentId);
        });
    }

    /**
     * Filter sessions for a specific instrument.
     * Checks both the enrollment path and the direct instrument_id column.
     */
    public function scopeForInstrument(Builder $query, int $instrumentId): Builder
    {
        return $query->where(function (Builder $q) use ($instrumentId) {
            $q->whereHas('enrollment', fn (Builder $inner) => $inner->where('instrument_id', $instrumentId))
              ->orWhere('class_sessions.instrument_id', $instrumentId);
        });
    }

    /**
     * Eager-load enrollment details AND direct student/teacher/instrument relations.
     * Direct relations are populated for manually created sessions without enrollment.
     */
    public function scopeWithEnrollmentDetails(Builder $query): Builder
    {
        return $query->with([
            'enrollment.student',
            'enrollment.teacher',
            'enrollment.instrument',
            'student',
            'teacher',
            'instrument',
        ]);
    }

    /**
     * Default schedule ordering: by date then start time.
     */
    public function scopeOrderBySchedule(Builder $query): Builder
    {
        return $query->orderBy('session_date')->orderBy('start_time');
    }
}
