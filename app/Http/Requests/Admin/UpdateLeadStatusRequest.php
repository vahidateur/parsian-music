<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\LeadStatusEnum;
use Illuminate\Validation\Rule;

/**
 * Validation contract for a lead status move.
 *
 * Membership of the target status is checked here; whether the transition itself
 * is legal stays with the lead status machine.
 *
 * Requirements: 6.5, 6.7
 */
class UpdateLeadStatusRequest extends AdminFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(LeadStatusEnum::values())],
        ];
    }
}
