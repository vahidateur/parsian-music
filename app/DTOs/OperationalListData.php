<?php

namespace App\DTOs;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

/**
 * Everything a Blade template needs to render one Operational_List.
 *
 * The paginator is kept for link generation only; rows are pre-mapped
 * OperationalRowData objects, so the view performs no query and holds no
 * business rule. Sort whitelist, filter options, empty mode and policy flags
 * all come from the server-side list contract.
 */
final readonly class OperationalListData
{
    /** The list has at least one matching record. */
    public const EMPTY_MODE_NONE = 'none';

    /** No record exists at all for this entity. */
    public const EMPTY_MODE_NO_RECORDS = 'no_records';

    /** Records exist but none matches the applied List_Context. */
    public const EMPTY_MODE_NO_MATCHES = 'no_matches';

    /**
     * @param array<int, OperationalRowData> $rows
     * @param array<string, array<int, array{value: string|int|bool, label: string}>> $filter_options
     * @param array<int, string> $sortable server-side sort whitelist
     * @param array<string, bool> $policy_flags list-level abilities (create, ...)
     */
    public function __construct(
        public ListContext $context,
        public LengthAwarePaginator $paginator,
        public array $rows,
        public int $total,
        public array $filter_options,
        public array $sortable,
        public string $default_sort,
        public string $default_direction,
        public string $empty_mode,
        public bool $has_active_context,
        public array $policy_flags = [],
        /** @var array<int, BulkRowViewData> */
        public array $bulk_rows = [],
        public ?Filter_Context $selection_context = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->empty_mode !== self::EMPTY_MODE_NONE;
    }

    public function allows(string $ability): bool
    {
        return $this->policy_flags[$ability] ?? false;
    }

    public function isSortable(string $column): bool
    {
        return in_array($column, $this->sortable, true);
    }

    /**
     * @return array<int, array{value: string|int|bool, label: string}>
     */
    public function options(string $filter): array
    {
        return $this->filter_options[$filter] ?? [];
    }

    /**
     * Currently applied normalized value of a filter, for rendering controls.
     */
    public function filter(string $name): string|int|bool|null
    {
        return $this->context->filters[$name] ?? null;
    }

    /**
     * @return array<string, string|int|bool>
     */
    public function queryParameters(): array
    {
        return $this->context->queryParameters();
    }

    /**
     * Context a sort link must carry: everything except the sort target itself
     * and the page, so re-sorting keeps search/filters and restarts paging.
     *
     * @return array<string, string|int|bool>
     */
    public function sortParameters(): array
    {
        return Arr::except($this->queryParameters(), ['sort', 'direction', 'page']);
    }

    /**
     * Context a filter form must resubmit as hidden fields: sort, direction and
     * page size, so submitting a search never silently drops the applied order.
     *
     * @return array<string, string|int|bool>
     */
    public function formParameters(): array
    {
        return Arr::except(
            $this->queryParameters(),
            array_merge(['search', 'page'], array_keys($this->context->filters))
        );
    }

    /**
     * Filter options prepared for a native `<select>`: string values (so a
     * boolean filter renders as `1`/`0` instead of an empty attribute) plus the
     * currently applied normalized selection.
     *
     * @return array<int, array{value: string, label: string, selected: bool}>
     */
    public function renderableOptions(string $filter): array
    {
        $applied = $this->filter($filter);
        $applied = $applied === null ? null : $this->asParameter($applied);

        return array_map(
            fn (array $option): array => [
                'value' => $this->asParameter($option['value']),
                'label' => $option['label'],
                'selected' => $applied !== null && $applied === $this->asParameter($option['value']),
            ],
            $this->options($filter)
        );
    }

    private function asParameter(string|int|bool $value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }
}
