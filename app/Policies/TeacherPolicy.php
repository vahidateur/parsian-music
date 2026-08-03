<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;
use App\Policies\Concerns\ResolvesAdminPersona;

class TeacherPolicy
{
    use ResolvesAdminPersona;

    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $this->isAdministrator($user);
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $this->isAdministrator($user);
    }

    /** Umbrella ability for the teacher-instrument management screen. */
    public function manageInstruments(User $user, Teacher $teacher): bool
    {
        return $this->isAdministrator($user);
    }

    /** Attach: link an instrument to the teacher. */
    public function attachInstrument(User $user, Teacher $teacher): bool
    {
        return $this->manageInstruments($user, $teacher);
    }

    /** Detach: unlink an instrument from the teacher. */
    public function detachInstrument(User $user, Teacher $teacher): bool
    {
        return $this->manageInstruments($user, $teacher);
    }

    /** Status change: move the teacher between TeacherStatusEnum values. */
    public function changeStatus(User $user, Teacher $teacher): bool
    {
        return $this->isAdministrator($user);
    }
}
