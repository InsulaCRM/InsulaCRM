<?php

namespace Tests\Unit\Models;

use App\Models\Deal;
use Tests\TestCase;

class DealTest extends TestCase
{
    public function test_stages_constant_has_all_stages(): void
    {
        $expected = [
            'prospecting', 'contacting', 'engaging', 'offer_presented',
            'under_contract', 'dispositions', 'assigned', 'closing',
            'closed_won', 'closed_lost',
        ];

        $this->assertEquals($expected, array_keys(Deal::STAGES));
    }

    public function test_stage_labels_returns_translated_labels(): void
    {
        $labels = Deal::stageLabels();

        $this->assertIsArray($labels);
        $this->assertArrayHasKey('prospecting', $labels);
        $this->assertEquals('Prospecting', $labels['prospecting']);
        $this->assertEquals('Closed Won', $labels['closed_won']);
    }

    public function test_stage_label_returns_single_translated_label(): void
    {
        $this->assertEquals('Under Contract', Deal::stageLabel('under_contract'));
        $this->assertEquals('Closed Lost', Deal::stageLabel('closed_lost'));
    }

    public function test_stage_label_handles_unknown_stage(): void
    {
        $label = Deal::stageLabel('some_unknown_stage');
        $this->assertEquals('Some Unknown Stage', $label);
    }

    public function test_due_diligence_days_remaining(): void
    {
        $this->actingAsAdmin();
        $deal = $this->createDeal(['due_diligence_end_date' => now()->addDays(5)]);

        $this->assertEquals(5, $deal->due_diligence_days_remaining);
    }

    public function test_due_diligence_days_remaining_null_when_no_date(): void
    {
        $this->actingAsAdmin();
        $deal = $this->createDeal(['due_diligence_end_date' => null]);

        $this->assertNull($deal->due_diligence_days_remaining);
    }

    public function test_is_due_diligence_urgent_when_two_days(): void
    {
        $this->actingAsAdmin();
        $deal = $this->createDeal(['due_diligence_end_date' => now()->addDays(2)]);

        $this->assertTrue($deal->is_due_diligence_urgent);
    }

    public function test_is_due_diligence_not_urgent_when_far_away(): void
    {
        $this->actingAsAdmin();
        $deal = $this->createDeal(['due_diligence_end_date' => now()->addDays(10)]);

        $this->assertFalse($deal->is_due_diligence_urgent);
    }

    public function test_stage_changed_at_auto_set_on_create(): void
    {
        $this->actingAsAdmin();
        $deal = $this->createDeal(['stage_changed_at' => null]);

        $this->assertNotNull($deal->fresh()->stage_changed_at);
    }
}
