<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

/**
 * Validation contract for scheduling the next lead follow-up.
 *
 * Requirements: 6.5, 6.7
 */
class ScheduleLeadFollowUpRequest extends AdminFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'next_follow_up_at' => ['required', 'date'],
        ];
    }
}
