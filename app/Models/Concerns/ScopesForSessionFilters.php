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
     * Uses enrollment.teacher_id as the canonical join path.
     */
    public function scopeForTeacher(Builder $query, int $teacherId): Builder
    {
        return $query->whereHas('enrollment', fn (Builder $q) => $q->where('teacher_id', $teacherId));
    }

    /**
     * Filter sessions belonging to a specific student.
     * Uses enrollment.student_id as the canonical join path.
     */
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->whereHas('enrollment', fn (Builder $q) => $q->where('student_id', $studentId));
    }

    /**
     * Filter sessions for a specific instrument.
     * Uses enrollment.instrument_id as the canonical join path.
     */
    public function scopeForInstrument(Builder $query, int $instrumentId): Builder
    {
        return $query->whereHas('enrollment', fn (Builder $q) => $q->where('instrument_id', $instrumentId));
    }

    /**
     * Eager-load the enrollment with student, teacher, and instrument.
     * Replaces 3+ copy-pasted with() calls across controllers.
     */
    public function scopeWithEnrollmentDetails(Builder $query): Builder
    {
        return $query->with([
            'enrollment.student',
            'enrollment.teacher',
            'enrollment.instrument',
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
