<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling;

use App\Domain\Scheduling\AcademyRulesProvider;
use App\Domain\Scheduling\AvailabilityEvaluator;
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
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Focused Task 2.2 coverage for the sole domain availability decision path. */
final class AvailabilityEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_reports_every_applicable_authorized_overlap_with_ranges_and_hard_classification(): void
    {
        [$student, $teacher, $instrument] = $this->resources();
        Room::factory()->named('Studio')->active()->create();
        ClassSession::factory()->direct(['student_id' => $student->id, 'teacher_id' => $teacher->id, 'instrument_id' => $instrument->id])->create(['session_date' => '2026-08-04', 'start_time' => '10:00', 'duration_minutes' => 60, 'room' => 'Studio']);

        $result = $this->domain($this->rules())->evaluate($this->proposal($student, $teacher, $instrument, '10:30', 'Studio'));
        $conflicts = $result->details['conflicts']->conflicts;

        $this->assertSame(AvailabilityState::Conflict, $result->state);
        $this->assertSame(['room', 'student', 'teacher'], array_values(array_unique(array_map(static fn ($conflict): string => $conflict->resource, $conflicts))));
        foreach ($conflicts as $conflict) { $this->assertSame('hard', $conflict->classification); $this->assertNotNull($conflict->range); }
    }

    public function test_cancelled_intervals_are_non_blocking_while_completed_intervals_remain_blocking(): void
    {
        [$student, $teacher, $instrument] = $this->resources();
        $attributes = ['session_date' => '2026-08-04', 'start_time' => '10:00', 'duration_minutes' => 60, 'room' => 'Other Room'];
        $session = ClassSession::factory()->direct(['student_id' => $student->id, 'teacher_id' => $teacher->id, 'instrument_id' => $instrument->id])->cancelled()->create($attributes);
        $proposal = $this->proposal($student, $teacher, $instrument, '10:30');

        $this->assertSame(AvailabilityState::Available, $this->domain($this->rules())->evaluate($proposal)->state);
        $session->update(['status' => SessionStatusEnum::Completed]);
        $this->assertSame(AvailabilityState::Conflict, $this->domain($this->rules())->evaluate($proposal)->state);
    }

    public function test_effective_buffers_and_required_rooms_are_enforced_by_the_same_domain_result(): void
    {
        [$student, $teacher, $instrument] = $this->resources();
        Room::factory()->named('Studio')->active()->create();
        ClassSession::factory()->direct(['student_id' => $student->id, 'teacher_id' => $teacher->id, 'instrument_id' => $instrument->id])->create(['session_date' => '2026-08-04', 'start_time' => '10:00', 'duration_minutes' => 60, 'room' => 'Other Room']);
        $rules = $this->rules(bufferAfter: 15, roomRequirements: [(string) $instrument->id => ['Studio']]);

        $buffered = $this->domain($rules)->evaluate($this->proposal($student, $teacher, $instrument, '11:00', 'Studio'));
        $missingRoom = $this->domain($rules)->evaluate($this->proposal($student, $teacher, $instrument, '12:00'));

        $this->assertSame(AvailabilityState::Conflict, $buffered->state);
        $this->assertSame(15, $buffered->details['buffers']['teacher_after']);
        $this->assertContains('TEACHER_BUFFER_OVERLAP', array_map(static fn ($conflict): string => $conflict->code, $buffered->details['conflicts']->conflicts));
        $this->assertSame(AvailabilityState::Invalid, $missingRoom->state);
        $this->assertContains('ROOM_REQUIRED', array_map(static fn ($conflict): string => $conflict->code, $missingRoom->details['conflicts']->conflicts));
    }

    /** @return array{Student, Teacher, Instrument} */
    private function resources(): array
    {
        return [Student::factory()->create(), Teacher::factory()->create(), Instrument::factory()->create()];
    }

    private function proposal(Student $student, Teacher $teacher, Instrument $instrument, string $time, ?string $room = null): ScheduleProposal
    {
        return new ScheduleProposal(null, null, new RelationPath(RelationPathType::Direct, null, $student->id, $teacher->id, $instrument->id), TimeRange::fromLocal('2026-08-04', $time, 60, new DateTimeZone('Asia/Tehran')), $room, SessionStatusEnum::Scheduled, null, \App\Domain\Scheduling\ProposalSource::Form);
    }

    /** @param array<string, list<string>> $roomRequirements */
    private function rules(int $bufferAfter = 0, array $roomRequirements = []): EffectiveSchedulingRules
    {
        return new EffectiveSchedulingRules('test-v1', 'test', new DateTimeZone('Asia/Tehran'), [1, 2, 3, 4, 5, 6, 7], 8 * 60, 20 * 60, 30, 120, 8, 4, null, 0, $bufferAfter, $roomRequirements);
    }

    private function domain(EffectiveSchedulingRules $rules): SchedulingDomain
    {
        $facts = new ConflictFactsProvider(new ConflictDetectionService);
        return new SchedulingDomain(new ScheduleProposalNormalizer(new RelationPathResolver), new AvailabilityEvaluator($facts, new ConflictClassifier, new AcademyRulesProvider($rules), new RoomSuitabilityService(new RoomResolver, new RoomOptionProvider, $facts)));
    }
}
