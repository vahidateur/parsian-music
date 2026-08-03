<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Policies\Concerns\ResolvesAdminPersona;

class LeadPolicy
{
    use ResolvesAdminPersona;

    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
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
        return $this->isAdministrator($user);
    }

    public function update(User $user, Lead $lead): bool
    {
        if ($lead->assigned_to === $user->id) {
            return true;
        }

        return $this->isAdministrator($user);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->isAdministrator($user);
    }

    /** Convert: turn the lead into a student. */
    public function convert(User $user, Lead $lead): bool
    {
        return $this->isAdministrator($user);
    }

    /** Assign: set the owner of the lead. */
    public function assign(User $user, Lead $lead): bool
    {
        return $this->update($user, $lead);
    }

    /** Status change: move the lead between LeadStatusEnum values. */
    public function changeStatus(User $user, Lead $lead): bool
    {
        return $this->update($user, $lead);
    }

    /** Schedule the next follow-up on the lead. */
    public function scheduleFollowUp(User $user, Lead $lead): bool
    {
        return $this->update($user, $lead);
    }
}
