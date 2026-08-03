<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

/**
 * Validation contract for assigning a lead to an admin user.
 *
 * Requirements: 6.5, 6.7
 */
class AssignLeadRequest extends AdminFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }
}
