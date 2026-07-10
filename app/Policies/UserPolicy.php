<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function view(User $user, User $target): bool
    {
        return $user->role->canManage($target->role) || $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function update(User $user, User $target): bool
    {
        // Users can always edit themselves; otherwise must outrank target
        return $user->id === $target->id || $user->role->canManage($target->role);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->role->canManage($target->role) && $user->id !== $target->id;
    }

    public function toggle(User $user, User $target): bool
    {
        return $this->delete($user, $target);
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->role->canManage($target->role) && $user->id !== $target->id;
    }
}
