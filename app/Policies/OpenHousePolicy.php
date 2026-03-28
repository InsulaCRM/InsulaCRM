<?php

namespace App\Policies;

use App\Models\OpenHouse;
use App\Models\User;

class OpenHousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAgent() || $user->isListingAgent() || $user->isBuyersAgent();
    }

    public function view(User $user, OpenHouse $openHouse): bool
    {
        return $this->ownsOrCanManage($user, $openHouse);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, OpenHouse $openHouse): bool
    {
        return $this->ownsOrCanManage($user, $openHouse);
    }

    public function delete(User $user, OpenHouse $openHouse): bool
    {
        return $this->ownsOrCanManage($user, $openHouse);
    }

    private function ownsOrCanManage(User $user, OpenHouse $openHouse): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $openHouse->agent_id === $user->id;
    }
}
