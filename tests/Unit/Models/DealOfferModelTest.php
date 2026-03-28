<?php

namespace Tests\Unit\Models;

use App\Models\DealOffer;
use Tests\TestCase;

class DealOfferModelTest extends TestCase
{
    public function test_statuses_constant_has_all_values(): void
    {
        $expected = ['pending', 'countered', 'accepted', 'rejected', 'withdrawn', 'expired'];
        $this->assertEquals($expected, array_keys(DealOffer::STATUSES));
    }

    public function test_financing_types_constant_has_all_values(): void
    {
        $expected = ['cash', 'conventional', 'fha', 'va', 'other'];
        $this->assertEquals($expected, array_keys(DealOffer::FINANCING_TYPES));
    }

    public function test_is_expired_when_past_deadline_and_pending(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal();

        $offer = DealOffer::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'buyer_name' => 'John Doe',
            'offer_price' => 350000,
            'status' => 'pending',
            'expiration_date' => now()->subDays(1),
        ]);

        $this->assertTrue($offer->is_expired);
    }

    public function test_not_expired_when_accepted(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal();

        $offer = DealOffer::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'buyer_name' => 'John Doe',
            'offer_price' => 350000,
            'status' => 'accepted',
            'expiration_date' => now()->subDays(1),
        ]);

        $this->assertFalse($offer->is_expired);
    }

    public function test_not_expired_when_no_expiration_date(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal();

        $offer = DealOffer::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'buyer_name' => 'John Doe',
            'offer_price' => 350000,
            'status' => 'pending',
        ]);

        $this->assertFalse($offer->is_expired);
    }

    public function test_contingencies_is_cast_to_array(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal();

        $offer = DealOffer::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'buyer_name' => 'Jane Smith',
            'offer_price' => 400000,
            'contingencies' => ['inspection', 'appraisal', 'financing'],
        ]);

        $fresh = $offer->fresh();
        $this->assertIsArray($fresh->contingencies);
        $this->assertCount(3, $fresh->contingencies);
        $this->assertContains('inspection', $fresh->contingencies);
    }

    public function test_offer_price_is_cast_to_decimal(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal();

        $offer = DealOffer::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'buyer_name' => 'Test Buyer',
            'offer_price' => 250000.50,
        ]);

        $this->assertEquals('250000.50', $offer->fresh()->offer_price);
    }
}
