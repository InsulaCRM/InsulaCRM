<?php

namespace Tests\Feature\Api;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;
use Tests\TestCase;

class DealPropertyLinkageTest extends TestCase
{
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTenantWithAdmin([
            'api_key' => 'test-api-key-for-deal-property',
            'api_enabled' => true,
        ]);

        $this->headers = ['X-API-Key' => 'test-api-key-for-deal-property'];
    }

    public function test_deal_created_from_lead_automatically_links_its_property(): void
    {
        $lead = $this->createLead();

        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id' => $lead->id,
        ]);

        $response = $this->postJson('/api/v1/deals', [
            'lead_id' => $lead->id,
            'title' => 'Wholesale Deal',
        ], $this->headers);

        $response->assertStatus(201);
        $deal = Deal::withoutGlobalScopes()->findOrFail($response->json('deal_id'));

        $this->assertSame($property->id, $deal->property_id);
    }

    public function test_deal_rejects_property_from_another_lead(): void
    {
        $lead = $this->createLead();
        $otherLead = $this->createLead(['first_name' => 'Other']);

        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id' => $otherLead->id,
        ]);

        $response = $this->postJson('/api/v1/deals', [
            'lead_id' => $lead->id,
            'property_id' => $property->id,
        ], $this->headers);

        $response->assertStatus(404);
    }
}
