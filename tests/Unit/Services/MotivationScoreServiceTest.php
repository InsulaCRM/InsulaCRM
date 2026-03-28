<?php

namespace Tests\Unit\Services;

use App\Models\Activity;
use App\Models\Property;
use App\Services\MotivationScoreService;
use Tests\TestCase;

class MotivationScoreServiceTest extends TestCase
{
    private MotivationScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MotivationScoreService();
        $this->actingAsAdmin();
    }

    public function test_score_zero_for_cold_lead_with_no_data(): void
    {
        $lead = $this->createLead(['temperature' => 'cold', 'motivation_score' => 0]);
        $score = $this->service->recalculate($lead);
        $this->assertEquals(0, $score);
    }

    public function test_hot_temperature_adds_fifteen_points(): void
    {
        $lead = $this->createLead(['temperature' => 'hot']);
        $score = $this->service->recalculate($lead);
        $this->assertGreaterThanOrEqual(15, $score);
    }

    public function test_warm_temperature_adds_eight_points(): void
    {
        $lead = $this->createLead(['temperature' => 'warm']);
        $score = $this->service->recalculate($lead);
        $this->assertEquals(8, $score);
    }

    public function test_activities_add_engagement_points(): void
    {
        $lead = $this->createLead(['temperature' => 'cold']);

        for ($i = 0; $i < 5; $i++) {
            Activity::factory()->create([
                'tenant_id' => $this->tenant->id,
                'lead_id' => $lead->id,
                'agent_id' => $this->adminUser->id,
            ]);
        }

        $score = $this->service->recalculate($lead);
        $this->assertEquals(10, $score);
    }

    public function test_ten_plus_activities_add_fifteen_points(): void
    {
        $lead = $this->createLead(['temperature' => 'cold']);

        for ($i = 0; $i < 12; $i++) {
            Activity::factory()->create([
                'tenant_id' => $this->tenant->id,
                'lead_id' => $lead->id,
                'agent_id' => $this->adminUser->id,
            ]);
        }

        $score = $this->service->recalculate($lead);
        $this->assertEquals(15, $score);
    }

    public function test_property_condition_adds_points(): void
    {
        $lead = $this->createLead(['temperature' => 'cold']);

        Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id' => $lead->id,
            'condition' => 'poor',
            'distress_markers' => [],
        ]);

        $score = $this->service->recalculate($lead);
        $this->assertEquals(10, $score);
    }

    public function test_distress_markers_add_points(): void
    {
        $lead = $this->createLead(['temperature' => 'cold']);

        Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id' => $lead->id,
            'condition' => 'excellent',
            'distress_markers' => ['tax_delinquent', 'probate', 'vacant'],
        ]);

        $score = $this->service->recalculate($lead);
        $this->assertEquals(9, $score);
    }

    public function test_distress_markers_capped_at_ten(): void
    {
        $lead = $this->createLead(['temperature' => 'cold']);

        Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id' => $lead->id,
            'condition' => 'excellent',
            'distress_markers' => ['tax_delinquent', 'probate', 'vacant', 'fire_damage'],
        ]);

        $score = $this->service->recalculate($lead);
        $this->assertEquals(10, $score);
    }

    public function test_score_capped_at_one_hundred(): void
    {
        $lead = $this->createLead(['temperature' => 'hot']);

        for ($i = 0; $i < 12; $i++) {
            Activity::factory()->create([
                'tenant_id' => $this->tenant->id,
                'lead_id' => $lead->id,
                'agent_id' => $this->adminUser->id,
            ]);
        }

        Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'lead_id' => $lead->id,
            'condition' => 'poor',
            'distress_markers' => ['tax_delinquent', 'probate', 'vacant', 'fire_damage'],
        ]);

        $score = $this->service->recalculate($lead);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_score_saved_to_database(): void
    {
        $lead = $this->createLead(['temperature' => 'hot', 'motivation_score' => 0]);
        $this->service->recalculate($lead);
        $this->assertGreaterThan(0, $lead->fresh()->motivation_score);
    }
}
