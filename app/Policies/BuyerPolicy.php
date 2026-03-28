<?php

namespace App\Policies;

use App\Models\Buyer;
use App\Models\User;

class BuyerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageBuyers();
    }

    public function view(User $user, Buyer $buyer): bool
    {
        return $user->canManageBuyers();
    }

    public function create(User $user): bool
    {
        return $user->canManageBuyers();
    }

    public function update(User $user, Buyer $buyer): bool
    {
        return $user->canManageBuyers();
    }

    public function delete(User $user, Buyer $buyer): bool
    {
        return $user->canManageBuyers();
    }

    public function bulkDelete(User $user): bool
    {
        return $user->canManageBuyers();
    }

    public function import(User $user): bool
    {
        return $user->canManageBuyers();
    }

    public function export(User $user): bool
    {
        return $user->canManageBuyers();
    }
}
