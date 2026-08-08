<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling;

use App\Domain\Scheduling\AcademyRulesProvider;
use App\Domain\Scheduling\AvailabilityEvaluator;
use App\Domain\Scheduling\AvailabilityState;
use App\Domain\Scheduling\ConflictClassifier;
use App\Domain\Scheduling\ConflictFactsProvider;
use App\Domain\Scheduling\EffectiveSchedulingRules;
use App\Domain\Scheduling\ProposalSource;
use App\Domain\Scheduling\RelationPath;
use App\Domain\Scheduling\RelationPathType;
use App\Domain\Scheduling\RoomSuitabilityService;
use App\Domain\Scheduling\ScheduleProposal;
use App\Domain\Scheduling\ScheduleProposalNormalizer;
use App\Domain\Scheduling\SchedulingDomain;
use App\Domain\Scheduling\TimeRange;
use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\RecurringSchedule;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Services\ConflictDetectionService;
use App\Services\RelationPathResolver;
use App\Services\RoomOptionProvider;
use App\Services\RoomResolver;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\DeterministicSchedulingCases;
use Tests\TestCase;
use Throwable;

/**
 * Feature: interactive-session-scheduling, Property 3: Scheduling consistency and complete conflict invariant.
 *
 * **Validates: Requirements 4.2-4.8, 9.3-9.7, 10.1-10.3.**
 */
final class AvailabilityConsistencyPropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_availability_and_conflict_classification_are_deterministic_and_safe(): void
    {
        $resourcesSeen = [];

        foreach (DeterministicSchedulingCases::intervals() as $case) {
            $expected = [];
            $result = null;

            try {
                [$result, $expected] = $this->evaluateCase($case);
                $report = $result->details['conflicts'];
                $states = array_values(array_filter(AvailabilityState::cases(), static fn (AvailabilityState $state): bool => $state === $result->state));

                self::assertCount(1, $states);
                self::assertSame($expected['state'], $result->state);

                if ($expected['state'] !== AvailabilityState::Available) {
                    self::assertNotSame(AvailabilityState::Available, $result->state, 'A conflicting or hard-constraint scenario must never be available.');
                }

                foreach ($expected['codes'] as $code) {
                    self::assertContains($code, array_map(static fn ($conflict): string => $conflict->code, $report->conflicts));
                }

                foreach ($expected['resources'] as $resource) {
                    self::assertContains($resource, array_map(static fn ($conflict): string => $conflict->resource, $report->conflicts));
                }

                foreach ($expected['range_resources'] as $resource) {
                    self::assertNotEmpty(array_filter($report->conflicts, static fn ($conflict): bool => $conflict->resource === $resource && $conflict->range !== null));
                }

                if ($expected['state'] === AvailabilityState::Available) {
                    self::assertFalse($report->hasBlockingConflict());
                    self::assertFalse($report->hasInvalidConstraint());
                }

                foreach ($report->conflicts as $conflict) {
                    $resourcesSeen[$conflict->resource] = true;
                }
            } catch (Throwable $error) {
                self::fail(DeterministicSchedulingCases::firstFailure(
                    sprintf('Property 3; seed=%d; case=%d', $case['seed'], $case['case']),
                    $expected,
                    $result === null ? ['exception' => $error->getMessage()] : $result->jsonSerialize(),
                ));
            }
        }

        $resources = array_keys($resourcesSeen);
        sort($resources);
        self::assertSame(['academy_rule', 'enrollment', 'recurring_occurrence', 'room', 'student', 'teacher'], $resources);
    }

    /** @param array<string, int|string> $case @return array{0: \App\Domain\Scheduling\AvailabilityResult, 1: array{state: AvailabilityState, codes: list<string>, resources: list<string>, range_resources: list<string>}} */
    private function evaluateCase(array $case): array
    {
        [$student, $teacher, $instrument] = $this->resources();
        $date = '2026-08-03';
        $start = 540 + ((int) $case['start_minute'] % 600);
        $duration = (int) $case['duration_minutes'];
        $time = $this->time($start);
        $room = 'Property Room '.$case['case'];
        $scenario = (int) $case['case'] % 12;

        return match ($scenario) {
            0 => $this->physicalConflict($student, $teacher, $instrument, $date, $start, $duration, $time, $room),
            1 => $this->adjacentInterval($student, $teacher, $instrument, $date, $start, $duration, $time, $room),
            2 => $this->bufferedAdjacency($student, $teacher, $instrument, $date, $start, $duration, $time, $room),
            3 => $this->lifecycleInterval($student, $teacher, $instrument, $date, $start, $duration, $time, $room, SessionStatusEnum::Cancelled, AvailabilityState::Available, []),
            4 => $this->lifecycleInterval($student, $teacher, $instrument, $date, $start, $duration, $time, $room, SessionStatusEnum::Completed, AvailabilityState::Conflict, ['TEACHER_OVERLAP']),
            5 => $this->hardRule($student, $teacher, $instrument, $date, $duration, '07:00', $this->rules(openingMinute: 8 * 60), 'OUTSIDE_WORKING_HOURS'),
            6 => $this->dailyLimit($student, $teacher, $instrument, $date, $start, $duration, $time),
            7 => $this->consecutiveLimit($student, $teacher, $instrument, $date, $start, $duration, $time),
            8 => $this->hardRule($student, $teacher, $instrument, $date, $duration, $time, $this->rules(lunch: ['start' => $start + 5, 'end' => $start + 15]), 'LUNCH_OVERLAP'),
            9 => $this->missingRoom($student, $teacher, $instrument, $date, $duration, $time, $room),
            10 => $this->unavailableRoom($student, $teacher, $instrument, $date, $duration, $time, $room),
            default => $this->incompatibleRoom($student, $teacher, $instrument, $date, $duration, $time, $room),
        };
    }

    /** @return array{0: \App\Domain\Scheduling\AvailabilityResult, 1: array{state: AvailabilityState, codes: list<string>, resources: list<string>, range_resources: list<string>}} */
    private function physicalConflict(Student $student, Teacher $teacher, Instrument $instrument, string $date, int $start, int $duration, string $time, string $room): array
    {
        Room::factory()->named($room)->active()->create();
        $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'teacher_id' => $teacher->id, 'instrument_id' => $instrument->id]);
        $recurring = RecurringSchedule::query()->create(['enrollment_id' => $enrollment->id, 'weekday' => 1, 'start_time' => $time, 'duration_minutes' => $duration, 'room' => $room, 'is_active' => true]);
        ClassSession::factory()->create(['enrollment_id' => $enrollment->id, 'recurring_schedule_id' => $recurring->id, 'session_date' => $date, 'start_time' => $time, 'duration_minutes' => $duration, 'room' => $room]);

        $proposal = $this->proposal($student, $teacher, $instrument, $date, $this->time($start + max(1, intdiv($duration, 2))), $duration, $room, $enrollment);

        return [$this->domain($this->rules())->evaluate($proposal), ['state' => AvailabilityState::Conflict, 'codes' => [], 'resources' => ['teacher', 'student', 'enrollment', 'room', 'recurring_occurrence'], 'range_resources' => ['teacher', 'student', 'enrollment', 'room', 'recurring_occurrence']]];
    }

    /** @return array{0: \App\Domain\Scheduling\AvailabilityResult, 1: array{state: AvailabilityState, codes: list<string>, resources: list<string>, range_resources: list<string>}} */
    private function adjacentInterval(Student $student, Teacher $teacher, Instrument $instrument, string $date, int $start, int $duration, string $time, string $room): array
    {
        Room::factory()->named($room)->active()->create();
        $this->directSession($student, $teacher, $instrument, $date, $time, $duration, $room);

        return [$this->domain($this->rules())->evaluate($this->proposal($student, $teacher, $instrument, $date, $this->time($start + $duration), $duration, $room)), ['state' => AvailabilityState::Available, 'codes' => [], 'resources' => [], 'range_resources' => []]];
    }

    /** @return array{0: \App\Domain\Scheduling\AvailabilityResult, 1: array{state: AvailabilityState, codes: list<string>, resources: list<string>, range_resources: list<string>}} */
    private function bufferedAdjacency(Student $student, Teacher $teacher, Instrument $instrument, string $date, int $start, int $duration, string $time, string $room): array
    {
        Room::factory()->named($room)->active()->create();
        $this->directSession($student, $teacher, $instrument, $date, $time, $duration, $room);

        return [$this->domain($this->rules(bufferAfter: 15))->evaluate($this->proposal($student, $teacher, $instrument, $date, $this->time($start + $duration), $duration, $room)), ['state' => AvailabilityState::Conflict, 'codes' => ['TEACHER_BUFFER_OVERLAP'], 'resources' => ['teacher'], 'range_resources' => ['teacher']]];
    }

    /** @return array{0: \App\Domain\Scheduling\AvailabilityResult, 1: array{state: AvailabilityState, codes: list<string>, resources: list<string>, range_resources: list<string>}} */
    private function lifecycleInterval(Student $student, Teacher $teacher, Instrument $instrument, string $date, int $start, int $duration, string $time, string $room, SessionStatusEnum $status, AvailabilityState $state, array $codes): array
    {
        Room::factory()->named($room)->active()->create();
        $this->directSession($student, $teacher, $instrument, $date, $time, $duration, $room, $status);

        return [$this->domain($this->rules())->evaluate($this->proposal($student, $teacher, $instrument, $date, $this->time($start + max(1, intdiv($duration, 2))), $duration, $room)), ['state' => $state, 'codes' => $codes, 'resources' => [], 'range_resources' => []]];
    }

    /** @return array{0: \App\Domain\Scheduling\AvailabilityResult, 1: array{state: AvailabilityState, codes: list<string>, resources: list<string>, range_resources: list<string>}} */
    private function hardRule(Student $student, Teacher $teacher, Instrument $instrument, string $date, int $duration, string $time, EffectiveSchedulingRules $rules, string $code): array
    {
        return [$this->domain($rules)->evaluate($this->proposal($student, $teacher, $instrument, $date, $time, $duration)), ['state' => AvailabilityState::Invalid, 'codes' => [$code], 'resources' => ['academy_rule'], 'range_resources' => []]];
    }

    /** @return array{0: \App\Domain\Scheduling\AvailabilityResult, 1: array{state: AvailabilityState, codes: list<string>, resources: list<string>, range_resources: list<string>}} */
    private function dailyLimit(Student $student, Teacher $teacher, Instrument $instrument, string $date, int $start, int $duration, string $time): array
    {
        $this->directSession($student, $teacher, $instrument, $date, $time, $duration, 'Rule Fixture');

        return [$this->domain($this->rules(dailyLimit: 1))->evaluate($this->proposal($student, $teacher, $instrument, $date, $this->time($start + $duration + 15), $duration)), ['state' => AvailabilityState::Invalid, 'codes' => ['DAILY_SESSION_LIMIT'], 'resources' => ['academy_rule'], 'range_resources' => []]];
    }

    /** @return array{0: \App\Domain\Scheduling\AvailabilityResult, 1: array{state: AvailabilityState, codes: list<string>, resources: list<string>, range_resources: list<string>}} */
    private function consecutiveLimit(Student $student, Teacher $teacher, Instrument $instrument, string $date, int $start, int $duration, string $time): array
    {
        $this->directSession($student, $teacher, $instrument, $date, $time, $duration, 'Rule Fixture');

        return [$this->domain($this->rules(consecutiveLimit: 1))->evaluate($this->proposal($student, $teacher, $instrument, $date, $this->time($start + $duration), $duration)), ['state' => AvailabilityState::Invalid, 'codes' => ['CONSECUTIVE_SESSION_LIMIT'], 'resources' => ['academy_rule'], 'range_resources' => []]];
    }

    /** @return array{0: \App\Domain\Scheduling\AvailabilityResult, 1: array{state: AvailabilityState, codes: list<string>, resources: list<string>, range_resources: list<string>}} */
    private function missingRoom(Student $student, Teacher $teacher, Instrument $instrument, string $date, int $duration, string $time, string $room): array
    {
        $rules = $this->rules(roomRequirements: [(string) $instrument->id => [$room]]);

        return [$this->domain($rules)->evaluate($this->proposal($student, $teacher, $instrument, $date, $time, $duration)), ['state' => AvailabilityState::Invalid, 'codes' => ['ROOM_REQUIRED'], 'resources' => ['room'], 'range_resources' => []]];
    }

    /** @return array{0: \App\Domain\Scheduling\AvailabilityResult, 1: array{state: AvailabilityState, codes: list<string>, resources: list<string>, range_resources: list<string>}} */
    private function unavailableRoom(Student $student, Teacher $teacher, Instrument $instrument, string $date, int $duration, string $time, string $room): array
    {
        Room::factory()->named($room)->inactive()->create();
        $rules = $this->rules(roomRequirements: [(string) $instrument->id => [$room]]);

        return [$this->domain($rules)->evaluate($this->proposal($student, $teacher, $instrument, $date, $time, $duration, $room)), ['state' => AvailabilityState::Invalid, 'codes' => ['ROOM_UNAVAILABLE'], 'resources' => ['room'], 'range_resources' => []]];
    }

    /** @return array{0: \App\Domain\Scheduling\AvailabilityResult, 1: array{state: AvailabilityState, codes: list<string>, resources: list<string>, range_resources: list<string>}} */
    private function incompatibleRoom(Student $student, Teacher $teacher, Instrument $instrument, string $date, int $duration, string $time, string $room): array
    {
        Room::factory()->named($room)->active()->create();
        $rules = $this->rules(roomRequirements: [(string) $instrument->id => ['Required '.$room]]);

        return [$this->domain($rules)->evaluate($this->proposal($student, $teacher, $instrument, $date, $time, $duration, $room)), ['state' => AvailabilityState::Invalid, 'codes' => ['ROOM_INCOMPATIBLE'], 'resources' => ['room'], 'range_resources' => []]];
    }

    private function directSession(Student $student, Teacher $teacher, Instrument $instrument, string $date, string $time, int $duration, ?string $room, SessionStatusEnum $status = SessionStatusEnum::Scheduled): void
    {
        ClassSession::factory()->direct(['student_id' => $student->id, 'teacher_id' => $teacher->id, 'instrument_id' => $instrument->id])->create(['session_date' => $date, 'start_time' => $time, 'duration_minutes' => $duration, 'room' => $room, 'status' => $status]);
    }

    private function proposal(Student $student, Teacher $teacher, Instrument $instrument, string $date, string $time, int $duration, ?string $room = null, ?StudentEnrollment $enrollment = null): ScheduleProposal
    {
        return new ScheduleProposal(null, null, new RelationPath($enrollment === null ? RelationPathType::Direct : RelationPathType::Enrollment, $enrollment?->id, $student->id, $teacher->id, $instrument->id), TimeRange::fromLocal($date, $time, $duration, new DateTimeZone('Asia/Tehran')), $room, SessionStatusEnum::Scheduled, null, ProposalSource::Form);
    }

    /** @return array{Student, Teacher, Instrument} */
    private function resources(): array
    {
        return [Student::factory()->create(), Teacher::factory()->create(), Instrument::factory()->create()];
    }

    /** @param array<string, list<string>> $roomRequirements */
    private function rules(int $openingMinute = 8 * 60, int $dailyLimit = 20, int $consecutiveLimit = 20, ?array $lunch = null, int $bufferAfter = 0, array $roomRequirements = []): EffectiveSchedulingRules
    {
        return new EffectiveSchedulingRules('property-v1', 'property', new DateTimeZone('Asia/Tehran'), [1, 2, 3, 4, 5, 6, 7], $openingMinute, 24 * 60, 15, 180, $dailyLimit, $consecutiveLimit, $lunch, 0, $bufferAfter, $roomRequirements);
    }

    private function domain(EffectiveSchedulingRules $rules): SchedulingDomain
    {
        $facts = new ConflictFactsProvider(new ConflictDetectionService);

        return new SchedulingDomain(new ScheduleProposalNormalizer(new RelationPathResolver), new AvailabilityEvaluator($facts, new ConflictClassifier, new AcademyRulesProvider($rules), new RoomSuitabilityService(new RoomResolver, new RoomOptionProvider, $facts)));
    }

    private function time(int $minute): string
    {
        return sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
    }
}
