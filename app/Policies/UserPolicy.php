<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function manageTeamMember(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->tenant_id === $target->tenant_id;
    }
}
