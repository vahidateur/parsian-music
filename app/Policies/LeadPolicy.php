<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function view(User $user, Lead $lead): bool
    {
        if ($lead->assigned_to === $user->id) {
            return true;
        }
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function update(User $user, Lead $lead): bool
    {
        if ($lead->assigned_to === $user->id) {
            return true;
        }
        return $this->create($user);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function convert(User $user, Lead $lead): bool
    {
        return $this->create($user);
    }
}
