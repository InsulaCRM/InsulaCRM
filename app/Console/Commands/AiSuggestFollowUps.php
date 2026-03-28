<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Task;
use App\Models\Tenant;
use App\Services\AiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AiSuggestFollowUps extends Command
{
    protected $signature = 'ai:suggest-follow-ups';
    protected $description = 'Create AI-suggested follow-up tasks for leads with no recent contact';

    public function handle(): int
    {
        $tenants = Tenant::where('ai_enabled', true)->get();

        foreach ($tenants as $tenant) {
            try {
                $ai = new AiService($tenant);
                if (!$ai->isAvailable()) {
                    continue;
                }

                // Find active leads with no activity in the last 5 days and no pending follow-up tasks
                $staleLeads = Lead::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->whereNotIn('status', ['closed_won', 'closed_lost', 'dead', 'do_not_contact'])
                    ->where(function ($q) {
                        $q->whereDoesntHave('activities', fn($a) => $a->where('created_at', '>=', now()->subDays(5)))
                          ->orWhereDoesntHave('activities');
                    })
                    ->whereDoesntHave('tasks', fn($t) => $t->where('is_completed', false))
                    ->with(['property', 'activities'])
                    ->limit(10) // Process max 10 per tenant per run to control API costs
                    ->get();

                foreach ($staleLeads as $lead) {
                    try {
                        $result = $ai->draftFollowUp($lead, 'note');

                        Task::withoutGlobalScopes()->create([
                            'tenant_id' => $tenant->id,
                            'lead_id' => $lead->id,
                            'agent_id' => $lead->agent_id,
                            'title' => 'AI Follow-Up: ' . $lead->full_name,
                            'due_date' => now()->addDay(),
                            'is_completed' => false,
                        ]);

                        Log::info('AI auto-created follow-up task', ['lead_id' => $lead->id]);
                    } catch (\Throwable $e) {
                        Log::warning('AI follow-up suggestion failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
                    }
                }

                $this->info("Processed {$staleLeads->count()} stale leads for tenant: {$tenant->name}");
            } catch (\Throwable $e) {
                Log::warning('AI suggest follow-ups failed for tenant', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            }
        }

        return self::SUCCESS;
    }
}
