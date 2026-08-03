<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;
use App\Policies\Concerns\ResolvesAdminPersona;

/**
 * Named abilities for the room Operational_List and its form flow.
 *
 * Rooms are list + form only, so `view` mirrors `viewAny`; `toggle` is the named
 * status-change ability behind `admin.rooms.toggle`.
 */
class RoomPolicy
{
    use ResolvesAdminPersona;

    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function view(User $user, Room $room): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function update(User $user, Room $room): bool
    {
        return $this->isAdministrator($user);
    }

    public function delete(User $user, Room $room): bool
    {
        return $this->isAdministrator($user);
    }

    /** Status change: activate/deactivate a room. */
    public function toggle(User $user, Room $room): bool
    {
        return $this->isAdministrator($user);
    }
}
