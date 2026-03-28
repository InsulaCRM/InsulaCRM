<?php

namespace App\Contracts\Integrations;

interface SmsProviderInterface
{
    public function driver(): string;

    public function name(): string;

    public function send(string $to, string $message): bool;

    public function requiresConfig(): bool;

    public function configFields(): array;

    public function setConfig(array $config): void;
}
