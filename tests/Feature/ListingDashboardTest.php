<?php

namespace Tests\Feature;

use App\Models\Showing;
use Tests\TestCase;

class ListingDashboardTest extends TestCase
{
    public function test_admin_can_view_listings_dashboard(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);

        $response = $this->get('/listings');
        $response->assertStatus(200);
    }

    public function test_dashboard_shows_kpi_cards(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);

        // Create a deal in listing stage
        $this->createDeal(['stage' => 'active_listing']);

        $response = $this->get('/listings');
        $response->assertStatus(200);
        $response->assertSee('Active Listings');
    }

    public function test_dashboard_filters_by_stage(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);

        $this->createDeal(['stage' => 'active_listing']);
        $this->createDeal(['stage' => 'offer_received']);

        $response = $this->get('/listings?stage=active_listing');
        $response->assertStatus(200);
    }

    public function test_dashboard_filters_by_agent(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);

        $this->createDeal(['stage' => 'active_listing']);

        $response = $this->get('/listings?agent=' . $this->adminUser->id);
        $response->assertStatus(200);
    }

    public function test_wholesale_tenant_cannot_access_listings(): void
    {
        $this->actingAsAdmin(['business_mode' => 'wholesale']);

        $response = $this->get('/listings');
        $response->assertStatus(404);
    }

    public function test_listing_agent_can_access_dashboard(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('listing_agent');

        $response = $this->get('/listings');
        $response->assertStatus(200);
    }

    public function test_buyers_agent_can_access_dashboard(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->actingAsRole('buyers_agent');

        $response = $this->get('/listings');
        $response->assertStatus(200);
    }
}
