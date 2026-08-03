<?php

namespace App\Services\Lists;

use App\DTOs\ListContextDefinition;
use App\DTOs\ListFilterDefinition;
use App\DTOs\OperationalRowData;
use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Operational_List query for rooms (list + form flow only, no detail route).
 */
final class RoomListQuery extends OperationalListQuery
{
    public function definition(): ListContextDefinition
    {
        return new ListContextDefinition(
            entity: 'rooms',
            sortable: ['name', 'capacity', 'is_active', 'created_at'],
            default_sort: 'name',
            default_direction: ListContextDefinition::DIRECTION_ASC,
            filters: [
                new ListFilterDefinition('is_active', ListFilterDefinition::TYPE_BOOL),
            ],
        );
    }

    protected function modelClass(): string
    {
        return Room::class;
    }

    protected function baseQuery(): Builder
    {
        return Room::query();
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $query->where('rooms.name', 'like', $this->likePattern($search));
    }

    protected function applyFilter(Builder $query, string $name, string|int|bool $value): void
    {
        if ($name === 'is_active') {
            $query->where('rooms.is_active', (bool) $value);
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
        /** @var Room $record */
        return new OperationalRowData(
            id: $record->id,
            label: (string) $record->name,
            fields: [
                'capacity' => $record->capacity,
            ],
            status: $record->is_active ? 'active' : 'inactive',
            allowed_actions: $this->rowActions($record, $policy),
        );
    }
}
