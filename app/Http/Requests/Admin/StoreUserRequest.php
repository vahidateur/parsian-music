<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Actions\Admin\UserAction;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validation contract of the account create form.
 *
 * The role allow-list comes from `RoleEnum::assignableRoleValues()` of the acting
 * user, which is the same set the form renders. This is the privilege-escalation
 * boundary of the role field, not an authorization decision: the named UserPolicy
 * abilities are still resolved by the controller.
 *
 * Requirements: 6.5, 6.7
 */
class StoreUserRequest extends AdminFormRequest
{
    /**
     * The password is never normalized — it is persisted as submitted, hashed.
     *
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
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone'     => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email'     => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'role'      => ['required', Rule::in($this->user()->role->assignableRoleValues())],
            'password'  => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
