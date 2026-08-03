<?php

namespace App\Services\Lists;

use App\DTOs\ListContextDefinition;
use App\DTOs\ListFilterDefinition;
use App\DTOs\OperationalRowData;
use App\Models\Instrument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Operational_List query for instruments (list + form flow only, no detail route).
 */
final class InstrumentListQuery extends OperationalListQuery
{
    public function definition(): ListContextDefinition
    {
        return new ListContextDefinition(
            entity: 'instruments',
            sortable: ['name_fa', 'name', 'is_active', 'created_at'],
            default_sort: 'name_fa',
            default_direction: ListContextDefinition::DIRECTION_ASC,
            filters: [
                new ListFilterDefinition('is_active', ListFilterDefinition::TYPE_BOOL),
            ],
        );
    }

    protected function modelClass(): string
    {
        return Instrument::class;
    }

    protected function baseQuery(): Builder
    {
        return Instrument::query()->withCount(['teachers', 'enrollments']);
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $pattern = $this->likePattern($search);

        $query->where(fn (Builder $inner) => $inner
            ->where('instruments.name_fa', 'like', $pattern)
            ->orWhere('instruments.name', 'like', $pattern)
            ->orWhere('instruments.slug', 'like', $pattern));
    }

    protected function applyFilter(Builder $query, string $name, string|int|bool $value): void
    {
        if ($name === 'is_active') {
            $query->where('instruments.is_active', (bool) $value);
        }
    }

    protected function rowAbilities(): array
    {
        return ['update', 'delete', 'toggle'];
    }

    protected function filterOptions(): array
    {
        return [
            'is_active' => [
                ['value' => true, 'label' => __('admin.statuses.active')],
                ['value' => false, 'label' => __('admin.statuses.inactive')],
            ],
        ];
    }

    protected function toRow(Model $record, ListPolicyResolver $policy): OperationalRowData
    {
        /** @var Instrument $record */
        return new OperationalRowData(
            id: $record->id,
            label: (string) $record->display_name,
            fields: [
                'name' => $record->name,
                'name_fa' => $record->name_fa,
                'slug' => $record->slug,
                'teachers_count' => (int) ($record->teachers_count ?? 0),
                'enrollments_count' => (int) ($record->enrollments_count ?? 0),
            ],
            status: $record->is_active ? 'active' : 'inactive',
            allowed_actions: $this->rowActions($record, $policy),
        );
    }
}
