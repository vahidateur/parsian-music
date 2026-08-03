<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Admin\EnrollmentAction;
use App\Enums\EnrollmentStatusEnum;
use Illuminate\Validation\Rule;

/**
 * Validation contract of the enrollment create form.
 *
 * The duplicate-enrollment rule stays in EnrollmentService because it depends on
 * the enrollment state machine, and the service reports it as a field error.
 *
 * Requirements: 6.5, 6.7
 */
class StoreEnrollmentRequest extends AdminFormRequest
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
            'student_id' => ['required', 'exists:students,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'instrument_id' => ['required', 'exists:instruments,id'],
            'status' => ['nullable', 'string', Rule::in(EnrollmentStatusEnum::values())],
            'notes' => ['nullable', 'string'],
        ];
    }
}
