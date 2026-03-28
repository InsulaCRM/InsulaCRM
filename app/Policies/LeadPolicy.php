<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageLeads();
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->ownsOrCanManage($user, $lead);
    }

    public function create(User $user): bool
    {
        return $user->canManageLeads();
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->ownsOrCanManage($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->ownsOrCanManage($user, $lead);
    }

    public function export(User $user): bool
    {
        return $user->canManageLeads();
    }

    public function bulkUpdate(User $user): bool
    {
        return $user->canManageLeads();
    }

    public function claim(User $user, Lead $lead): bool
    {
        return $this->ownsOrCanManage($user, $lead) || $lead->agent_id === null;
    }

    private function ownsOrCanManage(User $user, Lead $lead): bool
    {
        if (! $user->canManageLeads()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $lead->agent_id === $user->id;
    }
}
