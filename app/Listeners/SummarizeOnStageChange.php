<?php

namespace App\Listeners;

use App\Events\DealStageChanged;
use App\Models\Activity;
use App\Models\AiLog;
use App\Models\Deal;
use App\Services\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SummarizeOnStageChange implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 2;
    public int $backoff = 30;

    public function handle(DealStageChanged $event): void
    {
        $deal = $event->deal;
        $deal->load('lead');

        $tenant = $deal->lead?->tenant ?? \App\Models\Tenant::find($deal->tenant_id);
        if (!$tenant || !$tenant->ai_enabled) {
            return;
        }

        $ai = new AiService($tenant);
        if (!$ai->isAvailable()) {
            return;
        }

        try {
            $summary = $ai->summarizeNotes($deal->lead);

            Activity::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'lead_id' => $deal->lead_id,
                'deal_id' => $deal->id,
                'agent_id' => $deal->agent_id,
                'type' => 'note',
                'subject' => 'AI Handoff Summary',
                'body' => "Auto-generated on stage change from " . Deal::stageLabel($event->oldStage) . " to " . Deal::stageLabel($deal->stage) . ":\n\n" . $summary,
                'logged_at' => now(),
            ]);

            AiLog::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'type' => 'stage_change_summary',
                'model_type' => Deal::class,
                'model_id' => $deal->id,
                'prompt_summary' => "Stage change summary: {$event->oldStage} → {$deal->stage}",
                'result' => $summary,
            ]);

            Log::info('AI stage change summary created', ['deal_id' => $deal->id, 'from' => $event->oldStage, 'to' => $deal->stage]);
        } catch (\Throwable $e) {
            Log::warning('AI stage change summary failed', ['deal_id' => $deal->id, 'error' => $e->getMessage()]);
        }
    }
}
