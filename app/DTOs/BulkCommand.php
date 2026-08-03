<?php

namespace App\DTOs;

use App\Enums\BulkActionEnum;
use App\Enums\BulkEntityEnum;
use App\Enums\BulkSelectionModeEnum;
use InvalidArgumentException;
use JsonSerializable;

/** Immutable, validated input passed from a bulk request to an action service. */
final readonly class BulkCommand implements JsonSerializable
{
    /** @param array<int, int|string> $ids */
    public function __construct(
        BulkEntityEnum|string $entity,
        BulkActionEnum|string $action,
        BulkSelectionModeEnum|string $mode,
        array $ids = [],
        public ?FilterContext $filter_context = null,
        public int|string|null $actor_id = null,
        ?string $request_fingerprint = null,
        ?string $selection_reference = null,
    ) {
        $this->entity = $entity instanceof BulkEntityEnum ? $entity : BulkEntityEnum::from(trim($entity));
        $this->action = $action instanceof BulkActionEnum ? $action : BulkActionEnum::from(trim($action));
        $this->mode = $mode instanceof BulkSelectionModeEnum ? $mode : BulkSelectionModeEnum::from(trim($mode));
        $this->ids = self::ids($ids);
        if ($this->mode === BulkSelectionModeEnum::CurrentPage && $this->ids === []) {
            throw new InvalidArgumentException('Current-page bulk commands require at least one ID.');
        }
        if ($this->mode === BulkSelectionModeEnum::AllFiltered && ($this->filter_context === null || $this->ids !== [])) {
            throw new InvalidArgumentException('All-filtered commands require a context and no explicit IDs.');
        }
        if ($actor_id !== null) {
            self::identifier($actor_id, 'actor_id');
        }
        $this->request_fingerprint = self::optionalString($request_fingerprint, 'request_fingerprint');
        $this->selection_reference = self::optionalString($selection_reference, 'selection_reference');
    }

    public readonly BulkEntityEnum $entity;
    public readonly BulkActionEnum $action;
    public readonly BulkSelectionModeEnum $mode;
    /** @var array<int, int|string> */
    public readonly array $ids;
    public readonly ?string $request_fingerprint;
    public readonly ?string $selection_reference;

    /** @param array<int, int|string> $ids */
    private static function ids(array $ids): array
    {
        $normalized = [];
        $seen = [];
        foreach ($ids as $id) {
            $value = self::identifier($id, 'selection ID');
            $key = get_debug_type($value).':'.(string) $value;
            if (isset($seen[$key])) {
                throw new InvalidArgumentException('Bulk selection IDs must be unique.');
            }
            $seen[$key] = true;
            $normalized[] = $value;
        }
        return array_values($normalized);
    }

    private static function identifier(int|string $value, string $label): int|string
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            throw new InvalidArgumentException("{$label} must be a non-empty stable identifier.");
        }
        return $value;
    }

    private static function optionalString(?string $value, string $label): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException("{$label} cannot be empty.");
        }
        return $value;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'entity' => $this->entity->value,
            'action' => $this->action->value,
            'mode' => $this->mode->value,
            'ids' => $this->ids,
            'filter_context' => $this->filter_context?->toArray(),
            'actor_id' => $this->actor_id,
            'request_fingerprint' => $this->request_fingerprint,
            'selection_reference' => $this->selection_reference,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
