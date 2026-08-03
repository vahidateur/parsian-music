<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Admin\EnrollmentAction;
use App\Enums\EnrollmentStatusEnum;
use Illuminate\Validation\Rule;

/**
 * Validation contract of the enrollment edit form.
 *
 * The edit form reassigns the teacher and moves the status; the student and the
 * instrument of an existing enrollment are not editable.
 *
 * Requirements: 6.5, 6.7
 */
class UpdateEnrollmentRequest extends AdminFormRequest
{
    /**
     * @return array<string, string>
     */
    public function normalizedFields(): array
    {
        return EnrollmentAction::NORMALIZED_FIELDS;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'exists:teachers,id'],
            'status' => ['required', 'string', Rule::in(EnrollmentStatusEnum::values())],
            'notes' => ['nullable', 'string'],
        ];
    }
}
