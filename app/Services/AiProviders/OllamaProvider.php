<?php

namespace App\Services\AiProviders;

use Illuminate\Support\Facades\Http;

class OllamaProvider implements AiProviderInterface
{
    public function __construct(
        protected string $baseUrl = 'http://localhost:11434',
        protected string $model = 'llama3.1',
    ) {
        $this->baseUrl = rtrim($this->baseUrl, '/');
    }

    public function chat(string $systemPrompt, string $userMessage, array $options = []): string
    {
        $response = Http::timeout(120)->post("{$this->baseUrl}/api/chat", [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'stream' => false,
            'options' => [
                'num_predict' => $options['max_tokens'] ?? 1024,
                'temperature' => $options['temperature'] ?? 0.7,
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Ollama error: ' . $response->body());
        }

        return $response->json('message.content', '');
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function listModels(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");

            if ($response->failed()) {
                return [];
            }

            return collect($response->json('models', []))
                ->map(function ($m) {
                    $name = $m['name'] ?? '';
                    $size = isset($m['size']) ? ' (' . round($m['size'] / 1073741824, 1) . ' GB)' : '';
                    return ['id' => $name, 'name' => $name . $size];
                })
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
