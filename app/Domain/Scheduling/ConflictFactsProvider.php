<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Services\ConflictDetectionService;
use DateTimeInterface;
use DateTimeZone;

/** Adapts the established conflict service into safe interval/resource facts. */
final readonly class ConflictFactsProvider
{
    public function __construct(private ConflictDetectionService $conflicts) {}

    /** @return list<ConflictFact> */
    public function forProposal(ScheduleProposal $proposal, EffectiveSchedulingRules $rules, ?string $room = null): array
    {
        $date = $proposal->timeRange->start->format('Y-m-d');
        $time = $proposal->timeRange->start->format('H:i');
        $duration = $proposal->timeRange->durationMinutes();
        $ignore = $proposal->sessionId === null ? null : (int) $proposal->sessionId;
        $sets = [
            'teacher' => $this->conflicts->teacherConflictSessions((int) $proposal->relationPath->teacherId, $date, $time, $duration, $ignore),
            'student' => $this->conflicts->studentConflictSessions((int) $proposal->relationPath->studentId, $date, $time, $duration, $ignore),
        ];
        if ($proposal->relationPath->enrollmentId !== null) { $sets['enrollment'] = $this->conflicts->enrollmentConflictSessions((int) $proposal->relationPath->enrollmentId, $date, $time, $duration, $ignore); }
        if ($room !== null) { $sets['room'] = $this->conflicts->roomConflictSessions($room, $date, $time, $duration, $ignore); }

        $facts = [];
        foreach ($sets as $resource => $sessions) {
            foreach ($sessions as $session) { $facts[] = $this->fact($resource, $session, $rules->timezone); }
        }
        if ($rules->teacherBufferBefore > 0 || $rules->teacherBufferAfter > 0) {
            foreach ($this->conflicts->teacherBufferedConflictSessions((int) $proposal->relationPath->teacherId, $date, $time, $duration, $rules->teacherBufferBefore, $rules->teacherBufferAfter, $ignore) as $session) {
                $fact = $this->fact('teacher_buffer', $session, $rules->timezone);
                if ($fact->range === null || ! $fact->range->overlaps($proposal->timeRange)) { $facts[] = $fact; }
            }
        }

        return $facts;
    }

    /** @return list<ConflictFact> */
    public function teacherDayFacts(ScheduleProposal $proposal, EffectiveSchedulingRules $rules): array
    {
        $date = $proposal->timeRange->start->format('Y-m-d');
        $ignore = $proposal->sessionId === null ? null : (int) $proposal->sessionId;
        return $this->conflicts->teacherSessionsOnDate((int) $proposal->relationPath->teacherId, $date, $ignore)->map(fn (ClassSession $session): ConflictFact => $this->fact('teacher', $session, $rules->timezone))->all();
    }

    public function roomIsOccupied(string $room, ScheduleProposal $proposal): bool
    {
        return $this->conflicts->checkRoomConflict($room, $proposal->timeRange->start->format('Y-m-d'), $proposal->timeRange->start->format('H:i'), $proposal->timeRange->durationMinutes(), $proposal->sessionId === null ? null : (int) $proposal->sessionId);
    }

    private function fact(string $resource, ClassSession $session, DateTimeZone $timezone): ConflictFact
    {
        $date = $session->session_date instanceof DateTimeInterface ? $session->session_date->format('Y-m-d') : (string) $session->session_date;
        $time = $session->start_time instanceof DateTimeInterface ? $session->start_time->format('H:i') : (string) $session->start_time;
        try { $range = TimeRange::fromLocal($date, $time, (int) $session->duration_minutes, $timezone); } catch (\InvalidArgumentException) { $range = null; }
        $status = $session->status instanceof SessionStatusEnum ? $session->status : SessionStatusEnum::tryFrom((string) $session->status);
        return new ConflictFact($resource, $session->getKey(), $range, $status, $session->recurring_schedule_id !== null);
    }
}

/** @internal Raw fact only; SchedulingDomain is the sole result owner. */
final readonly class ConflictFact
{
    public function __construct(public string $resource, public int|string|null $sessionId, public ?TimeRange $range, public ?SessionStatusEnum $status, public bool $recurring) {}
}
