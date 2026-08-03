<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Admin\StudentAction;
use App\Enums\StudentStatusEnum;
use App\Models\Student;
use Illuminate\Validation\Rule;

/**
 * Validation contract of the student Record_Form (create and edit).
 *
 * Requirements: 6.5, 6.7
 */
class StudentRequest extends AdminFormRequest
{
    /**
     * @return array<string, string>
     */
    public function normalizedFields(): array
    {
        return StudentAction::NORMALIZED_FIELDS;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('students', 'phone')->ignore($student instanceof Student ? $student->id : null),
            ],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', Rule::in(StudentStatusEnum::values())],
            'join_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
