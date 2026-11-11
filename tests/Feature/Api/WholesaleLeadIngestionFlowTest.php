<?php

namespace Tests\Feature\Api;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;
use Tests\TestCase;

class WholesaleLeadIngestionFlowTest extends TestCase
{
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTenantWithAdmin([
            'api_key' => 'test-api-key-for-wholesale-flow',
            'api_enabled' => true,
        ]);

        $this->headers = ['X-API-Key' => 'test-api-key-for-wholesale-flow'];
    }

    public function test_provider_wholesale_intelligence_survives_ingest_and_drives_priority_before_deal_conversion(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'first_name' => 'John',
            'last_name' => 'Seller',
            'phone' => '(828) 555-0101',
            'email' => 'JOHN.SELLER@example.com',
            'source' => 'batchdata',
            'external_id' => 'batchdata-1001',
            'property_address' => '123 Main St',
            'property_city' => 'Hickory',
            'property_county' => 'Catawba',
            'property_state' => 'NC',
            'property_zip' => '28601',
            'property_type' => 'single_family',
            'property_condition' => 'heavy_rehab',
            'property_estimated_value' => 300000,
            'property_repair_estimate' => 50000,
            'property_asking_price' => 200000,
            'list_types' => ['pre_foreclosure', 'tax_delinquent'],
            'distress_markers' => ['pre-foreclosure', 'tax delinquent'],
            'high_equity' => true,
            'vacant' => true,
        ], $this->headers);

        $response->assertStatus(201)
            ->assertJsonPath('source', 'batchdata')
            ->assertJsonStructure(['lead_id', 'property_id']);

        $lead = Lead::withoutGlobalScopes()->findOrFail($response->json('lead_id'));
        $property = Property::withoutGlobalScopes()->findOrFail($response->json('property_id'));

        $this->assertSame('john.seller@example.com', $lead->email);
        $this->assertSame('Catawba', $property->county);
        $this->assertSame(
            ['pre_foreclosure', 'tax_delinquent'],
            data_get($lead->custom_fields, 'list_types')
        );
        $this->assertTrue((bool) data_get($lead->custom_fields, 'high_equity'));
        $this->assertTrue((bool) data_get($lead->custom_fields, 'vacant'));

        $index = $this->getJson(
            '/api/v1/leads?state=NC&county=Catawba&sort=acquisition_priority&priority_tier=high&per_page=25',
            $this->headers
        );

        $index->assertOk()
            ->assertJsonPath('data.0.id', $lead->id)
            ->assertJsonPath('data.0.acquisition_priority.tier', 'high')
            ->assertJsonPath('data.0.acquisition_priority.score', 60);

        $convert = $this->postJson(
            "/api/v1/leads/{$lead->id}/convert-to-deal",
            [],
            $this->headers
        );

        $convert->assertStatus(201)
            ->assertJsonPath('already_exists', false)
            ->assertJsonPath('lead_id', $lead->id)
            ->assertJsonPath('property_id', $property->id);

        $deal = Deal::withoutGlobalScopes()->findOrFail($convert->json('deal_id'));

        $this->assertSame($lead->id, $deal->lead_id);
        $this->assertSame($property->id, $deal->property_id);
        $this->assertSame('prospecting', $deal->stage);
    }

    public function test_external_id_deduplication_returns_existing_seller_without_creating_a_second_property(): void
    {
        $payload = [
            'first_name' => 'Jane',
            'last_name' => 'Seller',
            'phone' => '828-555-0202',
            'source' => 'batchdata',
            'external_id' => 'batchdata-2002',
            'property_address' => '456 Oak St',
            'property_city' => 'Hickory',
            'property_county' => 'Catawba',
            'property_state' => 'NC',
            'property_zip' => '28602',
            'list_types' => ['vacant'],
        ];

        $first = $this->postJson('/api/v1/leads', $payload, $this->headers);
        $first->assertStatus(201);

        $second = $this->postJson('/api/v1/leads', $payload, $this->headers);
        $second->assertOk()
            ->assertJson([
                'success' => true,
                'duplicate' => true,
                'lead_id' => $first->json('lead_id'),
            ]);

        $this->assertSame(
            1,
            Lead::withoutGlobalScopes()
                ->where('tenant_id', $this->tenant->id)
                ->where('custom_fields->external_id', 'batchdata-2002')
                ->count()
        );

        $this->assertSame(
            1,
            Property::withoutGlobalScopes()
                ->where('tenant_id', $this->tenant->id)
                ->where('lead_id', $first->json('lead_id'))
                ->count()
        );
    }
}
