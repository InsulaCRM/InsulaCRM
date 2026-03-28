<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\BusinessModeService;
use Tests\TestCase;

class BusinessModeServiceTest extends TestCase
{
    public function test_is_realestate_returns_true_for_realestate_tenant(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $this->assertTrue(BusinessModeService::isRealEstate($this->tenant));
    }

    public function test_is_realestate_returns_false_for_wholesale_tenant(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'wholesale']);
        $this->assertFalse(BusinessModeService::isRealEstate($this->tenant));
    }

    public function test_is_realestate_returns_false_by_default(): void
    {
        $this->createTenantWithAdmin();
        $this->assertFalse(BusinessModeService::isRealEstate($this->tenant));
    }

    public function test_get_stages_returns_realestate_stages(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $stages = BusinessModeService::getStages($this->tenant);

        $this->assertArrayHasKey('listing_agreement', $stages);
        $this->assertArrayHasKey('active_listing', $stages);
        $this->assertArrayHasKey('showing', $stages);
        $this->assertArrayHasKey('offer_received', $stages);
        $this->assertArrayNotHasKey('prospecting', $stages);
        $this->assertArrayNotHasKey('dispositions', $stages);
    }

    public function test_get_stages_returns_wholesale_stages(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'wholesale']);
        $stages = BusinessModeService::getStages($this->tenant);

        $this->assertArrayHasKey('prospecting', $stages);
        $this->assertArrayHasKey('dispositions', $stages);
        $this->assertArrayNotHasKey('listing_agreement', $stages);
        $this->assertArrayNotHasKey('active_listing', $stages);
    }

    public function test_get_stage_labels_returns_translated_labels(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $labels = BusinessModeService::getStageLabels($this->tenant);

        $this->assertIsArray($labels);
        $this->assertArrayHasKey('listing_agreement', $labels);
        $this->assertIsString($labels['listing_agreement']);
    }

    public function test_get_showing_outcomes(): void
    {
        $outcomes = BusinessModeService::getShowingOutcomes();

        $this->assertCount(4, $outcomes);
        $this->assertArrayHasKey('interested', $outcomes);
        $this->assertArrayHasKey('not_interested', $outcomes);
        $this->assertArrayHasKey('made_offer', $outcomes);
        $this->assertArrayHasKey('needs_second_showing', $outcomes);
    }

    public function test_get_financing_types(): void
    {
        $types = BusinessModeService::getFinancingTypes();

        $this->assertCount(5, $types);
        $this->assertArrayHasKey('cash', $types);
        $this->assertArrayHasKey('conventional', $types);
        $this->assertArrayHasKey('fha', $types);
        $this->assertArrayHasKey('va', $types);
        $this->assertArrayHasKey('other', $types);
    }

    public function test_get_default_checklist_items(): void
    {
        $items = BusinessModeService::getDefaultChecklistItems();

        $this->assertCount(9, $items);
        $keys = array_column($items, 'item_key');
        $this->assertContains('inspection', $keys);
        $this->assertContains('appraisal', $keys);
        $this->assertContains('financing', $keys);
        $this->assertContains('title_search', $keys);
        $this->assertContains('final_walkthrough', $keys);
    }

    public function test_realestate_stages_constant_has_11_stages(): void
    {
        $this->assertCount(11, BusinessModeService::REALESTATE_STAGES);
    }

    public function test_wholesale_stages_constant_has_10_stages(): void
    {
        $this->assertCount(10, BusinessModeService::WHOLESALE_STAGES);
    }
}
