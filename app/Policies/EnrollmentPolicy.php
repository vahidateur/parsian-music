<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\StudentEnrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN, RoleEnum::TEACHER]);
    }

    public function view(User $user, StudentEnrollment $enrollment): bool
    {
        if ($user->role === RoleEnum::TEACHER) {
            // Teachers can only see their own enrollments
            return $enrollment->teacher_id === optional($user->teacher)->id;
        }
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function update(User $user, StudentEnrollment $enrollment): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, StudentEnrollment $enrollment): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }
}
