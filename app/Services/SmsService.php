<?php

namespace App\Services;

use App\Contracts\Integrations\SmsProviderInterface;
use App\Integrations\IntegrationManager;

class SmsService
{
    public function send(string $to, string $message): bool
    {
        $provider = $this->getProvider();

        return $provider->send($to, $message);
    }

    public function getProvider(): SmsProviderInterface
    {
        $manager = app(IntegrationManager::class);

        return $manager->getSmsProvider();
    }
}
