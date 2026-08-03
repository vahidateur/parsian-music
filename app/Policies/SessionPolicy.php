<?php

namespace App\Policies;

use App\Models\ClassSession;
use App\Models\User;
use App\Policies\Concerns\ResolvesAdminPersona;

class SessionPolicy
{
    use ResolvesAdminPersona;

    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user) || $this->isTeacher($user);
    }

    public function view(User $user, ClassSession $session): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function update(User $user, ClassSession $session): bool
    {
        return $this->isAdministrator($user);
    }

    public function delete(User $user, ClassSession $session): bool
    {
        return $this->isAdministrator($user);
    }

    /** Attendance marking is allowed for the owning teacher as well. */
    public function markAttendance(User $user, ClassSession $session): bool
    {
        if ($this->isTeacher($user)) {
            return $session->teacher_id === optional($user->teacher)->id;
        }

        return $this->isAdministrator($user);
    }

    /** Status change: move the session between SessionStatusEnum values. */
    public function changeStatus(User $user, ClassSession $session): bool
    {
        return $this->isAdministrator($user);
    }

    /** Assign: set the teacher or room of the session. */
    public function assign(User $user, ClassSession $session): bool
    {
        return $this->isAdministrator($user);
    }

    /** Generate: create sessions in batch from an enrollment schedule. */
    public function generate(User $user): bool
    {
        return $this->isAdministrator($user);
    }
}
