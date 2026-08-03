<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BulkActionEnum;
use App\Enums\BulkEntityEnum;
use App\Enums\BulkSelectionModeEnum;
use App\Services\AuditRecordService;
use App\Services\Lists\StudentListQuery;
use App\Services\Lists\TeacherListQuery;
use App\Services\SelectionContextService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Throwable;

final class BulkRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $entity = BulkEntityEnum::tryFrom((string) $this->input('entity'));
        $table = match ($entity) {
            BulkEntityEnum::Teacher => 'teachers',
            BulkEntityEnum::Student => 'students',
            default => null,
        };

        return [
            'entity' => ['bail', 'required', 'string', new Enum(BulkEntityEnum::class)],
            'action' => ['bail', 'required', 'string', new Enum(BulkActionEnum::class)],
            'mode' => ['bail', 'required', 'string', new Enum(BulkSelectionModeEnum::class)],
            'ids' => [
                'bail',
                'array',
                'min:1',
                Rule::requiredIf(fn (): bool => $this->input('mode') === BulkSelectionModeEnum::CurrentPage->value),
                Rule::prohibitedIf(fn (): bool => $this->input('mode') === BulkSelectionModeEnum::AllFiltered->value),
            ],
            'ids.*' => [
                'bail',
                'integer',
                'distinct',
                ...($table === null ? [] : [Rule::exists($table, 'id')]),
            ],
            'filter_context' => [
                'bail',
                'string',
                Rule::requiredIf(fn (): bool => $this->input('mode') === BulkSelectionModeEnum::AllFiltered->value),
                Rule::prohibitedIf(fn (): bool => $this->input('mode') === BulkSelectionModeEnum::CurrentPage->value),
            ],
            'selection_reference' => ['sometimes', 'nullable', 'string', 'max:128'],
            'request_fingerprint' => ['sometimes', 'nullable', 'string', 'max:128'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        app(AuditRecordService::class)->recordRejectedOperation([
            'actor_id' => $this->user()?->getAuthIdentifier(),
            'entity_type' => $this->auditScalar($this->input('entity'))
                ?? $this->auditScalar($this->route('entityType')),
            'action' => $this->auditScalar($this->input('action')),
            'selection_mode' => $this->auditScalar($this->input('mode')),
        ], 'validation', null, [
            'validation_fields' => array_keys($validator->errors()->toArray()),
        ]);

        if ($this->expectsJson() || $this->routeIs('admin.*.bulk')) {
            throw new HttpResponseException(response()->json([
                'errors' => $validator->errors()->toArray(),
            ], 422));
        }

        parent::failedValidation($validator);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $entity = $this->input('entity');
            $routeEntity = $this->route('entityType');

            if (is_string($entity) && is_string($routeEntity) && $entity !== $routeEntity) {
                $validator->errors()->add('entity', __('validation.in', ['attribute' => 'entity']));
                return;
            }

            if ((string) $this->input('mode') !== BulkSelectionModeEnum::AllFiltered->value
                || ! is_string($entity)
                || ! is_string($this->input('filter_context'))) {
                return;
            }

            try {
                $contexts = app(SelectionContextService::class);
                $verified = $contexts->verify(
                    $this->input('filter_context'),
                    $this->pluralEntity($entity),
                );
                $query = $entity === BulkEntityEnum::Teacher->value
                    ? app(TeacherListQuery::class)
                    : app(StudentListQuery::class);
                $contexts->reconstruct($verified, $query->definition());
            } catch (Throwable) {
                $validator->errors()->add('filter_context', __('validation.in', ['attribute' => 'filter context']));
            }
        });
    }

    private function pluralEntity(string $entity): string
    {
        return match (BulkEntityEnum::tryFrom($entity)) {
            BulkEntityEnum::Teacher => 'teachers',
            BulkEntityEnum::Student => 'students',
            default => $entity,
        };
    }

    private function auditScalar(mixed $value): ?string
    {
        return is_string($value) || is_int($value) ? (string) $value : null;
    }
}
