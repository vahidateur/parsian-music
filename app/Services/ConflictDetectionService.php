<?php

namespace App\Services;

use App\Models\ClassSession;
use Carbon\Carbon;

/**
 * Conflict detection service.
 *
 * Canonical service for detecting scheduling conflicts. The three public
 * check methods share a single private implementation (the interval-overlap
 * algorithm), differing only in the base query filter.
 *
 * Overlap rule: (existing_start < new_end) AND (existing_end > new_start).
 */
class ConflictDetectionService
{
    /**
     * Check if a teacher already has a session overlapping the proposed time.
     *
     * @param  mixed  $startTime  Accepts "HH:MM", a DateTime instance, or Carbon.
     */
    public function checkTeacherConflict(int $teacherId, string $date, mixed $startTime, int $duration): bool
    {
        [$start, $end] = $this->resolveTimeRange($date, $startTime, $duration);

        return $this->hasOverlappingSession(
            fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('teacher_id', $teacherId)),
            $date,
            $start,
            $end
        );
    }

    /**
     * Check if a room is already booked for an overlapping time.
     *
     * @param  mixed  $startTime  Accepts "HH:MM", a DateTime instance, or Carbon.
     */
    public function checkRoomConflict(string $room, string $date, mixed $startTime, int $duration): bool
    {
        [$start, $end] = $this->resolveTimeRange($date, $startTime, $duration);

        return $this->hasOverlappingSession(
            fn ($q) => $q->where('room', $room),
            $date,
            $start,
            $end
        );
    }

    /**
     * Check if an enrollment already has a session at an overlapping time.
     *
     * @param  mixed  $startTime  Accepts "HH:MM", a DateTime instance, or Carbon.
     */
    public function checkTimeOverlap(int $enrollmentId, string $date, mixed $startTime, int $duration): bool
    {
        [$start, $end] = $this->resolveTimeRange($date, $startTime, $duration);

        return $this->hasOverlappingSession(
            fn ($q) => $q->where('enrollment_id', $enrollmentId),
            $date,
            $start,
            $end
        );
    }

    /**
     * Single implementation of the overlap check across all conflict types.
     *
     * @param  callable(\Illuminate\Database\Eloquent\Builder): void  $filterScope
     */
    protected function hasOverlappingSession(callable $filterScope, string $date, Carbon $start, Carbon $end): bool
    {
        // `session_date` is persisted with a time component (e.g. "2026-07-04
        // 00:00:00"), so an exact string match against a plain "Y-m-d" value
        // would silently miss every row. whereDate() normalizes the comparison.
        $query = ClassSession::whereDate('session_date', $date)
            ->whereTime('start_time', '<', $end->format('H:i:s'));

        $filterScope($query);

        return $query->get()
            ->contains(fn (ClassSession $session) => $this->sessionEndsAfter($session, $start));
    }

    /**
     * Compute the [start, end] Carbon pair for the proposed session.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveTimeRange(string $date, mixed $startTime, int $duration): array
    {
        $start = Carbon::parse("{$date} {$startTime}");
        $end = $start->copy()->addMinutes($duration);

        return [$start, $end];
    }

    /**
     * Determine if an existing session ends after the new session starts.
     *
     * Implements the overlap rule: existing_end > new_start.
     * Combined with the query filter (existing_start < new_end), this
     * gives the full overlap check.
     *
     * NOTE: `start_time` is cast to `datetime`, so Carbon anchors it to
     * "today" at cast-time rather than the session's own `session_date`.
     * We must always rebuild the end time using `session_date` explicitly,
     * otherwise conflicts on any date other than today go undetected.
     */
    protected function sessionEndsAfter(ClassSession $session, Carbon $newStart): bool
    {
        $timeOfDay = $session->start_time instanceof Carbon
            ? $session->start_time->format('H:i:s')
            : (string) $session->start_time;

        $existingEnd = Carbon::parse("{$session->session_date->toDateString()} {$timeOfDay}")
            ->addMinutes($session->duration_minutes);

        return $existingEnd->gt($newStart);
    }
}
