<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Showing;
use Tests\TestCase;

class ShowingManagementTest extends TestCase
{
    private function reAdmin(): self
    {
        return $this->actingAsAdmin(['business_mode' => 'realestate']);
    }

    public function test_admin_can_view_showings_index(): void
    {
        $this->reAdmin();

        $response = $this->get('/showings');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_create_showing_form(): void
    {
        $this->reAdmin();

        $response = $this->get('/showings/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_showing(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();
        $lead = $this->createLead();

        $response = $this->post('/showings', [
            'property_id' => $property->id,
            'lead_id' => $lead->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
            'duration_minutes' => 30,
            'notes' => 'First showing',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('showings', [
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'lead_id' => $lead->id,
        ]);
    }

    public function test_showing_creates_activity_on_lead(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();
        $lead = $this->createLead();

        $this->post('/showings', [
            'property_id' => $property->id,
            'lead_id' => $lead->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '10:00',
        ]);

        $this->assertDatabaseHas('activities', [
            'lead_id' => $lead->id,
            'type' => 'meeting',
            'subject' => 'Showing scheduled',
        ]);
    }

    public function test_admin_can_view_showing_detail(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();

        $showing = Showing::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
        ]);

        $response = $this->get("/showings/{$showing->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_update_showing(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();

        $showing = Showing::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
            'status' => 'scheduled',
        ]);

        $response = $this->put("/showings/{$showing->id}", [
            'property_id' => $property->id,
            'showing_date' => '2026-04-16',
            'showing_time' => '15:00',
            'status' => 'completed',
            'feedback' => 'Client loved the kitchen.',
            'outcome' => 'interested',
        ]);

        $response->assertRedirect();
        $this->assertEquals('completed', $showing->fresh()->status);
        $this->assertEquals('interested', $showing->fresh()->outcome);
    }

    public function test_admin_can_delete_showing(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();

        $showing = Showing::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
        ]);

        $response = $this->delete("/showings/{$showing->id}");
        $response->assertRedirect('/showings');
        $this->assertDatabaseMissing('showings', ['id' => $showing->id]);
    }

    public function test_agent_can_only_see_own_showings(): void
    {
        $this->createTenantWithAdmin(['business_mode' => 'realestate']);
        $property = $this->createProperty();

        // Admin's showing
        Showing::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
        ]);

        $agent = $this->actingAsRole('listing_agent');

        // Agent's showing
        Showing::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $agent->id,
            'showing_date' => '2026-04-16',
            'showing_time' => '10:00',
        ]);

        $response = $this->get('/showings');
        $response->assertStatus(200);
    }

    public function test_validation_requires_property_and_date(): void
    {
        $this->reAdmin();

        $response = $this->post('/showings', []);
        $response->assertSessionHasErrors(['property_id', 'showing_date', 'showing_time']);
    }

    public function test_wholesale_tenant_cannot_access_showings(): void
    {
        $this->actingAsAdmin(['business_mode' => 'wholesale']);

        $response = $this->get('/showings');
        $response->assertStatus(404);
    }

    public function test_showing_filters_by_status(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();

        Showing::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'showing_date' => '2026-04-15',
            'showing_time' => '14:00',
            'status' => 'completed',
        ]);

        Showing::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'showing_date' => '2026-04-16',
            'showing_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->get('/showings?status=completed');
        $response->assertStatus(200);
    }
}
