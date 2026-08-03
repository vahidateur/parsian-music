<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Http\Middleware\CheckRole;
use App\Models\AuditRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdminBulkFixtures;
use Tests\TestCase;

final class BulkAuditRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_partial_request_writes_one_execution_audit_after_item_processing(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        $succeeded = AdminBulkFixtures::student(['status' => 'active']);
        $failed = AdminBulkFixtures::student(['status' => 'paused']);

        $response = $this->actingAs($actor)->postJson(route('admin.students.bulk'), [
            'entity' => 'student',
            'action' => 'deactivate',
            'mode' => 'current_page',
            'ids' => [$succeeded->id, $failed->id],
            'selection_reference' => 'selection-partial',
            'phone' => '09120000000',
            'notes' => 'private request data',
            'raw_payload' => ['credentials' => 'secret'],
        ]);

        $response->assertOk()->assertJsonPath('data.total', 2);
        $response->assertJsonPath('data.succeeded', 1);
        $response->assertJsonPath('data.failed', 1);
        $this->assertSame('inactive', $succeeded->refresh()->status->value);
        $this->assertSame('paused', $failed->refresh()->status->value);

        $this->assertSame(1, AuditRecord::query()->where('event_type', AuditRecord::EVENT_EXECUTION)->count());
        $this->assertSame(0, AuditRecord::query()->where('event_type', AuditRecord::EVENT_REJECTED_OPERATION)->count());
        $record = AuditRecord::query()->sole();
        $this->assertSame([1, 0, 1], [$record->succeeded, $record->skipped, $record->failed]);
        $this->assertSame([$failed->id], $record->reason_categories['invalid_transition']);
        $this->assertSame([$failed->id], $record->reason_identifiers);
        $this->assertSame(['selection_reference' => 'selection-partial'], $record->metadata);
    }

    public function test_validation_rejection_writes_one_rejected_event_without_execution(): void
    {
        $actor = AdminBulkFixtures::policyActor();

        $response = $this->actingAs($actor)->postJson(route('admin.students.bulk'), [
            'entity' => 'student',
            'action' => 'deactivate',
            'mode' => 'current_page',
            'ids' => [],
            'phone' => '09120000000',
            'notes' => 'private request data',
            'raw_payload' => ['password' => 'secret'],
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, AuditRecord::query()->where('event_type', AuditRecord::EVENT_REJECTED_OPERATION)->count());
        $this->assertSame(0, AuditRecord::query()->where('event_type', AuditRecord::EVENT_EXECUTION)->count());
        $record = AuditRecord::query()->sole();
        $this->assertSame('validation', array_key_first($record->reason_categories));
        $this->assertContains('ids', $record->metadata['validation_fields']);
        $this->assertArrayNotHasKey('phone', $record->metadata);
        $this->assertArrayNotHasKey('notes', $record->metadata);
        $this->assertArrayNotHasKey('raw_payload', $record->metadata);
    }

    public function test_collection_authorization_rejection_writes_one_rejected_event(): void
    {
        $actor = AdminBulkFixtures::unauthorizedPolicyActor();
        $teacher = AdminBulkFixtures::eligibleTeacher();

        $response = $this->withoutMiddleware(CheckRole::class)
            ->actingAs($actor)
            ->postJson(route('admin.teachers.bulk'), [
                'entity' => 'teacher',
                'action' => 'deactivate',
                'mode' => 'current_page',
                'ids' => [$teacher->id],
            ]);

        $response->assertForbidden();
        $this->assertSame(1, AuditRecord::query()->where('event_type', AuditRecord::EVENT_REJECTED_OPERATION)->count());
        $this->assertSame(0, AuditRecord::query()->where('event_type', AuditRecord::EVENT_EXECUTION)->count());
        $record = AuditRecord::query()->sole();
        $this->assertSame('authorization', array_key_first($record->reason_categories));
        $this->assertSame($actor->id, $record->actor_id);
        $this->assertSame('teacher', $record->entity_type);
        $this->assertSame(RoleEnum::TEACHER->value, $actor->role->value);
    }
}
