<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\SkillLevelEnum;
use Illuminate\Validation\Rule;

/**
 * Validation contract for converting a lead into a student.
 *
 * Requirements: 6.5, 6.7
 */
class ConvertLeadRequest extends AdminFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'skill_level' => ['nullable', Rule::in(SkillLevelEnum::values())],
            'start_date'  => ['nullable', 'date'],
            'notes'       => ['nullable', 'string'],
        ];
    }
}
