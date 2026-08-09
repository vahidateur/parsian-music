<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SessionGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SessionSchedulingDomainDelegationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_accepts_a_slot_blocked_only_by_a_cancelled_session_and_preserves_subscription_counting(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $student = Student::factory()->create();
        $teacher = Teacher::factory()->create();
        $instrument = Instrument::factory()->create();
        Room::factory()->named('Domain Create Room')->active()->create();
        $subscription = Subscription::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
            'sessions_used' => 0,
        ]);
        $date = now()->addDay()->toDateString();
        ClassSession::factory()->direct([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
        ])->cancelled()->create([
            'session_date' => $date,
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'Domain Create Room',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.sessions.store'), [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
            'session_date' => $date,
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'Domain Create Room',
            'notes' => 'Preserved note',
        ]);

        $response->assertRedirect(route('admin.sessions.index'));
        $session = ClassSession::query()->where('notes', 'Preserved note')->sole();
        $this->assertSame('Preserved note', $session->notes);
        $this->assertStringStartsWith('sv1_', (string) $session->session_version);
        $this->assertDatabaseHas('audit_records', [
            'actor_id' => $admin->id,
            'action' => 'create',
            'selection_mode' => 'form',
        ]);
        $this->assertDatabaseCount('scheduling_resource_versions', 5);
        $this->assertSame(1, $subscription->refresh()->sessions_used);
    }

    public function test_edit_delegates_the_session_write_to_the_coordinator_and_keeps_subscription_counters_atomic(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        [$oldStudent, $oldTeacher, $oldInstrument] = $this->resources();
        [$newStudent, $newTeacher, $newInstrument] = $this->resources();
        Room::factory()->named('Old Domain Room')->active()->create();
        Room::factory()->named('New Domain Room')->active()->create();
        $oldSubscription = Subscription::factory()->create([
            'student_id' => $oldStudent->id,
            'teacher_id' => $oldTeacher->id,
            'instrument_id' => $oldInstrument->id,
            'sessions_used' => 1,
        ]);
        $newSubscription = Subscription::factory()->create([
            'student_id' => $newStudent->id,
            'teacher_id' => $newTeacher->id,
            'instrument_id' => $newInstrument->id,
            'sessions_used' => 0,
        ]);
        $session = ClassSession::factory()->direct([
            'student_id' => $oldStudent->id,
            'teacher_id' => $oldTeacher->id,
            'instrument_id' => $oldInstrument->id,
        ])->create([
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'status' => SessionStatusEnum::Scheduled,
            'room' => 'Old Domain Room',
            'notes' => 'Before coordinator',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.sessions.update', $session), [
            'student_id' => $newStudent->id,
            'teacher_id' => $newTeacher->id,
            'instrument_id' => $newInstrument->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '17:30',
            'duration_minutes' => 60,
            'status' => SessionStatusEnum::Scheduled->value,
            'room' => 'New Domain Room',
            'notes' => 'After coordinator',
            'updated_at' => $session->updated_at->toISOString(),
        ]);

        $response->assertRedirect(route('admin.sessions.index'));
        $session->refresh();
        $this->assertSame('After coordinator', $session->notes);
        $this->assertNotNull($session->session_version);
        $this->assertSame(0, $oldSubscription->refresh()->sessions_used);
        $this->assertSame(1, $newSubscription->refresh()->sessions_used);
        $this->assertDatabaseCount('audit_records', 1);
        $this->assertDatabaseHas('audit_records', [
            'actor_id' => $admin->id,
            'action' => 'update',
            'selection_mode' => 'form',
        ]);
        $this->assertDatabaseCount('scheduling_resource_versions', 9);
    }

    public function test_generator_uses_the_domain_to_ignore_cancelled_teacher_facts_without_touching_the_calendar_feed(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $teacher = Teacher::factory()->create();
        $instrument = Instrument::factory()->create();
        $scheduledStudent = Student::factory()->create();
        $cancelledStudent = Student::factory()->create();
        Room::factory()->named('DG Room')->active()->create();
        $date = now()->addDay()->toDateString();
        ClassSession::factory()->direct([
            'student_id' => $cancelledStudent->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
        ])->cancelled()->create([
            'session_date' => $date,
            'start_time' => '16:00:00',
            'duration_minutes' => 60,
            'room' => 'DG Room',
        ]);
        $enrollment = StudentEnrollment::factory()->create([
            'student_id' => $scheduledStudent->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
        ]);
        $schedule = \App\Models\RecurringSchedule::query()->create([
            'enrollment_id' => $enrollment->id,
            'weekday' => now()->addDay()->dayOfWeek,
            'start_time' => '16:00:00',
            'duration_minutes' => 60,
            'room' => 'DG Room',
            'is_active' => true,
        ]);

        $generated = app(SessionGeneratorService::class)->generateForSchedule($schedule, 1, $admin);

        $this->assertCount(1, $generated);
        $occurrence = $generated->sole();
        $this->assertSame($schedule->id, $occurrence->recurring_schedule_id);
        $this->assertStringStartsWith('sv1_', (string) $occurrence->session_version);
        $this->assertDatabaseCount('audit_records', 1);
        $this->assertDatabaseHas('audit_records', [
            'actor_id' => $admin->id,
            'action' => 'create',
            'selection_mode' => 'recurrence',
        ]);
        $this->assertDatabaseCount('scheduling_resource_versions', 6);
        $this->assertSame(2, ClassSession::query()->count());
    }

    public function test_rejected_create_through_the_domain_coordinator_leaves_no_session_audit_or_version_effect(): void
    {
        $admin = User::factory()->create(['role' => RoleEnum::ADMIN]);
        [$student, $teacher, $instrument] = $this->resources();
        Room::factory()->named('Rejected Domain Room')->active()->create();
        $date = now()->addDay()->toDateString();
        ClassSession::factory()->direct([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
        ])->create([
            'session_date' => $date,
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'Rejected Domain Room',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.sessions.store'), [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'instrument_id' => $instrument->id,
            'session_date' => $date,
            'start_time' => '16:30',
            'duration_minutes' => 60,
            'room' => 'Rejected Domain Room',
        ]);

        $response->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('class_sessions', 1);
        $this->assertDatabaseCount('audit_records', 0);
        $this->assertDatabaseCount('scheduling_resource_versions', 0);
    }

    /** @return array{Student, Teacher, Instrument} */
    private function resources(): array
    {
        return [Student::factory()->create(), Teacher::factory()->create(), Instrument::factory()->create()];
    }
}
