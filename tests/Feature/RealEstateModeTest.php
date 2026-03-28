<?php

namespace Tests\Feature;

use App\Services\BusinessModeService;
use Tests\TestCase;

class RealEstateModeTest extends TestCase
{
    public function test_realestate_tenant_gets_realestate_pipeline_stages(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);

        $response = $this->get('/pipeline');
        $response->assertStatus(200);
        $response->assertSee('Listing Agreement');
        $response->assertSee('Active Listing');
    }

    public function test_wholesale_tenant_gets_wholesale_pipeline_stages(): void
    {
        $this->actingAsAdmin(['business_mode' => 'wholesale']);

        $response = $this->get('/pipeline');
        $response->assertStatus(200);
        $response->assertSee('Prospecting');
        $response->assertSee('Dispositions');
    }

    public function test_realestate_sidebar_shows_showings_link(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Showings');
        $response->assertSee('Open Houses');
        $response->assertSee('Listings');
    }

    public function test_wholesale_sidebar_hides_re_links(): void
    {
        $this->actingAsAdmin(['business_mode' => 'wholesale']);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertDontSee('href="/showings"', false);
        $response->assertDontSee('href="/open-houses"', false);
        $response->assertDontSee('href="/listings"', false);
    }

    public function test_mode_middleware_blocks_wholesale_from_showings(): void
    {
        $this->actingAsAdmin(['business_mode' => 'wholesale']);

        $response = $this->get('/showings');
        $response->assertStatus(404);
    }

    public function test_mode_middleware_blocks_wholesale_from_open_houses(): void
    {
        $this->actingAsAdmin(['business_mode' => 'wholesale']);

        $response = $this->get('/open-houses');
        $response->assertStatus(404);
    }

    public function test_mode_middleware_blocks_wholesale_from_listings(): void
    {
        $this->actingAsAdmin(['business_mode' => 'wholesale']);

        $response = $this->get('/listings');
        $response->assertStatus(404);
    }

    public function test_realestate_property_shows_cma_not_arv(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $property = $this->createProperty();

        $response = $this->get("/properties/{$property->id}");
        $response->assertStatus(200);
        $response->assertSee('Comparable Market Analysis');
    }

    public function test_wholesale_property_shows_arv_not_cma(): void
    {
        $this->actingAsAdmin(['business_mode' => 'wholesale']);
        $property = $this->createProperty();

        $response = $this->get("/properties/{$property->id}");
        $response->assertStatus(200);
        $response->assertSee('ARV Worksheet');
    }

    public function test_realestate_deal_shows_commission_calculator(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'active_listing']);

        $response = $this->get("/pipeline/{$deal->id}");
        $response->assertStatus(200);
        $response->assertSee('Commission Calculator');
    }

    public function test_realestate_deal_shows_transaction_checklist(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $response = $this->get("/pipeline/{$deal->id}");
        $response->assertStatus(200);
        $response->assertSee('Transaction Checklist');
    }

    public function test_realestate_deal_shows_offers_section(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'offer_received']);

        $response = $this->get("/pipeline/{$deal->id}");
        $response->assertStatus(200);
        $response->assertSee('Offers');
    }
}
