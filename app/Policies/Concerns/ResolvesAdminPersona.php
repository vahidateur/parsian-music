<?php

namespace App\Policies\Concerns;

use App\Enums\RoleEnum;
use App\Models\User;

/**
 * Shared persona resolution for the admin Authorization_Layer.
 *
 * The secretary persona has no dedicated `RoleEnum` value: it is mapped onto the
 * existing `admin` role here, inside the policies. A future dedicated secretary
 * role only has to widen `administrativePersonaRoles()`, so no admin view
 * template, controller body or JavaScript module needs to change.
 */
trait ResolvesAdminPersona
{
    /**
     * Roles carrying the operational admin persona (admin covers secretary).
     *
     * @return array<int, RoleEnum>
     */
    protected function administrativePersonaRoles(): array
    {
        return [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN];
    }

    /** True when the actor operates with the admin/secretary persona. */
    protected function isAdministrator(User $user): bool
    {
        return in_array($user->role, $this->administrativePersonaRoles(), true);
    }

    /** True when the actor operates with the teacher persona. */
    protected function isTeacher(User $user): bool
    {
        return $user->role === RoleEnum::TEACHER;
    }
}
