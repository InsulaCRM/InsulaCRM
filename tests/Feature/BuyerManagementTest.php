<?php

namespace Tests\Feature;

use App\Models\Buyer;
use Tests\TestCase;

class BuyerManagementTest extends TestCase
{
    public function test_admin_can_view_buyers_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/buyers');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_buyer(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/buyers', [
            'first_name' => 'Jane',
            'last_name' => 'Investor',
            'company' => 'Invest Corp',
            'phone' => '555-0200',
            'email' => 'jane@invest.com',
            'max_purchase_price' => 500000,
            'preferred_property_types' => ['single_family', 'multi_family'],
            'preferred_zip_codes' => ['33101', '33102', '33103'],
            'preferred_states' => ['FL', 'TX'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('buyers', [
            'first_name' => 'Jane',
            'last_name' => 'Investor',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_admin_can_view_buyer_detail(): void
    {
        $this->actingAsAdmin();

        $buyer = Buyer::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->get("/buyers/{$buyer->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_update_buyer(): void
    {
        $this->actingAsAdmin();

        $buyer = Buyer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Old',
        ]);

        $response = $this->put("/buyers/{$buyer->id}", [
            'first_name' => 'New',
            'last_name' => $buyer->last_name,
            'email' => $buyer->email,
            'max_purchase_price' => $buyer->max_purchase_price,
        ]);

        $response->assertRedirect();
        $this->assertEquals('New', $buyer->fresh()->first_name);
    }

    public function test_disposition_agent_can_view_buyers(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('disposition_agent');

        $response = $this->get('/buyers');
        $response->assertStatus(200);
    }

    public function test_acquisition_agent_cannot_view_buyers(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('acquisition_agent');

        $response = $this->get('/buyers');
        $response->assertStatus(403);
    }

    public function test_field_scout_cannot_view_buyers(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('field_scout');

        $response = $this->get('/buyers');
        $response->assertStatus(403);
    }
}
