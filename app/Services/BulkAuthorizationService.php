<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\BulkCommand;
use App\Enums\BulkActionEnum;
use App\Enums\BulkEntityEnum;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Authorization boundary for teacher and student bulk operations.
 *
 * Collection authorization is deliberately separate from item authorization:
 * callers must invoke authorize() before resolving an ID list or filter
 * context, then invoke authorizeRecord() for every resolved record immediately
 * before any mutation.
 */
final class BulkAuthorizationService
{
    /** @var array<string, class-string<Model>> */
    private const ENTITY_MODELS = [
        BulkEntityEnum::Teacher->value => Teacher::class,
        BulkEntityEnum::Student->value => Student::class,
    ];

    /**
     * Authorize access to the requested entity collection before resolution.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize(BulkCommand $command, Authenticatable $actor): void
    {
        Gate::forUser($actor)->authorize('viewAny', $this->modelClass($command->entity));
    }

    /**
     * Authorize one already-resolved record immediately before its mutation.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeRecord(
        BulkCommand $command,
        Model $record,
        Authenticatable $actor,
    ): void {
        $modelClass = $this->modelClass($command->entity);

        if (! $record instanceof $modelClass) {
            throw new InvalidArgumentException(sprintf(
                'Bulk record must be an instance of %s for the %s entity.',
                $modelClass,
                $command->entity->value,
            ));
        }

        Gate::forUser($actor)->authorize($this->ability($command->action), $record);
    }

    /** @return class-string<Model> */
    public function modelClass(BulkEntityEnum|string $entity): string
    {
        $entity = $entity instanceof BulkEntityEnum
            ? $entity
            : BulkEntityEnum::from(trim($entity));

        return self::ENTITY_MODELS[$entity->value];
    }

    public function ability(BulkActionEnum|string $action): string
    {
        $action = $action instanceof BulkActionEnum
            ? $action
            : BulkActionEnum::from(trim($action));

        return match ($action) {
            BulkActionEnum::Activate,
            BulkActionEnum::Deactivate => 'update',
            BulkActionEnum::Delete => 'delete',
        };
    }
}
