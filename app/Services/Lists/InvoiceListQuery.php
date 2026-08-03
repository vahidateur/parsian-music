<?php

namespace App\Services\Lists;

use App\DTOs\ListContextDefinition;
use App\DTOs\ListFilterDefinition;
use App\DTOs\OperationalRowData;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Operational_List query for invoices.
 *
 * Paid and outstanding amounts are computed from the eager-loaded payments
 * relation, so the query count does not grow with the number of rendered rows.
 */
final class InvoiceListQuery extends OperationalListQuery
{
    public function definition(): ListContextDefinition
    {
        return new ListContextDefinition(
            entity: 'invoices',
            sortable: ['invoice_number', 'issue_date', 'due_date', 'total', 'status', 'created_at', 'student_name'],
            default_sort: 'issue_date',
            default_direction: ListContextDefinition::DIRECTION_DESC,
            filters: [
                new ListFilterDefinition('student_id', ListFilterDefinition::TYPE_INT),
                new ListFilterDefinition('status', ListFilterDefinition::TYPE_STRING, array_column(InvoiceStatusEnum::cases(), 'value')),
            ],
        );
    }

    protected function modelClass(): string
    {
        return Invoice::class;
    }

    protected function baseQuery(): Builder
    {
        return Invoice::query()->with(['student', 'payments']);
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $pattern = $this->likePattern($search);

        $query->where(fn (Builder $inner) => $inner
            ->where('invoices.invoice_number', 'like', $pattern)
            ->orWhereHas('student', fn (Builder $student) => $student->where('full_name', 'like', $pattern)));
    }

    protected function applyFilter(Builder $query, string $name, string|int|bool $value): void
    {
        match ($name) {
            'student_id' => $query->where('invoices.student_id', (int) $value),
            'status' => $query->where('invoices.status', $value),
            default => null,
        };
    }

    protected function sortMap(): array
    {
        return [
            'student_name' => function (QueryBuilder $query): void {
                $query->select('students.full_name')
                    ->from('students')
                    ->whereColumn('students.id', 'invoices.student_id')
                    ->limit(1);
            },
        ];
    }

    protected function rowAbilities(): array
    {
        return ['view', 'update', 'delete', 'issue', 'cancel', 'registerPayment'];
    }

    protected function filterOptions(): array
    {
        return [
            'student_id' => $this->pairOptions(Student::query()->orderBy('full_name')->pluck('full_name', 'id')),
            'status' => $this->enumOptions(
                InvoiceStatusEnum::cases(),
                fn (InvoiceStatusEnum $case): string => $case->label()
            ),
        ];
    }

    protected function toRow(Model $record, ListPolicyResolver $policy): OperationalRowData
    {
        /** @var Invoice $record */
        $paid = (float) $record->payments
            ->where('status', PaymentStatusEnum::Completed)
            ->sum('amount');
        $total = (float) $record->total;

        return new OperationalRowData(
            id: $record->id,
            label: (string) $record->invoice_number,
            fields: [
                'issue_date' => $record->issue_date?->toDateString(),
                'due_date' => $record->due_date?->toDateString(),
                'total' => (int) round($total),
                'paid' => (int) round($paid),
                'outstanding' => (int) round(max(0, $total - $paid)),
                'currency' => $record->currency,
            ],
            status: $record->status?->value,
            relations: [
                'student' => $record->student?->full_name,
            ],
            allowed_actions: $this->rowActions($record, $policy),
        );
    }
}
