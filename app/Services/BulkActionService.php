<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Admin\DeleteStudentAction;
use App\Actions\Admin\DeleteTeacherAction;
use App\Actions\Admin\ProtectedDependencyChecker;
use App\Actions\Admin\StatusTransitionAction;
use App\DTOs\BulkCommand;
use App\DTOs\BulkItemResultData;
use App\DTOs\BulkResultData;
use App\Enums\BulkActionEnum;
use App\Enums\BulkEntityEnum;
use App\Enums\BulkItemResultStatusEnum;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;
use Throwable;

/** Executes each selected bulk item in its own locked transaction. */
final class BulkActionService
{
    public function __construct(
        ?StatusTransitionAction $statusTransitions = null,
        ?ProtectedDependencyChecker $dependencies = null,
        ?DeleteTeacherAction $deleteTeachers = null,
        ?DeleteStudentAction $deleteStudents = null,
        private readonly ?SelectionContextService $selectionContexts = null,
        ?AuditRecordService $auditRecords = null,
    ) {
        $dependencies ??= new ProtectedDependencyChecker();
        $this->statusTransitions = $statusTransitions ?? new StatusTransitionAction();
        $this->dependencies = $dependencies;
        $this->deleteTeachers = $deleteTeachers ?? new DeleteTeacherAction($dependencies);
        $this->deleteStudents = $deleteStudents ?? new DeleteStudentAction($dependencies);
        $this->auditRecords = $auditRecords ?? new AuditRecordService();
    }

    private readonly StatusTransitionAction $statusTransitions;
    private readonly ProtectedDependencyChecker $dependencies;
    private readonly DeleteTeacherAction $deleteTeachers;
    private readonly DeleteStudentAction $deleteStudents;
    private readonly AuditRecordService $auditRecords;

    public function execute(BulkCommand $command, ?Authenticatable $actor = null): BulkResultData
    {
        $actor = $this->resolveActor($command, $actor);
        $modelClass = $this->modelClass($command->entity);
        $this->authorizeCollection($actor, $modelClass);
        $ids = $this->resolveIds($command, $modelClass);
        $items = [];

        foreach ($ids as $id) {
            $items[] = $this->processItem($command, $id, $actor, $modelClass);
        }

        $result = $this->result($command, $items);
        $this->auditRecords->recordExecution($command, $result);

        return $result;
    }

    public function handle(BulkCommand $command, ?Authenticatable $actor = null): BulkResultData
    {
        return $this->execute($command, $actor);
    }

    /** @return class-string<Teacher|Student> */
    private function modelClass(BulkEntityEnum $entity): string
    {
        return $entity === BulkEntityEnum::Teacher ? Teacher::class : Student::class;
    }

    private function resolveActor(BulkCommand $command, ?Authenticatable $actor): Authenticatable
    {
        $actor ??= $command->actor_id === null ? auth()->user() : User::query()->find($command->actor_id);

        if (! $actor instanceof Authenticatable) {
            throw new AuthorizationException('Authentication is required for bulk actions.');
        }

        return $actor;
    }

    /** @param class-string<Teacher|Student> $modelClass */
    private function authorizeCollection(Authenticatable $actor, string $modelClass): void
    {
        Gate::forUser($actor)->authorize('viewAny', $modelClass);
    }

    /** @param class-string<Teacher|Student> $modelClass @return array<int, int|string> */
    private function resolveIds(BulkCommand $command, string $modelClass): array
    {
        if ($command->mode->value !== 'all_filtered') {
            return $command->ids;
        }

        if ($command->filter_context === null || $this->selectionContexts === null) {
            throw new InvalidArgumentException('A verified selection context is required for all-filtered execution.');
        }

        $context = $this->selectionContexts->verify(
            $command->filter_context,
            $command->entity === BulkEntityEnum::Teacher ? 'teachers' : 'students',
        );
        $query = $modelClass::query();

        if ($context->search !== null && $context->search !== '') {
            $pattern = '%' . $context->search . '%';
            $query->where(function (Builder $inner) use ($pattern, $command): void {
                $columns = $command->entity === BulkEntityEnum::Teacher
                    ? ['full_name', 'phone']
                    : ['full_name', 'phone', 'parent_phone'];
                foreach ($columns as $index => $column) {
                    $index === 0
                        ? $inner->where($column, 'like', $pattern)
                        : $inner->orWhere($column, 'like', $pattern);
                }
            });
        }

        foreach ($context->filters as $name => $value) {
            if ($name !== 'status' || ! is_string($value)) {
                throw new InvalidArgumentException('Selection context contains an unsupported bulk filter.');
            }
            $query->where('status', $value);
        }

        $allowedSorts = $command->entity === BulkEntityEnum::Teacher
            ? ['full_name', 'phone', 'status', 'hire_date', 'created_at']
            : ['full_name', 'phone', 'status', 'join_date', 'created_at'];
        if (! in_array($context->sort, $allowedSorts, true)
            || ! in_array($context->direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Selection context sort is invalid.');
        }

        return $query->orderBy($context->sort, $context->direction)->pluck('id')->all();
    }

    /** @param class-string<Teacher|Student> $modelClass */
    private function processItem(
        BulkCommand $command,
        int|string $id,
        Authenticatable $actor,
        string $modelClass,
    ): BulkItemResultData {
        try {
            return DB::transaction(function () use ($command, $id, $actor, $modelClass): BulkItemResultData {
                /** @var Teacher|Student|null $record */
                $record = $modelClass::query()->lockForUpdate()->find($id);

                if ($record === null) {
                    return $this->skipped($id, 'not_found', 'The selected record is no longer available.');
                }

                $ability = $command->action === BulkActionEnum::Delete ? 'delete' : 'update';
                if (! Gate::forUser($actor)->allows($ability, $record)) {
                    return $this->failed($id, 'unauthorized', 'You are not authorized to change this record.');
                }

                if ($command->action === BulkActionEnum::Delete) {
                    $categories = $this->dependencies->categories($record);
                    if ($categories !== []) {
                        return $this->failed($id, 'protected_dependency', 'The record has protected dependencies.', 'protected_dependency');
                    }

                    $deleted = $record instanceof Teacher
                        ? $this->deleteTeachers->delete($record)
                        : $this->deleteStudents->delete($record);

                    return $deleted
                        ? new BulkItemResultData($id, BulkItemResultStatusEnum::Succeeded)
                        : $this->failed($id, 'protected_dependency', 'The record has protected dependencies.', 'protected_dependency');
                }

                return $this->statusTransitions->execute($record, $command->action);
            });
        } catch (Throwable) {
            return $this->failed($id, 'processing_error', 'The selected record could not be processed.', 'processing_error');
        }
    }

    /** @param array<int, BulkItemResultData> $items */
    private function result(BulkCommand $command, array $items): BulkResultData
    {
        $succeeded = count(array_filter($items, fn (BulkItemResultData $item): bool => $item->status === BulkItemResultStatusEnum::Succeeded));
        $skipped = count(array_filter($items, fn (BulkItemResultData $item): bool => $item->status === BulkItemResultStatusEnum::Skipped));
        $failed = count($items) - $succeeded - $skipped;
        $selectionReference = $command->selection_reference;
        $contextFingerprint = $command->filter_context?->context_fingerprint;

        if ($items === [] && $selectionReference === null && $contextFingerprint === null) {
            $selectionReference = $command->request_fingerprint ?? 'bulk-selection';
        }

        return new BulkResultData(
            entity: $command->entity,
            action: $command->action,
            mode: $command->mode,
            total: count($items),
            succeeded: $succeeded,
            skipped: $skipped,
            failed: $failed,
            items: $items,
            selection_reference: $selectionReference,
            context_fingerprint: $contextFingerprint,
        );
    }

    private function skipped(int|string $id, string $category, string $message): BulkItemResultData
    {
        return new BulkItemResultData($id, BulkItemResultStatusEnum::Skipped, $category, $message, $category);
    }

    private function failed(int|string $id, string $category, string $message, ?string $identifier = null): BulkItemResultData
    {
        return new BulkItemResultData($id, BulkItemResultStatusEnum::Failed, $category, $message, $identifier ?? $category);
    }
}
