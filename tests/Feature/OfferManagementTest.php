<?php

namespace Tests\Feature;

use App\Models\DealOffer;
use Tests\TestCase;

class OfferManagementTest extends TestCase
{
    public function test_can_create_offer_on_deal(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'offer_received']);

        $response = $this->post("/pipeline/{$deal->id}/offers", [
            'buyer_name' => 'John Doe',
            'buyer_agent_name' => 'Jane Agent',
            'offer_price' => 350000,
            'earnest_money' => 5000,
            'financing_type' => 'conventional',
            'contingencies' => ['inspection', 'appraisal'],
            'expiration_date' => '2026-04-20',
            'notes' => 'Strong offer',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('deal_offers', [
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'buyer_name' => 'John Doe',
            'offer_price' => '350000.00',
            'financing_type' => 'conventional',
        ]);
    }

    public function test_offer_creation_logs_activity(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'offer_received']);

        $this->post("/pipeline/{$deal->id}/offers", [
            'buyer_name' => 'Jane Smith',
            'offer_price' => 400000,
        ]);

        $this->assertDatabaseHas('activities', [
            'deal_id' => $deal->id,
            'subject' => 'Offer received',
        ]);
    }

    public function test_can_update_offer_status(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'offer_received']);

        $offer = DealOffer::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'buyer_name' => 'Test Buyer',
            'offer_price' => 300000,
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/offers/{$offer->id}", [
            'status' => 'accepted',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertEquals('accepted', $offer->fresh()->status);
    }

    public function test_offer_status_change_logs_activity(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'offer_received']);

        $offer = DealOffer::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'buyer_name' => 'Test Buyer',
            'offer_price' => 300000,
            'status' => 'pending',
        ]);

        $this->patchJson("/offers/{$offer->id}", [
            'status' => 'rejected',
        ]);

        $this->assertDatabaseHas('activities', [
            'deal_id' => $deal->id,
            'subject' => 'Offer status changed',
        ]);
    }

    public function test_can_counter_offer(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'offer_received']);

        $offer = DealOffer::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'buyer_name' => 'Test Buyer',
            'offer_price' => 300000,
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/offers/{$offer->id}", [
            'status' => 'countered',
            'counter_price' => 325000,
        ]);

        $response->assertJson(['success' => true]);
        $this->assertEquals('countered', $offer->fresh()->status);
        $this->assertEquals('325000.00', $offer->fresh()->counter_price);
    }

    public function test_can_delete_offer(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'offer_received']);

        $offer = DealOffer::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'buyer_name' => 'Test Buyer',
            'offer_price' => 300000,
        ]);

        $response = $this->deleteJson("/offers/{$offer->id}");

        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('deal_offers', ['id' => $offer->id]);
    }

    public function test_offer_validation_requires_buyer_and_price(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'offer_received']);

        $response = $this->post("/pipeline/{$deal->id}/offers", []);
        $response->assertSessionHasErrors(['buyer_name', 'offer_price']);
    }

    public function test_offer_validates_financing_type(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'offer_received']);

        $response = $this->post("/pipeline/{$deal->id}/offers", [
            'buyer_name' => 'Test',
            'offer_price' => 300000,
            'financing_type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['financing_type']);
    }

    public function test_offer_status_update_validates_status(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'offer_received']);

        $offer = DealOffer::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'buyer_name' => 'Test',
            'offer_price' => 300000,
        ]);

        $response = $this->patchJson("/offers/{$offer->id}", [
            'status' => 'bogus_status',
        ]);

        $response->assertStatus(422);
    }
}
