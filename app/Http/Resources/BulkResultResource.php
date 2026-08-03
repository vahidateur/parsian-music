<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\BulkItemResultData;
use App\DTOs\BulkResultData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Lang;
use LogicException;

/** @mixin BulkResultData */
final class BulkResultResource extends JsonResource
{
    /** @var array<string, string> */
    private const REASON_KEYS = [
        'not_found' => 'not_found',
        'unauthorized' => 'unauthorized',
        'protected_dependency' => 'protected_dependency',
        'processing_error' => 'processing_error',
        'invalid_action' => 'invalid_action',
        'invalid_status' => 'invalid_status',
        'invalid_transition' => 'invalid_transition',
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $result = $this->result();

        return [
            'entity' => $result->entity->value,
            'action' => $result->action->value,
            'mode' => $result->mode->value,
            'selection_reference' => $result->selection_reference,
            'context_fingerprint' => $result->context_fingerprint,
            'total' => $result->total,
            'succeeded' => $result->succeeded,
            'skipped' => $result->skipped,
            'failed' => $result->failed,
            'outcome' => $result->outcome->value,
            'items' => array_map(fn (BulkItemResultData $item): array => $this->item($item), $result->items),
        ];
    }

    private function result(): BulkResultData
    {
        if (! $this->resource instanceof BulkResultData) {
            throw new LogicException('BulkResultResource requires BulkResultData.');
        }

        return $this->resource;
    }

    /** @return array<string, mixed> */
    private function item(BulkItemResultData $item): array
    {
        $payload = $item->toArray();

        if ($item->reason_category === null) {
            return $payload;
        }

        $reasonKey = self::REASON_KEYS[$item->reason_category]
            ?? self::REASON_KEYS[$item->reason_identifier ?? '']
            ?? 'generic';
        $translation = 'admin.bulk_result.reasons.'.$reasonKey;

        return [
            'id' => $item->id,
            'status' => $item->status->value,
            'reason' => [
                'category' => $this->translate($translation.'.category', $item->reason_category),
                'message' => $this->translate($translation.'.message', $item->reason_message),
                'identifier' => $item->reason_identifier,
            ],
        ];
    }

    private function translate(string $key, string $fallback): string
    {
        return Lang::has($key) ? __($key) : $fallback;
    }
}
