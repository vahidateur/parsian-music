<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Policies\Concerns\ResolvesAdminPersona;

class StudentPolicy
{
    use ResolvesAdminPersona;

    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user) || $this->isTeacher($user);
    }

    public function view(User $user, Student $student): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function update(User $user, Student $student): bool
    {
        return $this->isAdministrator($user);
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->isAdministrator($user);
    }

    /** Status change: move the student between StudentStatusEnum values. */
    public function changeStatus(User $user, Student $student): bool
    {
        return $this->isAdministrator($user);
    }

    /** Assign: bind the student to a teacher or enrollment owner. */
    public function assign(User $user, Student $student): bool
    {
        return $this->isAdministrator($user);
    }
}
