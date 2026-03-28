<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\CustomFieldService;
use Tests\TestCase;

class CustomFieldServiceTest extends TestCase
{
    public function test_get_defaults_returns_all_property_types(): void
    {
        $defaults = CustomFieldService::getDefaults('property_type');

        $this->assertArrayHasKey('single_family', $defaults);
        $this->assertArrayHasKey('multi_family', $defaults);
        $this->assertArrayHasKey('commercial', $defaults);
        $this->assertArrayHasKey('land', $defaults);
        $this->assertArrayHasKey('other', $defaults);
    }

    public function test_get_defaults_returns_all_lead_statuses(): void
    {
        $defaults = CustomFieldService::getDefaults('lead_status');

        $this->assertCount(14, $defaults);
        $this->assertArrayHasKey('new', $defaults);
        $this->assertArrayHasKey('dead', $defaults);
        $this->assertArrayHasKey('closed_won', $defaults);
    }

    public function test_get_defaults_returns_empty_for_unknown_type(): void
    {
        $defaults = CustomFieldService::getDefaults('nonexistent_field');

        $this->assertEmpty($defaults);
    }

    public function test_get_options_includes_custom_options(): void
    {
        $this->actingAsAdmin();

        $this->tenant->update([
            'custom_options' => [
                'property_type' => ['warehouse' => 'Warehouse'],
            ],
        ]);

        $options = CustomFieldService::getOptions('property_type', $this->tenant);

        $this->assertArrayHasKey('warehouse', $options);
        $this->assertArrayHasKey('single_family', $options); // still has defaults
    }

    public function test_add_option_creates_custom_option(): void
    {
        $this->actingAsAdmin();

        $result = CustomFieldService::addOption('property_type', 'Warehouse', $this->tenant);

        $this->assertTrue($result['success']);
        $this->assertEquals('warehouse', $result['slug']);

        $options = CustomFieldService::getOptions('property_type', $this->tenant->fresh());
        $this->assertArrayHasKey('warehouse', $options);
    }

    public function test_add_option_prevents_duplicate_default(): void
    {
        $this->actingAsAdmin();

        $result = CustomFieldService::addOption('property_type', 'Single Family', $this->tenant);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
    }

    public function test_add_option_prevents_duplicate_custom(): void
    {
        $this->actingAsAdmin();

        CustomFieldService::addOption('property_type', 'Warehouse', $this->tenant);
        $result = CustomFieldService::addOption('property_type', 'Warehouse', $this->tenant->fresh());

        $this->assertFalse($result['success']);
    }

    public function test_remove_option_removes_custom_option(): void
    {
        $this->actingAsAdmin();

        CustomFieldService::addOption('property_type', 'Warehouse', $this->tenant);
        $removed = CustomFieldService::removeOption('property_type', 'warehouse', $this->tenant->fresh());

        $this->assertTrue($removed);

        $options = CustomFieldService::getOptions('property_type', $this->tenant->fresh());
        $this->assertArrayNotHasKey('warehouse', $options);
    }

    public function test_remove_option_blocks_system_default(): void
    {
        $this->actingAsAdmin();

        $removed = CustomFieldService::removeOption('property_type', 'single_family', $this->tenant);

        $this->assertFalse($removed);
    }

    public function test_get_valid_slugs_returns_keys(): void
    {
        $slugs = CustomFieldService::getValidSlugs('lead_status');

        $this->assertContains('new', $slugs);
        $this->assertContains('dead', $slugs);
        $this->assertContains('closed_won', $slugs);
    }

    public function test_get_field_types_returns_all_types(): void
    {
        $types = CustomFieldService::getFieldTypes();

        $this->assertArrayHasKey('lead_status', $types);
        $this->assertArrayHasKey('property_type', $types);
        $this->assertArrayHasKey('property_condition', $types);
        $this->assertArrayHasKey('distress_markers', $types);
        $this->assertArrayHasKey('activity_type', $types);
        $this->assertArrayHasKey('lead_source', $types);
    }

    public function test_outreach_activity_types_defined(): void
    {
        $outreach = CustomFieldService::$outreachActivityTypes;

        $this->assertContains('call', $outreach);
        $this->assertContains('sms', $outreach);
        $this->assertContains('email', $outreach);
        $this->assertNotContains('note', $outreach);
        $this->assertNotContains('meeting', $outreach);
    }

    public function test_legacy_custom_lead_sources_merged(): void
    {
        $this->actingAsAdmin();

        $this->tenant->update([
            'custom_lead_sources' => [
                ['slug' => 'zillow', 'name' => 'Zillow'],
            ],
        ]);

        $options = CustomFieldService::getOptions('lead_source', $this->tenant->fresh());

        $this->assertArrayHasKey('zillow', $options);
        $this->assertArrayHasKey('cold_call', $options); // still has defaults
    }
}
