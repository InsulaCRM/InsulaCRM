<?php

namespace App\Console\Commands;

use App\Mail\DigestEmail;
use App\Models\Deal;
use App\Models\Tenant;
use App\Models\TransactionChecklist;
use App\Models\User;
use App\Services\BusinessModeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendExpiringContingencies extends Command
{
    protected $signature = 'digest:expiring-contingencies';
    protected $description = 'Alert about deals with due diligence ending within 3 days (RE mode)';

    public function handle(): int
    {
        $tenants = Tenant::where('business_mode', 'realestate')->get();
        $count = 0;

        foreach ($tenants as $tenant) {
            $deals = Deal::where('tenant_id', $tenant->id)
                ->whereNotIn('stage', ['closed_won', 'closed_lost'])
                ->whereNotNull('due_diligence_end_date')
                ->where('due_diligence_end_date', '<=', now()->addDays(3))
                ->where('due_diligence_end_date', '>=', now())
                ->with(['lead', 'agent'])
                ->get();

            if ($deals->isEmpty()) {
                continue;
            }

            // Group by agent
            $grouped = $deals->groupBy('agent_id');

            foreach ($grouped as $agentId => $agentDeals) {
                $agent = User::find($agentId);
                if (!$agent || !$agent->is_active) continue;

                $items = $agentDeals->map(function ($deal) {
                    $daysLeft = now()->diffInDays($deal->due_diligence_end_date);
                    $incomplete = TransactionChecklist::where('deal_id', $deal->id)
                        ->where('is_completed', false)->count();
                    return "<strong>{$deal->title}</strong> — " .
                        __(':days day(s) left', ['days' => $daysLeft]) .
                        ($incomplete > 0 ? ", <span class=\"badge badge-red\">{$incomplete} " . __('incomplete items') . "</span>" : '');
                })->toArray();

                $sections = [
                    ['title' => __('Expiring Contingencies'), 'items' => $items],
                ];

                try {
                    Mail::to($agent->email)->send(new DigestEmail(
                        digestTitle: __('Due Diligence Deadline Alert'),
                        sections: $sections,
                        recipientName: $agent->name,
                    ));
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed: {$agent->email}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Sent {$count} expiring contingency alert(s).");
        return self::SUCCESS;
    }
}
