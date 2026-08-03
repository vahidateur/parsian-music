<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Invoice;
use App\Models\User;
use App\Policies\Concerns\ResolvesAdminPersona;

class InvoicePolicy
{
    use ResolvesAdminPersona;

    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->isAdministrator($user);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->role === RoleEnum::SUPER_ADMIN;
    }

    /** Status change: issue a draft invoice. */
    public function issue(User $user, Invoice $invoice): bool
    {
        return $this->isAdministrator($user);
    }

    /** Status change: cancel an invoice. */
    public function cancel(User $user, Invoice $invoice): bool
    {
        return $this->isAdministrator($user);
    }

    /** Duplicate: copy an invoice into a new draft. */
    public function duplicate(User $user, Invoice $invoice): bool
    {
        return $this->isAdministrator($user);
    }

    /** Payment: register a payment against the invoice. */
    public function registerPayment(User $user, Invoice $invoice): bool
    {
        return $this->isAdministrator($user);
    }

    /** Payment: remove a payment previously registered on the invoice. */
    public function deletePayment(User $user, Invoice $invoice): bool
    {
        return $this->isAdministrator($user);
    }
}
