<?php

namespace App\Policies;

use App\Models\StudentEnrollment;
use App\Models\User;
use App\Policies\Concerns\ResolvesAdminPersona;

class EnrollmentPolicy
{
    use ResolvesAdminPersona;

    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user) || $this->isTeacher($user);
    }

    public function view(User $user, StudentEnrollment $enrollment): bool
    {
        if ($this->isTeacher($user)) {
            // Teachers can only see their own enrollments
            return $enrollment->teacher_id === optional($user->teacher)->id;
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function update(User $user, StudentEnrollment $enrollment): bool
    {
        return $this->isAdministrator($user);
    }

    public function delete(User $user, StudentEnrollment $enrollment): bool
    {
        return $this->isAdministrator($user);
    }

    /** Status change: move the enrollment between EnrollmentStatusEnum values. */
    public function changeStatus(User $user, StudentEnrollment $enrollment): bool
    {
        return $this->isAdministrator($user);
    }

    /** Assign: set the teacher, instrument or room of the enrollment. */
    public function assign(User $user, StudentEnrollment $enrollment): bool
    {
        return $this->isAdministrator($user);
    }
}
