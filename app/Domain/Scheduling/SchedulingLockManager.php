<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/** Acquires every existing-session mutation lock in one repeatable order. */
final class SchedulingLockManager
{
    public function lock(ScheduleProposal $proposal): LockedSchedulingScope
    {
        if ($proposal->sessionId === null) {
            throw new \InvalidArgumentException('An existing session is required for a versioned mutation.');
        }

        $session = ClassSession::query()->lockForUpdate()->find($proposal->sessionId);
        if (! $session instanceof ClassSession) {
            throw (new ModelNotFoundException)->setModel(ClassSession::class, [$proposal->sessionId]);
        }

        $ids = $this->resourceIds($session, $proposal);
        $keys = ['class_session:'.$session->getKey()];
        foreach ($ids as $type => $values) {
            foreach ($values as $id) {
                $this->lockResource($type, $id);
                $keys[] = $type.':'.$id;
            }
        }

        foreach ($this->roomNames($session, $proposal) as $room) {
            $model = Room::query()->where('name', $room)->lockForUpdate()->first();
            if ($model instanceof Room) {
                $keys[] = 'room:'.$model->getKey();
            }
        }

        return new LockedSchedulingScope($session, array_values(array_unique($keys)));
    }

    /** @return array<string, list<int|string>> */
    private function resourceIds(ClassSession $session, ScheduleProposal $proposal): array
    {
        $ids = [
            'enrollment' => [$session->enrollment_id, $proposal->relationPath->enrollmentId],
            'instrument' => [$session->instrument_id, $proposal->relationPath->instrumentId],
            'student' => [$session->student_id, $proposal->relationPath->studentId],
            'teacher' => [$session->teacher_id, $proposal->relationPath->teacherId],
        ];
        ksort($ids, SORT_STRING);

        foreach ($ids as $type => $values) {
            $values = array_filter($values, static fn (mixed $id): bool => is_int($id) || (is_string($id) && ctype_digit($id)));
            $ids[$type] = array_values(array_unique($values, SORT_REGULAR));
            sort($ids[$type], SORT_NUMERIC);
        }

        return $ids;
    }

    /** @return list<string> */
    private function roomNames(ClassSession $session, ScheduleProposal $proposal): array
    {
        $rooms = array_filter([$session->getRawOriginal('room'), $proposal->room], 'is_string');
        $rooms = array_values(array_unique(array_map('trim', $rooms)));
        sort($rooms, SORT_STRING);

        return $rooms;
    }

    private function lockResource(string $type, int|string $id): void
    {
        $model = match ($type) {
            'enrollment' => StudentEnrollment::class,
            'instrument' => Instrument::class,
            'student' => Student::class,
            'teacher' => Teacher::class,
        };
        if ($model::query()->whereKey($id)->lockForUpdate()->first() === null) {
            throw (new ModelNotFoundException)->setModel($model, [$id]);
        }
    }
}

/** @internal Locked state and the deterministically ordered resource version scope. */
final readonly class LockedSchedulingScope
{
    /** @param list<string> $resourceKeys */
    public function __construct(public ClassSession $session, public array $resourceKeys) {}
}
