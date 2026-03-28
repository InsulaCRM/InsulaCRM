<?php

namespace App\Policies;

use App\Models\Showing;
use App\Models\User;

class ShowingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAgent() || $user->isListingAgent() || $user->isBuyersAgent();
    }

    public function view(User $user, Showing $showing): bool
    {
        return $this->ownsOrCanManage($user, $showing);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Showing $showing): bool
    {
        return $this->ownsOrCanManage($user, $showing);
    }

    public function delete(User $user, Showing $showing): bool
    {
        return $this->ownsOrCanManage($user, $showing);
    }

    private function ownsOrCanManage(User $user, Showing $showing): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $showing->agent_id === $user->id;
    }
}
