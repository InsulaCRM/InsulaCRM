<?php

namespace App\Services;

use App\Events\NewLeadAvailable;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadDistributionService
{
    /**
     * Distribute a lead to an agent based on the tenant's distribution method.
     *
     * Returns the assigned User, or null if the lead should remain unassigned.
     */
    public function distribute(Lead $lead, Tenant $tenant): ?User
    {
        return match ($tenant->distribution_method) {
            'round_robin' => $this->roundRobin($lead, $tenant),
            'shark_tank' => $this->sharkTank($lead, $tenant),
            'hybrid' => $this->hybrid($lead, $tenant),
            'ai_smart' => $this->aiSmart($lead, $tenant),
            default => null,
        };
    }

    /**
     * Round-robin: assign leads to active agents in order.
     */
    protected function roundRobin(Lead $lead, Tenant $tenant): ?User
    {
        $agents = $tenant->users()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($agents->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($lead, $tenant, $agents) {
            // Lock the tenant row to prevent concurrent index updates
            $locked = Tenant::lockForUpdate()->find($tenant->id);

            $index = $locked->round_robin_index ?? 0;
            $agent = $agents[$index % $agents->count()];

            $lead->agent_id = $agent->id;
            $lead->save();

            $locked->round_robin_index = ($index + 1) % $agents->count();
            $locked->save();

            return $agent;
        });
    }

    /**
     * Shark tank: lead stays unassigned and must be claimed by an agent.
     */
    protected function sharkTank(Lead $lead, Tenant $tenant): ?User
    {
        return null;
    }

    /**
     * Hybrid: lead stays unassigned initially (claim window), then a
     * scheduled job will handle fallback assignment.
     */
    protected function hybrid(Lead $lead, Tenant $tenant): ?User
    {
        // Leave the lead unassigned (no agent_id) so agents can claim it
        // during the claim window. After the window expires, the
        // AssignUnclaimedLeads command will auto-assign via round robin.
        $lead->agent_id = null;
        $lead->save();

        NewLeadAvailable::dispatch($lead, $tenant);

        return null;
    }

    /**
     * AI Smart: use AI to analyze agent workload and expertise for optimal assignment.
     * Falls back to round robin if AI is unavailable or fails.
     */
    protected function aiSmart(Lead $lead, Tenant $tenant): ?User
    {
        $agents = $tenant->users()
            ->where('is_active', true)
            ->whereHas('role', fn($q) => $q->whereIn('name', ['admin', 'agent', 'acquisition_agent']))
            ->get();

        if ($agents->isEmpty()) {
            return null;
        }

        $agentProfiles = $agents->map(function ($agent) {
            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'role' => $agent->role?->name ?? 'agent',
                'active_leads' => \App\Models\Lead::withoutGlobalScopes()
                    ->where('agent_id', $agent->id)
                    ->whereNotIn('status', ['closed_won', 'closed_lost', 'dead'])
                    ->count(),
                'closed_deals' => \App\Models\Deal::withoutGlobalScopes()
                    ->where('agent_id', $agent->id)
                    ->where('stage', 'closed_won')
                    ->count(),
                'activities_7d' => \App\Models\Activity::withoutGlobalScopes()
                    ->where('agent_id', $agent->id)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
            ];
        })->toArray();

        try {
            $ai = new AiService($tenant);
            if (!$ai->isAvailable()) {
                return $this->roundRobin($lead, $tenant);
            }

            $chosenId = $ai->routeLead($lead, $agentProfiles);
            if ($chosenId) {
                $lead->agent_id = $chosenId;
                $lead->save();
                return $agents->firstWhere('id', $chosenId);
            }
        } catch (\Throwable $e) {
            Log::warning('AI smart routing failed, falling back to round robin', ['error' => $e->getMessage()]);
        }

        // Fallback to round robin if AI fails
        return $this->roundRobin($lead, $tenant);
    }
}
