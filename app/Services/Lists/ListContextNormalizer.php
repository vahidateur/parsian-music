<?php

namespace App\Services\Lists;

use App\DTOs\ListContext;
use App\DTOs\ListContextDefinition;
use App\Support\PersianTextNormalizer;

/**
 * Turns raw list request input into one canonical, immutable ListContext.
 *
 * The Persian/Arabic character map is owned by PersianTextNormalizer, so list
 * search and Record_Form persistence share one normalization contract.
 *
 * Contract:
 *   - search is trimmed, whitespace-collapsed, Persian/Arabic normalized and
 *     truncated to 100 characters; an empty result becomes null.
 *   - filters are typed and allow-listed; an unknown or invalid filter is
 *     ignored and the documented default is applied instead.
 *   - sort must be whitelisted per list, direction is only `asc|desc`, and both
 *     fall back to the explicit defaults of the definition.
 *   - per_page comes from the definition allow-list, default 20.
 *   - page is a positive integer, default 1.
 *
 * No raw user value ever leaves this class: everything exposed on the returned
 * context (sort, direction, filter keys, per_page) originates from the
 * server-side definition, so nothing can be injected into orderBy or a query
 * string.
 */
final class ListContextNormalizer
{
    public const MAX_SEARCH_LENGTH = 100;

    public const SEARCH_KEY = 'search';

    public const SORT_KEY = 'sort';

    public const DIRECTION_KEY = 'direction';

    public const PAGE_KEY = 'page';

    public const PER_PAGE_KEY = 'per_page';

    /**
     * @param array<string, mixed> $input raw request input (query string / form)
     */
    public function normalize(ListContextDefinition $definition, array $input): ListContext
    {
        $search = $this->normalizeSearch($input[self::SEARCH_KEY] ?? null);
        $filters = $this->normalizeFilters($definition, $input);
        $sort = $this->normalizeSort($definition, $input[self::SORT_KEY] ?? null);
        $direction = $this->normalizeDirection($definition, $input[self::DIRECTION_KEY] ?? null);
        $perPage = $this->normalizePerPage($definition, $input[self::PER_PAGE_KEY] ?? null);
        $page = $this->normalizePage($input[self::PAGE_KEY] ?? null);

        $normalizedQuery = $this->buildNormalizedQuery(
            $definition,
            $search,
            $filters,
            $sort,
            $direction,
            $page,
            $perPage,
        );

        return new ListContext(
            entity: $definition->entity,
            search: $search,
            filters: $filters,
            sort: $sort,
            direction: $direction,
            page: $page,
            per_page: $perPage,
            normalized_query: $normalizedQuery,
            context_fingerprint: $this->fingerprint($definition->entity, $normalizedQuery),
        );
    }

    /**
     * Canonical form of a raw search term, or null when nothing is searchable.
     */
    public function normalizeSearch(mixed $raw): ?string
    {
        if (! is_string($raw) && ! is_int($raw)) {
            return null;
        }

        $value = PersianTextNormalizer::canonical((string) $raw);
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        $value = trim($value);

        if (mb_strlen($value) > self::MAX_SEARCH_LENGTH) {
            $value = trim(mb_substr($value, 0, self::MAX_SEARCH_LENGTH));
        }

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string|int|bool>
     */
    private function normalizeFilters(ListContextDefinition $definition, array $input): array
    {
        $filters = [];

        foreach ($definition->filters as $name => $filter) {
            $accepted = array_key_exists($name, $input)
                ? $filter->accept($input[$name])
                : null;

            $value = $accepted ?? $filter->default;

            if ($value !== null) {
                $filters[$name] = $value;
            }
        }

        ksort($filters);

        return $filters;
    }

    private function normalizeSort(ListContextDefinition $definition, mixed $raw): string
    {
        if (is_string($raw) && $definition->isSortable(trim($raw))) {
            return trim($raw);
        }

        return $definition->default_sort;
    }

    private function normalizeDirection(ListContextDefinition $definition, mixed $raw): string
    {
        $value = is_string($raw) ? strtolower(trim($raw)) : null;

        return in_array($value, [ListContextDefinition::DIRECTION_ASC, ListContextDefinition::DIRECTION_DESC], true)
            ? $value
            : $definition->default_direction;
    }

    private function normalizePerPage(ListContextDefinition $definition, mixed $raw): int
    {
        $value = $this->toPositiveInt($raw);

        return $value !== null && $definition->allowsPerPage($value)
            ? $value
            : $definition->default_per_page;
    }

    private function normalizePage(mixed $raw): int
    {
        return $this->toPositiveInt($raw) ?? 1;
    }

    private function toPositiveInt(mixed $raw): ?int
    {
        if (is_bool($raw) || is_array($raw) || $raw === null) {
            return null;
        }

        $value = is_string($raw) ? trim($raw) : $raw;

        if (! is_numeric($value) || (string) (int) $value !== (string) $value) {
            return null;
        }

        return (int) $value > 0 ? (int) $value : null;
    }

    /**
     * Canonical query parameters: only values that differ from the documented
     * default are emitted, so the context round-trips through a URL without
     * carrying redundant or unnormalized input.
     *
     * @param array<string, string|int|bool> $filters
     * @return array<string, string|int|bool>
     */
    private function buildNormalizedQuery(
        ListContextDefinition $definition,
        ?string $search,
        array $filters,
        string $sort,
        string $direction,
        int $page,
        int $perPage,
    ): array {
        $query = [];

        if ($search !== null) {
            $query[self::SEARCH_KEY] = $search;
        }

        foreach ($filters as $name => $value) {
            if ($definition->filter($name)?->default === $value) {
                continue;
            }

            $query[$name] = $value;
        }

        if ($sort !== $definition->default_sort) {
            $query[self::SORT_KEY] = $sort;
        }

        if ($direction !== $definition->default_direction) {
            $query[self::DIRECTION_KEY] = $direction;
        }

        if ($perPage !== $definition->default_per_page) {
            $query[self::PER_PAGE_KEY] = $perPage;
        }

        if ($page > 1) {
            $query[self::PAGE_KEY] = $page;
        }

        ksort($query);

        return $query;
    }

    /**
     * @param array<string, string|int|bool> $normalizedQuery
     */
    private function fingerprint(string $entity, array $normalizedQuery): string
    {
        return hash('sha256', $entity . '|' . json_encode(
            $normalizedQuery,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }
}
