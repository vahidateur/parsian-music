<?php

namespace App\DTOs;

use App\Enums\BulkActionEnum;
use App\Enums\BulkEntityEnum;
use App\Enums\BulkItemResultStatusEnum;
use App\Enums\BulkResultOutcomeEnum;
use App\Enums\BulkSelectionModeEnum;
use InvalidArgumentException;
use JsonSerializable;

/** Immutable aggregate result with conserved counts and stable selection metadata. */
final readonly class BulkResultData implements JsonSerializable
{
    /** @param array<int, BulkItemResultData> $items */
    public function __construct(
        BulkEntityEnum|string $entity,
        BulkActionEnum|string $action,
        BulkSelectionModeEnum|string $mode,
        public int $total,
        public int $succeeded,
        public int $skipped,
        public int $failed,
        array $items = [],
        ?string $selection_reference = null,
        ?string $context_fingerprint = null,
        BulkResultOutcomeEnum|string|null $outcome = null,
    ) {
        $this->entity = $entity instanceof BulkEntityEnum ? $entity : BulkEntityEnum::from(trim($entity));
        $this->action = $action instanceof BulkActionEnum ? $action : BulkActionEnum::from(trim($action));
        $this->mode = $mode instanceof BulkSelectionModeEnum ? $mode : BulkSelectionModeEnum::from(trim($mode));
        foreach ([$total, $succeeded, $skipped, $failed] as $count) {
            if ($count < 0) {
                throw new InvalidArgumentException('Bulk result counts cannot be negative.');
            }
        }
        if ($total !== $succeeded + $skipped + $failed) {
            throw new InvalidArgumentException('Bulk result counts must conserve total.');
        }
        $normalized = [];
        $seen = [];
        foreach ($items as $item) {
            if (! $item instanceof BulkItemResultData) {
                throw new InvalidArgumentException('Bulk result items must be BulkItemResultData instances.');
            }
            $key = get_debug_type($item->id).':'.(string) $item->id;
            if (isset($seen[$key])) {
                throw new InvalidArgumentException('Bulk result item IDs must be unique.');
            }
            $seen[$key] = true;
            $normalized[] = $item;
        }
        if ($normalized !== [] && count($normalized) !== $total) {
            throw new InvalidArgumentException('Detailed bulk results must contain every processed item.');
        }
        if ($normalized === [] && $selection_reference === null && $context_fingerprint === null) {
            throw new InvalidArgumentException('Aggregate results require stable selection metadata.');
        }
        $expected = $failed > 0 || $skipped > 0
            ? BulkResultOutcomeEnum::PartialSuccess
            : BulkResultOutcomeEnum::CompleteSuccess;
        $given = $outcome === null ? $expected : ($outcome instanceof BulkResultOutcomeEnum
            ? $outcome
            : BulkResultOutcomeEnum::from(trim($outcome)));
        if ($given !== $expected) {
            throw new InvalidArgumentException('Bulk result outcome does not match item counts.');
        }
        $this->items = array_values($normalized);
        $this->selection_reference = self::optional($selection_reference, 'selection reference');
        $this->context_fingerprint = self::optional($context_fingerprint, 'context fingerprint');
        $this->outcome = $given;
    }

    public readonly BulkEntityEnum $entity;
    public readonly BulkActionEnum $action;
    public readonly BulkSelectionModeEnum $mode;
    public readonly ?string $selection_reference;
    public readonly ?string $context_fingerprint;
    /** @var array<int, BulkItemResultData> */
    public readonly array $items;
    public readonly BulkResultOutcomeEnum $outcome;

    private static function optional(?string $value, string $label): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? throw new InvalidArgumentException("{$label} cannot be empty.") : $value;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'entity' => $this->entity->value,
            'action' => $this->action->value,
            'mode' => $this->mode->value,
            'selection_reference' => $this->selection_reference,
            'context_fingerprint' => $this->context_fingerprint,
            'total' => $this->total,
            'succeeded' => $this->succeeded,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
            'outcome' => $this->outcome->value,
            'items' => array_map(
                static fn (BulkItemResultData $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
