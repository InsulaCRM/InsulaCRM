<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\Tenant;
use App\Services\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoQualifyLead implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        public Lead $lead,
        public Tenant $tenant,
    ) {}

    public function handle(): void
    {
        $ai = new AiService($this->tenant);
        if (!$ai->isAvailable()) {
            return;
        }

        try {
            $result = $ai->qualifyLead($this->lead);
            if (isset($result['temperature'])) {
                $this->lead->update(['temperature' => $result['temperature']]);
                Log::info('AI auto-qualified lead', [
                    'lead_id' => $this->lead->id,
                    'temperature' => $result['temperature'],
                    'reasoning' => $result['reasoning'] ?? '',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('AI auto-qualify failed', ['lead_id' => $this->lead->id, 'error' => $e->getMessage()]);
        }
    }
}
