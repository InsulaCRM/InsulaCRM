<?php

namespace Tests\Feature;

use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_access_dashboard(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/settings');
        $response->assertStatus(200);
    }

    public function test_agent_cannot_access_settings(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('agent');

        $response = $this->get('/settings');
        $response->assertStatus(403);
    }

    public function test_field_scout_can_access_dashboard(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('field_scout');

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_field_scout_cannot_access_leads(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('field_scout');

        $response = $this->get('/leads');
        $response->assertStatus(403);
    }

    public function test_acquisition_agent_can_access_leads(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('acquisition_agent');

        $response = $this->get('/leads');
        $response->assertStatus(200);
    }

    public function test_disposition_agent_cannot_access_leads(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('disposition_agent');

        $response = $this->get('/leads');
        $response->assertStatus(403);
    }

    public function test_disposition_agent_can_access_pipeline(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('disposition_agent');

        $response = $this->get('/pipeline');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_reports(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/reports');
        $response->assertStatus(200);
    }

    public function test_agent_cannot_access_reports(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('agent');

        $response = $this->get('/reports');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_plugins(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/settings/plugins');
        $response->assertStatus(200);
    }

    public function test_agent_cannot_view_plugins(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('agent');

        $response = $this->get('/settings/plugins');
        $response->assertStatus(403);
    }
}
