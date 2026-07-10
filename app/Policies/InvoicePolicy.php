<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->role === RoleEnum::SUPER_ADMIN;
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $this->create($user);
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, [RoleEnum::SUPER_ADMIN, RoleEnum::ADMIN]);
    }

    public function registerPayment(User $user, Invoice $invoice): bool
    {
        return $this->create($user);
    }
}
