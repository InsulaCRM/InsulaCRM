<?php

namespace App\Services\AiProviders;

interface AiProviderInterface
{
    /**
     * Send a prompt to the AI provider and return the text response.
     */
    public function chat(string $systemPrompt, string $userMessage, array $options = []): string;

    /**
     * Test the connection to the AI provider.
     */
    public function testConnection(): bool;

    /**
     * List available models from the provider.
     * Returns array of ['id' => 'model-id', 'name' => 'Display Name'] entries.
     */
    public function listModels(): array;
}
