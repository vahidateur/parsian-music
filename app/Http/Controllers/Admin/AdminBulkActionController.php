<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\BulkCommand;
use App\Enums\BulkEntityEnum;
use App\Enums\BulkSelectionModeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkPreviewRequest;
use App\Http\Requests\Admin\BulkRequest;
use App\Http\Resources\BulkResultResource;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AuditRecordService;
use App\Services\BulkActionService;
use App\Services\Lists\StudentListQuery;
use App\Services\Lists\TeacherListQuery;
use App\Services\SelectionContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final class AdminBulkActionController extends Controller
{
    public function preview(
        BulkPreviewRequest $request,
        string $entityType,
        SelectionContextService $contexts,
        TeacherListQuery $teachers,
        StudentListQuery $students,
    ): JsonResponse {
        $entity = $this->entity($entityType);
        $this->authorize('viewAny', $this->modelClass($entity));

        $query = $this->listQuery($entityType, $teachers, $students);
        $context = $contexts->reconstruct(
            $request->validated('filter_context'),
            $query->definition(),
        );
        $list = $query->get($context, $request->user());

        return response()->json([
            'entity' => $this->entity($entityType)->value,
            'mode' => BulkSelectionModeEnum::AllFiltered->value,
            'count' => $list->total,
            'context_fingerprint' => $list->selection_context?->context_fingerprint,
        ]);
    }

    public function store(
        BulkRequest $request,
        string $entityType,
        BulkActionService $bulkActions,
        AuditRecordService $auditRecords,
    ): JsonResponse {
        $entity = $this->entity($entityType);
        $validated = $request->validated();

        try {
            $this->authorize('viewAny', $this->modelClass($entity));

            $command = new BulkCommand(
                entity: $entity,
                action: $validated['action'],
                mode: $validated['mode'],
                ids: $validated['ids'] ?? [],
                filter_context: isset($validated['filter_context'])
                    ? app(SelectionContextService::class)->verify(
                        $validated['filter_context'],
                        $this->pluralEntity($entity),
                    )
                    : null,
                actor_id: $request->user()?->getAuthIdentifier(),
                request_fingerprint: $validated['request_fingerprint'] ?? null,
                selection_reference: $validated['selection_reference'] ?? null,
            );

            $result = $bulkActions->execute($command, $request->user());

            return BulkResultResource::make($result)->response();
        } catch (AuthorizationException $exception) {
            $auditRecords->recordRejectedOperation(
                $this->rejectionContext($request, $entity, $validated),
                'authorization',
            );

            throw $exception;
        } catch (InvalidArgumentException) {
            $auditRecords->recordRejectedOperation(
                $this->rejectionContext($request, $entity, $validated),
                'invalid_context',
                null,
                ['validation_fields' => ['filter_context']],
            );

            return response()->json([
                'errors' => [
                    'filter_context' => [__('validation.in', ['attribute' => 'filter context'])],
                ],
            ], 422);
        }
    }

    /** @param array<string, mixed> $validated */
    private function rejectionContext(
        BulkRequest $request,
        BulkEntityEnum $entity,
        array $validated,
    ): array {
        return [
            'actor_id' => $request->user()?->getAuthIdentifier(),
            'entity_type' => $entity->value,
            'action' => $this->auditScalar($validated['action'] ?? null),
            'selection_mode' => $this->auditScalar($validated['mode'] ?? null),
            'selection_reference' => $this->auditScalar($validated['selection_reference'] ?? null),
        ];
    }

    private function auditScalar(mixed $value): ?string
    {
        return is_string($value) || is_int($value) ? (string) $value : null;
    }

    private function entity(string $entityType): BulkEntityEnum
    {
        return BulkEntityEnum::from($entityType);
    }

    private function modelClass(BulkEntityEnum $entity): string
    {
        return $entity === BulkEntityEnum::Teacher ? Teacher::class : Student::class;
    }

    private function pluralEntity(BulkEntityEnum $entity): string
    {
        return $entity === BulkEntityEnum::Teacher ? 'teachers' : 'students';
    }

    private function listQuery(
        string $entityType,
        TeacherListQuery $teachers,
        StudentListQuery $students,
    ): TeacherListQuery|StudentListQuery {
        return $this->entity($entityType) === BulkEntityEnum::Teacher ? $teachers : $students;
    }
}
