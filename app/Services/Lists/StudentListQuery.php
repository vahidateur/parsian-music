<?php

namespace App\Services\Lists;

use App\DTOs\ListContextDefinition;
use App\DTOs\ListFilterDefinition;
use App\DTOs\OperationalRowData;
use App\Enums\StudentStatusEnum;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Operational_List query for students.
 */
final class StudentListQuery extends OperationalListQuery
{
    public function definition(): ListContextDefinition
    {
        return new ListContextDefinition(
            entity: 'students',
            sortable: ['full_name', 'phone', 'status', 'join_date', 'created_at'],
            default_sort: 'full_name',
            default_direction: ListContextDefinition::DIRECTION_ASC,
            filters: [
                new ListFilterDefinition('status', ListFilterDefinition::TYPE_STRING, StudentStatusEnum::values()),
            ],
        );
    }

    protected function modelClass(): string
    {
        return Student::class;
    }

    protected function baseQuery(): Builder
    {
        return Student::query()->withCount('enrollments');
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $pattern = $this->likePattern($search);

        $query->where(fn (Builder $inner) => $inner
            ->where('students.full_name', 'like', $pattern)
            ->orWhere('students.phone', 'like', $pattern)
            ->orWhere('students.parent_phone', 'like', $pattern));
    }

    protected function applyFilter(Builder $query, string $name, string|int|bool $value): void
    {
        if ($name === 'status') {
            $query->where('students.status', $value);
        }
    }

    protected function isSelectable(Model $record): bool
    {
        return true;
    }

    protected function filterOptions(): array
    {
        return [
            'status' => $this->enumOptions(
                StudentStatusEnum::cases(),
                fn (StudentStatusEnum $case): string => __('admin.statuses.' . $case->value)
            ),
        ];
    }

    protected function toRow(Model $record, ListPolicyResolver $policy): OperationalRowData
    {
        /** @var Student $record */
        return new OperationalRowData(
            id: $record->id,
            label: (string) $record->full_name,
            fields: [
                'phone' => $record->phone,
                'parent_phone' => $record->parent_phone,
                'join_date' => $record->join_date?->toDateString(),
                'enrollments_count' => (int) ($record->enrollments_count ?? 0),
            ],
            status: $record->status?->value,
            allowed_actions: $this->rowActions($record, $policy),
            selectable: $this->isSelectable($record),
        );
    }
}
