<?php

namespace Tests\Feature;

use App\Models\DealSignatureRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenSignSettingsTest extends TestCase
{
    public function test_admin_can_save_tenant_scoped_opensign_settings_with_encrypted_secrets(): void
    {
        $this->actingAsAdmin();

        $response = $this->put(route('settings.updateOpenSign'), [
            'opensign_base_url' => 'https://sandbox.opensignlabs.com/api/v1.2',
            'opensign_api_token' => 'tenant-api-token',
            'opensign_webhook_secret' => 'tenant-webhook-secret',
            'opensign_sender_name' => 'Test Company',
            'opensign_sender_email' => 'sign@example.com',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'integrations']));

        $options = $this->tenant->fresh()->custom_options['opensign'];

        $this->assertSame('https://sandbox.opensignlabs.com/api/v1.2', $options['base_url']);
        $this->assertSame('Test Company', $options['sender_name']);
        $this->assertSame('sign@example.com', $options['sender_email']);
        $this->assertNotSame('tenant-api-token', $options['api_token']);
        $this->assertNotSame('tenant-webhook-secret', $options['webhook_secret']);
        $this->assertSame('tenant-api-token', decrypt($options['api_token']));
        $this->assertSame('tenant-webhook-secret', decrypt($options['webhook_secret']));
    }

    public function test_blank_secrets_preserve_existing_tenant_credentials(): void
    {
        $this->actingAsAdmin([
            'custom_options' => [
                'opensign' => [
                    'base_url' => 'https://sandbox.opensignlabs.com/api/v1.2',
                    'api_token' => encrypt('existing-token'),
                    'webhook_secret' => encrypt('existing-secret'),
                    'sender_name' => 'Existing Sender',
                    'sender_email' => 'existing@example.com',
                ],
            ],
        ]);

        $this->put(route('settings.updateOpenSign'), [
            'opensign_base_url' => 'https://sandbox.opensignlabs.com/api/v1.2',
            'opensign_api_token' => '',
            'opensign_webhook_secret' => '',
            'opensign_sender_name' => 'Updated Sender',
            'opensign_sender_email' => 'updated@example.com',
        ])->assertRedirect();

        $options = $this->tenant->fresh()->custom_options['opensign'];

        $this->assertSame('existing-token', decrypt($options['api_token']));
        $this->assertSame('existing-secret', decrypt($options['webhook_secret']));
        $this->assertSame('Updated Sender', $options['sender_name']);
        $this->assertSame('updated@example.com', $options['sender_email']);
    }

    public function test_connection_test_uses_tenant_saved_token(): void
    {
        $this->actingAsAdmin([
            'custom_options' => [
                'opensign' => [
                    'base_url' => 'https://sandbox.opensignlabs.com/api/v1.2',
                    'api_token' => encrypt('tenant-token'),
                    'webhook_secret' => encrypt('tenant-secret'),
                ],
            ],
        ]);

        Http::fake([
            'https://sandbox.opensignlabs.com/api/v1/getuser' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $response = $this->postJson(route('settings.testOpenSign'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'OpenSign connection successful.',
            ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.opensignlabs.com/api/v1/getuser'
                && $request->hasHeader('x-api-token', 'tenant-token');
        });
    }

    public function test_webhook_signature_is_verified_using_the_matching_tenant_secret(): void
    {
        $this->actingAsAdmin([
            'custom_options' => [
                'opensign' => [
                    'webhook_secret' => encrypt('tenant-secret'),
                ],
            ],
        ]);

        $deal = $this->createDeal(['stage' => 'dispositions']);

        DealSignatureRequest::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'document_type' => 'wholesale_contract',
            'title' => 'Purchase Agreement',
            'status' => 'sent',
            'opensign_object_id' => 'tenant-object-123',
        ]);

        $payload = [
            'objectId' => 'tenant-object-123',
            'event' => 'signed',
        ];
        $raw = json_encode($payload);

        $validSignature = hash_hmac('sha256', $raw, 'tenant-secret');

        $response = $this->call('POST', '/api/v1/webhooks/opensign', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $validSignature,
        ], $raw);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'signature_request_id' => 1,
                'status' => 'partially_signed',
            ]);

        $wrongSignature = hash_hmac('sha256', $raw, 'wrong-secret');

        $response = $this->call('POST', '/api/v1/webhooks/opensign', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $wrongSignature,
        ], $raw);

        $response->assertStatus(401);
    }
}
