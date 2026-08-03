<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BulkEntityEnum;
use App\Enums\BulkSelectionModeEnum;
use App\Services\Lists\StudentListQuery;
use App\Services\Lists\TeacherListQuery;
use App\Services\SelectionContextService;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;
use Throwable;

final class BulkPreviewRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'entity' => ['bail', 'required', 'string', new Enum(BulkEntityEnum::class)],
            'mode' => ['bail', 'required', 'string', new Enum(BulkSelectionModeEnum::class), 'in:all_filtered'],
            'filter_context' => ['bail', 'required', 'string'],
        ];
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

            if (! is_string($entity) || ! is_string($this->input('filter_context'))) {
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
}
