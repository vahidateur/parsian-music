<?php

namespace Tests\Unit\Http\Resources;

use App\Enums\SessionStatusEnum;
use App\Http\Resources\CalendarEventResource;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CalendarEventResourceTest extends TestCase
{
    public function test_it_transforms_a_complete_enrollment_backed_session(): void
    {
        $student = $this->student('Enrollment Student');
        $teacher = $this->teacher('Enrollment Teacher');
        $instrument = $this->instrument('Violin');
        $enrollment = (new StudentEnrollment)->setRelation('student', $student)
            ->setRelation('teacher', $teacher)
            ->setRelation('instrument', $instrument);
        $session = $this->makeSession([
            'id' => 42,
            'enrollment_id' => 7,
            'session_date' => '2025-07-14',
            'start_time' => '2025-07-14 09:15:00',
            'duration_minutes' => 75,
            'status' => SessionStatusEnum::Scheduled,
            'room' => 'A101',
            'session_fee' => 5000000,
            'notes' => 'Bring the shoulder rest.',
        ])->setRelation('enrollment', $enrollment);

        $event = $this->transform($session);

        $this->assertSame(42, $event['id']);
        $this->assertSame('Enrollment Student — Violin', $event['title']);
        $this->assertSame('2025-07-14T09:15:00', $event['start']);
        $this->assertSame('2025-07-14T10:30:00', $event['end']);
        $this->assertSame('scheduled', $event['status']);
        $this->assertSame('Enrollment Student', $event['studentName']);
        $this->assertSame('Enrollment Teacher', $event['teacherName']);
        $this->assertSame('Violin', $event['instrumentName']);
        $this->assertSame('A101', $event['room']);
        $this->assertSame([
            'enrollment_id' => 7,
            'session_fee' => 5000000,
            'duration_minutes' => 75,
            'notes' => 'Bring the shoulder rest.',
            'session_date' => '2025-07-14',
        ], $event['extendedProps']);
    }

    public function test_it_transforms_a_direct_relation_session_with_nullable_room_and_notes(): void
    {
        $session = $this->makeSession([
            'id' => 84,
            'enrollment_id' => null,
            'session_date' => '2025-12-01',
            'start_time' => '2025-12-01 16:40:00',
            'duration_minutes' => 20,
            'status' => SessionStatusEnum::Completed,
            'room' => null,
            'session_fee' => null,
            'notes' => null,
        ])->setRelation('student', $this->student('Direct Student'))
            ->setRelation('teacher', $this->teacher('Direct Teacher'))
            ->setRelation('instrument', $this->instrument('Piano'));

        $event = $this->transform($session);

        $this->assertSame('Direct Student — Piano', $event['title']);
        $this->assertSame('2025-12-01T16:40:00', $event['start']);
        $this->assertSame('2025-12-01T17:00:00', $event['end']);
        $this->assertNull($event['room']);
        $this->assertNull($event['extendedProps']['notes']);
        $this->assertNull($event['extendedProps']['enrollment_id']);
        $this->assertSame(20, $event['extendedProps']['duration_minutes']);
    }

    #[DataProvider('validStatuses')]
    public function test_it_exposes_only_allowed_session_status_values(string $status): void
    {
        $session = $this->makeSession([
            'id' => 100,
            'session_date' => '2025-07-14',
            'start_time' => '2025-07-14 11:00:00',
            'duration_minutes' => 30,
            'status' => SessionStatusEnum::from($status),
        ])->setRelation('student', $this->student('Status Student'))
            ->setRelation('teacher', $this->teacher('Status Teacher'))
            ->setRelation('instrument', $this->instrument('Daf'));

        $event = $this->transform($session);

        $this->assertContains($event['status'], SessionStatusEnum::values());
        $this->assertSame($status, $event['status']);
    }

    /** @return array<string, array{string}> */
    public static function validStatuses(): array
    {
        return array_combine(
            SessionStatusEnum::values(),
            array_map(static fn (string $status): array => [$status], SessionStatusEnum::values()),
        );
    }

    /** @param array<string, mixed> $attributes */
    private function makeSession(array $attributes): ClassSession
    {
        $session = new ClassSession(array_merge([
            'id' => 1,
            'enrollment_id' => null,
            'session_date' => '2025-07-14',
            'start_time' => '2025-07-14 09:00:00',
            'duration_minutes' => 30,
            'status' => SessionStatusEnum::Scheduled,
            'room' => 'A101',
            'session_fee' => 0,
            'notes' => null,
        ], $attributes));

        $session->setAttribute('id', $attributes['id'] ?? 1);

        return $session;
    }

    private function student(string $name): Student
    {
        return new Student(['full_name' => $name]);
    }

    private function teacher(string $name): Teacher
    {
        return new Teacher(['full_name' => $name]);
    }

    private function instrument(string $name): Instrument
    {
        return new Instrument(['name' => $name, 'name_fa' => null]);
    }

    /** @return array<string, mixed> */
    private function transform(ClassSession $session): array
    {
        return (new CalendarEventResource($session))->toArray(Request::create('/'));
    }
}
