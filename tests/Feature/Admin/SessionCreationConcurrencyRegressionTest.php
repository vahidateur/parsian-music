<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Instrument;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Services\ConflictDetectionService;
use App\Services\RoomResolver;
use App\Services\SessionCreateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SessionCreationConcurrencyRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_session_creation_does_not_persist_a_stale_conflicting_write(): void
    {
        $student = Student::factory()->create();
        $teacher = Teacher::factory()->create();
        $instrument = Instrument::factory()->create();
        Room::create(['name' => 'A101', 'is_active' => true]);
        $subscription = Subscription::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
        ]);
        $payload = [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'A101',
        ];

        $standardService = app(SessionCreateService::class);
        $baselineTransactionLevel = DB::transactionLevel();
        $service = new SessionCreateService(
            new StaleManualSessionConflictDetector(
                $baselineTransactionLevel,
                fn () => $standardService->create($payload),
            ),
            app(RoomResolver::class),
        );

        $service->create($payload);

        $this->assertDatabaseCount('class_sessions', 1);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'sessions_used' => 1]);
    }
}

/** Forces the peer manual create into the original check-to-create gap. */
final class StaleManualSessionConflictDetector extends ConflictDetectionService
{
    public function __construct(
        private readonly int $baselineTransactionLevel,
        private readonly \Closure $afterStaleCheck,
    ) {}

    public function checkStudentOverlap(
        int $studentId,
        string $date,
        mixed $startTime,
        int $duration,
        ?int $ignoreSessionId = null,
    ): bool {
        $hasConflict = parent::checkStudentOverlap($studentId, $date, $startTime, $duration, $ignoreSessionId);

        if (! $hasConflict && DB::transactionLevel() === $this->baselineTransactionLevel) {
            ($this->afterStaleCheck)();
        }

        return $hasConflict;
    }
}
