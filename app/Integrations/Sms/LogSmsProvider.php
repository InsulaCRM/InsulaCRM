<?php

namespace App\Integrations\Sms;

use App\Contracts\Integrations\SmsProviderInterface;
use Illuminate\Support\Facades\Log;

class LogSmsProvider implements SmsProviderInterface
{
    public function driver(): string
    {
        return 'log';
    }

    public function name(): string
    {
        return 'Log (Development)';
    }

    public function send(string $to, string $message): bool
    {
        Log::channel('single')->info('SMS sent', [
            'to' => $to,
            'message' => $message,
        ]);

        return true;
    }

    public function requiresConfig(): bool
    {
        return false;
    }

    public function configFields(): array
    {
        return [];
    }

    public function setConfig(array $config): void
    {
        // No config needed for log driver
    }
}
