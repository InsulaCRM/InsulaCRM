<?php

namespace Tests\Feature;

use App\Services\OpenSignService;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class OpenSignWebhookTest extends TestCase
{
    public function test_invalid_signature_is_rejected_before_processing(): void
    {
        Config::set('services.opensign.webhook_secret', 'test-secret');

        $response = $this->postJson('/api/v1/webhooks/opensign', [
            'objectId' => 'opensign-123',
            'event' => 'completed',
        ], [
            'x-webhook-signature' => 'invalid',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid OpenSign webhook signature.']);
    }

    public function test_missing_webhook_secret_returns_service_unavailable(): void
    {
        Config::set('services.opensign.webhook_secret', '');

        $response = $this->postJson('/api/v1/webhooks/opensign', [
            'objectId' => 'opensign-123',
            'event' => 'completed',
        ]);

        $response->assertStatus(503)
            ->assertJson(['error' => 'OpenSign webhook secret is not configured.']);
    }

    public function test_valid_signature_is_forwarded_to_opensign_service(): void
    {
        Config::set('services.opensign.webhook_secret', 'test-secret');

        $payload = [
            'objectId' => 'opensign-123',
            'event' => 'completed',
        ];

        $raw = json_encode($payload);
        $signature = hash_hmac('sha256', $raw, 'test-secret');

        $record = new \App\Models\DealSignatureRequest([
            'id' => 42,
            'status' => 'completed',
        ]);

        $mock = Mockery::mock(OpenSignService::class);
        $mock->shouldReceive('handleWebhook')
            ->once()
            ->with($payload)
            ->andReturn($record);

        $this->app->instance(OpenSignService::class, $mock);

        $response = $this->call('POST', '/api/v1/webhooks/opensign', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], $raw);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'signature_request_id' => 42,
                'status' => 'completed',
            ]);
    }

    public function test_valid_signature_with_unknown_document_is_acknowledged(): void
    {
        Config::set('services.opensign.webhook_secret', 'test-secret');

        $payload = [
            'objectId' => 'unknown-object',
            'event' => 'signed',
        ];

        $raw = json_encode($payload);
        $signature = hash_hmac('sha256', $raw, 'test-secret');

        $mock = Mockery::mock(OpenSignService::class);
        $mock->shouldReceive('handleWebhook')
            ->once()
            ->with($payload)
            ->andReturn(null);

        $this->app->instance(OpenSignService::class, $mock);

        $response = $this->call('POST', '/api/v1/webhooks/opensign', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], $raw);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'ignored' => true,
            ]);
    }
}
