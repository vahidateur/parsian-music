<?php

namespace App\DTOs;

/**
 * Immutable, canonical state for an operational list request.
 *
 * @phpstan-type QueryParameters array<string, string|int|bool>
 */
final readonly class ListContext
{
    /**
     * @param array<string, string|int|bool> $filters
     * @param QueryParameters $normalized_query
     */
    public function __construct(
        public string $entity,
        public ?string $search,
        public array $filters,
        public string $sort,
        public string $direction,
        public int $page,
        public int $per_page,
        public array $normalized_query,
        public string $context_fingerprint,
    ) {}

    /**
     * @return QueryParameters
     */
    public function queryParameters(): array
    {
        return $this->normalized_query;
    }
}
