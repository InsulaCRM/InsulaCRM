<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\OpenHouse;
use App\Models\OpenHouseAttendee;
use Tests\TestCase;

class OpenHouseManagementTest extends TestCase
{
    private function reAdmin(): self
    {
        return $this->actingAsAdmin(['business_mode' => 'realestate']);
    }

    public function test_admin_can_view_open_houses_index(): void
    {
        $this->reAdmin();

        $response = $this->get('/open-houses');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_open_house(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();

        $response = $this->post('/open-houses', [
            'property_id' => $property->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
            'description' => 'Spring open house event',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('open_houses', [
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
        ]);
    }

    public function test_admin_can_view_open_house_detail(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();

        $oh = OpenHouse::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
        ]);

        $response = $this->get("/open-houses/{$oh->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_update_open_house(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();

        $oh = OpenHouse::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
        ]);

        $response = $this->put("/open-houses/{$oh->id}", [
            'property_id' => $property->id,
            'event_date' => '2026-04-21',
            'start_time' => '10:00',
            'end_time' => '14:00',
            'status' => 'completed',
        ]);

        $response->assertRedirect();
        $this->assertEquals('2026-04-21', $oh->fresh()->event_date->format('Y-m-d'));
    }

    public function test_admin_can_delete_open_house(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();

        $oh = OpenHouse::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
        ]);

        $response = $this->delete("/open-houses/{$oh->id}");
        $response->assertRedirect('/open-houses');
        $this->assertDatabaseMissing('open_houses', ['id' => $oh->id]);
    }

    public function test_add_attendee_creates_new_lead(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();

        $oh = OpenHouse::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
        ]);

        $response = $this->postJson("/open-houses/{$oh->id}/attendees", [
            'first_name' => 'Jane',
            'last_name' => 'Walker',
            'email' => 'jane.walker@example.com',
            'phone' => '555-1234',
        ]);

        $response->assertJson(['success' => true]);

        // Lead should be auto-created with source 'open_house'
        $this->assertDatabaseHas('leads', [
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Jane',
            'last_name' => 'Walker',
            'email' => 'jane.walker@example.com',
            'lead_source' => 'open_house',
        ]);

        // Attendee count should increment
        $this->assertEquals(1, $oh->fresh()->attendee_count);
    }

    public function test_add_attendee_matches_existing_lead_by_email(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();

        $existingLead = $this->createLead([
            'first_name' => 'Existing',
            'last_name' => 'Client',
            'email' => 'existing@example.com',
        ]);

        $oh = OpenHouse::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
        ]);

        $response = $this->postJson("/open-houses/{$oh->id}/attendees", [
            'first_name' => 'Existing',
            'last_name' => 'Client',
            'email' => 'existing@example.com',
        ]);

        $response->assertJson(['success' => true]);

        // Should link to existing lead, not create a new one
        $attendee = OpenHouseAttendee::where('open_house_id', $oh->id)->first();
        $this->assertEquals($existingLead->id, $attendee->lead_id);

        // Only one lead with this email should exist
        $this->assertEquals(1, Lead::where('email', 'existing@example.com')->count());
    }

    public function test_remove_attendee_decrements_count(): void
    {
        $this->reAdmin();
        $property = $this->createProperty();

        $oh = OpenHouse::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'event_date' => '2026-04-20',
            'start_time' => '13:00',
            'end_time' => '16:00',
            'attendee_count' => 1,
        ]);

        $attendee = OpenHouseAttendee::create([
            'tenant_id' => $this->tenant->id,
            'open_house_id' => $oh->id,
            'first_name' => 'Test',
            'last_name' => 'Person',
        ]);

        $response = $this->deleteJson("/open-house-attendees/{$attendee->id}");

        $response->assertJson(['success' => true]);
        $this->assertEquals(0, $oh->fresh()->attendee_count);
        $this->assertDatabaseMissing('open_house_attendees', ['id' => $attendee->id]);
    }

    public function test_wholesale_tenant_cannot_access_open_houses(): void
    {
        $this->actingAsAdmin(['business_mode' => 'wholesale']);

        $response = $this->get('/open-houses');
        $response->assertStatus(404);
    }

    public function test_validation_requires_property_and_date(): void
    {
        $this->reAdmin();

        $response = $this->post('/open-houses', []);
        $response->assertSessionHasErrors(['property_id', 'event_date', 'start_time', 'end_time']);
    }
}
