<?php

namespace Tests\Feature;

use Tests\TestCase;

class SettingsTest extends TestCase
{
    public function test_admin_can_view_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/settings');
        $response->assertStatus(200);
    }

    public function test_admin_can_update_general_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->put('/settings/general', [
            'name' => 'Updated Company',
            'timezone' => 'America/Chicago',
            'currency' => 'EUR',
            'date_format' => 'd/m/Y',
            'country' => 'NL',
            'measurement_system' => 'metric',
            'locale' => 'nl',
        ]);

        $response->assertRedirect('/settings?tab=general');
        $this->assertEquals('Updated Company', $this->tenant->fresh()->name);
        $this->assertEquals('nl', $this->tenant->fresh()->locale);
    }

    public function test_admin_can_invite_team_member(): void
    {
        $this->actingAsAdmin();

        $agentRole = \App\Models\Role::where('name', 'agent')->first();

        $response = $this->post('/settings/invite-agent', [
            'name' => 'New Agent',
            'email' => 'newagent@test.com',
            'password' => 'password123',
            'role_id' => $agentRole->id,
        ]);

        $response->assertRedirect('/settings?tab=team');
        $this->assertDatabaseHas('users', [
            'email' => 'newagent@test.com',
            'tenant_id' => $this->tenant->id,
            'role_id' => $agentRole->id,
        ]);
    }

    public function test_admin_can_toggle_agent(): void
    {
        $this->actingAsAdmin();
        $agent = $this->createUserWithRole('agent', ['is_active' => true]);

        $response = $this->patch("/settings/agents/{$agent->id}/toggle");

        $response->assertRedirect('/settings?tab=team');
        $this->assertFalse($agent->fresh()->is_active);
    }

    public function test_admin_can_update_distribution_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->put('/settings/distribution', [
            'distribution_method' => 'shark_tank',
            'claim_window_minutes' => 5,
        ]);

        $response->assertRedirect('/settings?tab=distribution');
        $this->assertEquals('shark_tank', $this->tenant->fresh()->distribution_method);
    }

    public function test_admin_can_generate_api_key(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/settings/api/generate-key');

        $response->assertRedirect('/settings?tab=api');
        $this->assertNotNull($this->tenant->fresh()->api_key);
        $this->assertTrue($this->tenant->fresh()->api_enabled);
    }

    public function test_admin_can_toggle_api(): void
    {
        $this->actingAsAdmin();
        $this->tenant->update(['api_enabled' => true, 'api_key' => 'test123']);

        $response = $this->post('/settings/api/toggle');

        $response->assertRedirect('/settings?tab=api');
        $this->assertFalse($this->tenant->fresh()->api_enabled);
    }

    public function test_admin_can_add_custom_option(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/settings/custom-options', [
            'field_type' => 'property_type',
            'option_name' => 'Warehouse',
        ]);

        $response->assertRedirect('/settings?tab=custom-fields');
        $this->assertNotNull($this->tenant->fresh()->custom_options['property_type']['warehouse'] ?? null);
    }

    public function test_admin_can_add_custom_lead_source(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/settings/lead-sources', [
            'lead_source_name' => 'Zillow',
        ]);

        $response->assertRedirect('/settings?tab=lead-costs');
        $customSources = $this->tenant->fresh()->custom_lead_sources;
        $this->assertNotEmpty($customSources);
        $this->assertEquals('zillow', $customSources[0]['slug']);
    }

    public function test_cannot_invite_with_invalid_role(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/settings/invite-agent', [
            'name' => 'Hacker',
            'email' => 'hacker@test.com',
            'password' => 'password123',
            'role_id' => 99999,
        ]);

        $response->assertSessionHasErrors('role_id');
    }

    public function test_language_list_api(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/settings/languages');
        $response->assertStatus(200);
        $response->assertJsonStructure(['languages']);
    }
}
