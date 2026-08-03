<?php

namespace App\Services\Lists;

use App\DTOs\ListContextDefinition;
use App\DTOs\ListFilterDefinition;
use App\DTOs\OperationalRowData;
use App\Enums\SessionStatusEnum;
use App\Models\ClassSession;
use App\Models\Instrument;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Operational_List query for class sessions.
 *
 * Session identity may come from the enrollment path or from the direct
 * student/teacher/instrument columns of a manually created session; both are
 * resolved through the existing model scopes instead of a second mapping.
 */
final class SessionListQuery extends OperationalListQuery
{
    public function definition(): ListContextDefinition
    {
        return new ListContextDefinition(
            entity: 'sessions',
            sortable: [
                'session_date', 'start_time', 'duration_minutes', 'room', 'status', 'created_at',
                'student_name', 'teacher_name', 'instrument_name',
            ],
            default_sort: 'session_date',
            default_direction: ListContextDefinition::DIRECTION_ASC,
            filters: [
                new ListFilterDefinition('student_id', ListFilterDefinition::TYPE_INT),
                new ListFilterDefinition('teacher_id', ListFilterDefinition::TYPE_INT),
                new ListFilterDefinition('instrument_id', ListFilterDefinition::TYPE_INT),
                new ListFilterDefinition('room', ListFilterDefinition::TYPE_STRING),
                new ListFilterDefinition('status', ListFilterDefinition::TYPE_STRING, SessionStatusEnum::values()),
                new ListFilterDefinition('date', ListFilterDefinition::TYPE_STRING),
            ],
        );
    }

    protected function modelClass(): string
    {
        return ClassSession::class;
    }

    protected function baseQuery(): Builder
    {
        return ClassSession::query()->withEnrollmentDetails();
    }

    protected function applyFilter(Builder $query, string $name, string|int|bool $value): void
    {
        match ($name) {
            'student_id' => $query->forStudent((int) $value),
            'teacher_id' => $query->forTeacher((int) $value),
            'instrument_id' => $query->forInstrument((int) $value),
            'room' => $query->where('class_sessions.room', $value),
            'status' => $query->where('class_sessions.status', $value),
            'date' => $query->whereDate('class_sessions.session_date', $value),
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
        return ['view', 'update', 'delete', 'markAttendance'];
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
                SessionStatusEnum::cases(),
                fn (SessionStatusEnum $case): string => __('admin.session_statuses.' . $case->value)
            ),
        ];
    }

    protected function toRow(Model $record, ListPolicyResolver $policy): OperationalRowData
    {
        /** @var ClassSession $record */
        $student = $record->student ?? $record->enrollment?->student;
        $teacher = $record->teacher ?? $record->enrollment?->teacher;
        $instrument = $record->instrument ?? $record->enrollment?->instrument;

        return new OperationalRowData(
            id: $record->id,
            label: $record->session_date?->toDateString() ?? (string) $record->id,
            fields: [
                'session_date' => $record->session_date?->toDateString(),
                'start_time' => $record->start_time?->format('H:i'),
                'duration_minutes' => $record->duration_minutes,
                'room' => $record->room,
            ],
            status: $record->status?->value,
            relations: [
                'student' => $student?->full_name,
                'teacher' => $teacher?->full_name,
                'instrument' => $instrument?->display_name,
            ],
            allowed_actions: $this->rowActions($record, $policy),
        );
    }

    /**
     * Correlated sub-select that resolves a related name through the direct
     * column first and through the enrollment path as fallback.
     */
    private function relationNameSort(string $table, string $column, string $foreignKey): \Closure
    {
        return function (QueryBuilder $query) use ($table, $column, $foreignKey): void {
            $query->select($table . '.' . $column)
                ->from($table)
                ->where(function (QueryBuilder $inner) use ($table, $foreignKey): void {
                    $inner->whereColumn($table . '.id', 'class_sessions.' . $foreignKey)
                        ->orWhereIn($table . '.id', function (QueryBuilder $sub) use ($foreignKey): void {
                            $sub->select('student_enrollments.' . $foreignKey)
                                ->from('student_enrollments')
                                ->whereColumn('student_enrollments.id', 'class_sessions.enrollment_id');
                        });
                })
                ->limit(1);
        };
    }
}
