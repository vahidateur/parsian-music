<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private StudentEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $student = Student::forceCreate([
            'student_code' => 'STU-001',
            'full_name' => 'Test Student',
            'phone' => '09120000001',
            'status' => 'active',
            'join_date' => now(),
        ]);

        $teacher = Teacher::forceCreate([
            'teacher_code' => 'TCH-001',
            'full_name' => 'Test Teacher',
            'phone' => '09120000002',
            'status' => 'active',
            'hire_date' => now(),
        ]);

        $instrument = Instrument::create([
            'name' => 'Piano',
            'slug' => 'piano',
            'is_active' => true,
        ]);

        $this->enrollment = StudentEnrollment::create([
            'student_id' => $student->id,
            'instrument_id' => $instrument->id,
            'teacher_id' => $teacher->id,
            'skill_level' => 'beginner',
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /** Manual session creation must use enrollment_id, not student_id/teacher_id. */
    public function test_admin_can_create_session_using_enrollment_id(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sessions.store'), [
            'enrollment_id' => $this->enrollment->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'Room 1',
        ]);

        $response->assertRedirect(route('admin.sessions.index'));
        $this->assertDatabaseHas('class_sessions', [
            'enrollment_id' => $this->enrollment->id,
            'room' => 'Room 1',
        ]);
    }

    /** Time outside the 15:00-21:30 booking window must be rejected. */
    public function test_session_creation_rejects_time_outside_allowed_window(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sessions.store'), [
            'enrollment_id' => $this->enrollment->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'room' => 'Room 1',
        ]);

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('class_sessions', 0);
    }

    /** Overlapping sessions for the same room must be rejected via conflict detection. */
    public function test_session_creation_rejects_room_conflict(): void
    {
        ClassSession::create([
            'enrollment_id' => $this->enrollment->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'Room 1',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.sessions.store'), [
            'enrollment_id' => $this->enrollment->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:30',
            'duration_minutes' => 60,
            'room' => 'Room 1',
        ]);

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('class_sessions', 1);
    }

    /** The create form must render enrollment options, no student_id/teacher_id fields. */
    public function test_create_session_form_renders_enrollment_dropdown(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sessions.create'));

        $response->assertOk();
        $response->assertSee('name="enrollment_id"', false);
        $response->assertDontSee('name="student_id"', false);
        $response->assertDontSee('name="teacher_id"', false);
    }

    /** Calendar page must load without errors and use Persian locale by default. */
    public function test_calendar_page_loads_successfully(): void
    {
        ClassSession::create([
            'enrollment_id' => $this->enrollment->id,
            'session_date' => now()->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'Room 1',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.calendar.index'));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
    }

    /** Sessions index status filter must only accept valid enum values (no "makeup"). */
    public function test_sessions_index_status_filter_rejects_invalid_status(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sessions.index', ['status' => 'makeup']));

        $response->assertStatus(422);
    }

    /** Sessions index status filter accepts all valid enum values without error. */
    public function test_sessions_index_status_filter_accepts_valid_statuses(): void
    {
        foreach (\App\Enums\SessionStatusEnum::values() as $status) {
            $response = $this->actingAs($this->admin)->get(route('admin.sessions.index', ['status' => $status]));
            $response->assertOk();
        }
    }
}
