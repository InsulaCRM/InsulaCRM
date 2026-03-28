<?php

namespace Tests\Feature;

use Tests\TestCase;

class DealManagementTest extends TestCase
{
    public function test_admin_can_view_pipeline(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/pipeline');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_deal_detail(): void
    {
        $this->actingAsAdmin();
        $deal = $this->createDeal();

        $response = $this->get("/pipeline/{$deal->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_update_deal_stage(): void
    {
        $this->actingAsAdmin();
        $deal = $this->createDeal(['stage' => 'prospecting']);

        $response = $this->patch("/pipeline/{$deal->id}/stage", [
            'stage' => 'under_contract',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertEquals('under_contract', $deal->fresh()->stage);
    }

    public function test_field_scout_cannot_access_pipeline(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('field_scout');

        $response = $this->get('/pipeline');
        $response->assertStatus(403);
    }

    public function test_disposition_agent_can_access_pipeline(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('disposition_agent');

        $response = $this->get('/pipeline');
        $response->assertStatus(200);
    }
}
