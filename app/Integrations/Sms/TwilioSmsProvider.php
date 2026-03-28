<?php

namespace App\Integrations\Sms;

use App\Contracts\Integrations\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioSmsProvider implements SmsProviderInterface
{
    protected array $config = [];

    public function driver(): string
    {
        return 'twilio';
    }

    public function name(): string
    {
        return 'Twilio';
    }

    public function send(string $to, string $message): bool
    {
        $accountSid = $this->config['account_sid'] ?? '';
        $authToken = $this->config['auth_token'] ?? '';
        $fromNumber = $this->config['from_number'] ?? '';

        if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
            Log::error('Twilio SMS: Missing configuration (account_sid, auth_token, or from_number).');
            return false;
        }

        try {
            $response = Http::withBasicAuth($accountSid, $authToken)
                ->asForm()
                ->post(
                    "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json",
                    [
                        'To' => $to,
                        'From' => $fromNumber,
                        'Body' => $message,
                    ]
                );

            if ($response->successful()) {
                Log::info('Twilio SMS sent', [
                    'to' => $to,
                    'sid' => $response->json('sid'),
                ]);
                return true;
            }

            Log::error('Twilio SMS failed', [
                'to' => $to,
                'status' => $response->status(),
                'error' => $response->json('message', 'Unknown error'),
                'code' => $response->json('code'),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Twilio SMS exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function requiresConfig(): bool
    {
        return true;
    }

    public function configFields(): array
    {
        return [
            [
                'name' => 'account_sid',
                'label' => 'Account SID',
                'type' => 'text',
                'placeholder' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                'required' => true,
            ],
            [
                'name' => 'auth_token',
                'label' => 'Auth Token',
                'type' => 'password',
                'placeholder' => '',
                'required' => true,
            ],
            [
                'name' => 'from_number',
                'label' => 'From Phone Number',
                'type' => 'text',
                'placeholder' => '+1234567890',
                'required' => true,
                'hint' => 'Your Twilio phone number in E.164 format (e.g. +1234567890).',
            ],
        ];
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }
}
