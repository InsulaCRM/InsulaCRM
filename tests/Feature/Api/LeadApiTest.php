<?php

namespace Tests\Feature\Api;

use App\Models\Lead;
use App\Models\Tenant;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTenantWithAdmin([
            'api_key' => 'test-api-key-for-leads',
            'api_enabled' => true,
        ]);
        $this->headers = ['X-API-Key' => 'test-api-key-for-leads'];
    }

    public function test_list_leads(): void
    {
        Lead::factory()->count(3)->create(['tenant_id' => $this->tenant->id, 'agent_id' => $this->adminUser->id]);

        $response = $this->getJson('/api/v1/leads', $this->headers);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_list_leads_filtered_by_status(): void
    {
        $this->createLead(['status' => 'new']);
        $this->createLead(['status' => 'dead']);

        $response = $this->getJson('/api/v1/leads?status=new', $this->headers);
        $response->assertStatus(200);
    }

    public function test_create_lead_via_api(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'first_name' => 'API',
            'last_name' => 'Lead',
            'phone' => '555-0300',
            'email' => 'api@lead.com',
            'source' => 'website',
        ], $this->headers);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('leads', [
            'first_name' => 'API',
            'last_name' => 'Lead',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_create_lead_with_property(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'first_name' => 'Property',
            'last_name' => 'Lead',
            'property_address' => '456 Oak Ave',
            'property_city' => 'Miami',
            'property_state' => 'FL',
            'property_zip' => '33101',
            'property_type' => 'single_family',
        ], $this->headers);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('property_id'));
    }

    public function test_create_lead_detects_duplicate_by_phone(): void
    {
        $this->createLead(['phone' => '555-0400']);

        $response = $this->postJson('/api/v1/leads', [
            'first_name' => 'Dup',
            'last_name' => 'Lead',
            'phone' => '555-0400',
        ], $this->headers);

        $response->assertStatus(200);
        $response->assertJson(['duplicate' => true]);
    }

    public function test_create_lead_detects_duplicate_by_email(): void
    {
        $this->createLead(['email' => 'existing@lead.com']);

        $response = $this->postJson('/api/v1/leads', [
            'first_name' => 'Dup',
            'last_name' => 'Lead',
            'email' => 'existing@lead.com',
        ], $this->headers);

        $response->assertStatus(200);
        $response->assertJson(['duplicate' => true]);
    }

    public function test_create_lead_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'phone' => '555-0500',
        ], $this->headers);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error', 'details']);
    }

    public function test_create_lead_resolves_utm_source_to_ppc(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'first_name' => 'UTM',
            'last_name' => 'Lead',
            'utm_source' => 'google',
            'utm_campaign' => 'spring2026',
        ], $this->headers);

        $response->assertStatus(201);
        $response->assertJson(['source' => 'ppc']);
    }

    public function test_create_lead_defaults_source_to_api(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'first_name' => 'No Source',
            'last_name' => 'Lead',
        ], $this->headers);

        $response->assertStatus(201);
        $response->assertJson(['source' => 'api']);
    }

    public function test_show_lead_via_api(): void
    {
        $lead = $this->createLead();

        $response = $this->getJson("/api/v1/leads/{$lead->id}", $this->headers);
        $response->assertStatus(200);
        $response->assertJson(['id' => $lead->id]);
    }

    public function test_show_lead_from_other_tenant_returns_404(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other', 'slug' => 'other', 'email' => 'other@test.com', 'status' => 'active',
        ]);
        $otherUser = $this->createUserWithRole('admin');
        $otherLead = Lead::factory()->create([
            'tenant_id' => $otherTenant->id,
            'agent_id' => $otherUser->id,
        ]);

        $response = $this->getJson("/api/v1/leads/{$otherLead->id}", $this->headers);
        $response->assertStatus(404);
    }

    public function test_update_lead_via_api(): void
    {
        $lead = $this->createLead(['status' => 'new']);

        $response = $this->putJson("/api/v1/leads/{$lead->id}", [
            'status' => 'contacting',
            'temperature' => 'hot',
        ], $this->headers);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals('contacting', $lead->fresh()->status);
        $this->assertEquals('hot', $lead->fresh()->temperature);
    }

    public function test_update_lead_validates_status(): void
    {
        $lead = $this->createLead();

        $response = $this->putJson("/api/v1/leads/{$lead->id}", [
            'status' => 'invalid_status',
        ], $this->headers);

        $response->assertStatus(422);
    }

    public function test_only_own_tenant_leads_listed(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other', 'slug' => 'other', 'email' => 'other@test.com', 'status' => 'active',
        ]);
        $otherUser = $this->createUserWithRole('admin');

        $this->createLead(['first_name' => 'Mine']);
        Lead::factory()->create([
            'tenant_id' => $otherTenant->id,
            'agent_id' => $otherUser->id,
            'first_name' => 'NotMine',
        ]);

        $response = $this->getJson('/api/v1/leads', $this->headers);
        $response->assertStatus(200);

        $data = $response->json('data');
        $names = array_column($data, 'first_name');
        $this->assertContains('Mine', $names);
        $this->assertNotContains('NotMine', $names);
    }
}
