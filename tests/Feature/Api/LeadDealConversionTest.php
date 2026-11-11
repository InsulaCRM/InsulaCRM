<?php

namespace Tests\Feature\Api;

use App\Models\Deal;
use App\Models\Property;
use Tests\TestCase;

class LeadDealConversionTest extends TestCase
{
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTenantWithAdmin([
            'api_key' => 'test-api-key-for-conversion',
            'api_enabled' => true,
        ]);

        $this->headers = ['X-API-Key' => 'test-api-key-for-conversion'];
    }

    public function test_seller_with_property_can_be_converted_to_deal_without_reentering_property_data(): void
    {
        $lead = $this->createLead();
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id' => $lead->id,
            'address' => '123 Main St',
        ]);

        $response = $this->postJson("/api/v1/leads/{$lead->id}/convert-to-deal", [], $this->headers);

        $response->assertStatus(201);
        $deal = Deal::withoutGlobalScopes()->findOrFail($response->json('deal_id'));

        $this->assertSame($lead->id, $deal->lead_id);
        $this->assertSame($property->id, $deal->property_id);
        $this->assertSame('prospecting', $deal->stage);
    }

    public function test_conversion_is_idempotent_for_active_deal(): void
    {
        $lead = $this->createLead();
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id' => $lead->id,
        ]);

        $first = $this->postJson("/api/v1/leads/{$lead->id}/convert-to-deal", [], $this->headers);
        $first->assertStatus(201);

        $second = $this->postJson("/api/v1/leads/{$lead->id}/convert-to-deal", [], $this->headers);
        $second->assertStatus(200);
        $second->assertJson([
            'already_exists' => true,
            'deal_id' => $first->json('deal_id'),
            'property_id' => $property->id,
        ]);

        $this->assertSame(1, Deal::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('lead_id', $lead->id)
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->count());
    }

    public function test_conversion_requires_a_property(): void
    {
        $lead = $this->createLead();

        $response = $this->postJson("/api/v1/leads/{$lead->id}/convert-to-deal", [], $this->headers);

        $response->assertStatus(422);
    }
}
