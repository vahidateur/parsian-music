<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

/** Applies the explicit default policy: every detected conflict blocks unless non-blocking. */
final class ConflictClassifier
{
    /** @param list<ConflictFact> $facts */
    public function classify(array $facts): ConflictReport
    {
        $conflicts = [];
        foreach ($facts as $fact) {
            $cancelled = $fact->status?->value === 'cancelled';
            $resource = $fact->resource === 'teacher_buffer' ? 'teacher' : $fact->resource;
            $code = $fact->range === null ? 'MALFORMED_BLOCKING_SESSION' : match ($fact->resource) {
                'teacher_buffer' => 'TEACHER_BUFFER_OVERLAP', 'teacher' => 'TEACHER_OVERLAP', 'student' => 'STUDENT_OVERLAP', 'enrollment' => 'ENROLLMENT_OVERLAP', 'room' => 'ROOM_OVERLAP', default => 'CONFLICT',
            };
            $conflicts[] = new SchedulingConflict($resource, $cancelled ? 'CANCELLED_SESSION' : $code, $cancelled ? 'non_blocking' : 'hard', $fact->range === null, $fact->sessionId, $fact->range);
            if (! $cancelled && $fact->recurring) { $conflicts[] = new SchedulingConflict('recurring_occurrence', 'RECURRING_OCCURRENCE_OVERLAP', 'hard', false, $fact->sessionId, $fact->range); }
        }

        return new ConflictReport($conflicts);
    }

    public function hardRule(string $code, array $parameters = []): SchedulingConflict
    {
        return new SchedulingConflict('academy_rule', $code, 'hard', true, parameters: $parameters);
    }
}
