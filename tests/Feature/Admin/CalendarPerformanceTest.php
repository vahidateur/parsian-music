<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Performance coverage for the calendar events endpoint.
 *
 * Requirement 12.2 states a normal-load response target of 200ms for up to 50
 * sessions. Wall-clock numbers alone are machine dependent, so the target is
 * measured with warm-up requests plus a median sample, and it is backed by two
 * machine-independent guarantees: the marginal cost over an empty range stays
 * small, and the query count stays flat as the session count grows.
 */
class CalendarPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const SESSION_DATE = '2025-07-14';

    /** A date inside the allowed range that never holds a session. */
    private const EMPTY_DATE = '2025-07-20';

    private const SESSION_COUNT = 50;

    private const TARGET_MS = 200;

    private const WARM_UP_REQUESTS = 2;

    private const SAMPLE_REQUESTS = 5;

    private User $admin;

    private StudentEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $student = Student::forceCreate([
            'student_code' => 'CAL-PERF-001',
            'full_name' => 'Calendar Performance Student',
            'phone' => '09120000003',
            'status' => 'active',
            'join_date' => self::SESSION_DATE,
        ]);
        $teacher = Teacher::factory()->create(['full_name' => 'Calendar Performance Teacher']);
        $instrument = Instrument::factory()->create([
            'name' => 'Piano',
            'name_fa' => 'پیانو',
            'slug' => 'calendar-performance-piano',
        ]);

        $this->enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
            'skill_level' => 'beginner',
            'status' => 'active',
            'started_at' => self::SESSION_DATE,
        ]);
    }

    /**
     * **Validates: Requirements 12.2**
     */
    public function test_events_endpoint_serves_fifty_sessions_within_the_normal_load_target(): void
    {
        $this->createSessions(self::SESSION_COUNT);

        $this->assertCount(self::SESSION_COUNT, $this->eventsFrom($this->eventsResponse()));

        $loadedMs = $this->medianDurationMs(self::SESSION_DATE, self::SESSION_COUNT);

        $this->assertLessThan(
            self::TARGET_MS,
            $loadedMs,
            sprintf(
                'Calendar events endpoint median was %.2fms for %d sessions (target %dms).',
                $loadedMs,
                self::SESSION_COUNT,
                self::TARGET_MS,
            ),
        );
    }

    /**
     * The absolute median above depends on the host; this keeps the assertion
     * meaningful by isolating the work the endpoint adds for 50 sessions from
     * the framework/HTTP overhead shared with an empty response.
     *
     * **Validates: Requirements 12.2**
     */
    public function test_serving_fifty_sessions_costs_only_a_fraction_of_the_response_target(): void
    {
        $this->createSessions(self::SESSION_COUNT);

        $this->assertCount(0, $this->eventsFrom($this->eventsResponse(self::EMPTY_DATE)));

        $emptyMs = $this->medianDurationMs(self::EMPTY_DATE, 0);
        $loadedMs = $this->medianDurationMs(self::SESSION_DATE, self::SESSION_COUNT);
        $marginalMs = $loadedMs - $emptyMs;

        $this->assertLessThan(
            self::TARGET_MS / 2,
            $marginalMs,
            sprintf(
                'Serving %d sessions added %.2fms over an empty range (%.2fms vs %.2fms).',
                self::SESSION_COUNT,
                $marginalMs,
                $loadedMs,
                $emptyMs,
            ),
        );
    }

    /**
     * A flat query count is what keeps the 200ms target reachable on any host:
     * the eager-loaded relations must not grow with the number of sessions.
     *
     * **Validates: Requirements 12.2**
     */
    public function test_events_endpoint_query_count_stays_flat_as_sessions_grow(): void
    {
        $this->createSessions(1);
        $singleSessionQueries = $this->countQueriesForEventsRequest(1);

        $this->createSessions(self::SESSION_COUNT - 1, 1);
        $fiftySessionQueries = $this->countQueriesForEventsRequest(self::SESSION_COUNT);

        $this->assertSame(
            $singleSessionQueries,
            $fiftySessionQueries,
            sprintf(
                'Query count grew from %d (1 session) to %d (%d sessions), which means relations are not eager loaded.',
                $singleSessionQueries,
                $fiftySessionQueries,
                self::SESSION_COUNT,
            ),
        );
        $this->assertLessThanOrEqual(
            12,
            $fiftySessionQueries,
            sprintf('Calendar events endpoint executed %d queries for %d sessions.', $fiftySessionQueries, self::SESSION_COUNT),
        );
    }

    private function createSessions(int $count, int $offset = 0): void
    {
        foreach (range($offset, $offset + $count - 1) as $index) {
            ClassSession::create([
                'enrollment_id' => $this->enrollment->id,
                'student_id' => null,
                'teacher_id' => null,
                'instrument_id' => null,
                'session_date' => self::SESSION_DATE,
                'start_time' => sprintf('%02d:%02d:00', 8 + intdiv($index, 4), ($index % 4) * 15),
                'duration_minutes' => 30,
                'status' => SessionStatusEnum::Scheduled,
                'room' => 'A101',
                'session_fee' => 500000,
                'notes' => null,
            ]);
        }
    }

    /**
     * Warm-up requests remove framework boot, container resolution, and schema
     * noise so the sampled median reflects steady-state endpoint work only.
     */
    private function medianDurationMs(string $date, int $expectedCount): float
    {
        foreach (range(1, self::WARM_UP_REQUESTS) as $ignored) {
            $this->eventsResponse($date);
        }

        $durations = [];
        foreach (range(1, self::SAMPLE_REQUESTS) as $ignored) {
            $startedAt = hrtime(true);
            $response = $this->eventsResponse($date);
            $durations[] = (hrtime(true) - $startedAt) / 1_000_000;

            $this->assertCount($expectedCount, $this->eventsFrom($response));
        }

        sort($durations);

        return $durations[intdiv(count($durations), 2)];
    }

    private function countQueriesForEventsRequest(int $expectedCount): int
    {
        $this->eventsResponse();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->eventsResponse();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount($expectedCount, $this->eventsFrom($response));

        return $queries;
    }

    private function eventsResponse(string $date = self::SESSION_DATE): TestResponse
    {
        return $this->actingAs($this->admin)
            ->getJson(route('admin.calendar.events', [
                'start' => $date,
                'end' => $date,
            ]))
            ->assertOk();
    }

    private function eventsFrom(TestResponse $response): array
    {
        $payload = $response->json();

        return $payload['data'] ?? $payload;
    }
}
