<?php

namespace App\Services\Lists;

use App\DTOs\ListContextDefinition;
use App\DTOs\ListFilterDefinition;
use App\DTOs\OperationalRowData;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Operational_List query for panel users.
 */
final class UserListQuery extends OperationalListQuery
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function definition(): ListContextDefinition
    {
        return new ListContextDefinition(
            entity: 'users',
            sortable: ['full_name', 'phone', 'email', 'role', 'is_active', 'created_at'],
            default_sort: 'created_at',
            default_direction: ListContextDefinition::DIRECTION_DESC,
            filters: [
                new ListFilterDefinition('role', ListFilterDefinition::TYPE_STRING, RoleEnum::values()),
                new ListFilterDefinition('status', ListFilterDefinition::TYPE_STRING, [self::STATUS_ACTIVE, self::STATUS_INACTIVE]),
            ],
        );
    }

    protected function modelClass(): string
    {
        return User::class;
    }

    protected function baseQuery(): Builder
    {
        return User::query()->with('createdBy');
    }

    protected function applySearch(Builder $query, string $search): void
    {
        $pattern = $this->likePattern($search);

        $query->where(fn (Builder $inner) => $inner
            ->where('users.full_name', 'like', $pattern)
            ->orWhere('users.phone', 'like', $pattern)
            ->orWhere('users.email', 'like', $pattern));
    }

    protected function applyFilter(Builder $query, string $name, string|int|bool $value): void
    {
        match ($name) {
            'role' => $query->where('users.role', $value),
            'status' => $query->where('users.is_active', $value === self::STATUS_ACTIVE),
            default => null,
        };
    }

    protected function rowAbilities(): array
    {
        return ['view', 'update', 'delete', 'toggle', 'resetPassword'];
    }

    protected function filterOptions(): array
    {
        return [
            'role' => $this->enumOptions(
                RoleEnum::cases(),
                fn (RoleEnum $case): string => $case->label()
            ),
            'status' => [
                ['value' => self::STATUS_ACTIVE, 'label' => __('admin.statuses.active')],
                ['value' => self::STATUS_INACTIVE, 'label' => __('admin.statuses.inactive')],
            ],
        ];
    }

    protected function toRow(Model $record, ListPolicyResolver $policy): OperationalRowData
    {
        /** @var User $record */
        return new OperationalRowData(
            id: $record->id,
            label: (string) $record->full_name,
            fields: [
                'phone' => $record->phone,
                'email' => $record->email,
                'role' => $record->role?->value,
                'last_login_at' => $record->last_login_at?->toDateTimeString(),
            ],
            status: $record->is_active ? self::STATUS_ACTIVE : self::STATUS_INACTIVE,
            relations: [
                'created_by' => $record->createdBy?->full_name,
            ],
            allowed_actions: $this->rowActions($record, $policy),
        );
    }
}
