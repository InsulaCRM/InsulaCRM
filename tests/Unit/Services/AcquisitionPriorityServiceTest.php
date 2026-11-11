<?php

namespace Tests\Unit\Services;

use App\Models\Property;
use App\Services\AcquisitionPriorityService;
use Tests\TestCase;

class AcquisitionPriorityServiceTest extends TestCase
{
    private AcquisitionPriorityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AcquisitionPriorityService();
        $this->actingAsAdmin();
    }

    public function test_provider_list_type_contributes_to_acquisition_priority_without_property(): void
    {
        $lead = $this->createLead([
            'temperature' => 'cold',
            'custom_fields' => ['list_types' => ['pre_foreclosure']],
        ]);

        $result = $this->service->score($lead);

        $this->assertSame(18, $result['score']);
        $this->assertSame('medium', $result['tier']);
        $this->assertContains('Pre Foreclosure', $result['reasons']);
    }

    public function test_provider_wholesale_flags_contribute_once_even_when_property_repeats_signal(): void
    {
        $lead = $this->createLead([
            'temperature' => 'cold',
            'custom_fields' => [
                'list_types' => ['tax_delinquent'],
                'tax_delinquent' => true,
            ],
        ]);

        Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id' => $lead->id,
            'distress_markers' => ['tax_delinquent', 'vacant'],
        ]);

        $result = $this->service->score($lead);

        // tax_delinquent is counted once (16) despite appearing in provider
        // list_types, provider flags, and property distress markers. Vacant
        // contributes separately (12).
        $this->assertSame(28, $result['score']);
        $this->assertCount(2, $result['reasons']);
    }
}
