<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    public function test_api_requires_key(): void
    {
        $response = $this->getJson('/api/v1/leads');
        $response->assertStatus(401);
        $response->assertJson(['error' => 'API key required. Pass via X-API-Key header.']);
    }

    public function test_api_rejects_invalid_key(): void
    {
        $response = $this->getJson('/api/v1/leads', [
            'X-API-Key' => 'invalid-key',
        ]);
        $response->assertStatus(403);
    }

    public function test_api_rejects_disabled_key(): void
    {
        $this->createTenantWithAdmin([
            'api_key' => 'valid-key-disabled',
            'api_enabled' => false,
        ]);

        $response = $this->getJson('/api/v1/leads', [
            'X-API-Key' => 'valid-key-disabled',
        ]);
        $response->assertStatus(403);
    }

    public function test_api_accepts_valid_header_key(): void
    {
        $this->createTenantWithAdmin([
            'api_key' => 'valid-api-key-123',
            'api_enabled' => true,
        ]);

        $response = $this->getJson('/api/v1/leads', [
            'X-API-Key' => 'valid-api-key-123',
        ]);
        $response->assertStatus(200);
    }

    public function test_api_rejects_key_via_query_param(): void
    {
        $this->createTenantWithAdmin([
            'api_key' => 'valid-api-key-456',
            'api_enabled' => true,
        ]);

        // API key via query param is no longer accepted (security: prevents key leaking in logs)
        $response = $this->getJson('/api/v1/leads?api_key=valid-api-key-456');
        $response->assertStatus(401);
    }

    public function test_api_rejects_suspended_tenant(): void
    {
        $this->createTenantWithAdmin([
            'api_key' => 'suspended-key',
            'api_enabled' => true,
            'status' => 'suspended',
        ]);

        $response = $this->getJson('/api/v1/leads', [
            'X-API-Key' => 'suspended-key',
        ]);
        $response->assertStatus(403);
        $response->assertJson(['error' => 'Account is suspended.']);
    }
}
