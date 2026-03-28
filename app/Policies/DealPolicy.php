<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAgent() || $user->isDispositionAgent();
    }

    public function view(User $user, Deal $deal): bool
    {
        return $this->ownsOrCanManage($user, $deal);
    }

    public function update(User $user, Deal $deal): bool
    {
        return $this->ownsOrCanManage($user, $deal);
    }

    public function changeStage(User $user, Deal $deal): bool
    {
        return $this->ownsOrCanManage($user, $deal);
    }

    public function export(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function uploadDocument(User $user, Deal $deal): bool
    {
        return $this->ownsOrCanManage($user, $deal);
    }

    public function notifyBuyer(User $user, Deal $deal): bool
    {
        return $this->ownsOrCanManage($user, $deal);
    }

    private function ownsOrCanManage(User $user, Deal $deal): bool
    {
        if ($user->isAdmin() || $user->isDispositionAgent()) {
            return true;
        }

        return $user->isAgent() && $deal->agent_id === $user->id;
    }
}
