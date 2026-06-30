<?php

namespace App\Services;

use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\RecurringSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Session generation service.
 *
 * Canonical orchestrator for creating future ClassSession records from a
 * RecurringSchedule. Encapsulates all session creation logic:
 *   1. Date occurrence calculation (pure date math).
 *   2. Duplicate detection (existing enrollment + date + time).
 *   3. Conflict detection via ConflictDetectionService (teacher/room/enrollment).
 *   4. Transactional bulk creation.
 *
 * Weekday convention: DB stores 0 (Sunday) through 6 (Saturday), matching
 * Carbon's dayOfWeek (0=Sun … 6=Sat).
 */
class SessionGeneratorService
{
    public function __construct(
        protected ConflictDetectionService $conflictDetector,
    ) {}

    /**
     * Generate future ClassSession records from a RecurringSchedule.
     *
     * Skips any session that already exists for the same
     * (enrollment_id + session_date + start_time) combination, or that
     * conflicts with an existing session.
     *
     * @return Collection<int, ClassSession>  The newly created sessions.
     */
    public function generateForSchedule(RecurringSchedule $schedule, int $weeks = 8): Collection
    {
        $created = collect();

        if (! $schedule->is_active) {
            return $created;
        }

        $enrollmentId = $schedule->enrollment_id;
        $targetWeekday = (int) $schedule->weekday; // 0-6 matching Carbon::dayOfWeek
        $startTime = $schedule->start_time;
        $durationMinutes = $schedule->duration_minutes;
        $room = $schedule->room;

        // Resolve teacher_id once from the enrollment relation.
        $teacherId = $schedule->enrollment?->teacher_id
            ?? $schedule->enrollment()->value('teacher_id');

        // Build the set of session dates for the next N occurrences,
        // starting from the next matching weekday (today excluded).
        $dates = $this->getNextOccurrences($targetWeekday, $weeks);

        // Bulk-load existing sessions for these dates to avoid N queries.
        $existing = ClassSession::where('enrollment_id', $enrollmentId)
            ->whereIn('session_date', $dates)
            ->where('start_time', $startTime)
            ->pluck('session_date')
            ->all();

        DB::transaction(function () use (
            &$created, $dates, $existing, $schedule,
            $enrollmentId, $teacherId, $startTime, $durationMinutes, $room
        ) {
            foreach ($dates as $dateString) {
                if (in_array($dateString, $existing, true)) {
                    continue; // Skip duplicates
                }

                // Conflict detection: skip if teacher, room, or enrollment overlaps.
                if ($teacherId && $this->conflictDetector->checkTeacherConflict(
                    $teacherId, $dateString, $startTime, $durationMinutes
                )) {
                    continue;
                }
                if ($this->conflictDetector->checkRoomConflict(
                    $room, $dateString, $startTime, $durationMinutes
                )) {
                    continue;
                }
                if ($this->conflictDetector->checkTimeOverlap(
                    $enrollmentId, $dateString, $startTime, $durationMinutes
                )) {
                    continue;
                }

                $created->push(ClassSession::create([
                    'enrollment_id'         => $enrollmentId,
                    'recurring_schedule_id' => $schedule->id,
                    'session_date'          => $dateString,
                    'start_time'            => $startTime,
                    'duration_minutes'      => $durationMinutes,
                    'room'                  => $room,
                    'status'                => SessionStatusEnum::Scheduled,
                ]));
            }
        });

        return $created;
    }

    /**
     * Resolve the next N occurrences of a target weekday.
     *
     * Pure date math: starts from tomorrow, advances to the first matching
     * weekday, then yields weekly occurrences for `$weeks` weeks.
     *
     * @return Collection<int, string>  List of "YYYY-MM-DD" date strings.
     */
    protected function getNextOccurrences(int $targetWeekday, int $weeks): Collection
    {
        $dates = collect();

        $cursor = Carbon::today()->addDay();

        while ((int) $cursor->dayOfWeek !== $targetWeekday) {
            $cursor = $cursor->addDay();
        }

        for ($i = 0; $i < $weeks; $i++) {
            $dates->push($cursor->toDateString());
            $cursor = $cursor->addWeek();
        }

        return $dates;
    }
}
