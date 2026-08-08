<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling;

use App\Domain\Scheduling\AcademyRulesProvider;
use App\Domain\Scheduling\AvailabilityEvaluator;
use App\Domain\Scheduling\AvailabilityResult;
use App\Domain\Scheduling\AvailabilityState;
use App\Domain\Scheduling\ConflictClassifier;
use App\Domain\Scheduling\ConflictFactsProvider;
use App\Domain\Scheduling\EffectiveSchedulingRules;
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
use App\Models\Room;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ConflictDetectionService;
use App\Services\RelationPathResolver;
use App\Services\RoomOptionProvider;
use App\Services\RoomResolver;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\DeterministicSchedulingCases;
use Tests\TestCase;

/**
 * Property 10: Effective rules and room suitability invariant.
 *
 * **Validates: Requirements 9.1-9.7, 9.9, 10.1-10.5**
 */
final class EffectiveRulesRoomSuitabilityPropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_effective_rules_and_room_suitability_hold_for_seeded_generated_cases(): void
    {
        $ruleCases = DeterministicSchedulingCases::rules();
        $roomCases = DeterministicSchedulingCases::rooms();
        $this->assertCount(DeterministicSchedulingCases::MINIMUM_CASES, $ruleCases);
        $this->assertCount(DeterministicSchedulingCases::MINIMUM_CASES, $roomCases);

        foreach ($ruleCases as $index => $ruleCase) {
            $roomCase = $roomCases[$index];
            [$student, $teacher, $instrument] = $this->resources();
            $facts = $this->roomFacts($index, $roomCase);
            $requirements = [(string) $instrument->id => array_values(array_map(
                static fn (array $fact): string => $fact['name'],
                array_filter($facts, static fn (array $fact): bool => $fact['capability'] === $roomCase['required_capability']),
            ))];
            $rules = $this->rules($index, $ruleCase, $requirements);
            $date = $this->dateForWeekday($rules->enabledWeekdays[0]);
            $proposal = $this->proposal($student, $teacher, $instrument, $date, $this->time($rules->openingMinute + 60), $rules->minimumDuration, $facts[0]['name']);
            $expectedRooms = $this->persistAuthorizedRoomFacts($facts, $proposal, $student, $teacher, $instrument);
            $result = $this->domain($rules)->evaluate($proposal);

            $this->assertSame(AvailabilityState::Available, $result->state, $this->diagnostic($ruleCase, 'available-result', AvailabilityState::Available->value, $result->state->value));
            $this->assertSame($rules->version, $result->details['rules']->version, $this->diagnostic($ruleCase, 'rules-version', $rules->version, $result->details['rules']->version));
            $this->assertSame($rules->source, $result->details['rules']->source, $this->diagnostic($ruleCase, 'rules-source', $rules->source, $result->details['rules']->source));
            $this->assertSame(['teacher_before' => $rules->teacherBufferBefore, 'teacher_after' => $rules->teacherBufferAfter], $result->details['buffers'], $this->diagnostic($ruleCase, 'effective-buffers', ['before' => $rules->teacherBufferBefore, 'after' => $rules->teacherBufferAfter], $result->details['buffers']));
            $this->assertSame($expectedRooms, $result->details['eligible_rooms'], $this->diagnostic($roomCase, 'ordered-eligible-rooms', $expectedRooms, $result->details['eligible_rooms']));

            $this->assertNamedConstraint($this->domain($rules)->evaluate($this->proposal($student, $teacher, $instrument, $date, $this->time($rules->openingMinute + 60), $rules->minimumDuration)), 'ROOM_REQUIRED', AvailabilityState::Invalid, $roomCase, 'missing-required-room');
            $this->assertNamedConstraint($this->domain($rules)->evaluate($this->proposal($student, $teacher, $instrument, $date, $this->time($rules->openingMinute + 60), $rules->minimumDuration, $facts[2]['name'])), 'ROOM_UNAVAILABLE', AvailabilityState::Invalid, $roomCase, 'inactive-room');
            $this->assertNamedConstraint($this->domain($rules)->evaluate($this->proposal($student, $teacher, $instrument, $date, $this->time($rules->openingMinute + 60), $rules->minimumDuration, $facts[3]['name'])), 'ROOM_INCOMPATIBLE', AvailabilityState::Invalid, $roomCase, 'incompatible-room');
            $this->assertNamedConstraint($this->domain($rules)->evaluate($this->proposal($student, $teacher, $instrument, $date, $this->time($rules->openingMinute + 60), $rules->minimumDuration, $facts[4]['name'])), 'ROOM_OVERLAP', AvailabilityState::Conflict, $roomCase, 'occupied-room');

            $this->assertContradictoryRulesAreRejected($ruleCase, $requirements, $index);
            $this->assertGeneratedRuleViolation($index, $ruleCase, $rules, $student, $teacher, $instrument, $facts[0]['name'], $date);
        }
    }

    /** @param array<string, mixed> $roomCase @return list<array{name:string,authorized:bool,active:bool,capability:string,occupied:bool}> */
    private function roomFacts(int $index, array $roomCase): array
    {
        $required = (string) $roomCase['required_capability'];
        $capabilities = ['piano', 'violin', 'daf'];
        $other = $capabilities[(array_search($required, $capabilities, true) + 1) % count($capabilities)];

        return [
            ['name' => "A{$index}", 'authorized' => true, 'active' => true, 'capability' => $required, 'occupied' => false],
            ['name' => "B{$index}", 'authorized' => (bool) $roomCase['authorized'], 'active' => (bool) $roomCase['active'], 'capability' => (string) $roomCase['capabilities'][0], 'occupied' => (bool) $roomCase['occupied']],
            ['name' => "I{$index}", 'authorized' => true, 'active' => false, 'capability' => $required, 'occupied' => false],
            ['name' => "X{$index}", 'authorized' => true, 'active' => true, 'capability' => $other, 'occupied' => false],
            ['name' => "O{$index}", 'authorized' => true, 'active' => true, 'capability' => $required, 'occupied' => true],
            ['name' => "U{$index}", 'authorized' => false, 'active' => true, 'capability' => $required, 'occupied' => false],
        ];
    }

    /** @param list<array{name:string,authorized:bool,active:bool,capability:string,occupied:bool}> $facts @return list<array{id:int|string,name:string}> */
    private function persistAuthorizedRoomFacts(array $facts, ScheduleProposal $proposal, Student $student, Teacher $teacher, Instrument $instrument): array
    {
        $eligible = [];
        foreach ($facts as $fact) {
            if (! $fact['authorized']) {
                continue;
            }

            $room = Room::factory()->named($fact['name'])->state(['is_active' => $fact['active']])->create();
            if ($fact['active'] && $fact['capability'] === $facts[0]['capability'] && ! $fact['occupied']) {
                $eligible[] = ['id' => $room->id, 'name' => $room->name];
            }
            if ($fact['occupied']) {
                ClassSession::factory()->direct([
                    'student_id' => Student::factory()->create()->id,
                    'teacher_id' => Teacher::factory()->create()->id,
                    'instrument_id' => Instrument::factory()->create()->id,
                ])->create([
                    'session_date' => $proposal->timeRange->start->format('Y-m-d'),
                    'start_time' => $proposal->timeRange->start->format('H:i'),
                    'duration_minutes' => $proposal->timeRange->durationMinutes(),
                    'room' => $room->name,
                ]);
            }
        }

        usort($eligible, static fn (array $left, array $right): int => $left['name'] <=> $right['name']);

        return $eligible;
    }

    /** @param array<string, mixed> $ruleCase @param array<string, list<string>> $requirements */
    private function rules(int $index, array $ruleCase, array $requirements, ?int $bufferAfter = null): EffectiveSchedulingRules
    {
        $opening = (int) $ruleCase['opening_minute'];

        return new EffectiveSchedulingRules(
            "property-10-{$index}",
            'property-test',
            new DateTimeZone('Asia/Tehran'),
            $ruleCase['enabled_weekdays'],
            $opening,
            (int) $ruleCase['closing_minute'],
            (int) $ruleCase['minimum_duration'],
            (int) $ruleCase['maximum_duration'],
            (int) $ruleCase['daily_limit'],
            (int) $ruleCase['consecutive_limit'],
            ['start' => $opening + 240, 'end' => $opening + 270],
            (int) $ruleCase['buffer_before'],
            $bufferAfter ?? (int) $ruleCase['buffer_after'],
            $requirements,
        );
    }

    /** @param array<string, mixed> $ruleCase @param array<string, list<string>> $requirements */
    private function assertContradictoryRulesAreRejected(array $ruleCase, array $requirements, int $index): void
    {
        try {
            new EffectiveSchedulingRules("contradictory-{$index}", 'property-test', new DateTimeZone('Asia/Tehran'), $ruleCase['enabled_weekdays'], (int) $ruleCase['opening_minute'], (int) $ruleCase['opening_minute'], (int) $ruleCase['minimum_duration'], (int) $ruleCase['maximum_duration'], (int) $ruleCase['daily_limit'], (int) $ruleCase['consecutive_limit'], null, (int) $ruleCase['buffer_before'], (int) $ruleCase['buffer_after'], $requirements);
            $this->fail($this->diagnostic($ruleCase, 'contradictory-rules', 'InvalidArgumentException', 'constructed'));
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }
    }

    /** @param array<string, mixed> $ruleCase */
    private function assertGeneratedRuleViolation(int $index, array $ruleCase, EffectiveSchedulingRules $rules, Student $student, Teacher $teacher, Instrument $instrument, string $room, string $date): void
    {
        $scenario = $index % 7;
        $duration = $rules->minimumDuration;
        $time = $this->time($rules->openingMinute + 60);
        $expectedCode = match ($scenario) {
            0 => 'DISABLED_WEEKDAY', 1 => 'OUTSIDE_WORKING_HOURS', 2 => 'INVALID_DURATION', 3 => 'LUNCH_OVERLAP',
            4 => 'DAILY_SESSION_LIMIT', 5 => 'CONSECUTIVE_SESSION_LIMIT', default => 'TEACHER_BUFFER_OVERLAP',
        };
        $violationRules = $scenario === 6 ? $this->rules($index, $ruleCase, $rules->roomRequirements, 15) : $rules;

        if ($scenario === 0) {
            $date = $this->dateForWeekday(array_values(array_diff([1, 2, 3, 4, 5, 6, 7], $rules->enabledWeekdays))[0]);
        } elseif ($scenario === 1) {
            $time = $this->time($rules->openingMinute - 1);
        } elseif ($scenario === 2) {
            $duration = $rules->minimumDuration - 1;
        } elseif ($scenario === 3) {
            $time = $this->time($rules->lunch['start']);
        } elseif ($scenario === 4) {
            $this->createTeacherSessions($rules->dailySessionLimit, $student, $teacher, $instrument, $date, $this->time($rules->openingMinute + 120), $rules->minimumDuration);
        } elseif ($scenario === 5) {
            for ($position = 0; $position < $rules->consecutiveSessionLimit; $position++) {
                $this->createTeacherSessions(1, $student, $teacher, $instrument, $date, $this->time($rules->openingMinute + ($rules->minimumDuration * $position)), $rules->minimumDuration);
            }
            $time = $this->time($rules->openingMinute + ($rules->minimumDuration * $rules->consecutiveSessionLimit));
        } else {
            $this->createTeacherSessions(1, $student, $teacher, $instrument, $date, $this->time($rules->openingMinute), 60);
        }

        $result = $this->domain($violationRules)->evaluate($this->proposal($student, $teacher, $instrument, $date, $time, $duration, $room));
        $this->assertNamedConstraint($result, $expectedCode, $scenario === 6 ? null : AvailabilityState::Invalid, $ruleCase, "rule-{$expectedCode}");
    }

    private function createTeacherSessions(int $count, Student $student, Teacher $teacher, Instrument $instrument, string $date, string $time, int $duration): void
    {
        for ($number = 0; $number < $count; $number++) {
            ClassSession::factory()->direct(['student_id' => $student->id, 'teacher_id' => $teacher->id, 'instrument_id' => $instrument->id])->create(['session_date' => $date, 'start_time' => $time, 'duration_minutes' => $duration, 'room' => 'Busy-'.$teacher->id]);
        }
    }

    /** @param array<string, mixed> $case */
    private function assertNamedConstraint(AvailabilityResult $result, string $code, ?AvailabilityState $state, array $case, string $boundary): void
    {
        $codes = array_map(static fn ($conflict): string => $conflict->code, $result->details['conflicts']->conflicts);
        if ($state !== null) {
            $this->assertSame($state, $result->state, $this->diagnostic($case, $boundary.'-state', $state->value, $result->state->value));
        }
        $this->assertContains($code, $codes, $this->diagnostic($case, $boundary.'-code', $code, $codes));
    }

    /** @return array{Student, Teacher, Instrument} */
    private function resources(): array { return [Student::factory()->create(), Teacher::factory()->create(), Instrument::factory()->create()]; }

    private function proposal(Student $student, Teacher $teacher, Instrument $instrument, string $date, string $time, int $duration, ?string $room = null): ScheduleProposal
    {
        return new ScheduleProposal(null, null, new RelationPath(RelationPathType::Direct, null, $student->id, $teacher->id, $instrument->id), TimeRange::fromLocal($date, $time, $duration, new DateTimeZone('Asia/Tehran')), $room, SessionStatusEnum::Scheduled, null, \App\Domain\Scheduling\ProposalSource::Form);
    }

    private function domain(EffectiveSchedulingRules $rules): SchedulingDomain
    {
        $facts = new ConflictFactsProvider(new ConflictDetectionService);

        return new SchedulingDomain(new ScheduleProposalNormalizer(new RelationPathResolver), new AvailabilityEvaluator($facts, new ConflictClassifier, new AcademyRulesProvider($rules), new RoomSuitabilityService(new RoomResolver, new RoomOptionProvider, $facts)));
    }

    private function dateForWeekday(int $weekday): string { return (new DateTimeImmutable('2026-08-03', new DateTimeZone('Asia/Tehran')))->modify('+'.($weekday - 1).' days')->format('Y-m-d'); }
    private function time(int $minute): string { return sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60); }

    /** @param array<string, mixed> $case */
    private function diagnostic(array $case, string $boundary, mixed $expected, mixed $observed): string
    {
        return DeterministicSchedulingCases::firstFailure("property-10/{$boundary}/seed={$case['seed']}/case={$case['case']}", $expected, $observed);
    }
}
