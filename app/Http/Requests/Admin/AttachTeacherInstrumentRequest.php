<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\SkillLevelEnum;
use App\Models\Teacher;
use Illuminate\Validation\Rule;

/**
 * Validation contract for attaching an instrument to a teacher.
 *
 * The uniqueness rule is scoped to the teacher bound to the route so the same
 * instrument can never be attached twice to that teacher.
 *
 * Requirements: 6.5, 6.7
 */
class AttachTeacherInstrumentRequest extends AdminFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teacher = $this->route('teacher');
        $teacherId = $teacher instanceof Teacher ? $teacher->id : null;

        return [
            'instrument_id' => [
                'required',
                'exists:instruments,id',
                Rule::unique('teacher_instruments', 'instrument_id')
                    ->where(fn ($query) => $query->where('teacher_id', $teacherId)),
            ],
            'skill_level' => ['required', 'string', Rule::in(SkillLevelEnum::values())],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
