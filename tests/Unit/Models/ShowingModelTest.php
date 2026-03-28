<?php

namespace Tests\Unit\Models;

use App\Models\Showing;
use Tests\TestCase;

class ShowingModelTest extends TestCase
{
    public function test_statuses_constant_has_all_values(): void
    {
        $expected = ['scheduled', 'completed', 'cancelled', 'no_show'];
        $this->assertEquals($expected, array_keys(Showing::STATUSES));
    }

    public function test_outcomes_constant_has_all_values(): void
    {
        $expected = ['interested', 'not_interested', 'made_offer', 'needs_second_showing'];
        $this->assertEquals($expected, array_keys(Showing::OUTCOMES));
    }

    public function test_status_label_returns_translated_string(): void
    {
        $this->assertEquals('Scheduled', Showing::statusLabel('scheduled'));
        $this->assertEquals('No Show', Showing::statusLabel('no_show'));
    }

    public function test_outcome_label_returns_translated_string(): void
    {
        $this->assertEquals('Interested', Showing::outcomeLabel('interested'));
        $this->assertEquals('Needs Second Showing', Showing::outcomeLabel('needs_second_showing'));
    }

    public function test_status_label_handles_unknown_value(): void
    {
        $label = Showing::statusLabel('unknown_status');
        $this->assertEquals('Unknown Status', $label);
    }

    public function test_showing_date_is_cast_to_date(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $property = $this->createProperty();

        $showing = Showing::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'showing_date' => '2026-04-01',
            'showing_time' => '14:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $showing->showing_date);
    }

    public function test_showing_has_property_relationship(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $property = $this->createProperty();

        $showing = Showing::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'showing_date' => '2026-04-01',
            'showing_time' => '14:00',
        ]);

        $this->assertNotNull($showing->property);
        $this->assertEquals($property->id, $showing->property->id);
    }
}
