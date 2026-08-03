<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\DTOs\BulkCommand;
use App\Services\BulkAuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;
use Tests\Support\AdminBulkFixtures;
use Tests\TestCase;

final class BulkAuthorizationServiceTest extends TestCase
{
    public function test_collection_authorization_rejects_before_selection_resolution(): void
    {
        $actor = AdminBulkFixtures::unauthorizedPolicyActor();
        $teacher = AdminBulkFixtures::eligibleTeacher();
        $command = new BulkCommand('teacher', 'delete', 'current_page', [$teacher->id]);

        try {
            (new BulkAuthorizationService())->authorize($command, $actor);
            self::fail('An actor without viewAny must not reach bulk selection resolution.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('teachers', ['id' => $teacher->id]);
        }
    }

    public function test_status_actions_use_update_and_delete_uses_delete_for_each_record(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        $teacher = AdminBulkFixtures::eligibleTeacher();
        $service = new BulkAuthorizationService();

        $service->authorize(new BulkCommand('teacher', 'activate', 'current_page', [$teacher->id]), $actor);
        $service->authorizeRecord(
            new BulkCommand('teacher', 'deactivate', 'current_page', [$teacher->id]),
            $teacher,
            $actor,
        );
        $service->authorizeRecord(
            new BulkCommand('teacher', 'delete', 'current_page', [$teacher->id]),
            $teacher,
            $actor,
        );

        $this->assertSame('update', $service->ability('activate'));
        $this->assertSame('update', $service->ability('deactivate'));
        $this->assertSame('delete', $service->ability('delete'));
    }

    public function test_viewany_does_not_replace_per_record_update_authorization(): void
    {
        $teacherActor = AdminBulkFixtures::unauthorizedPolicyActor();
        $student = AdminBulkFixtures::eligibleStudent();
        $command = new BulkCommand('student', 'deactivate', 'current_page', [$student->id]);
        $service = new BulkAuthorizationService();

        // Teacher personas may view the student collection, but cannot mutate a student.
        $service->authorize($command, $teacherActor);

        $this->expectException(AuthorizationException::class);
        $service->authorizeRecord($command, $student, $teacherActor);
    }

    public function test_record_entity_must_match_the_command_entity(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        $teacher = AdminBulkFixtures::eligibleTeacher();
        $command = new BulkCommand('student', 'delete', 'current_page', [$teacher->id]);

        $this->expectException(InvalidArgumentException::class);
        (new BulkAuthorizationService())->authorizeRecord($command, $teacher, $actor);
    }
}
