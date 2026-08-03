<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResolvesAdminPersona;

class UserPolicy
{
    use ResolvesAdminPersona;

    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function view(User $user, User $target): bool
    {
        return $user->role->canManage($target->role) || $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
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

    /** Status change: activate/deactivate the target account. */
    public function toggle(User $user, User $target): bool
    {
        return $this->delete($user, $target);
    }

    /** Assign: change the role of the target account. */
    public function assign(User $user, User $target): bool
    {
        return $user->role->canManage($target->role);
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->role->canManage($target->role) && $user->id !== $target->id;
    }
}
