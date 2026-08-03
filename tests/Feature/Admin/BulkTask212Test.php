<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\DTOs\BulkCommand;
use App\DTOs\Filter_Context;
use App\Models\AuditRecord;
use App\Services\BulkActionService;
use App\Services\SelectionContextService;
use App\Services\Lists\StudentListQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\AdminBulkFixtures;
use Tests\TestCase;

final class BulkTask212Test extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_routes_are_named_and_state_changing_endpoints_are_post_only(): void
    {
        foreach (['admin.teachers.bulk.preview', 'admin.teachers.bulk', 'admin.students.bulk.preview', 'admin.students.bulk'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name.' must exist');
            $this->assertContains('POST', $route->methods());
            $this->assertNotContains('GET', $route->methods());
        }
    }

    public function test_state_changing_bulk_routes_carry_csrf_middleware(): void
    {
        $route = Route::getRoutes()->getByName('admin.students.bulk');

        $this->assertNotNull($route);
        $this->assertContains('web', $route->middleware());
        $this->assertContains('auth', $route->middleware());
    }

    public function test_unauthenticated_bulk_requests_are_rejected_before_resolution(): void
    {
        $student = AdminBulkFixtures::eligibleStudent();

        $response = $this->postJson(route('admin.students.bulk'), [
            'entity' => 'student', 'action' => 'deactivate', 'mode' => 'current_page', 'ids' => [$student->id],
        ]);

        $response->assertRedirect(route('login'));
        $this->assertSame('active', $student->refresh()->status->value);
        $this->assertDatabaseCount('audit_records', 0);
    }

    public function test_signed_context_preview_resolves_server_count_without_page_or_mutation(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        AdminBulkFixtures::student(['status' => 'active']);
        AdminBulkFixtures::student(['status' => 'inactive']);
        $list = app(StudentListQuery::class)->forInput(['status' => 'active', 'page' => 9], $actor);
        $token = app(SelectionContextService::class)->token($list->selection_context);

        $response = $this->actingAs($actor)->postJson(route('admin.students.bulk.preview'), [
            'entity' => 'student', 'mode' => 'all_filtered', 'filter_context' => $token,
        ]);

        $response->assertOk()
            ->assertJsonPath('entity', 'student')
            ->assertJsonPath('mode', 'all_filtered')
            ->assertJsonPath('count', 1);
        $this->assertArrayNotHasKey('page', json_decode(base64_decode(strtr($token, '-_', '+/')), true));
        $this->assertDatabaseCount('audit_records', 0);
    }

    public function test_duplicate_unknown_and_wrong_entity_input_are_atomic_rejections(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        $student = AdminBulkFixtures::eligibleStudent();
        $payload = ['entity' => 'student', 'action' => 'deactivate', 'mode' => 'current_page'];

        $this->actingAs($actor)->postJson(route('admin.students.bulk'), $payload + ['ids' => [$student->id, $student->id]])->assertStatus(422);
        $this->actingAs($actor)->postJson(route('admin.students.bulk'), $payload + ['ids' => [$student->id, 999999]])->assertStatus(422);
        $this->actingAs($actor)->postJson(route('admin.students.bulk'), array_merge($payload, ['entity' => 'teacher', 'ids' => [$student->id]]))->assertStatus(422);
        $this->actingAs($actor)->postJson(route('admin.students.bulk'), array_merge($payload, ['action' => 'archive', 'ids' => [$student->id]]))->assertStatus(422);

        $this->assertSame('active', $student->refresh()->status->value);
        $this->assertSame(4, AuditRecord::query()->where('event_type', AuditRecord::EVENT_REJECTED_OPERATION)->count());
        $this->assertSame(0, AuditRecord::query()->where('event_type', AuditRecord::EVENT_EXECUTION)->count());
    }

    public function test_tampered_filter_context_is_rejected_without_resolving_or_mutating_records(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        $student = AdminBulkFixtures::student(['status' => 'active']);
        $list = app(StudentListQuery::class)->forInput(['status' => 'active'], $actor);
        $token = app(SelectionContextService::class)->token($list->selection_context);
        $decoded = json_decode(base64_decode(strtr($token, '-_', '+/')), true, 512, JSON_THROW_ON_ERROR);
        $decoded['filters']['status'] = 'inactive';
        $tampered = rtrim(strtr(base64_encode(json_encode($decoded, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

        $response = $this->actingAs($actor)->postJson(route('admin.students.bulk'), [
            'entity' => 'student', 'action' => 'deactivate', 'mode' => 'all_filtered', 'filter_context' => $tampered,
        ]);

        $response->assertStatus(422);
        $this->assertSame('active', $student->refresh()->status->value);
        $this->assertSame(0, AuditRecord::query()->where('event_type', AuditRecord::EVENT_EXECUTION)->count());
    }

    public function test_each_item_is_rechecked_and_successful_items_commit_when_another_fails(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        $succeeded = AdminBulkFixtures::student(['status' => 'active']);
        $failed = AdminBulkFixtures::student(['status' => 'paused']);

        $response = $this->actingAs($actor)->postJson(route('admin.students.bulk'), [
            'entity' => 'student', 'action' => 'deactivate', 'mode' => 'current_page',
            'ids' => [$succeeded->id, $failed->id], 'selection_reference' => 'task-2-12-partial',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.succeeded', 1)
            ->assertJsonPath('data.failed', 1)
            ->assertJsonPath('data.skipped', 0)
            ->assertJsonPath('data.outcome', 'partial_success');
        $this->assertSame('inactive', $succeeded->refresh()->status->value);
        $this->assertSame('paused', $failed->refresh()->status->value);
        $this->assertSame(1, AuditRecord::query()->where('event_type', AuditRecord::EVENT_EXECUTION)->count());
    }

    public function test_disappeared_after_selection_is_reported_as_a_skip_and_not_substituted(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        $student = AdminBulkFixtures::eligibleStudent();
        $command = new BulkCommand('student', 'deactivate', 'current_page', [$student->id, 987654], actor_id: $actor->id);

        $result = app(\App\Services\BulkActionService::class)->execute($command, $actor);

        $this->assertSame(2, $result->total);
        $this->assertSame(1, $result->succeeded);
        $this->assertSame(1, $result->skipped);
        $this->assertSame(0, $result->failed);
        $this->assertSame(987654, $result->items[1]->id);
        $this->assertSame('not_found', $result->items[1]->reason_identifier);
        $this->assertSame('inactive', $student->refresh()->status->value);
    }

    public function test_eligible_and_protected_deletions_have_independent_item_outcomes(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        $eligible = AdminBulkFixtures::eligibleTeacher();
        $protected = AdminBulkFixtures::protectedTeacher();

        $response = $this->actingAs($actor)->postJson(route('admin.teachers.bulk'), [
            'entity' => 'teacher', 'action' => 'delete', 'mode' => 'current_page',
            'ids' => [$eligible->id, $protected->id], 'selection_reference' => 'task-2-12-delete',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.succeeded', 1)
            ->assertJsonPath('data.failed', 1)
            ->assertJsonPath('data.outcome', 'partial_success');
        $this->assertDatabaseMissing('teachers', ['id' => $eligible->id]);
        $this->assertDatabaseHas('teachers', ['id' => $protected->id]);
        $this->assertSame(1, $protected->enrollments()->count());
        $this->assertSame(1, AuditRecord::query()->where('event_type', AuditRecord::EVENT_EXECUTION)->count());
        $this->assertSame([$protected->id], AuditRecord::query()->sole()->reason_identifiers);
    }

    public function test_collection_authorization_happens_before_all_filtered_resolution(): void
    {
        $actor = AdminBulkFixtures::unauthorizedPolicyActor();
        $context = new Filter_Context('teachers', null, [], 'full_name', 'asc', 'tampered-fingerprint');
        $command = new BulkCommand(
            entity: 'teacher', action: 'deactivate', mode: 'all_filtered', filter_context: $context, actor_id: $actor->id,
        );

        $this->expectException(AuthorizationException::class);
        (new BulkActionService(selectionContexts: app(SelectionContextService::class)))->execute($command, $actor);
    }

    public function test_all_skipped_item_processing_is_partial_and_audited_once(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        $missingId = 987654;
        $command = new BulkCommand('student', 'deactivate', 'current_page', [$missingId], actor_id: $actor->id);

        $result = app(BulkActionService::class)->execute($command, $actor);

        $this->assertSame([1, 0, 1, 0], [$result->total, $result->succeeded, $result->skipped, $result->failed]);
        $this->assertSame('partial_success', $result->outcome->value);
        $this->assertSame($missingId, $result->items[0]->id);
        $this->assertSame('not_found', $result->items[0]->reason_identifier);
        $this->assertSame(1, AuditRecord::query()->where('event_type', AuditRecord::EVENT_EXECUTION)->count());
        $this->assertSame(0, AuditRecord::query()->where('event_type', AuditRecord::EVENT_REJECTED_OPERATION)->count());
    }

    public function test_execution_audit_is_private_exactly_once_and_result_ids_are_conserved(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        $first = AdminBulkFixtures::student(['status' => 'active']);
        $second = AdminBulkFixtures::student(['status' => 'paused']);

        $response = $this->actingAs($actor)->postJson(route('admin.students.bulk'), [
            'entity' => 'student', 'action' => 'deactivate', 'mode' => 'current_page',
            'ids' => [$first->id, $second->id], 'selection_reference' => 'private-selection',
            'phone' => '09120000000', 'notes' => 'private note',
            'raw_payload' => ['password' => 'secret'],
        ]);

        $payload = $response->assertOk()->json('data');
        $this->assertSame($payload['total'], $payload['succeeded'] + $payload['skipped'] + $payload['failed']);
        $this->assertSame([$first->id, $second->id], array_column($payload['items'], 'id'));
        $this->assertSame(1, AuditRecord::query()->where('event_type', AuditRecord::EVENT_EXECUTION)->count());
        $this->assertSame(0, AuditRecord::query()->where('event_type', AuditRecord::EVENT_REJECTED_OPERATION)->count());

        $audit = AuditRecord::query()->sole();
        $this->assertSame(['selection_reference' => 'private-selection'], $audit->metadata);
        $this->assertArrayNotHasKey('phone', $audit->toArray());
        $this->assertArrayNotHasKey('notes', $audit->metadata);
        $this->assertArrayNotHasKey('raw_payload', $audit->metadata);
        $this->assertArrayNotHasKey('password', $audit->metadata);
    }
}
