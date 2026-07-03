<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\SessionStatusEnum;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use App\Services\StudentHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Student $student;
    private StudentEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $this->student = Student::forceCreate([
            'student_code' => 'S-00001',
            'full_name' => 'Test Student',
            'phone' => '09120000001',
            'status' => 'active',
            'join_date' => now(),
        ]);

        $teacher = Teacher::forceCreate([
            'teacher_code' => 'T-00001',
            'full_name' => 'Test Teacher',
            'phone' => '09120000002',
            'status' => 'active',
        ]);

        $instrument = Instrument::create([
            'name' => 'Piano',
            'name_fa' => 'پیانو',
            'slug' => 'piano',
            'is_active' => true,
        ]);

        $this->enrollment = StudentEnrollment::create([
            'student_id' => $this->student->id,
            'instrument_id' => $instrument->id,
            'teacher_id' => $teacher->id,
            'skill_level' => 'beginner',
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /** Timeline always has at least student_created. */
    public function test_timeline_contains_student_created(): void
    {
        $service = new StudentHistoryService();
        $timeline = $service->buildTimeline($this->student);

        $this->assertNotEmpty($timeline);
        $types = $timeline->pluck('type');
        $this->assertContains('student_created', $types);
    }

    /** Enrollment created event appears in timeline. */
    public function test_timeline_contains_enrollment_created(): void
    {
        $service = new StudentHistoryService();
        $timeline = $service->buildTimeline($this->student);

        $this->assertContains('enrollment_created', $timeline->pluck('type'));
    }

    /** Completed session appears in timeline. */
    public function test_timeline_contains_session_completed(): void
    {
        ClassSession::create([
            'enrollment_id' => $this->enrollment->id,
            'session_date' => now()->subDay(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'A101',
            'status' => SessionStatusEnum::Completed->value,
        ]);

        $service = new StudentHistoryService();
        $timeline = $service->buildTimeline($this->student);

        $this->assertContains('session_completed', $timeline->pluck('type'));
    }

    /** Absent attendance appears in timeline. */
    public function test_timeline_contains_attendance_absent(): void
    {
        $session = ClassSession::create([
            'enrollment_id' => $this->enrollment->id,
            'session_date' => now()->subDay(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'A101',
            'status' => SessionStatusEnum::Completed->value,
        ]);

        ClassAttendance::create([
            'class_session_id' => $session->id,
            'student_id' => $this->student->id,
            'status' => AttendanceStatusEnum::Absent->value,
        ]);

        $service = new StudentHistoryService();
        $timeline = $service->buildTimeline($this->student);

        $this->assertContains('attendance_marked', $timeline->pluck('type'));
    }

    /** Timeline events are sorted newest-first. */
    public function test_timeline_is_sorted_newest_first(): void
    {
        $service = new StudentHistoryService();
        $timeline = $service->buildTimeline($this->student);

        $timestamps = $timeline->pluck('timestamp')->map(fn ($ts) => $ts->timestamp)->values();
        $sorted = $timestamps->sortDesc()->values();
        $this->assertEquals($sorted->toArray(), $timestamps->toArray());
    }

    /** Student show page renders 200 and includes timeline section. */
    public function test_student_show_page_renders_timeline(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.students.show', $this->student));

        $response->assertOk();
        // Timeline section should be present (key renders in either locale)
        $response->assertSee('student_history', false);
        // Timeline card for student_created should appear
        $response->assertSee('student_created', false);
    }
}
