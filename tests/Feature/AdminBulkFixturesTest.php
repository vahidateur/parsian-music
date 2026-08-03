<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\ClassSession;
use App\Models\Room;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdminBulkFixtures;
use Tests\TestCase;

/** Focused persistence contracts for admin-bulk task fixtures. */
final class AdminBulkFixturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixture_builds_both_persisted_session_relation_paths(): void
    {
        $enrollmentSession = AdminBulkFixtures::enrollmentSession();
        $directSession = AdminBulkFixtures::directSession();

        $this->assertNotNull($enrollmentSession->enrollment_id);
        $this->assertSame($enrollmentSession->enrollment->student_id, $enrollmentSession->student_id);
        $this->assertSame($enrollmentSession->enrollment->teacher_id, $enrollmentSession->teacher_id);
        $this->assertSame($enrollmentSession->enrollment->instrument_id, $enrollmentSession->instrument_id);

        $this->assertNull($directSession->enrollment_id);
        $this->assertNotNull($directSession->student_id);
        $this->assertNotNull($directSession->teacher_id);
        $this->assertNotNull($directSession->instrument_id);
    }

    public function test_fixture_persists_an_explicit_relation_conflict_without_mixing_paths(): void
    {
        $session = AdminBulkFixtures::relationConflict()->load('enrollment');

        $this->assertInstanceOf(ClassSession::class, $session);
        $this->assertNotNull($session->enrollment);
        $this->assertNotSame($session->enrollment->student_id, $session->student_id);
        $this->assertNotSame($session->enrollment->teacher_id, $session->teacher_id);
        $this->assertNotSame($session->enrollment->instrument_id, $session->instrument_id);
    }

    public function test_fixture_covers_room_resolution_states_without_synthetic_records(): void
    {
        $active = AdminBulkFixtures::activeRoom();
        $inactive = AdminBulkFixtures::inactiveRoom();
        $legacyName = AdminBulkFixtures::unresolvedRoomName();

        $this->assertTrue($active->is_active);
        $this->assertFalse($inactive->is_active);
        $this->assertDatabaseHas('rooms', ['id' => $active->id, 'name' => $active->name]);
        $this->assertDatabaseHas('rooms', ['id' => $inactive->id, 'name' => $inactive->name]);
        $this->assertDatabaseMissing('rooms', ['name' => $legacyName]);
    }

    public function test_protected_graph_keeps_all_deletion_dependencies_attached(): void
    {
        $teacher = AdminBulkFixtures::protectedTeacher();
        $student = AdminBulkFixtures::protectedStudent();

        $this->assertGreaterThan(0, $teacher->enrollments()->count());
        $this->assertGreaterThan(0, $teacher->subscriptions()->count());
        $this->assertGreaterThan(0, $student->enrollments()->count());
        $this->assertGreaterThan(0, $student->subscriptions()->count());
        $this->assertGreaterThan(0, $student->invoices()->count());
        $this->assertNotNull($student->lead()->first());
        $this->assertGreaterThan(0, ClassSession::query()->whereIn('enrollment_id', $student->enrollments()->pluck('id'))->count());
    }

    public function test_policy_actor_fixtures_match_current_policy_roles(): void
    {
        $authorized = AdminBulkFixtures::policyActor();
        $unauthorized = AdminBulkFixtures::unauthorizedPolicyActor();

        $this->assertSame(RoleEnum::ADMIN, $authorized->role);
        $this->assertSame(RoleEnum::TEACHER, $unauthorized->role);
        $this->assertTrue($authorized->is_active);
        $this->assertTrue($unauthorized->is_active);
    }

    public function test_eligible_targets_have_no_protected_dependencies(): void
    {
        $teacher = AdminBulkFixtures::eligibleTeacher();
        $student = AdminBulkFixtures::eligibleStudent();

        $this->assertInstanceOf(Teacher::class, $teacher);
        $this->assertInstanceOf(Student::class, $student);
        $this->assertSame(0, $teacher->enrollments()->count());
        $this->assertSame(0, $student->enrollments()->count());
        $this->assertSame(0, $student->invoices()->count());
    }
}
