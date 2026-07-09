<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 4.6 — Test session creation with subscription deduction.
 */
class ClassSessionSubscriptionTest extends TestCase
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
            'student_code' => 'STU-SUB-001',
            'full_name' => 'Sub Test Student',
            'phone' => '09120000010',
            'status' => 'active',
            'join_date' => now(),
        ]);

        $this->teacher = Teacher::forceCreate([
            'teacher_code' => 'TCH-SUB-001',
            'full_name' => 'Sub Test Teacher',
            'phone' => '09120000011',
            'status' => 'active',
            'hire_date' => now(),
        ]);

        $this->instrument = Instrument::create([
            'name' => 'Violin',
            'slug' => 'violin',
            'is_active' => true,
        ]);
    }

    private function sessionPayload(array $overrides = []): array
    {
        return array_merge([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->instrument->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'duration_minutes' => 60,
            'room' => 'A101',
        ], $overrides);
    }

    private function createSubscription(int $sessionsUsed = 0, int $sessionsAllocated = 4): Subscription
    {
        return Subscription::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'instrument_id' => $this->instrument->id,
            'sessions_used' => $sessionsUsed,
            'sessions_allocated' => $sessionsAllocated,
        ]);
    }

    /** Session creation with valid subscription increments sessions_used by exactly 1. */
    public function test_session_creation_increments_sessions_used_by_one(): void
    {
        $subscription = $this->createSubscription(sessionsUsed: 2);

        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), $this->sessionPayload())
            ->assertRedirect(route('admin.sessions.index'));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'sessions_used' => 3,
        ]);
    }

    /** Overage is allowed: sessions_used = 4, sessions_allocated = 4 → sessions_used becomes 5. */
    public function test_session_creation_allows_overage_beyond_allocation(): void
    {
        $subscription = $this->createSubscription(sessionsUsed: 4, sessionsAllocated: 4);

        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), $this->sessionPayload())
            ->assertRedirect(route('admin.sessions.index'));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'sessions_used' => 5,
        ]);
    }

    /** Session creation without a matching subscription succeeds — no subscription tracking. */
    public function test_session_creation_without_subscription_succeeds(): void
    {
        // No subscription — session is still created, sessions_used is not touched.
        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), $this->sessionPayload())
            ->assertRedirect(route('admin.sessions.index'));

        $this->assertDatabaseCount('class_sessions', 1);
    }

    /** sessions_used starts at 0; after one session it must be exactly 1. */
    public function test_sessions_used_starts_at_zero_and_increments(): void
    {
        $subscription = $this->createSubscription(sessionsUsed: 0);

        $this->actingAs($this->admin)
            ->post(route('admin.sessions.store'), $this->sessionPayload())
            ->assertRedirect(route('admin.sessions.index'));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'sessions_used' => 1,
        ]);
    }
}
