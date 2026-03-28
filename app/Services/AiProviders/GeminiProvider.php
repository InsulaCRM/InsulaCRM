<?php

namespace App\Services\AiProviders;

use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProviderInterface
{
    public function __construct(
        protected string $apiKey,
        protected string $model = 'gemini-2.5-flash',
    ) {}

    public function chat(string $systemPrompt, string $userMessage, array $options = []): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::timeout(90)->post($url, [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $userMessage]],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $options['max_tokens'] ?? 8192,
                'temperature' => $options['temperature'] ?? 0.7,
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        // Gemini 2.5 thinking models return thoughts + response in separate parts.
        // Read the last non-thought part to get the actual response.
        $parts = $response->json('candidates.0.content.parts', []);
        $text = '';
        foreach ($parts as $part) {
            if (empty($part['thought'])) {
                $text = $part['text'] ?? '';
            }
        }

        return $text;
    }

    public function testConnection(): bool
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models?key={$this->apiKey}";
            $response = Http::timeout(10)->get($url);
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function listModels(): array
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models?key={$this->apiKey}";
            $response = Http::timeout(10)->get($url);

            if ($response->failed()) {
                return [];
            }

            return collect($response->json('models', []))
                ->filter(fn($m) => str_contains($m['name'] ?? '', 'generateContent'))
                ->map(function ($m) {
                    // Models come as "models/gemini-2.5-flash" — strip prefix
                    $id = str_replace('models/', '', $m['name'] ?? '');
                    return ['id' => $id, 'name' => $m['displayName'] ?? $id];
                })
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
