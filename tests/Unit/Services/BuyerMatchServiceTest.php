<?php

namespace Tests\Unit\Services;

use App\Models\Buyer;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\BuyerMatchService;
use Tests\TestCase;

class BuyerMatchServiceTest extends TestCase
{
    private BuyerMatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BuyerMatchService();
        $this->actingAsAdmin();
    }

    private function setupDealWithProperty(array $propertyAttrs, array $dealAttrs = []): \App\Models\Deal
    {
        $lead = $this->createLead();
        $this->createProperty(array_merge(['lead_id' => $lead->id], $propertyAttrs));
        return $this->createDeal(array_merge(['lead_id' => $lead->id], $dealAttrs));
    }

    public function test_matches_buyer_by_zip_code(): void
    {
        $deal = $this->setupDealWithProperty(
            ['zip_code' => '33101', 'property_type' => 'commercial', 'state' => 'XX'],
            ['contract_price' => 999999]
        );

        Buyer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'preferred_zip_codes' => ['33101'],
            'preferred_property_types' => ['land'],
            'preferred_states' => ['YY'],
            'max_purchase_price' => 100000,
        ]);

        $matches = $this->service->matchForDeal($deal);

        $this->assertCount(1, $matches);
        $this->assertEquals(30, $matches->first()['score']);
    }

    public function test_matches_buyer_by_property_type(): void
    {
        $deal = $this->setupDealWithProperty(
            ['property_type' => 'single_family', 'zip_code' => '00000', 'state' => 'XX'],
            ['contract_price' => 999999]
        );

        Buyer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'preferred_property_types' => ['single_family'],
            'preferred_zip_codes' => ['99999'],
            'preferred_states' => ['YY'],
            'max_purchase_price' => 100000,
        ]);

        $matches = $this->service->matchForDeal($deal);

        $this->assertCount(1, $matches);
        $this->assertEquals(25, $matches->first()['score']);
    }

    public function test_matches_buyer_by_price(): void
    {
        $deal = $this->setupDealWithProperty(
            ['zip_code' => '00000', 'property_type' => 'land', 'state' => 'XX'],
            ['contract_price' => 150000]
        );

        Buyer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'max_purchase_price' => 200000,
            'preferred_zip_codes' => ['99999'],
            'preferred_property_types' => ['commercial'],
            'preferred_states' => ['YY'],
        ]);

        $matches = $this->service->matchForDeal($deal);

        $this->assertCount(1, $matches);
        $this->assertEquals(20, $matches->first()['score']);
    }

    public function test_perfect_match_scores_ninety(): void
    {
        $deal = $this->setupDealWithProperty(
            ['zip_code' => '33101', 'property_type' => 'single_family', 'state' => 'FL'],
            ['contract_price' => 150000]
        );

        Buyer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'preferred_zip_codes' => ['33101'],
            'preferred_property_types' => ['single_family'],
            'preferred_states' => ['FL'],
            'max_purchase_price' => 200000,
        ]);

        $matches = $this->service->matchForDeal($deal);

        $this->assertCount(1, $matches);
        $this->assertEquals(90, $matches->first()['score']);
    }

    public function test_no_match_excluded_from_results(): void
    {
        $deal = $this->setupDealWithProperty(
            ['zip_code' => '00000', 'property_type' => 'land', 'state' => 'XX'],
            ['contract_price' => 999999]
        );

        Buyer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'preferred_zip_codes' => ['99999'],
            'preferred_property_types' => ['commercial'],
            'preferred_states' => ['YY'],
            'max_purchase_price' => 100000,
        ]);

        $matches = $this->service->matchForDeal($deal);
        $this->assertCount(0, $matches);
    }

    public function test_matches_persisted_to_database(): void
    {
        $deal = $this->setupDealWithProperty(
            ['zip_code' => '33101', 'property_type' => 'single_family', 'state' => 'FL'],
            ['contract_price' => 150000]
        );

        $buyer = Buyer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'preferred_zip_codes' => ['33101'],
            'preferred_property_types' => ['single_family'],
            'preferred_states' => ['FL'],
            'max_purchase_price' => 200000,
        ]);

        $this->service->matchForDeal($deal);

        $this->assertDatabaseHas('deal_buyer_matches', [
            'deal_id' => $deal->id,
            'buyer_id' => $buyer->id,
            'match_score' => 90,
        ]);
    }

    public function test_results_sorted_by_score_descending(): void
    {
        $deal = $this->setupDealWithProperty(
            ['zip_code' => '33101', 'property_type' => 'single_family', 'state' => 'FL'],
            ['contract_price' => 150000]
        );

        Buyer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'preferred_zip_codes' => ['33101'],
            'preferred_property_types' => ['single_family'],
            'preferred_states' => ['FL'],
            'max_purchase_price' => 200000,
        ]);

        Buyer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'preferred_zip_codes' => ['33101'],
            'preferred_property_types' => ['commercial'],
            'preferred_states' => ['TX'],
            'max_purchase_price' => 100000,
        ]);

        $matches = $this->service->matchForDeal($deal);

        $this->assertCount(2, $matches);
        $this->assertGreaterThan($matches->last()['score'], $matches->first()['score']);
    }

    public function test_only_matches_buyers_from_same_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Company', 'slug' => 'other-company', 'email' => 'other@test.com', 'status' => 'active',
        ]);

        $deal = $this->setupDealWithProperty(
            ['zip_code' => '33101', 'property_type' => 'single_family', 'state' => 'FL'],
            ['contract_price' => 150000]
        );

        Buyer::factory()->create([
            'tenant_id' => $otherTenant->id,
            'preferred_zip_codes' => ['33101'],
            'preferred_property_types' => ['single_family'],
            'preferred_states' => ['FL'],
            'max_purchase_price' => 200000,
        ]);

        $matches = $this->service->matchForDeal($deal);
        $this->assertCount(0, $matches);
    }
}
