<?php

namespace App\Console\Commands;

use App\Models\AiLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAiPipelineDigest extends Command
{
    protected $signature = 'ai:pipeline-digest';
    protected $description = 'Send AI-generated pipeline digest to tenant admins';

    public function handle(): int
    {
        $tenants = Tenant::where('ai_enabled', true)->get();

        foreach ($tenants as $tenant) {
            try {
                $ai = new AiService($tenant);
                if (!$ai->isAvailable()) {
                    continue;
                }

                // Gather KPI data within tenant scope
                $now = now();
                $weekAgo = $now->copy()->subWeek();
                $monthAgo = $now->copy()->subMonth();

                $kpiData = [
                    'total_leads' => \App\Models\Lead::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count(),
                    'leads_this_week' => \App\Models\Lead::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('created_at', '>=', $weekAgo)->count(),
                    'hot_leads' => \App\Models\Lead::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('temperature', 'hot')->count(),
                    'active_deals' => \App\Models\Deal::withoutGlobalScopes()->where('tenant_id', $tenant->id)->whereNotIn('stage', ['closed_won', 'closed_lost', 'dead'])->count(),
                    'deals_closed_this_month' => \App\Models\Deal::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('stage', 'closed_won')->where('updated_at', '>=', $monthAgo)->count(),
                    'total_pipeline_value' => '$' . number_format(\App\Models\Deal::withoutGlobalScopes()->where('tenant_id', $tenant->id)->whereNotIn('stage', ['closed_won', 'closed_lost', 'dead'])->sum('contract_price'), 2),
                    'pending_tasks' => \App\Models\Task::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('is_completed', false)->count(),
                    'overdue_tasks' => \App\Models\Task::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('is_completed', false)->where('due_date', '<', $now)->count(),
                ];

                $digest = $ai->generateWeeklyDigest($kpiData);

                AiLog::withoutGlobalScopes()->create([
                    'tenant_id' => $tenant->id,
                    'type' => 'digest',
                    'prompt_summary' => 'Scheduled pipeline digest',
                    'result' => $digest,
                ]);

                // Send to admin users
                $admins = User::where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->whereHas('role', fn($q) => $q->where('name', 'admin'))
                    ->get();

                foreach ($admins as $admin) {
                    try {
                        Mail::raw($digest, function ($msg) use ($admin, $tenant) {
                            $msg->to($admin->email)
                                ->subject("[{$tenant->name}] AI Pipeline Digest — " . now()->format('M d, Y'));
                        });
                    } catch (\Throwable $e) {
                        Log::warning('Failed to send digest email', ['user_id' => $admin->id, 'error' => $e->getMessage()]);
                    }
                }

                $this->info("Sent digest for tenant: {$tenant->name}");
            } catch (\Throwable $e) {
                Log::warning('AI pipeline digest failed for tenant', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
                $this->warn("Failed for tenant {$tenant->name}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
