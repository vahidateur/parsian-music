<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Student $student;

    private Teacher $teacher;

    private Instrument $instrument;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::ADMIN]);

        $this->student = Student::forceCreate([
            'student_code' => 'STU-001',
            'full_name' => 'Test Student',
            'phone' => '09120000001',
            'status' => 'active',
            'join_date' => now(),
        ]);

        $this->teacher = Teacher::forceCreate([
            'teacher_code' => 'TCH-001',
            'full_name' => 'Test Teacher',
            'phone' => '09120000002',
            'status' => 'active',
            'hire_date' => now(),
        ]);

        $this->instrument = Instrument::create([
            'name' => 'Piano',
            'slug' => 'piano',
            'is_active' => true,
        ]);
    }

    /** Manual session creation must use student_id/teacher_id/instrument_id directly, not enrollment_id. */
    public function test_admin_can_create_session_using_direct_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sessions.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->instrument->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'A101',
        ]);

        $response->assertRedirect(route('admin.sessions.index'));
        $this->assertDatabaseHas('class_sessions', [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->instrument->id,
            'room' => 'A101',
        ]);
    }

    /** Time outside the 15:00-21:30 booking window must be rejected. */
    public function test_session_creation_rejects_time_outside_allowed_window(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sessions.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->instrument->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'room' => 'A101',
        ]);

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('class_sessions', 0);
    }

    /** A room not in the hardcoded A101/A102/A103 list must be rejected. */
    public function test_session_creation_rejects_invalid_room(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sessions.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->instrument->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'Room X',
        ]);

        $response->assertSessionHasErrors('room');
        $this->assertDatabaseCount('class_sessions', 0);
    }

    /** Overlapping sessions for the same room must be rejected via conflict detection. */
    public function test_session_creation_rejects_room_conflict(): void
    {
        ClassSession::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->instrument->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'A101',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.sessions.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->instrument->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:30',
            'duration_minutes' => 60,
            'room' => 'A101',
        ]);

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('class_sessions', 1);
    }

    /** Back-to-back sessions (e.g. 16:00-17:00 then 17:00-18:00) must be allowed, not flagged as conflicts. */
    public function test_back_to_back_sessions_are_allowed(): void
    {
        ClassSession::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->instrument->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'A101',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.sessions.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->instrument->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '17:00',
            'duration_minutes' => 60,
            'room' => 'A101',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('admin.sessions.index'));
        $this->assertDatabaseCount('class_sessions', 2);
    }

    /** The create form must render student/teacher/instrument fields, no enrollment_id or fee/discount fields. */
    public function test_create_session_form_renders_direct_fields_not_enrollment(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sessions.create'));

        $response->assertOk();
        $response->assertSee('name="student_id"', false);
        $response->assertSee('name="teacher_id"', false);
        $response->assertSee('name="instrument_id"', false);
        $response->assertDontSee('name="enrollment_id"', false);
        $response->assertDontSee('name="session_fee"', false);
        $response->assertDontSee('name="discount"', false);
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
