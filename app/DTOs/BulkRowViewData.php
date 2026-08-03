<?php

namespace App\DTOs;

use InvalidArgumentException;

/** Immutable server-provided row contract for bulk selection controls. */
final readonly class BulkRowViewData
{
    /**
     * @param int|string $id stable persisted row identifier
     * @param array<string, mixed> $display_data query-resolved display values
     * @param array<int, string|int> $allowed_actions bulk abilities for this row
     */
    public array $allowed_actions;

    public function __construct(
        public int|string $id,
        public array $display_data,
        array $allowed_actions,
        public bool $selectable,
        public string $entity_key,
    ) {
        if ((is_int($id) && $id <= 0) || (is_string($id) && trim($id) === '')) {
            throw new InvalidArgumentException('Bulk row IDs must be stable identifiers.');
        }

        if (trim($entity_key) === '') {
            throw new InvalidArgumentException('Bulk row entity keys are required.');
        }

        $this->allowed_actions = array_values(array_unique(array_map(
            static fn (mixed $action): string => trim((string) $action),
            array_filter($allowed_actions, static fn (mixed $action): bool => is_string($action) || is_int($action)),
        )));
    }

    public function allows(string $action): bool
    {
        return in_array($action, $this->allowed_actions, true);
    }
}
