<?php

namespace Tests\Feature\Admin;

use App\DTOs\BulkCommand;
use App\DTOs\BulkItemResultData;
use App\DTOs\BulkResultData;
use App\Enums\BulkActionEnum;
use App\Enums\BulkEntityEnum;
use App\Enums\BulkItemResultStatusEnum;
use App\Enums\BulkSelectionModeEnum;
use App\Models\AuditRecord;
use App\Models\User;
use App\Services\AuditRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditRecordServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_records_schema_is_additive_and_contains_required_fields(): void
    {
        $this->assertTrue(Schema::hasTable('audit_records'));
        $this->assertTrue(Schema::hasColumns('audit_records', [
            'actor_id',
            'event_type',
            'entity_type',
            'action',
            'selection_mode',
            'context_fingerprint',
            'total',
            'succeeded',
            'skipped',
            'failed',
            'reason_categories',
            'reason_identifiers',
            'metadata',
            'occurred_at',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_execution_audit_persists_aggregate_and_item_reason_data(): void
    {
        $actor = User::factory()->create();
        $command = new BulkCommand(
            entity: BulkEntityEnum::Student,
            action: BulkActionEnum::Delete,
            mode: BulkSelectionModeEnum::CurrentPage,
            ids: [11, 12],
            actor_id: $actor->id,
            request_fingerprint: 'request-fingerprint',
            selection_reference: 'selection-reference',
        );
        $result = new BulkResultData(
            entity: BulkEntityEnum::Student,
            action: BulkActionEnum::Delete,
            mode: BulkSelectionModeEnum::CurrentPage,
            total: 2,
            succeeded: 1,
            skipped: 0,
            failed: 1,
            items: [
                new BulkItemResultData(11, BulkItemResultStatusEnum::Succeeded),
                new BulkItemResultData(
                    12,
                    BulkItemResultStatusEnum::Failed,
                    'protected_dependency',
                    'Protected dependency',
                ),
            ],
            context_fingerprint: 'context-fingerprint',
        );

        $record = (new AuditRecordService)->recordExecution($command, $result);

        $this->assertSame(AuditRecord::EVENT_EXECUTION, $record->event_type);
        $this->assertSame($actor->id, $record->actor_id);
        $this->assertSame('student', $record->entity_type);
        $this->assertSame('delete', $record->action);
        $this->assertSame('current_page', $record->selection_mode);
        $this->assertSame('context-fingerprint', $record->context_fingerprint);
        $this->assertSame([12], $record->reason_categories['protected_dependency']);
        $this->assertSame([12], $record->reason_identifiers);
        $this->assertSame(['selection_reference' => 'selection-reference'], $record->metadata);
        $this->assertSame([1, 0, 1], [$record->succeeded, $record->skipped, $record->failed]);
        $this->assertNotNull($record->occurred_at);
        $this->assertSame(1, AuditRecord::query()->count());
    }

    public function test_rejected_operation_is_one_event_and_accepts_only_safe_context(): void
    {
        $actor = User::factory()->create();

        $record = (new AuditRecordService)->recordRejectedOperation([
            'actor_id' => $actor->id,
            'entity' => 'student',
            'action' => 'delete',
            'mode' => 'current_page',
            'context_fingerprint' => 'context-fingerprint',
            'selection_reference' => 'selection-reference',
            'reason_category' => 'invalid_context',
            'reason_identifier' => 12,
            'phone' => '09120000000',
            'notes' => 'private note',
            'password' => 'secret',
            'raw_payload' => ['phone' => '09120000000'],
            'metadata' => [
                'validation_fields' => ['ids', 'context'],
                'phone' => '09120000000',
                'notes' => 'private note',
                'credential' => 'secret',
            ],
        ]);

        $this->assertSame(AuditRecord::EVENT_REJECTED_OPERATION, $record->event_type);
        $this->assertSame('invalid_context', array_key_first($record->reason_categories));
        $this->assertSame([12], $record->reason_identifiers);
        $this->assertSame([
            'selection_reference' => 'selection-reference',
            'validation_fields' => ['ids', 'context'],
        ], $record->metadata);
        $this->assertSame(1, AuditRecord::query()->count());
        $this->assertArrayNotHasKey('phone', $record->toArray());
        $this->assertArrayNotHasKey('notes', $record->metadata);
        $this->assertArrayNotHasKey('raw_payload', $record->metadata);
        $this->assertArrayNotHasKey('credential', $record->metadata);
    }

    public function test_metadata_filter_discards_unknown_nested_and_sensitive_values(): void
    {
        $filtered = (new AuditRecordService)->filterMetadata([
            'selection_reference' => 'selection-reference',
            'validation_fields' => ['ids', ['nested'], 'notes', 'bad field'],
            'phone' => '09120000000',
            'notes' => 'private note',
            'password' => 'secret',
            'model_attributes' => ['status' => 'active'],
        ]);

        $this->assertSame([
            'selection_reference' => 'selection-reference',
            'validation_fields' => ['ids'],
        ], $filtered);
    }
}
