<?php

namespace App\Services;

use App\Jobs\DispatchWebhook;
use App\Models\Webhook;

class WebhookService
{
    public static function dispatch(string $event, array $payload, int $tenantId): void
    {
        $webhooks = Webhook::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            if (in_array($event, $webhook->events) || in_array('*', $webhook->events)) {
                DispatchWebhook::dispatch($webhook, $event, $payload);
            }
        }
    }
}
