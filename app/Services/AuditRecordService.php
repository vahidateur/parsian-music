<?php

namespace App\Services;

use App\DTOs\BulkCommand;
use App\DTOs\BulkResultData;
use App\Enums\BulkItemResultStatusEnum;
use App\Models\AuditRecord;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Persists one privacy-filtered audit event for a bulk operation. */
class AuditRecordService
{
    /** Only stable selection metadata and validation field names may be persisted. */
    private const ALLOWED_METADATA_KEYS = [
        'selection_reference',
        'validation_fields',
    ];

    private const FORBIDDEN_VALIDATION_FIELDS = [
        'phone',
        'notes',
        'password',
        'password_confirmation',
        'credential',
        'credentials',
        'raw_payload',
    ];

    public function recordExecution(
        BulkCommand $command,
        BulkResultData $result,
        array $metadata = [],
    ): AuditRecord {
        if ($command->entity !== $result->entity
            || $command->action !== $result->action
            || $command->mode !== $result->mode) {
            throw new InvalidArgumentException('Audit command and result context must match.');
        }

        $reasonCategories = [];
        $reasonIdentifiers = [];

        foreach ($result->items as $item) {
            if ($item->status === BulkItemResultStatusEnum::Succeeded) {
                continue;
            }

            if ($item->reason_category !== null) {
                $reasonCategories[$item->reason_category] ??= [];
                $reasonCategories[$item->reason_category][] = $item->id;
            }
            $reasonIdentifiers[] = $item->id;
        }

        $metadata['selection_reference'] ??= $command->selection_reference;

        return DB::transaction(fn (): AuditRecord => AuditRecord::create([
            'actor_id' => $command->actor_id,
            'event_type' => AuditRecord::EVENT_EXECUTION,
            'entity_type' => $result->entity->value,
            'action' => $result->action->value,
            'selection_mode' => $result->mode->value,
            'context_fingerprint' => $result->context_fingerprint
                ?? $command->filter_context?->context_fingerprint,
            'total' => $result->total,
            'succeeded' => $result->succeeded,
            'skipped' => $result->skipped,
            'failed' => $result->failed,
            'reason_categories' => $reasonCategories === [] ? null : $reasonCategories,
            'reason_identifiers' => $reasonIdentifiers === [] ? null : array_values($reasonIdentifiers),
            'metadata' => $this->filterMetadata($metadata) ?: null,
            'occurred_at' => now(),
        ]));
    }

    /**
     * Record a rejected operation without retaining the rejected request body.
     *
     * The array form is intentionally limited to audit context keys, allowing
     * validation failures to be audited before a valid BulkCommand exists.
     *
     * @param BulkCommand|array<string, mixed>|null $command
     */
    public function recordRejectedOperation(
        BulkCommand|array|null $command = null,
        ?string $reasonCategory = null,
        int|string|null $reasonIdentifier = null,
        array $metadata = [],
    ): AuditRecord {
        $context = $this->rejectedContext($command);
        $reasonCategory ??= $context['reason_category'];
        $reasonIdentifier ??= $context['reason_identifier'];
        if (is_array($context['metadata'] ?? null)) {
            $metadata = array_merge($context['metadata'], $metadata);
        }
        $metadata['selection_reference'] ??= $context['selection_reference'];

        $reasonCategories = $reasonCategory === null ? null : [$reasonCategory => $reasonIdentifier === null
            ? []
            : [$reasonIdentifier]];

        return DB::transaction(fn (): AuditRecord => AuditRecord::create([
            'actor_id' => $context['actor_id'],
            'event_type' => AuditRecord::EVENT_REJECTED_OPERATION,
            'entity_type' => $context['entity_type'],
            'action' => $context['action'],
            'selection_mode' => $context['selection_mode'],
            'context_fingerprint' => $context['context_fingerprint'],
            'total' => 0,
            'succeeded' => 0,
            'skipped' => 0,
            'failed' => 0,
            'reason_categories' => $reasonCategories,
            'reason_identifiers' => $reasonIdentifier === null ? null : [$reasonIdentifier],
            'metadata' => $this->filterMetadata($metadata) ?: null,
            'occurred_at' => now(),
        ]));
    }

    /**
     * Filter metadata by key and value shape; unknown keys and nested payloads
     * are discarded rather than copied into the audit record.
     *
     * @param array<string, mixed> $metadata
     * @return array<string, string|array<int, string>>
     */
    public function filterMetadata(array $metadata): array
    {
        $filtered = [];

        foreach (self::ALLOWED_METADATA_KEYS as $key) {
            if (! array_key_exists($key, $metadata)) {
                continue;
            }

            if ($key === 'selection_reference' && is_string($metadata[$key])) {
                $value = trim($metadata[$key]);
                if ($value !== '' && mb_strlen($value) <= 255) {
                    $filtered[$key] = $value;
                }
                continue;
            }

            if ($key === 'validation_fields' && is_array($metadata[$key])) {
                $fields = [];
                foreach ($metadata[$key] as $field) {
                    if (! is_string($field)) {
                        continue;
                    }
                    $field = trim($field);
                    if ($field !== ''
                        && ! in_array(strtolower($field), self::FORBIDDEN_VALIDATION_FIELDS, true)
                        && preg_match('/^[A-Za-z0-9_.-]+$/', $field) === 1) {
                        $fields[] = $field;
                    }
                }
                $fields = array_values(array_unique($fields));
                if ($fields !== []) {
                    $filtered[$key] = array_slice($fields, 0, 50);
                }
            }
        }

        return $filtered;
    }

    /** @return array<string, mixed> */
    private function rejectedContext(BulkCommand|array|null $command): array
    {
        if ($command instanceof BulkCommand) {
            return [
                'actor_id' => $command->actor_id,
                'entity_type' => $command->entity->value,
                'action' => $command->action->value,
                'selection_mode' => $command->mode->value,
                'context_fingerprint' => $command->filter_context?->context_fingerprint,
                'selection_reference' => $command->selection_reference,
                'reason_category' => null,
                'reason_identifier' => null,
                'metadata' => [],
            ];
        }

        $command ??= [];
        $filterContext = $command['filter_context'] ?? null;
        $contextFingerprint = $command['context_fingerprint'] ?? null;
        if (is_array($filterContext)) {
            $contextFingerprint ??= $filterContext['context_fingerprint'] ?? null;
        }

        return [
            'actor_id' => is_int($command['actor_id'] ?? null) || is_string($command['actor_id'] ?? null)
                ? $command['actor_id']
                : null,
            'entity_type' => $this->scalarContext($command['entity_type'] ?? $command['entity'] ?? null),
            'action' => $this->scalarContext($command['action'] ?? null),
            'selection_mode' => $this->scalarContext($command['selection_mode'] ?? $command['mode'] ?? null),
            'context_fingerprint' => $this->scalarContext($contextFingerprint),
            'selection_reference' => $this->scalarContext($command['selection_reference'] ?? null),
            'reason_category' => $this->scalarContext($command['reason_category'] ?? null),
            'reason_identifier' => is_int($command['reason_identifier'] ?? null)
                || is_string($command['reason_identifier'] ?? null)
                ? $command['reason_identifier']
                : null,
            'metadata' => is_array($command['metadata'] ?? null) ? $command['metadata'] : [],
        ];
    }

    private function scalarContext(mixed $value): ?string
    {
        return is_string($value) || is_int($value) ? (string) $value : null;
    }
}
