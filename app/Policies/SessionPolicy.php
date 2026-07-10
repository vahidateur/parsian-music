<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\ClassSession;
use App\Models\User;

class SessionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN, RoleEnum::TEACHER]);
    }

    public function view(User $user, ClassSession $session): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function update(User $user, ClassSession $session): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ClassSession $session): bool
    {
        return $this->create($user);
    }

    public function markAttendance(User $user, ClassSession $session): bool
    {
        if ($user->role === RoleEnum::TEACHER) {
            return $session->teacher_id === optional($user->teacher)->id;
        }
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }
}
