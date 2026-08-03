<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Admin\UserAction;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Validation contract of the account edit form.
 *
 * The role allow-list comes from `RoleEnum::assignableRoleValues()` of the acting
 * user, which is the same set the form renders. This is the privilege-escalation
 * boundary of the role field, not an authorization decision: the named UserPolicy
 * abilities and the self-management boundary stay in the controller.
 *
 * Requirements: 6.5, 6.7
 */
class UpdateUserRequest extends AdminFormRequest
{
    /**
     * @return array<string, string>
     */
    public function normalizedFields(): array
    {
        return UserAction::NORMALIZED_FIELDS;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : null;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone'     => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($userId)],
            'email'     => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role'      => ['required', Rule::in($this->user()->role->assignableRoleValues())],
        ];
    }
}
