<?php

namespace Tests\Unit\Models;

use App\Models\TransactionChecklist;
use Tests\TestCase;

class TransactionChecklistModelTest extends TestCase
{
    public function test_statuses_constant_has_all_values(): void
    {
        $expected = ['pending', 'in_progress', 'completed', 'waived', 'failed'];
        $this->assertEquals($expected, array_keys(TransactionChecklist::STATUSES));
    }

    public function test_default_items_has_9_entries(): void
    {
        $this->assertCount(9, TransactionChecklist::DEFAULT_ITEMS);
    }

    public function test_default_items_have_required_keys(): void
    {
        foreach (TransactionChecklist::DEFAULT_ITEMS as $item) {
            $this->assertArrayHasKey('item_key', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('sort_order', $item);
        }
    }

    public function test_is_overdue_when_deadline_passed_and_not_completed(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $item = TransactionChecklist::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'item_key' => 'inspection',
            'label' => 'Home Inspection',
            'status' => 'pending',
            'deadline' => now()->subDays(3),
            'sort_order' => 1,
        ]);

        $this->assertTrue($item->is_overdue);
    }

    public function test_not_overdue_when_completed(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $item = TransactionChecklist::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'item_key' => 'inspection',
            'label' => 'Home Inspection',
            'status' => 'completed',
            'deadline' => now()->subDays(3),
            'completed_at' => now()->subDays(4),
            'sort_order' => 1,
        ]);

        $this->assertFalse($item->is_overdue);
    }

    public function test_not_overdue_when_waived(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $item = TransactionChecklist::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'item_key' => 'inspection',
            'label' => 'Home Inspection',
            'status' => 'waived',
            'deadline' => now()->subDays(3),
            'sort_order' => 1,
        ]);

        $this->assertFalse($item->is_overdue);
    }

    public function test_not_overdue_when_no_deadline(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $item = TransactionChecklist::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'item_key' => 'inspection',
            'label' => 'Home Inspection',
            'status' => 'pending',
            'sort_order' => 1,
        ]);

        $this->assertFalse($item->is_overdue);
    }

    public function test_deadline_is_cast_to_date(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $item = TransactionChecklist::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'item_key' => 'test',
            'label' => 'Test',
            'deadline' => '2026-04-15',
            'sort_order' => 1,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $item->deadline);
    }
}
