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
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    private const START_DATE = '2025-07-14';

    private const END_DATE = '2025-07-20';

    private User $admin;

    private User $teacherUser;

    private Student $student;

    private Student $secondStudent;

    private Teacher $teacher;

    private Teacher $secondTeacher;

    private Instrument $instrument;

    private Instrument $secondInstrument;

    private StudentEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $this->teacherUser = User::factory()->create(['role' => RoleEnum::TEACHER]);

        $this->student = Student::forceCreate([
            'student_code' => 'CAL-001',
            'full_name' => 'Calendar Student',
            'phone' => '09120000001',
            'status' => 'active',
            'join_date' => self::START_DATE,
        ]);
        $this->secondStudent = Student::forceCreate([
            'student_code' => 'CAL-002',
            'full_name' => 'Second Calendar Student',
            'phone' => '09120000002',
            'status' => 'active',
            'join_date' => self::START_DATE,
        ]);
        $this->teacher = Teacher::factory()->create(['full_name' => 'Calendar Teacher']);
        $this->secondTeacher = Teacher::factory()->create(['full_name' => 'Second Calendar Teacher']);
        $this->instrument = Instrument::factory()->create([
            'name' => 'Piano',
            'name_fa' => 'پیانو',
            'slug' => 'calendar-piano',
        ]);
        $this->secondInstrument = Instrument::factory()->create([
            'name' => 'Violin',
            'name_fa' => 'ویولن',
            'slug' => 'calendar-violin',
        ]);

        $this->enrollment = StudentEnrollment::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->instrument->id,
            'skill_level' => 'beginner',
            'status' => 'active',
            'started_at' => self::START_DATE,
        ]);
    }

    public function test_authenticated_admin_can_open_the_calendar_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.calendar.index'));

        $response->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->getJson($this->eventsUrl())->assertRedirect(route('login'));
    }

    public function test_non_admin_users_receive_forbidden_response(): void
    {
        $this->actingAs($this->teacherUser)
            ->getJson($this->eventsUrl())
            ->assertForbidden();
    }

    public function test_admin_receives_complete_events_for_enrollment_and_direct_sessions(): void
    {
        $enrollmentSession = $this->createSession([
            'start_time' => '09:00:00',
            'duration_minutes' => 30,
            'session_fee' => 500000,
            'notes' => 'Enrollment-backed note',
        ]);
        $directSession = $this->createSession([
            'enrollment_id' => null,
            'student_id' => $this->secondStudent->id,
            'teacher_id' => $this->secondTeacher->id,
            'instrument_id' => $this->secondInstrument->id,
            'start_time' => '10:15:00',
            'duration_minutes' => 45,
            'session_fee' => null,
            'notes' => null,
            'room' => 'B202',
        ]);

        $response = $this->actingAs($this->admin)->getJson($this->eventsUrl());

        $response->assertOk();
        $events = $this->eventsFrom($response);
        $this->assertCount(2, $events);

        $enrollmentEvent = $this->eventById($events, $enrollmentSession->id);
        $this->assertEventShape($enrollmentEvent);
        $this->assertSame('Calendar Student', $enrollmentEvent['studentName']);
        $this->assertSame('Calendar Teacher', $enrollmentEvent['teacherName']);
        $this->assertSame('پیانو', $enrollmentEvent['instrumentName']);
        $this->assertSame('2025-07-14T09:00:00', $enrollmentEvent['start']);
        $this->assertSame('2025-07-14T09:30:00', $enrollmentEvent['end']);
        $this->assertSame($this->enrollment->id, $enrollmentEvent['extendedProps']['enrollment_id']);

        $directEvent = $this->eventById($events, $directSession->id);
        $this->assertEventShape($directEvent);
        $this->assertSame('Second Calendar Student', $directEvent['studentName']);
        $this->assertSame('Second Calendar Teacher', $directEvent['teacherName']);
        $this->assertSame('ویولن', $directEvent['instrumentName']);
        $this->assertSame('2025-07-14T10:15:00', $directEvent['start']);
        $this->assertSame('2025-07-14T11:00:00', $directEvent['end']);
        $this->assertNull($directEvent['extendedProps']['enrollment_id']);
        $this->assertNull($directEvent['extendedProps']['session_fee']);
        $this->assertNull($directEvent['extendedProps']['notes']);
    }

    public function test_events_use_eager_loaded_enrollment_and_direct_relation_paths(): void
    {
        $this->createSession();
        $this->createSession([
            'enrollment_id' => null,
            'student_id' => $this->secondStudent->id,
            'teacher_id' => $this->secondTeacher->id,
            'instrument_id' => $this->secondInstrument->id,
            'room' => 'B202',
        ]);

        $response = $this->actingAs($this->admin)->getJson($this->eventsUrl());

        $response->assertOk();
        $events = $this->eventsFrom($response);
        $this->assertCount(2, $events);
        $this->assertSame(
            ['Calendar Student', 'Calendar Teacher', 'پیانو'],
            [$events[0]['studentName'], $events[0]['teacherName'], $events[0]['instrumentName']],
        );
        $this->assertSame(
            ['Second Calendar Student', 'Second Calendar Teacher', 'ویولن'],
            [$events[1]['studentName'], $events[1]['teacherName'], $events[1]['instrumentName']],
        );
    }

    public function test_teacher_student_room_and_instrument_filters_scope_events(): void
    {
        $firstSession = $this->createSession(['room' => 'A101']);
        $secondEnrollment = StudentEnrollment::create([
            'student_id' => $this->secondStudent->id,
            'teacher_id' => $this->secondTeacher->id,
            'instrument_id' => $this->secondInstrument->id,
            'skill_level' => 'intermediate',
            'status' => 'active',
            'started_at' => self::START_DATE,
        ]);
        $secondSession = $this->createSession([
            'enrollment_id' => $secondEnrollment->id,
            'room' => 'B202',
            'start_time' => '10:00:00',
        ]);
        $directSession = $this->createSession([
            'enrollment_id' => null,
            'student_id' => $this->secondStudent->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->secondInstrument->id,
            'room' => 'C303',
            'start_time' => '11:00:00',
        ]);

        $this->assertEventIdsForFilter(['teacher_id' => $this->teacher->id], [$firstSession->id, $directSession->id]);
        $this->assertEventIdsForFilter(['student_id' => $this->student->id], [$firstSession->id]);
        $this->assertEventIdsForFilter(['room' => 'B202'], [$secondSession->id]);
        $this->assertEventIdsForFilter(['instrument_id' => $this->secondInstrument->id], [$secondSession->id, $directSession->id]);
    }

    public function test_missing_start_or_end_dates_return_field_specific_errors(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.calendar.events', ['end' => self::END_DATE]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['start']]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.calendar.events', ['start' => self::START_DATE]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['end']]);
    }

    public function test_invalid_start_or_end_dates_return_field_specific_errors(): void
    {
        $this->assertValidationError(['start' => '2025/07/14', 'end' => self::END_DATE], 'start');
        $this->assertValidationError(['start' => self::START_DATE, 'end' => 'not-a-date'], 'end');
    }

    public function test_same_day_date_ranges_are_valid(): void
    {
        $response = $this->actingAs($this->admin)->getJson($this->eventsUrl([
            'start' => self::START_DATE,
            'end' => self::START_DATE,
        ]));

        $response->assertOk();
    }

    public function test_chronological_date_ranges_are_valid(): void
    {
        $response = $this->actingAs($this->admin)->getJson($this->eventsUrl([
            'start' => self::START_DATE,
            'end' => self::END_DATE,
        ]));

        $response->assertOk();
    }

    public function test_reversed_date_ranges_return_an_end_validation_error(): void
    {
        $response = $this->actingAs($this->admin)->getJson($this->eventsUrl([
            'start' => self::END_DATE,
            'end' => self::START_DATE,
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('errors.end.0', 'The end date must be after or equal to the start date.');
    }

    public function test_date_ranges_over_92_days_return_maximum_range_error(): void
    {
        $response = $this->actingAs($this->admin)->getJson($this->eventsUrl([
            'start' => '2025-01-01',
            'end' => '2025-04-04',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('errors.end.0', 'The selected date range may not exceed 92 days.');
    }

    private function createSession(array $overrides = []): ClassSession
    {
        return ClassSession::create(array_merge([
            'enrollment_id' => $this->enrollment->id,
            'student_id' => null,
            'teacher_id' => null,
            'instrument_id' => null,
            'session_date' => self::START_DATE,
            'start_time' => '09:00:00',
            'duration_minutes' => 30,
            'status' => SessionStatusEnum::Scheduled,
            'room' => 'A101',
            'session_fee' => 500000,
            'notes' => null,
        ], $overrides));
    }

    private function assertEventIdsForFilter(array $filter, array $expectedIds): void
    {
        $events = $this->eventsFrom(
            $this->actingAs($this->admin)->getJson($this->eventsUrl($filter))
                ->assertOk(),
        );

        $actualIds = array_map(static fn (array $event): int => $event['id'], $events);
        sort($actualIds);
        sort($expectedIds);

        $this->assertSame($expectedIds, $actualIds);
    }

    private function assertValidationError(array $query, string $field): void
    {
        $this->actingAs($this->admin)
            ->getJson($this->eventsUrl($query))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => [$field]]);
    }

    private function assertEventShape(array $event): void
    {
        $this->assertIsInt($event['id']);
        $this->assertIsString($event['title']);
        $this->assertNotSame('', $event['title']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $event['start']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $event['end']);
        $this->assertContains($event['status'], SessionStatusEnum::values());
        $this->assertIsString($event['studentName']);
        $this->assertIsString($event['teacherName']);
        $this->assertIsString($event['instrumentName']);
        $this->assertArrayHasKey('room', $event);
        $this->assertArrayHasKey('extendedProps', $event);
        $this->assertArrayHasKey('enrollment_id', $event['extendedProps']);
        $this->assertArrayHasKey('session_fee', $event['extendedProps']);
        $this->assertArrayHasKey('duration_minutes', $event['extendedProps']);
        $this->assertArrayHasKey('notes', $event['extendedProps']);
        $this->assertArrayHasKey('session_date', $event['extendedProps']);
    }

    private function eventById(array $events, int $id): array
    {
        foreach ($events as $event) {
            if ($event['id'] === $id) {
                return $event;
            }
        }

        $this->fail("Event {$id} was not returned.");
    }

    private function eventsFrom(TestResponse $response): array
    {
        $json = $response->json();

        return $json['data'] ?? $json;
    }

    private function eventsUrl(array $query = []): string
    {
        return route('admin.calendar.events', array_merge([
            'start' => self::START_DATE,
            'end' => self::END_DATE,
        ], $query));
    }
}
