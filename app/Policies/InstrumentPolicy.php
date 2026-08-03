<?php

namespace App\Policies;

use App\Models\Instrument;
use App\Models\User;
use App\Policies\Concerns\ResolvesAdminPersona;

/**
 * Named abilities for the instrument Operational_List and its form flow.
 *
 * Instruments are list + form only, so `view` mirrors `viewAny`; `toggle` is the
 * named status-change ability behind `admin.instruments.toggle`.
 */
class InstrumentPolicy
{
    use ResolvesAdminPersona;

    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function view(User $user, Instrument $instrument): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function update(User $user, Instrument $instrument): bool
    {
        return $this->isAdministrator($user);
    }

    public function delete(User $user, Instrument $instrument): bool
    {
        return $this->isAdministrator($user);
    }

    /** Status change: activate/deactivate an instrument. */
    public function toggle(User $user, Instrument $instrument): bool
    {
        return $this->isAdministrator($user);
    }
}
