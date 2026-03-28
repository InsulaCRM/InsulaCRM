<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AssignUnclaimedLeads extends Command
{
    protected $signature = 'leads:assign-unclaimed';

    protected $description = 'Assign unclaimed hybrid leads via round robin after the claim window expires';

    public function handle(): int
    {
        $tenants = Tenant::where('distribution_method', 'hybrid')->get();

        $totalAssigned = 0;

        foreach ($tenants as $tenant) {
            $claimWindow = $tenant->claim_window_minutes ?? 3;
            $cutoff = Carbon::now()->subMinutes($claimWindow);

            // Find unclaimed leads older than the claim window.
            // Use withoutGlobalScopes so TenantScope doesn't interfere.
            $unclaimedLeads = Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereNull('agent_id')
                ->where('created_at', '<=', $cutoff)
                ->orderBy('created_at')
                ->get();

            if ($unclaimedLeads->isEmpty()) {
                continue;
            }

            $agents = $tenant->users()
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            if ($agents->isEmpty()) {
                $this->warn("Tenant [{$tenant->name}] has no active agents — skipping.");
                continue;
            }

            $index = $tenant->round_robin_index ?? 0;

            foreach ($unclaimedLeads as $lead) {
                $agent = $agents[$index % $agents->count()];

                $lead->agent_id = $agent->id;
                $lead->save();

                $index = ($index + 1) % $agents->count();
                $totalAssigned++;
            }

            $tenant->round_robin_index = $index;
            $tenant->save();
        }

        $this->info("Assigned {$totalAssigned} unclaimed lead(s) via round robin.");

        return self::SUCCESS;
    }
}
