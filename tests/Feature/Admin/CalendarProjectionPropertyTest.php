<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Http\Resources\CalendarEventResource;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Support\AdminBulkFixtures;
use Tests\Support\DeterministicSchedulingCases;
use Tests\TestCase;
use Throwable;

/**
 * Property 15: persisted calendar projection count-and-ID preservation.
 *
 * The PHP integration boundary verifies persisted source -> query -> resource
 * -> named endpoint -> the existing reject-only normalization contract. The
 * FullCalendar card renderer is JavaScript-only; its independently executable
 * property remains in tests/js/properties/calendar-persisted-session-projection.property.test.js.
 *
 * **Validates: Requirements 13.4, 13.9, 15.7, 17.1, 17.2, 17.3, 17.7, 21.5**
 */
final class CalendarProjectionPropertyTest extends TestCase
{
    private User $admin;

    /** @var array<int, Student> */
    private array $students = [];

    /** @var array<int, Teacher> */
    private array $teachers = [];

    /** @var array<int, StudentEnrollment> */
    private array $enrollments = [];

    /** @var array<int, string> */
    private array $rooms = [];

    /** @var array<int, array{id: int, date: string, start: string, teacher_id: int, student_id: int, room: string}> */
    private array $sourceRecords = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        for ($index = 0; $index < 3; $index++) {
            $this->students[$index] = AdminBulkFixtures::student();
            $this->teachers[$index] = AdminBulkFixtures::teacher();
            $instrument = Instrument::factory()->create();
            $this->enrollments[$index] = AdminBulkFixtures::enrollment(
                $this->students[$index],
                $this->teachers[$index],
                $instrument,
            );
            $this->rooms[$index] = 'Projection Room '.$index;
            AdminBulkFixtures::activeRoom($this->rooms[$index]);
        }

        for ($index = 0; $index < 60; $index++) {
            $resourceIndex = $index % 3;
            $date = (new \DateTimeImmutable('2026-08-01'))
                ->modify('+'.intdiv($index, 2).' days')
                ->format('Y-m-d');
            $start = $index % 2 === 0 ? '09:00:00' : '10:00:00';
            $attributes = [
                'session_date' => $date,
                'start_time' => $start,
                'duration_minutes' => 30,
                'room' => $this->rooms[$resourceIndex],
            ];

            $session = $index % 2 === 0
                ? AdminBulkFixtures::enrollmentSession($this->enrollments[$resourceIndex], $attributes)
                : AdminBulkFixtures::directSession(
                    $this->students[$resourceIndex],
                    $this->teachers[$resourceIndex],
                    $this->enrollments[$resourceIndex]->instrument,
                    $attributes,
                );

            $this->sourceRecords[] = [
                'id' => (int) $session->getKey(),
                'date' => $date,
                'start' => $start,
                'teacher_id' => $this->teachers[$resourceIndex]->id,
                'student_id' => $this->students[$resourceIndex]->id,
                'room' => $this->rooms[$resourceIndex],
            ];
        }
    }

    public function test_generated_persisted_sessions_preserve_count_and_ids_through_the_calendar_projection(): void
    {
        $cases = DeterministicSchedulingCases::calendarProjection();
        $this->assertCount(DeterministicSchedulingCases::MINIMUM_CASES, $cases);
        $initialCount = ClassSession::count();

        foreach ($cases as $case) {
            $this->assertProjectionCase($case, $initialCount);
        }
    }

    /**
     * @param array{seed: int, case: int, family: string, start: string, end: string, teacher_index: int|null, student_index: int|null, room_index: int|null} $case
     */
    private function assertProjectionCase(array $case, int $initialCount): void
    {
        $filters = [
            'start' => $case['start'],
            'end' => $case['end'],
            'teacher_id' => $case['teacher_index'] === null ? null : $this->teachers[$case['teacher_index']]->id,
            'student_id' => $case['student_index'] === null ? null : $this->students[$case['student_index']]->id,
            'room' => $case['room_index'] === null ? null : ' '.$this->rooms[$case['room_index']].' ',
        ];
        $expectedRecords = array_values(array_filter(
            $this->sourceRecords,
            static fn (array $record): bool => $record['date'] >= $case['start']
                && $record['date'] <= $case['end']
                && ($filters['teacher_id'] === null || $record['teacher_id'] === $filters['teacher_id'])
                && ($filters['student_id'] === null || $record['student_id'] === $filters['student_id'])
                && ($filters['room'] === null || $record['room'] === trim($filters['room'])),
        ));
        $expectedIds = array_map(static fn (array $record): int => $record['id'], $expectedRecords);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $queryEvents = app(\App\Services\CalendarQueryService::class)->get($filters);
            $resourceEvents = CalendarEventResource::collection($queryEvents)
                ->resolve($this->resourceRequest($filters));
            $endpointEvents = $this->eventsFrom(
                $this->actingAs($this->admin)
                    ->getJson(route('admin.calendar.events', $filters))
                    ->assertOk(),
            );
            $normalizedEvents = $this->normalizeCalendarEventCollection($endpointEvents);

            $this->assertBoundary($case, 'CalendarQueryService', $expectedIds, $this->orderedIds($queryEvents->all()));
            $this->assertBoundary($case, 'CalendarEventResource', $expectedIds, $this->orderedIds($resourceEvents));
            $this->assertBoundary($case, 'named admin.calendar.events JSON', $expectedIds, $this->orderedIds($endpointEvents));
            $this->assertBoundary($case, 'existing normalization', $expectedIds, $this->orderedIds($normalizedEvents));
            $this->assertCardRepresentation($case, $normalizedEvents);
            $this->assertSame(
                $initialCount,
                ClassSession::count(),
                DeterministicSchedulingCases::firstFailure(
                    "Property 15 feed writes; seed={$case['seed']}; case={$case['case']}",
                    ['class_sessions' => $initialCount],
                    ['class_sessions' => ClassSession::count()],
                ),
            );

            $writes = array_values(array_filter(
                DB::getQueryLog(),
                static fn (array $query): bool => preg_match('/^\s*(insert|update|delete|replace|truncate)\b/i', $query['query']) === 1
                    && str_contains(strtolower($query['query']), 'class_sessions'),
            ));
            $this->assertSame([], $writes, DeterministicSchedulingCases::firstFailure(
                "Property 15 feed writes; seed={$case['seed']}; case={$case['case']}",
                [],
                $writes,
            ));
        } catch (Throwable $exception) {
            $this->fail(DeterministicSchedulingCases::firstFailure(
                "Property 15 exception; seed={$case['seed']}; case={$case['case']}",
                ['filters' => $filters, 'expected_ids' => $expectedIds],
                ['exception' => $exception::class, 'message' => $exception->getMessage()],
            ));
        }
    }

    /** @param array{seed: int, case: int} $case */
    private function assertBoundary(array $case, string $boundary, array $expected, array $observed): void
    {
        $this->assertSame(
            $expected,
            $observed,
            DeterministicSchedulingCases::firstFailure(
                "Property 15 {$boundary}; seed={$case['seed']}; case={$case['case']}",
                $expected,
                $observed,
            ),
        );
    }

    /** @param array<int, array<string, mixed>> $events */
    private function assertCardRepresentation(array $case, array $events): void
    {
        foreach ($events as $index => $event) {
            foreach (['id', 'start', 'end', 'status', 'studentName', 'teacherName', 'instrumentName', 'room'] as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $event,
                    "Property 15 card representation; seed={$case['seed']}; case={$case['case']}; event={$index}; missing={$field}",
                );
            }
        }
    }

    /** @param array<string, mixed> $filters */
    private function resourceRequest(array $filters): \Illuminate\Http\Request
    {
        $request = \Illuminate\Http\Request::create(route('admin.calendar.events', $filters), 'GET', $filters);
        $request->setUserResolver(fn (): User => $this->admin);

        return $request;
    }

    /** @param array<int, object|array<string, mixed>> $records @return array<int, int> */
    private function orderedIds(array $records): array
    {
        return array_map(
            static fn (object|array $record): int => (int) (is_array($record) ? $record['id'] : $record->getKey()),
            $records,
        );
    }

    /** @param array<int, array<string, mixed>> $events @return array<int, array<string, mixed>> */
    private function normalizeCalendarEventCollection(array $events): array
    {
        return array_values(array_filter($events, static function (array $event): bool {
            return array_key_exists('id', $event)
                && isset($event['start'], $event['end'], $event['status'])
                && $event['start'] < $event['end']
                && in_array($event['status'], ['scheduled', 'completed', 'cancelled', 'missed'], true);
        }));
    }

    /** @return array<int, array<string, mixed>> */
    private function eventsFrom(TestResponse $response): array
    {
        return $response->json('data') ?? [];
    }
}
