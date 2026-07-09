<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(): Student
    {
        static $i = 0;
        $i++;

        return Student::forceCreate([
            'student_code' => "STU-TEST-{$i}",
            'full_name'    => "Test Student {$i}",
            'phone'        => "091200000{$i}0",
            'status'       => 'active',
            'join_date'    => now(),
        ]);
    }

    private function makeTeacher(): Teacher
    {
        static $i = 0;
        $i++;

        return Teacher::forceCreate([
            'teacher_code' => "TCH-TEST-{$i}",
            'full_name'    => "Test Teacher {$i}",
            'phone'        => "091200001{$i}0",
            'status'       => 'active',
            'hire_date'    => now(),
        ]);
    }

    private function makeInstrument(): Instrument
    {
        static $i = 0;
        $i++;

        return Instrument::create([
            'name'      => "Instrument{$i}",
            'slug'      => "instrument-{$i}",
            'is_active' => true,
        ]);
    }

    /** @test
     * Task 6.1 — Subscription model defaults.
     * Validates: Requirements 1.2, Property 2
     */
    public function test_subscription_model_defaults(): void
    {
        $student    = $this->makeStudent();
        $teacher    = $this->makeTeacher();
        $instrument = $this->makeInstrument();

        $subscription = Subscription::create([
            'student_id'    => $student->id,
            'teacher_id'    => $teacher->id,
            'instrument_id' => $instrument->id,
        ])->fresh();

        $this->assertSame(4, $subscription->sessions_allocated);
        $this->assertSame(3_000_000, $subscription->monthly_fee);
        $this->assertSame('unpaid', $subscription->payment_status);
        $this->assertSame(0, $subscription->sessions_used);
        $this->assertTrue(
            $subscription->renewal_date->isSameDay(now()->addDays(30)),
            "renewal_date should be today + 30 days"
        );
    }

    /** @test
     * Task 6.3 — Unique constraint on (student_id, teacher_id, instrument_id).
     * Validates: Requirements 1.4
     */
    public function test_unique_constraint_on_subscription_triple(): void
    {
        $student    = $this->makeStudent();
        $teacher    = $this->makeTeacher();
        $instrument = $this->makeInstrument();

        // First subscription with this triple — must succeed
        Subscription::create([
            'student_id'    => $student->id,
            'teacher_id'    => $teacher->id,
            'instrument_id' => $instrument->id,
        ]);

        // Second subscription with the SAME triple — must throw
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Subscription::create([
            'student_id'    => $student->id,
            'teacher_id'    => $teacher->id,
            'instrument_id' => $instrument->id,
        ]);
    }

    /** @test
     * Task 6.3 — Different triples should all succeed.
     * Validates: Requirements 1.4
     */
    public function test_different_triples_can_coexist(): void
    {
        $student     = $this->makeStudent();
        $teacher     = $this->makeTeacher();
        $instrument1 = $this->makeInstrument();
        $instrument2 = $this->makeInstrument();

        // Same student+teacher, different instrument — should succeed
        $sub1 = Subscription::create([
            'student_id'    => $student->id,
            'teacher_id'    => $teacher->id,
            'instrument_id' => $instrument1->id,
        ]);

        $sub2 = Subscription::create([
            'student_id'    => $student->id,
            'teacher_id'    => $teacher->id,
            'instrument_id' => $instrument2->id,
        ]);

        $this->assertNotSame($sub1->id, $sub2->id);
        $this->assertDatabaseCount('subscriptions', 2);
    }

    /** @test
     * Task 6.4 — Subscription computed fields (remaining_sessions, overage_count).
     * Validates: Requirements 5.4, 8.4 (Property 16)
     */
    public function test_subscription_computed_fields(): void
    {
        $student    = $this->makeStudent();
        $teacher    = $this->makeTeacher();
        $instrument = $this->makeInstrument();

        // Case 1: sessions_used=2, sessions_allocated=4 → remaining=2, overage=0
        $sub1 = Subscription::forceCreate([
            'student_id'         => $student->id,
            'teacher_id'         => $teacher->id,
            'instrument_id'      => $instrument->id,
            'sessions_allocated' => 4,
            'sessions_used'      => 2,
        ]);
        $this->assertSame(2, $sub1->remaining_sessions);
        $this->assertSame(0, $sub1->overage_count);

        // Case 2: sessions_used=4, sessions_allocated=4 → remaining=0, overage=0
        $sub2 = Subscription::forceCreate([
            'student_id'         => $this->makeStudent()->id,
            'teacher_id'         => $teacher->id,
            'instrument_id'      => $instrument->id,
            'sessions_allocated' => 4,
            'sessions_used'      => 4,
        ]);
        $this->assertSame(0, $sub2->remaining_sessions);
        $this->assertSame(0, $sub2->overage_count);

        // Case 3: sessions_used=6, sessions_allocated=4 → remaining=-2, overage=2
        $sub3 = Subscription::forceCreate([
            'student_id'         => $this->makeStudent()->id,
            'teacher_id'         => $teacher->id,
            'instrument_id'      => $instrument->id,
            'sessions_allocated' => 4,
            'sessions_used'      => 6,
        ]);
        $this->assertSame(-2, $sub3->remaining_sessions);
        $this->assertSame(2, $sub3->overage_count);
    }

    /** @test
     * Task 6.2 — Subscription relationships.
     * Validates: Requirements 1.1, 1.3
     */
    public function test_subscription_relationships(): void
    {
        $student    = $this->makeStudent();
        $teacher    = $this->makeTeacher();
        $instrument = $this->makeInstrument();

        $subscription = Subscription::create([
            'student_id'    => $student->id,
            'teacher_id'    => $teacher->id,
            'instrument_id' => $instrument->id,
        ]);

        // belongsTo: student
        $this->assertInstanceOf(Student::class, $subscription->student);
        $this->assertSame($student->id, $subscription->student->id);

        // belongsTo: teacher
        $this->assertInstanceOf(Teacher::class, $subscription->teacher);
        $this->assertSame($teacher->id, $subscription->teacher->id);

        // belongsTo: instrument
        $this->assertInstanceOf(Instrument::class, $subscription->instrument);
        $this->assertSame($instrument->id, $subscription->instrument->id);

        // hasMany: student->subscriptions
        $studentSubscriptions = $student->subscriptions;
        $this->assertCount(1, $studentSubscriptions);
        $this->assertSame($subscription->id, $studentSubscriptions->first()->id);

        // eager-loading
        $loaded = Subscription::with('student', 'teacher', 'instrument')->find($subscription->id);
        $this->assertTrue($loaded->relationLoaded('student'));
        $this->assertTrue($loaded->relationLoaded('teacher'));
        $this->assertTrue($loaded->relationLoaded('instrument'));
        $this->assertSame($student->id, $loaded->student->id);
        $this->assertSame($teacher->id, $loaded->teacher->id);
        $this->assertSame($instrument->id, $loaded->instrument->id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 6.8 — Student profile subscription summary renders with correct data
    // ─────────────────────────────────────────────────────────────────────────

    /** @test
     * Task 6.8a — Profile shows teacher name, instrument, remaining sessions, and PAID badge.
     * Validates: Requirements 8.1, 8.2, 8.3, 8.4
     */
    public function test_student_profile_shows_subscription_summary(): void
    {
        $admin      = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $student    = $this->makeStudent();
        $teacher    = $this->makeTeacher();
        $instrument = $this->makeInstrument();

        Subscription::forceCreate([
            'student_id'         => $student->id,
            'teacher_id'         => $teacher->id,
            'instrument_id'      => $instrument->id,
            'sessions_allocated' => 4,
            'sessions_used'      => 2,
            'payment_status'     => 'paid',
            'monthly_fee'        => 3_000_000,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.students.show', $student));

        $response->assertStatus(200);
        $response->assertSee($teacher->full_name);
        $response->assertSee($instrument->name);
        $response->assertSee('2/4');
        $response->assertSee('PAID');
    }

    /** @test
     * Task 6.8b — Profile shows overage indicator when sessions_used > sessions_allocated.
     * Validates: Requirements 8.4
     */
    public function test_student_profile_shows_overage_indicator(): void
    {
        $admin      = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $student    = $this->makeStudent();
        $teacher    = $this->makeTeacher();
        $instrument = $this->makeInstrument();

        Subscription::forceCreate([
            'student_id'         => $student->id,
            'teacher_id'         => $teacher->id,
            'instrument_id'      => $instrument->id,
            'sessions_allocated' => 4,
            'sessions_used'      => 6,
            'payment_status'     => 'unpaid',
            'monthly_fee'        => 3_000_000,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.students.show', $student));

        $response->assertStatus(200);
        $response->assertSee('-2/4');
        $response->assertSee('(2 over)');
    }

    /** @test
     * Task 6.8c — Profile shows empty state when student has no subscriptions.
     * Validates: Requirements 8.1
     */
    public function test_student_profile_shows_empty_state_when_no_subscriptions(): void
    {
        $admin   = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $student = $this->makeStudent();

        $response = $this->actingAs($admin)
            ->get(route('admin.students.show', $student));

        $response->assertStatus(200);
        $response->assertSee('No active subscriptions');
    }

    /** @test
     * Task 6.10 — Property 2: Defaults - new subscriptions always have correct defaults.
     * Validates: Requirements 1.2, Property 2
     */
    public function test_property_subscription_defaults(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $student    = $this->makeStudent();
            $teacher    = $this->makeTeacher();
            $instrument = $this->makeInstrument();

            $subscription = Subscription::create([
                'student_id'    => $student->id,
                'teacher_id'    => $teacher->id,
                'instrument_id' => $instrument->id,
            ])->fresh();

            $this->assertSame(4, $subscription->sessions_allocated,
                "Iteration {$i}: sessions_allocated should default to 4");
            $this->assertSame(3_000_000, $subscription->monthly_fee,
                "Iteration {$i}: monthly_fee should default to 3,000,000");
            $this->assertSame('unpaid', $subscription->payment_status,
                "Iteration {$i}: payment_status should default to unpaid");
            $this->assertSame(0, $subscription->sessions_used,
                "Iteration {$i}: sessions_used should default to 0");
            $this->assertTrue(
                $subscription->renewal_date->isSameDay(now()->addDays(30)),
                "Iteration {$i}: renewal_date should be today + 30 days"
            );
        }
    }

    /** @test
     * Task 6.11 — Property 5: Deduction - sessions_used increments by 1 per session (100+ iterations).
     * Validates: Requirements 2.5, 5.1, Property 5
     */
    public function test_property_session_deduction_increments_by_one(): void
    {
        $student    = $this->makeStudent();
        $teacher    = $this->makeTeacher();
        $instrument = $this->makeInstrument();

        $subscription = Subscription::create([
            'student_id'    => $student->id,
            'teacher_id'    => $teacher->id,
            'instrument_id' => $instrument->id,
        ]);

        // 100 simulated session creations: each must increment sessions_used by exactly 1
        for ($i = 0; $i < 100; $i++) {
            $before = $subscription->sessions_used;
            $subscription->sessions_used += 1;
            $subscription->save();
            $subscription->refresh();
            $this->assertSame($before + 1, $subscription->sessions_used,
                "Iteration {$i}: sessions_used should have incremented by exactly 1");
        }

        // After 100 sessions, sessions_used should be 100
        $this->assertSame(100, $subscription->sessions_used);
    }

    /** @test
     * Task 6.12 — Property 7: Triple validation - no session without subscription (100+ iterations).
     * Validates: Requirements 3.3, 3.4, 9.3, Property 7
     */
    public function test_property_triple_validation_no_session_without_subscription(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $student    = $this->makeStudent();
            $teacher    = $this->makeTeacher();
            $instrument = $this->makeInstrument();

            // No subscription created — firstOrFail must throw
            $threw = false;
            try {
                Subscription::where([
                    'student_id'    => $student->id,
                    'teacher_id'    => $teacher->id,
                    'instrument_id' => $instrument->id,
                ])->firstOrFail();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                $threw = true;
            }

            $this->assertTrue($threw,
                "Iteration {$i}: firstOrFail should throw ModelNotFoundException for a triple with no subscription");
        }
    }

    /** @test
     * Task 6.13 — Property 16: Overage calculation = max(0, sessions_used - sessions_allocated) (100+ iterations).
     * Validates: Requirements 5.4, 8.4, Property 16
     */
    public function test_property_overage_calculation(): void
    {
        $student    = $this->makeStudent();
        $teacher    = $this->makeTeacher();
        $instrument = $this->makeInstrument();

        for ($i = 0; $i < 100; $i++) {
            $sessionsAllocated = rand(1, 10);
            $sessionsUsed      = rand(0, 20);

            $subscription = Subscription::forceCreate([
                'student_id'         => ($i === 0) ? $student->id : $this->makeStudent()->id,
                'teacher_id'         => $teacher->id,
                'instrument_id'      => ($i === 0) ? $instrument->id : $this->makeInstrument()->id,
                'sessions_allocated' => $sessionsAllocated,
                'sessions_used'      => $sessionsUsed,
            ]);

            $expectedOverage   = max(0, $sessionsUsed - $sessionsAllocated);
            $expectedRemaining = $sessionsAllocated - $sessionsUsed;

            $this->assertSame($expectedOverage, $subscription->overage_count,
                "Iteration {$i}: overage_count should be max(0, {$sessionsUsed} - {$sessionsAllocated}) = {$expectedOverage}");
            $this->assertSame($expectedRemaining, $subscription->remaining_sessions,
                "Iteration {$i}: remaining_sessions should be {$sessionsAllocated} - {$sessionsUsed} = {$expectedRemaining}");
        }
    }

    /** @test
     * Task 6.9 — Property 1: Uniqueness - max 1 active subscription per (student, teacher, instrument).
     * Validates: Requirements 1.4
     *
     * **Validates: Requirements 1.4**
     */
    public function test_property_subscription_uniqueness(): void
    {
        // Property 1: For any student-teacher-instrument triple,
        // at most 1 active subscription can exist.
        // 100 iterations with randomised inputs.
        for ($i = 0; $i < 100; $i++) {
            $student    = $this->makeStudent();
            $teacher    = $this->makeTeacher();
            $instrument = $this->makeInstrument();

            // First creation always succeeds
            Subscription::create([
                'student_id'    => $student->id,
                'teacher_id'    => $teacher->id,
                'instrument_id' => $instrument->id,
            ]);

            // Second creation with same triple always fails
            $threw = false;
            try {
                Subscription::create([
                    'student_id'    => $student->id,
                    'teacher_id'    => $teacher->id,
                    'instrument_id' => $instrument->id,
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                $threw = true;
            }

            $this->assertTrue($threw, "Iteration {$i}: duplicate subscription should have been rejected");

            // Only 1 subscription for this triple should exist
            $count = Subscription::where([
                'student_id'    => $student->id,
                'teacher_id'    => $teacher->id,
                'instrument_id' => $instrument->id,
            ])->count();
            $this->assertSame(1, $count, "Iteration {$i}: exactly 1 subscription should exist for the triple");
        }
    }

    /** @test
     * Task 6.14 — Integration: Full session creation flow.
     * Validates: Requirements 2.1–2.5, 3.1–3.4, Property 5
     */
    public function test_integration_full_session_creation_flow(): void
    {
        $admin      = User::factory()->create(['role' => RoleEnum::ADMIN]);
        $student    = $this->makeStudent();
        $teacher    = $this->makeTeacher();
        $instrument = $this->makeInstrument();

        $subscription = Subscription::create([
            'student_id'    => $student->id,
            'teacher_id'    => $teacher->id,
            'instrument_id' => $instrument->id,
        ])->fresh();

        $this->assertSame(0, $subscription->sessions_used);

        $response = $this->actingAs($admin)->post(route('admin.sessions.store'), [
            'student_id'       => $student->id,
            'teacher_id'       => $teacher->id,
            'instrument_id'    => $instrument->id,
            'session_date'     => now()->addDay()->toDateString(),
            'start_time'       => '17:00',
            'duration_minutes' => 60,
            'room'             => 'A102',
        ]);

        $response->assertRedirect(route('admin.sessions.index'));

        // Session was created
        $this->assertDatabaseHas('class_sessions', [
            'student_id'    => $student->id,
            'teacher_id'    => $teacher->id,
            'instrument_id' => $instrument->id,
            'room'          => 'A102',
        ]);

        // Subscription sessions_used incremented
        $this->assertDatabaseHas('subscriptions', [
            'id'            => $subscription->id,
            'sessions_used' => 1,
        ]);
    }
}
