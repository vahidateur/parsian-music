<?php

namespace App\Services\Lists;

use App\DTOs\ListContextDefinition;
use App\DTOs\ListFilterDefinition;
use App\DTOs\OperationalRowData;
use App\Enums\LeadPriorityEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Operational_List query for CRM leads.
 */
final class LeadListQuery extends OperationalListQuery
{
    public function definition(): ListContextDefinition
    {
        return new ListContextDefinition(
            entity: 'leads',
            sortable: ['full_name', 'phone', 'status', 'priority', 'source', 'created_at', 'next_follow_up_at'],
            default_sort: 'created_at',
            default_direction: ListContextDefinition::DIRECTION_DESC,
            filters: [
                new ListFilterDefinition('status', ListFilterDefinition::TYPE_STRING, LeadStatusEnum::values()),
                new ListFilterDefinition('source', ListFilterDefinition::TYPE_STRING, LeadSourceEnum::values()),
                new ListFilterDefinition('priority', ListFilterDefinition::TYPE_STRING, LeadPriorityEnum::values()),
                new ListFilterDefinition('assigned_to', ListFilterDefinition::TYPE_INT),
            ],
        );
    }

    protected function modelClass(): string
    {
        return Lead::class;
    }

    protected function baseQuery(): Builder
    {
        return Lead::query()->with(['assignedUser', 'preferredInstrument', 'preferredTeacher']);
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $pattern = $this->likePattern($search);

        $query->where(fn (Builder $inner) => $inner
            ->where('leads.full_name', 'like', $pattern)
            ->orWhere('leads.phone', 'like', $pattern)
            ->orWhere('leads.email', 'like', $pattern));
    }

    protected function applyFilter(Builder $query, string $name, string|int|bool $value): void
    {
        match ($name) {
            'status' => $query->where('leads.status', $value),
            'source' => $query->where('leads.source', $value),
            'priority' => $query->where('leads.priority', $value),
            'assigned_to' => $query->where('leads.assigned_to', (int) $value),
            default => null,
        };
    }

    protected function rowAbilities(): array
    {
        return ['view', 'update', 'delete', 'convert'];
    }

    protected function filterOptions(): array
    {
        return [
            'status' => $this->enumOptions(
                LeadStatusEnum::cases(),
                fn (LeadStatusEnum $case): string => $case->label()
            ),
            'source' => $this->enumOptions(
                LeadSourceEnum::cases(),
                fn (LeadSourceEnum $case): string => $case->label()
            ),
            'priority' => $this->enumOptions(
                LeadPriorityEnum::cases(),
                fn (LeadPriorityEnum $case): string => $case->label()
            ),
            'assigned_to' => $this->pairOptions(
                User::query()
                    ->whereIn('role', [RoleEnum::ADMIN->value, RoleEnum::SUPER_ADMIN->value])
                    ->orderBy('full_name')
                    ->pluck('full_name', 'id')
            ),
        ];
    }

    protected function toRow(Model $record, ListPolicyResolver $policy): OperationalRowData
    {
        /** @var Lead $record */
        return new OperationalRowData(
            id: $record->id,
            label: (string) $record->full_name,
            fields: [
                'phone' => $record->phone,
                'email' => $record->email,
                'priority' => $record->priority?->value,
                'source' => $record->source?->value,
                'next_follow_up_at' => $record->next_follow_up_at?->toDateTimeString(),
            ],
            status: $record->status?->value,
            relations: [
                'assigned_user' => $record->assignedUser?->full_name,
                'preferred_instrument' => $record->preferredInstrument?->display_name,
                'preferred_teacher' => $record->preferredTeacher?->full_name,
            ],
            allowed_actions: $this->rowActions($record, $policy),
        );
    }
}
