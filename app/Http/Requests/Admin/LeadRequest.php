<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Admin\LeadAction;
use App\Enums\LeadPriorityEnum;
use App\Enums\LeadSourceEnum;
use Illuminate\Validation\Rule;

/**
 * Validation contract of the lead Record_Form (create and edit).
 *
 * The status field is never part of this form: creation sets the initial status
 * and every later move goes through the lead status transition action.
 *
 * Requirements: 6.5, 6.7
 */
class LeadRequest extends AdminFormRequest
{
    /**
     * @return array<string, string>
     */
    public function normalizedFields(): array
    {
        return LeadAction::NORMALIZED_FIELDS;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'full_name'               => ['required', 'string', 'max:255'],
            'phone'                   => ['required', 'string', 'max:20'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'age'                     => ['nullable', 'integer', 'min:1', 'max:120'],
            'preferred_instrument_id' => ['nullable', 'exists:instruments,id'],
            'preferred_teacher_id'    => ['nullable', 'exists:teachers,id'],
            'source'                  => ['required', Rule::in(LeadSourceEnum::values())],
            'priority'                => ['nullable', Rule::in(LeadPriorityEnum::values())],
            'assigned_to'             => ['nullable', 'exists:users,id'],
            'notes'                   => ['nullable', 'string'],
            'next_follow_up_at'       => ['nullable', 'date'],
        ];
    }
}
