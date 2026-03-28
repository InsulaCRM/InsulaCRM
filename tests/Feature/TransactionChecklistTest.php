<?php

namespace Tests\Feature;

use App\Models\TransactionChecklist;
use Tests\TestCase;

class TransactionChecklistTest extends TestCase
{
    public function test_can_create_default_checklist_for_deal(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $response = $this->post("/pipeline/{$deal->id}/checklist");

        $response->assertRedirect();
        $this->assertEquals(9, $deal->checklistItems()->count());
    }

    public function test_checklist_not_duplicated_on_second_call(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $this->post("/pipeline/{$deal->id}/checklist");
        $response = $this->postJson("/pipeline/{$deal->id}/checklist");

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Checklist already exists.']);
        $this->assertEquals(9, $deal->checklistItems()->count());
    }

    public function test_can_update_checklist_item_status(): void
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

        $response = $this->patchJson("/checklist/{$item->id}", [
            'status' => 'in_progress',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertEquals('in_progress', $item->fresh()->status);
    }

    public function test_completed_status_sets_completed_at(): void
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

        $this->patchJson("/checklist/{$item->id}", [
            'status' => 'completed',
        ]);

        $this->assertNotNull($item->fresh()->completed_at);
    }

    public function test_non_completed_status_clears_completed_at(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $item = TransactionChecklist::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'item_key' => 'inspection',
            'label' => 'Home Inspection',
            'status' => 'completed',
            'completed_at' => now(),
            'sort_order' => 1,
        ]);

        $this->patchJson("/checklist/{$item->id}", [
            'status' => 'in_progress',
        ]);

        $this->assertNull($item->fresh()->completed_at);
    }

    public function test_can_update_checklist_item_deadline(): void
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

        $response = $this->patchJson("/checklist/{$item->id}", [
            'deadline' => '2026-05-01',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertEquals('2026-05-01', $item->fresh()->deadline->format('Y-m-d'));
    }

    public function test_can_add_custom_checklist_item(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $response = $this->postJson("/pipeline/{$deal->id}/checklist/add", [
            'label' => 'Custom Contingency',
            'deadline' => '2026-05-15',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('transaction_checklists', [
            'deal_id' => $deal->id,
            'label' => 'Custom Contingency',
        ]);
    }

    public function test_can_remove_checklist_item(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $item = TransactionChecklist::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'item_key' => 'custom',
            'label' => 'Deletable Item',
            'sort_order' => 1,
        ]);

        $response = $this->deleteJson("/checklist/{$item->id}");

        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('transaction_checklists', ['id' => $item->id]);
    }

    public function test_checklist_item_validation(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $deal = $this->createDeal(['stage' => 'under_contract']);

        $item = TransactionChecklist::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'item_key' => 'test',
            'label' => 'Test',
            'sort_order' => 1,
        ]);

        $response = $this->patchJson("/checklist/{$item->id}", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
    }
}
