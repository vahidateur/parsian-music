<?php

namespace App\Services\Lists;

use App\DTOs\ListContextDefinition;
use App\DTOs\ListFilterDefinition;
use App\DTOs\OperationalRowData;
use App\Enums\TeacherStatusEnum;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Operational_List query for teachers.
 */
final class TeacherListQuery extends OperationalListQuery
{
    public function definition(): ListContextDefinition
    {
        return new ListContextDefinition(
            entity: 'teachers',
            sortable: ['full_name', 'phone', 'status', 'hire_date', 'created_at'],
            default_sort: 'full_name',
            default_direction: ListContextDefinition::DIRECTION_ASC,
            filters: [
                new ListFilterDefinition('status', ListFilterDefinition::TYPE_STRING, TeacherStatusEnum::values()),
            ],
        );
    }

    protected function modelClass(): string
    {
        return Teacher::class;
    }

    protected function baseQuery(): Builder
    {
        return Teacher::query()->with('instruments');
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $pattern = $this->likePattern($search);

        $query->where(fn (Builder $inner) => $inner
            ->where('teachers.full_name', 'like', $pattern)
            ->orWhere('teachers.phone', 'like', $pattern));
    }

    protected function applyFilter(Builder $query, string $name, string|int|bool $value): void
    {
        if ($name === 'status') {
            $query->where('teachers.status', $value);
        }
    }

    protected function rowAbilities(): array
    {
        return ['view', 'update', 'delete', 'manageInstruments'];
    }

    protected function isSelectable(Model $record): bool
    {
        return true;
    }

    protected function filterOptions(): array
    {
        return [
            'status' => $this->enumOptions(
                TeacherStatusEnum::cases(),
                fn (TeacherStatusEnum $case): string => __('admin.statuses.' . $case->value)
            ),
        ];
    }

    protected function toRow(Model $record, ListPolicyResolver $policy): OperationalRowData
    {
        /** @var Teacher $record */
        return new OperationalRowData(
            id: $record->id,
            label: (string) $record->full_name,
            fields: [
                'phone' => $record->phone,
                'hire_date' => $record->hire_date?->toDateString(),
            ],
            status: $record->status?->value,
            relations: [
                'instruments' => $record->instruments
                    ->map(fn ($instrument): string => (string) $instrument->display_name)
                    ->implode('، ') ?: null,
            ],
            allowed_actions: $this->rowActions($record, $policy),
            selectable: $this->isSelectable($record),
        );
    }
}
