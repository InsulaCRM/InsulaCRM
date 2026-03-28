<?php

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        public Webhook $webhook,
        public string $event,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $body = [
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->payload,
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => $this->event,
            'User-Agent' => 'InsulaCRM-Webhook/1.0',
        ];

        if ($this->webhook->secret) {
            $headers['X-Webhook-Signature'] = hash_hmac('sha256', json_encode($body), $this->webhook->secret);
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($this->webhook->url, $body);

            if ($response->successful()) {
                $this->webhook->update([
                    'last_triggered_at' => now(),
                    'failure_count' => 0,
                ]);
            } else {
                $this->incrementFailure();
            }
        } catch (\Exception $e) {
            $this->incrementFailure();
            throw $e; // Let the queue retry
        }
    }

    protected function incrementFailure(): void
    {
        $this->webhook->increment('failure_count');

        Log::warning('Webhook delivery failed', [
            'webhook_id' => $this->webhook->id,
            'url' => $this->webhook->url,
            'event' => $this->event,
            'failure_count' => $this->webhook->failure_count,
            'tenant_id' => $this->webhook->tenant_id,
        ]);

        if ($this->webhook->failure_count >= 10) {
            $this->webhook->update(['is_active' => false]);
            Log::error('Webhook auto-disabled after 10 consecutive failures', [
                'webhook_id' => $this->webhook->id,
                'url' => $this->webhook->url,
                'tenant_id' => $this->webhook->tenant_id,
            ]);
        }
    }
}
