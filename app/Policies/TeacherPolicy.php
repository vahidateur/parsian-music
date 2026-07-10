<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function manageInstruments(User $user, Teacher $teacher): bool
    {
        return $this->create($user);
    }
}
