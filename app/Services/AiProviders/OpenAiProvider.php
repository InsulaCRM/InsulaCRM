<?php

namespace App\Services\AiProviders;

use Illuminate\Support\Facades\Http;

class OpenAiProvider implements AiProviderInterface
{
    protected string $baseUrl;

    public function __construct(
        protected string $apiKey,
        protected string $model = 'gpt-4o-mini',
        ?string $baseUrl = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?: 'https://api.openai.com', '/');
    }

    public function chat(string $systemPrompt, string $userMessage, array $options = []): string
    {
        $body = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
        ];
        $body[$this->tokenLimitParam()] = $options['max_tokens'] ?? 1024;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->timeout(60)->post("{$this->baseUrl}/v1/chat/completions", $body);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content', '');
    }

    protected function tokenLimitParam(): string
    {
        return 'max_completion_tokens';
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(10)->get("{$this->baseUrl}/v1/models");

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function listModels(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(10)->get("{$this->baseUrl}/v1/models");

            if ($response->failed()) {
                return [];
            }

            $models = collect($response->json('data', []))
                ->pluck('id')
                ->filter()
                ->sort()
                ->values();

            return $models->map(fn($id) => ['id' => $id, 'name' => $id])->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
