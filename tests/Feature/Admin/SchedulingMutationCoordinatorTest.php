<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Scheduling\AcademyRulesProvider;
use App\Domain\Scheduling\AvailabilityEvaluator;
use App\Domain\Scheduling\ConflictClassifier;
use App\Domain\Scheduling\ConflictFactsProvider;
use App\Domain\Scheduling\EffectiveSchedulingRules;
use App\Domain\Scheduling\LegacySessionVersionAdapter;
use App\Domain\Scheduling\ProposalSource;
use App\Domain\Scheduling\RelationPath;
use App\Domain\Scheduling\RelationPathType;
use App\Domain\Scheduling\ResourceVersionManager;
use App\Domain\Scheduling\RoomSuitabilityService;
use App\Domain\Scheduling\ScheduleProposal;
use App\Domain\Scheduling\SchedulingAuthorization;
use App\Domain\Scheduling\SchedulingDecisionCode;
use App\Domain\Scheduling\SchedulingLockManager;
use App\Domain\Scheduling\SchedulingMutationCoordinator;
use App\Domain\Scheduling\SchedulingMutationException;
use App\Domain\Scheduling\SessionAuditWriter;
use App\Domain\Scheduling\SessionVersion;
use App\Domain\Scheduling\SessionVersionManager;
use App\Domain\Scheduling\TimeRange;
use App\Enums\RoleEnum;
use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Room;
use App\Models\SessionAuditRecord;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ConflictDetectionService;
use App\Services\RoomOptionProvider;
use App\Services\RoomResolver;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use RuntimeException;
use Tests\TestCase;

/** Focused database coverage for Task 4.1's sole mutation transaction boundary. */
final class SchedulingMutationCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_mutation_commits_one_opaque_versioned_audit_and_resource_versions(): void
    {
        [$actor, $session, $proposal] = $this->mutationFixture('11:00', 'Updated note');

        $result = $this->coordinator()->mutate($actor, $proposal);

        $this->assertSame('sv1_', substr($result->version->value, 0, 4));
        $this->assertSame($result->version->value, $session->refresh()->session_version);
        $this->assertSame('11:00:00', $session->start_time->format('H:i:s'));
        $this->assertSame('Updated note', $session->notes);
        $this->assertCount(5, $result->resourceVersions);
        $this->assertDatabaseCount('audit_records', 1);
        $this->assertDatabaseCount('scheduling_resource_versions', 5);

        $audit = SessionAuditRecord::query()->findOrFail($result->audit->id);
        $this->assertSame($session->id, $audit->metadata['session_id']);
        $this->assertSame($result->version->value, $audit->metadata['resulting_version']);
        $this->assertSame(['start_time', 'notes'], $audit->metadata['changed_fields']);

        $this->expectException(LogicException::class);
        $audit->update(['action' => 'changed']);
    }

    public function test_stale_authorization_and_hard_conflict_rejections_leave_no_writes(): void
    {
        [$actor, $session, $proposal] = $this->mutationFixture('11:00', 'Unchanged');
        $stale = new ScheduleProposal($session->id, new SessionVersion('sv1_stale'), $proposal->relationPath, $proposal->timeRange, $proposal->room, $proposal->status, $proposal->notes, $proposal->source);
        $this->assertRejectedWithoutWrites($actor, $session, $stale, SchedulingDecisionCode::StaleVersion);

        [$actor, $session, $proposal] = $this->mutationFixture('11:00', 'Unchanged');
        $this->assertRejectedWithoutWrites(User::factory()->create(['role' => RoleEnum::STUDENT]), $session, $proposal, null);

        [$actor, $session, $proposal] = $this->mutationFixture('10:30', 'Unchanged');
        ClassSession::factory()->direct([
            'student_id' => $session->student_id,
            'teacher_id' => $session->teacher_id,
            'instrument_id' => $session->instrument_id,
        ])->create(['session_date' => '2026-08-04', 'start_time' => '10:00', 'duration_minutes' => 60, 'room' => $session->room]);
        $this->assertRejectedWithoutWrites($actor, $session, $proposal, SchedulingDecisionCode::Conflict);
    }

    public function test_audit_and_availability_infrastructure_failures_rollback_every_write(): void
    {
        foreach (['audit', 'cache'] as $failure) {
            [$actor, $session, $proposal] = $this->mutationFixture('11:00', 'Unchanged');
            $coordinator = $failure === 'audit'
                ? $this->coordinator(writer: new class extends SessionAuditWriter {
                    protected function store(array $attributes): SessionAuditRecord { throw new RuntimeException('audit unavailable'); }
                })
                : $this->coordinator(invalidator: static function (): void { throw new RuntimeException('cache unavailable'); });

            try {
                $coordinator->mutate($actor, $proposal);
                $this->fail("{$failure} failure must abort the transaction.");
            } catch (RuntimeException) {
                $this->assertSame('Original note', $session->refresh()->notes);
                $this->assertNull($session->session_version);
                $this->assertDatabaseCount('audit_records', 0);
                $this->assertDatabaseCount('scheduling_resource_versions', 0);
            }
        }
    }

    private function assertRejectedWithoutWrites(User $actor, ClassSession $session, ScheduleProposal $proposal, ?SchedulingDecisionCode $code): void
    {
        try {
            $this->coordinator()->mutate($actor, $proposal);
            $this->fail('The mutation must be rejected.');
        } catch (SchedulingMutationException $exception) {
            if ($code !== null) {
                $this->assertSame($code, $exception->decisionCode);
            }
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            $this->assertNull($code);
        }

        $this->assertSame('Original note', $session->refresh()->notes);
        $this->assertNull($session->session_version);
        $this->assertDatabaseCount('audit_records', 0);
        $this->assertDatabaseCount('scheduling_resource_versions', 0);
    }

    /** @return array{User, ClassSession, ScheduleProposal} */
    private function mutationFixture(string $time, string $notes): array
    {
        $actor = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $student = Student::factory()->create();
        $teacher = Teacher::factory()->create();
        $instrument = Instrument::factory()->create();
        $room = 'Studio '.(Room::query()->count() + 1);
        Room::factory()->named($room)->active()->create();
        $session = ClassSession::factory()->direct([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
        ])->create([
            'session_date' => '2026-08-04',
            'start_time' => '10:00',
            'duration_minutes' => 60,
            'status' => SessionStatusEnum::Scheduled,
            'room' => $room,
            'notes' => 'Original note',
        ]);

        return [$actor, $session, new ScheduleProposal(
            $session->id,
            new SessionVersion($session->updated_at->toISOString()),
            new RelationPath(RelationPathType::Direct, null, $student->id, $teacher->id, $instrument->id),
            TimeRange::fromLocal('2026-08-04', $time, 60, new DateTimeZone('Asia/Tehran')),
            $room,
            SessionStatusEnum::Scheduled,
            $notes,
            ProposalSource::Form,
        )];
    }

    private function coordinator(?SessionAuditWriter $writer = null, ?\Closure $invalidator = null): SchedulingMutationCoordinator
    {
        $facts = new ConflictFactsProvider(new ConflictDetectionService);
        $rules = new EffectiveSchedulingRules('coordinator-v1', 'test', new DateTimeZone('Asia/Tehran'), [1, 2, 3, 4, 5, 6, 7], 0, 1440, 30, 120, 8, 4, null, 0, 0);

        return new SchedulingMutationCoordinator(
            new SchedulingAuthorization,
            new AvailabilityEvaluator($facts, new ConflictClassifier, new AcademyRulesProvider($rules), new RoomSuitabilityService(new RoomResolver, new RoomOptionProvider, $facts)),
            new SchedulingLockManager,
            new SessionVersionManager(new LegacySessionVersionAdapter),
            new ResourceVersionManager,
            $writer ?? new SessionAuditWriter,
            $invalidator,
        );
    }
}
