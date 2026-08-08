<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Admin\TeacherAction;
use App\Enums\TeacherStatusEnum;
use App\Models\Teacher;
use Illuminate\Validation\Rule;

/**
 * Validation contract of the teacher Record_Form (create and edit).
 *
 * The phone uniqueness rule ignores the record bound to the route, so the edit
 * form can resubmit its own phone number.
 *
 * Requirements: 6.5, 6.7
 */
class TeacherRequest extends AdminFormRequest
{
    /**
     * @return array<string, string>
     */
    public function normalizedFields(): array
    {
        return TeacherAction::NORMALIZED_FIELDS;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teacher = $this->route('teacher');

        return [
            'teacher_code' => ['missing'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('teachers', 'phone')->ignore($teacher instanceof Teacher ? $teacher->id : null),
            ],
            'status' => ['nullable', 'string', Rule::in(TeacherStatusEnum::values())],
            'bio' => ['nullable', 'string'],
            'hire_date' => ['nullable', 'date'],
        ];
    }
}
