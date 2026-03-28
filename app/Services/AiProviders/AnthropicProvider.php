<?php

namespace App\Services\AiProviders;

use Illuminate\Support\Facades\Http;

class AnthropicProvider implements AiProviderInterface
{
    public function __construct(
        protected string $apiKey,
        protected string $model = 'claude-sonnet-4-6',
    ) {}

    public function chat(string $systemPrompt, string $userMessage, array $options = []): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => $options['max_tokens'] ?? 1024,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Anthropic API error: ' . $response->body());
        }

        $content = $response->json('content', []);
        return collect($content)->where('type', 'text')->pluck('text')->implode('');
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(10)->get('https://api.anthropic.com/v1/models');

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function listModels(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(10)->get('https://api.anthropic.com/v1/models');

            if ($response->failed()) {
                return $this->fallbackModels();
            }

            $models = collect($response->json('data', []))
                ->pluck('id')
                ->filter()
                ->sort()
                ->values();

            return $models->isEmpty()
                ? $this->fallbackModels()
                : $models->map(fn($id) => ['id' => $id, 'name' => $id])->toArray();
        } catch (\Throwable $e) {
            return $this->fallbackModels();
        }
    }

    protected function fallbackModels(): array
    {
        return [
            ['id' => 'claude-sonnet-4-6', 'name' => 'Claude Sonnet 4.6'],
            ['id' => 'claude-opus-4-6', 'name' => 'Claude Opus 4.6'],
            ['id' => 'claude-haiku-4-5-20251001', 'name' => 'Claude Haiku 4.5'],
        ];
    }
}
