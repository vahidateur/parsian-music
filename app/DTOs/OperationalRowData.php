<?php

namespace App\DTOs;

/**
 * One rendered row of an Operational_List.
 *
 * The row carries only values that were resolved from persisted data by the
 * query layer, so a Blade template never touches an Eloquent relation (and
 * therefore never triggers a query while rendering).
 */
final readonly class OperationalRowData
{
    /**
     * @param int|string $id stable unique record key (list tie-breaker)
     * @param string $label primary human label of the record
     * @param array<string, string|int|null> $fields scalar cells keyed by column name
     * @param string|null $status persisted status value, null when the entity has none
     * @param array<string, string|null> $relations already resolved relation labels
     * @param array<int, string> $allowed_actions abilities the current actor holds for this row
     */
    public function __construct(
        public int|string $id,
        public string $label,
        public array $fields = [],
        public ?string $status = null,
        public array $relations = [],
        public array $allowed_actions = [],
        public bool $selectable = false,
    ) {}

    public function allows(string $action): bool
    {
        return in_array($action, $this->allowed_actions, true);
    }

    public function field(string $key): string|int|null
    {
        return $this->fields[$key] ?? null;
    }

    public function relation(string $key): ?string
    {
        return $this->relations[$key] ?? null;
    }
}
