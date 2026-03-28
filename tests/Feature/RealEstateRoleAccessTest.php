<?php

namespace Tests\Feature;

use Tests\TestCase;

class RealEstateRoleAccessTest extends TestCase
{
    // ── Listing Agent ────────────────────────

    public function test_listing_agent_can_access_dashboard(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('listing_agent');

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_listing_agent_can_access_leads(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('listing_agent');

        $response = $this->get('/leads');
        $response->assertStatus(200);
    }

    public function test_listing_agent_can_access_pipeline(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('listing_agent');

        $response = $this->get('/pipeline');
        $response->assertStatus(200);
    }

    public function test_listing_agent_can_access_showings(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('listing_agent');

        $response = $this->get('/showings');
        $response->assertStatus(200);
    }

    public function test_listing_agent_can_access_open_houses(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('listing_agent');

        $response = $this->get('/open-houses');
        $response->assertStatus(200);
    }

    public function test_listing_agent_can_access_properties(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('listing_agent');

        $response = $this->get('/properties');
        $response->assertStatus(200);
    }

    public function test_listing_agent_can_access_calendar(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('listing_agent');

        $response = $this->get('/calendar');
        $response->assertStatus(200);
    }

    public function test_listing_agent_cannot_access_settings(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('listing_agent');

        $response = $this->get('/settings');
        $response->assertStatus(403);
    }

    // ── Buyers Agent ────────────────────────

    public function test_buyers_agent_can_access_dashboard(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('buyers_agent');

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_buyers_agent_can_access_leads(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('buyers_agent');

        $response = $this->get('/leads');
        $response->assertStatus(200);
    }

    public function test_buyers_agent_can_access_pipeline(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('buyers_agent');

        $response = $this->get('/pipeline');
        $response->assertStatus(200);
    }

    public function test_buyers_agent_can_access_showings(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('buyers_agent');

        $response = $this->get('/showings');
        $response->assertStatus(200);
    }

    public function test_buyers_agent_can_access_buyers(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('buyers_agent');

        $response = $this->get('/buyers');
        $response->assertStatus(200);
    }

    public function test_buyers_agent_cannot_access_settings(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('buyers_agent');

        $response = $this->get('/settings');
        $response->assertStatus(403);
    }

    // ── Wholesale roles cannot access RE routes ────────────

    public function test_acquisition_agent_cannot_access_showings(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'wholesale']);
        $this->actingAsRole('acquisition_agent');

        $response = $this->get('/showings');
        $response->assertStatus(403);
    }

    public function test_field_scout_cannot_access_showings(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('field_scout');

        $response = $this->get('/showings');
        $response->assertStatus(403);
    }

    public function test_disposition_agent_cannot_access_showings(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('disposition_agent');

        $response = $this->get('/showings');
        $response->assertStatus(403);
    }
}
