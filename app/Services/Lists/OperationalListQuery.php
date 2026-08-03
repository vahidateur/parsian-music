<?php

namespace App\Services\Lists;

use App\DTOs\BulkRowViewData;
use App\DTOs\Filter_Context;
use App\DTOs\ListContext;
use App\DTOs\ListContextDefinition;
use App\DTOs\OperationalListData;
use App\DTOs\OperationalRowData;
use App\Enums\BulkActionEnum;
use App\Services\SelectionContextService;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared implementation of the Operational_List query contract.
 *
 * Responsibilities:
 *   - consume an immutable ListContext (already normalized by the normalizer);
 *   - map allow-listed filters and whitelisted sort columns to bound query
 *     expressions (no raw string is ever concatenated into SQL);
 *   - append the stable unique-key tie-breaker to every order so paging is
 *     deterministic across requests;
 *   - eager-load every relation the row DTO reads, so the query count stays
 *     bounded and no query can run inside Blade;
 *   - paginate with the contracted page size and preserve the canonical
 *     context in every generated link;
 *   - map each Eloquent record to an OperationalRowData and return one
 *     OperationalListData with total count, filter options, sort whitelist,
 *     empty mode and policy flags.
 */
abstract class OperationalListQuery
{
    public function __construct(
        protected readonly ListContextNormalizer $normalizer,
        protected readonly SelectionContextService $selectionContextService,
    ) {}

    /** Server-side contract of this list (sortable whitelist, filters, page size). */
    abstract public function definition(): ListContextDefinition;

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** Base query including every relation the row DTO reads. */
    abstract protected function baseQuery(): Builder;

    abstract protected function toRow(Model $record, ListPolicyResolver $policy): OperationalRowData;

    /**
     * Normalize raw request input and run the list in one step.
     *
     * @param array<string, mixed> $input
     */
    public function forInput(array $input, ?Authenticatable $actor = null): OperationalListData
    {
        return $this->get($this->normalizer->normalize($this->definition(), $input), $actor);
    }

    public function get(ListContext $context, ?Authenticatable $actor = null): OperationalListData
    {
        $definition = $this->definition();
        $policy = new ListPolicyResolver($actor);

        $query = $this->baseQuery();

        if ($context->search !== null) {
            $this->applySearch($query, $context->search);
        }

        foreach ($context->filters as $name => $value) {
            $this->applyFilter($query, $name, $value);
        }

        $this->applyOrder($query, $context, $definition);

        $paginator = $query
            ->paginate($context->per_page, ['*'], ListContextNormalizer::PAGE_KEY, $context->page)
            ->withQueryString();

        // Canonical context wins over any raw query parameter echoed back by
        // withQueryString(), so pagination links always carry normalized values.
        $paginator->appends($context->queryParameters());

        $rows = [];
        $bulkRows = [];
        foreach ($paginator->items() as $record) {
            $row = $this->toRow($record, $policy);
            $rows[] = $row;
            $bulkRows[] = $this->toBulkRow($record, $row, $policy);
        }

        return new OperationalListData(
            context: $context,
            paginator: $paginator,
            rows: $rows,
            total: $paginator->total(),
            filter_options: $this->filterOptions(),
            sortable: $definition->sortable,
            default_sort: $definition->default_sort,
            default_direction: $definition->default_direction,
            empty_mode: $this->emptyMode($paginator->total(), $context, $definition),
            has_active_context: $this->hasActiveContext($context, $definition),
            policy_flags: $policy->flags($this->listAbilities(), $this->modelClass()),
            bulk_rows: $bulkRows,
            selection_context: $this->selectionContextService->create($context),
        );
    }

    /**
     * Apply the normalized search term. Lists without a searchable column
     * intentionally do nothing.
     */
    protected function applySearch(Builder $query, string $search): void {}

    /**
     * Apply one allow-listed filter. `$name` always comes from the definition
     * and `$value` is already cast to the declared type.
     */
    protected function applyFilter(Builder $query, string $name, string|int|bool $value): void {}

    /**
     * Sort keys that are not a column of the list table.
     *
     * @return array<string, string|Closure> sort key => qualified column or correlated sub-select
     */
    protected function sortMap(): array
    {
        return [];
    }

    /**
     * @return array<string, array<int, array{value: string|int|bool, label: string}>>
     */
    protected function filterOptions(): array
    {
        return [];
    }

    /** List-level abilities exposed to the view as policy flags. */
    protected function listAbilities(): array
    {
        return ['viewAny', 'create'];
    }

    /** Row-level abilities evaluated for every rendered record. */
    protected function rowAbilities(): array
    {
        return ['view', 'update', 'delete'];
    }

    /** Whether a row may take part in a Bulk_Action selection set. */
    protected function isSelectable(Model $record): bool
    {
        return false;
    }

    /** Stable singular key used by the bulk request boundary. */
    protected function bulkEntityKey(): string
    {
        return rtrim($this->definition()->entity, 's');
    }

    /**
     * Bulk status actions use the existing update policy; deletion uses delete.
     * This is display data only and never replaces endpoint authorization.
     *
     * @return array<int, string>
     */
    protected function bulkActions(Model $record, ListPolicyResolver $policy): array
    {
        $actions = [];

        if ($policy->allows('update', $record)) {
            $actions[] = BulkActionEnum::Activate->value;
            $actions[] = BulkActionEnum::Deactivate->value;
        }

        if ($policy->allows('delete', $record)) {
            $actions[] = BulkActionEnum::Delete->value;
        }

        return $actions;
    }

    protected function toBulkRow(
        Model $record,
        OperationalRowData $row,
        ListPolicyResolver $policy,
    ): BulkRowViewData {
        $actions = $this->bulkActions($record, $policy);

        return new BulkRowViewData(
            id: $row->id,
            display_data: [
                'label' => $row->label,
                'fields' => $row->fields,
                'status' => $row->status,
                'relations' => $row->relations,
            ],
            allowed_actions: $actions,
            selectable: $this->isSelectable($record) && $actions !== [],
            entity_key: $this->bulkEntityKey(),
        );
    }

    protected function table(): string
    {
        $modelClass = $this->modelClass();

        return (new $modelClass)->getTable();
    }

    /**
     * @return array<int, string>
     */
    protected function rowActions(Model $record, ListPolicyResolver $policy): array
    {
        return $policy->allowedFrom($this->rowAbilities(), $record);
    }

    /**
     * Build the LIKE pattern for a normalized search term.
     */
    protected function likePattern(string $search): string
    {
        return '%' . $search . '%';
    }

    /**
     * Whitelisted sort column plus the unique-key tie-breaker.
     */
    private function applyOrder(Builder $query, ListContext $context, ListContextDefinition $definition): void
    {
        $sort = $definition->isSortable($context->sort) ? $context->sort : $definition->default_sort;
        $target = $this->sortMap()[$sort] ?? $this->table() . '.' . $sort;

        $query->orderBy($target, $context->direction);
        $query->orderBy($this->table() . '.' . $definition->tie_breaker, $context->direction);
    }

    private function emptyMode(int $total, ListContext $context, ListContextDefinition $definition): string
    {
        if ($total > 0) {
            return OperationalListData::EMPTY_MODE_NONE;
        }

        return $this->hasActiveContext($context, $definition)
            ? OperationalListData::EMPTY_MODE_NO_MATCHES
            : OperationalListData::EMPTY_MODE_NO_RECORDS;
    }

    private function hasActiveContext(ListContext $context, ListContextDefinition $definition): bool
    {
        if ($context->search !== null) {
            return true;
        }

        foreach ($context->filters as $name => $value) {
            if ($definition->filter($name)?->default !== $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, \BackedEnum> $cases
     * @return array<int, array{value: string|int|bool, label: string}>
     */
    protected function enumOptions(array $cases, Closure $label): array
    {
        return array_map(
            fn (\BackedEnum $case): array => ['value' => $case->value, 'label' => $label($case)],
            $cases
        );
    }

    /**
     * @param \Illuminate\Support\Collection<int|string, string> $pairs id => label
     * @return array<int, array{value: string|int|bool, label: string}>
     */
    protected function pairOptions(\Illuminate\Support\Collection $pairs): array
    {
        return $pairs
            ->map(fn (?string $label, int|string $value): array => [
                'value' => $value,
                'label' => (string) $label,
            ])
            ->values()
            ->all();
    }
}
