<?php

namespace App\Services\AiProviders;

/**
 * Custom OpenAI-compatible API provider.
 * Works with LM Studio, text-generation-webui, LocalAI, llama.cpp server,
 * vLLM, Lemonade Server, and any other service exposing /v1/chat/completions.
 */
class CustomOpenAiProvider extends OpenAiProvider
{
    public function __construct(
        string $apiKey,
        string $model = '',
        string $baseUrl = 'http://localhost:1234',
    ) {
        parent::__construct(
            apiKey: $apiKey ?: 'not-needed',
            model: $model ?: 'default',
            baseUrl: $baseUrl,
        );
    }

    protected function tokenLimitParam(): string
    {
        return 'max_tokens';
    }
}
