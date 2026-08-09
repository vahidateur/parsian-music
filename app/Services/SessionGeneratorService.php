<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Scheduling\ProposalSource;
use App\Domain\Scheduling\RelationPath;
use App\Domain\Scheduling\RelationPathType;
use App\Domain\Scheduling\SchedulingDomain;
use App\Domain\Scheduling\SchedulingMutationException;
use App\Domain\Scheduling\SchedulingValidationException;
use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\RecurringSchedule;
use App\Models\StudentEnrollment;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Collection;

/** Preserves occurrence calculation and duplicate behavior while delegating writes to SchedulingDomain. */
final class SessionGeneratorService
{
    public function __construct(private readonly SchedulingDomain $scheduling) {}

    /** @return Collection<int, ClassSession> */
    public function generateForSchedule(RecurringSchedule $schedule, int $weeks = 8, ?User $actor = null): Collection
    {
        $created = collect();
        if (! $schedule->is_active) {
            return $created;
        }

        $enrollment = $schedule->enrollment ?? $schedule->enrollment()->first();
        if (! $enrollment instanceof StudentEnrollment) {
            return $created;
        }

        $dates = $this->getNextOccurrences((int) $schedule->weekday, $weeks);
        $storedStartTime = $this->storageTime($schedule->start_time);
        $proposalStartTime = substr($storedStartTime, 0, 5);
        $existing = ClassSession::query()
            ->where('enrollment_id', $enrollment->getKey())
            ->whereIn('session_date', $dates)
            ->where('start_time', $storedStartTime)
            ->pluck('session_date')
            ->all();
        $path = new RelationPath(
            RelationPathType::Enrollment,
            $enrollment->getKey(),
            $enrollment->student_id,
            $enrollment->teacher_id,
            $enrollment->instrument_id,
        );

        foreach ($dates as $date) {
            if (in_array($date, $existing, true)) {
                continue;
            }

            try {
                $proposal = $this->scheduling->normalize([
                    'student_id' => $enrollment->student_id,
                    'teacher_id' => $enrollment->teacher_id,
                    'instrument_id' => $enrollment->instrument_id,
                    'session_date' => $date,
                    'start_time' => $proposalStartTime,
                    'duration_minutes' => $schedule->duration_minutes,
                    'status' => SessionStatusEnum::Scheduled->value,
                    'room' => $schedule->room,
                    'notes' => null,
                    'source' => ProposalSource::Recurrence->value,
                ], $path, $this->timezone());
                $created->push($this->scheduling->create(
                    $actor,
                    $proposal,
                    ['recurring_schedule_id' => $schedule->getKey()],
                )->session);
            } catch (SchedulingValidationException|SchedulingMutationException) {
                continue;
            }
        }

        return $created;
    }

    /** @return Collection<int, string> */
    protected function getNextOccurrences(int $targetWeekday, int $weeks): Collection
    {
        $dates = collect();
        $cursor = Carbon::today()->addDay();

        while ((int) $cursor->dayOfWeek !== $targetWeekday) {
            $cursor = $cursor->addDay();
        }

        for ($index = 0; $index < $weeks; $index++) {
            $dates->push($cursor->toDateString());
            $cursor = $cursor->addWeek();
        }

        return $dates;
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone((string) config('app.timezone', 'Asia/Tehran'));
    }

    private function storageTime(mixed $time): string
    {
        return $time instanceof DateTimeInterface ? $time->format('H:i:s') : (string) $time;
    }
}
