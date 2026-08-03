<?php

namespace App\Services\Lists;

use App\DTOs\ListContextDefinition;
use App\DTOs\ListFilterDefinition;
use App\DTOs\OperationalRowData;
use App\Enums\EnrollmentStatusEnum;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Operational_List query for student enrollments (list + form flow only).
 */
final class EnrollmentListQuery extends OperationalListQuery
{
    public function definition(): ListContextDefinition
    {
        return new ListContextDefinition(
            entity: 'enrollments',
            sortable: [
                'started_at', 'status', 'skill_level', 'created_at',
                'student_name', 'teacher_name', 'instrument_name',
            ],
            default_sort: 'created_at',
            default_direction: ListContextDefinition::DIRECTION_DESC,
            filters: [
                new ListFilterDefinition('student_id', ListFilterDefinition::TYPE_INT),
                new ListFilterDefinition('teacher_id', ListFilterDefinition::TYPE_INT),
                new ListFilterDefinition('instrument_id', ListFilterDefinition::TYPE_INT),
                new ListFilterDefinition('status', ListFilterDefinition::TYPE_STRING, EnrollmentStatusEnum::values()),
            ],
        );
    }

    protected function modelClass(): string
    {
        return StudentEnrollment::class;
    }

    protected function baseQuery(): Builder
    {
        return StudentEnrollment::query()->with(['student', 'teacher', 'instrument']);
    }

    protected function applyFilter(Builder $query, string $name, string|int|bool $value): void
    {
        match ($name) {
            'student_id' => $query->where('student_enrollments.student_id', (int) $value),
            'teacher_id' => $query->where('student_enrollments.teacher_id', (int) $value),
            'instrument_id' => $query->where('student_enrollments.instrument_id', (int) $value),
            'status' => $query->where('student_enrollments.status', $value),
            default => null,
        };
    }

    protected function sortMap(): array
    {
        return [
            'student_name' => $this->relationNameSort('students', 'full_name', 'student_id'),
            'teacher_name' => $this->relationNameSort('teachers', 'full_name', 'teacher_id'),
            'instrument_name' => $this->relationNameSort('instruments', 'name_fa', 'instrument_id'),
        ];
    }

    protected function rowAbilities(): array
    {
        return ['view', 'update', 'delete'];
    }

    protected function filterOptions(): array
    {
        return [
            'student_id' => $this->pairOptions(Student::query()->orderBy('full_name')->pluck('full_name', 'id')),
            'teacher_id' => $this->pairOptions(Teacher::query()->orderBy('full_name')->pluck('full_name', 'id')),
            'instrument_id' => $this->pairOptions(
                Instrument::query()->orderBy('name_fa')->orderBy('name')->pluck('name_fa', 'id')
            ),
            'status' => $this->enumOptions(
                EnrollmentStatusEnum::cases(),
                fn (EnrollmentStatusEnum $case): string => __('admin.statuses.' . $case->value)
            ),
        ];
    }

    protected function toRow(Model $record, ListPolicyResolver $policy): OperationalRowData
    {
        /** @var StudentEnrollment $record */
        return new OperationalRowData(
            id: $record->id,
            label: (string) ($record->student?->full_name ?? $record->id),
            fields: [
                'skill_level' => $record->skill_level?->value,
                'started_at' => $record->started_at?->toDateString(),
                'ended_at' => $record->ended_at?->toDateString(),
            ],
            status: $record->status?->value,
            relations: [
                'student' => $record->student?->full_name,
                'teacher' => $record->teacher?->full_name,
                'instrument' => $record->instrument?->display_name,
            ],
            allowed_actions: $this->rowActions($record, $policy),
        );
    }

    private function relationNameSort(string $table, string $column, string $foreignKey): \Closure
    {
        return function (QueryBuilder $query) use ($table, $column, $foreignKey): void {
            $query->select($table . '.' . $column)
                ->from($table)
                ->whereColumn($table . '.id', 'student_enrollments.' . $foreignKey)
                ->limit(1);
        };
    }
}
