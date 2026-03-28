<?php

namespace App\Console\Commands;

use App\Models\AiLog;
use App\Models\Deal;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AiStaleDealAlerts extends Command
{
    protected $signature = 'ai:stale-deal-alerts';
    protected $description = 'Analyze stuck deals and send AI recommendations to agents';

    public function handle(): int
    {
        $tenants = Tenant::where('ai_enabled', true)->get();

        foreach ($tenants as $tenant) {
            try {
                $ai = new AiService($tenant);
                if (!$ai->isAvailable()) {
                    continue;
                }

                // Find deals stuck in the same stage for more than 7 days
                $staleDeals = Deal::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->whereNotIn('stage', ['closed_won', 'closed_lost', 'dead'])
                    ->where('stage_changed_at', '<=', now()->subDays(7))
                    ->with(['lead.property', 'agent'])
                    ->limit(5) // Control API costs
                    ->get();

                foreach ($staleDeals as $deal) {
                    try {
                        $advice = $ai->adviseDealStage($deal);

                        AiLog::withoutGlobalScopes()->create([
                            'tenant_id' => $tenant->id,
                            'type' => 'stale_deal_alert',
                            'model_type' => Deal::class,
                            'model_id' => $deal->id,
                            'prompt_summary' => "Stale deal alert for {$deal->title} ({$deal->stage})",
                            'result' => $advice,
                        ]);

                        // Create a notification for the deal agent (or admin if no agent)
                        $recipient = $deal->agent;
                        if (!$recipient) {
                            $recipient = User::where('tenant_id', $tenant->id)
                                ->where('is_active', true)
                                ->whereHas('role', fn($q) => $q->where('name', 'admin'))
                                ->first();
                        }

                        if ($recipient) {
                            $daysStuck = (int) now()->diffInDays($deal->stage_changed_at, true);
                            $recipient->notify(new \App\Notifications\StaleDealAlert($deal, $daysStuck, $advice, $tenant));
                        }

                        Log::info('AI stale deal alert sent', ['deal_id' => $deal->id]);
                    } catch (\Throwable $e) {
                        Log::warning('AI stale deal analysis failed', ['deal_id' => $deal->id, 'error' => $e->getMessage()]);
                    }
                }

                $this->info("Processed {$staleDeals->count()} stale deals for tenant: {$tenant->name}");
            } catch (\Throwable $e) {
                Log::warning('AI stale deal alerts failed for tenant', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            }
        }

        return self::SUCCESS;
    }
}
