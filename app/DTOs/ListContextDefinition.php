<?php

namespace App\DTOs;

use InvalidArgumentException;

/**
 * Immutable, server-side contract of one Operational_List: which columns are
 * sortable, which filters exist, and which page sizes are accepted.
 *
 * The definition is the only source of truth used by ListContextNormalizer, so
 * no raw user input can ever reach an orderBy clause or a query string.
 */
final readonly class ListContextDefinition
{
    public const DEFAULT_PER_PAGE = 20;

    public const DIRECTION_ASC = 'asc';

    public const DIRECTION_DESC = 'desc';

    /** @var array<int, string> */
    public array $sortable;

    /** @var array<string, ListFilterDefinition> */
    public array $filters;

    /** @var array<int, int> */
    public array $per_page_allow_list;

    /**
     * @param array<int, string> $sortable whitelist of sortable column names
     * @param array<int, ListFilterDefinition> $filters
     * @param array<int, int> $perPageAllowList
     */
    public function __construct(
        public string $entity,
        array $sortable,
        public string $default_sort,
        public string $default_direction = self::DIRECTION_DESC,
        array $filters = [],
        public int $default_per_page = self::DEFAULT_PER_PAGE,
        array $perPageAllowList = [self::DEFAULT_PER_PAGE],
        public string $tie_breaker = 'id',
    ) {
        $this->sortable = array_values(array_unique($sortable));

        if (! in_array($default_sort, $this->sortable, true)) {
            throw new InvalidArgumentException(
                "Default sort column [{$default_sort}] is not whitelisted for list [{$entity}]."
            );
        }

        if (! in_array($default_direction, [self::DIRECTION_ASC, self::DIRECTION_DESC], true)) {
            throw new InvalidArgumentException(
                "Default sort direction [{$default_direction}] is invalid for list [{$entity}]."
            );
        }

        $mapped = [];
        foreach ($filters as $filter) {
            $mapped[$filter->name] = $filter;
        }
        $this->filters = $mapped;

        $allowList = array_values(array_unique($perPageAllowList));
        if (! in_array($default_per_page, $allowList, true)) {
            $allowList[] = $default_per_page;
        }
        sort($allowList);
        $this->per_page_allow_list = $allowList;
    }

    public function isSortable(string $column): bool
    {
        return in_array($column, $this->sortable, true);
    }

    public function filter(string $name): ?ListFilterDefinition
    {
        return $this->filters[$name] ?? null;
    }

    public function allowsPerPage(int $perPage): bool
    {
        return in_array($perPage, $this->per_page_allow_list, true);
    }
}
