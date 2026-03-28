<?php

namespace App\Console\Commands;

use App\Mail\DigestEmail;
use App\Models\Activity;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInactiveClientDigest extends Command
{
    protected $signature = 'digest:inactive-clients';
    protected $description = 'Weekly digest of leads with no activity in 30 days';

    public function handle(): int
    {
        $tenants = Tenant::all();
        $count = 0;

        foreach ($tenants as $tenant) {
            $leads = Lead::where('tenant_id', $tenant->id)
                ->whereNotIn('status', ['closed', 'dead'])
                ->whereNotNull('agent_id')
                ->get();

            $inactive = $leads->filter(function ($lead) {
                $lastActivity = Activity::where('lead_id', $lead->id)
                    ->orderByDesc('logged_at')
                    ->first();
                return !$lastActivity || $lastActivity->logged_at->lt(now()->subDays(30));
            });

            if ($inactive->isEmpty()) {
                continue;
            }

            $grouped = $inactive->groupBy('agent_id');

            foreach ($grouped as $agentId => $agentLeads) {
                $agent = User::find($agentId);
                if (!$agent || !$agent->is_active) continue;

                $sections = [
                    [
                        'title' => __('Inactive Leads — No Activity in 30+ Days (:count)', ['count' => $agentLeads->count()]),
                        'items' => $agentLeads->take(20)->map(fn ($l) =>
                            "<strong>{$l->first_name} {$l->last_name}</strong> — " .
                            ucwords(str_replace('_', ' ', $l->status)) .
                            " — " . ucfirst($l->temperature ?? 'unknown')
                        )->toArray(),
                    ],
                ];

                try {
                    Mail::to($agent->email)->send(new DigestEmail(
                        digestTitle: __('Inactive Client Report'),
                        sections: $sections,
                        recipientName: $agent->name,
                    ));
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed: {$agent->email}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Sent {$count} inactive client digest(s).");
        return self::SUCCESS;
    }
}
