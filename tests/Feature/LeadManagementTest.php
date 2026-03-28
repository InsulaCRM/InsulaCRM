<?php

namespace Tests\Feature;

use App\Models\Lead;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    public function test_admin_can_view_leads_index(): void
    {
        $this->actingAsAdmin();
        Lead::factory()->count(3)->create(['tenant_id' => $this->tenant->id, 'agent_id' => $this->adminUser->id]);

        $response = $this->get('/leads');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_create_form(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/leads/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_lead(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/leads', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '555-0100',
            'email' => 'john@example.com',
            'lead_source' => 'website',
            'status' => 'new',
            'temperature' => 'warm',
            'agent_id' => $this->adminUser->id,
        ]);

        $lead = \App\Models\Lead::where('first_name', 'John')->where('last_name', 'Doe')->first();
        $response->assertRedirect("/leads/{$lead->id}");
        $this->assertDatabaseHas('leads', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_admin_can_view_lead_detail(): void
    {
        $this->actingAsAdmin();
        $lead = $this->createLead();

        $response = $this->get("/leads/{$lead->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_edit_lead(): void
    {
        $this->actingAsAdmin();
        $lead = $this->createLead(['first_name' => 'Old']);

        $response = $this->put("/leads/{$lead->id}", [
            'first_name' => 'New',
            'last_name' => $lead->last_name,
            'agent_id' => $this->adminUser->id,
            'lead_source' => $lead->lead_source,
            'status' => $lead->status,
            'temperature' => $lead->temperature,
        ]);

        $response->assertRedirect();
        $this->assertEquals('New', $lead->fresh()->first_name);
    }

    public function test_admin_can_delete_lead(): void
    {
        $this->actingAsAdmin();
        $lead = $this->createLead();

        $response = $this->delete("/leads/{$lead->id}");

        $response->assertRedirect('/leads');
        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
    }

    public function test_admin_can_update_lead_status_via_ajax(): void
    {
        $this->actingAsAdmin();
        $lead = $this->createLead(['status' => 'new']);

        $response = $this->patch("/leads/{$lead->id}/status", [
            'status' => 'contacting',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertEquals('contacting', $lead->fresh()->status);
    }

    public function test_lead_index_filters_by_status(): void
    {
        $this->actingAsAdmin();
        $this->createLead(['status' => 'new']);
        $this->createLead(['status' => 'dead']);

        $response = $this->get('/leads?status=new');
        $response->assertStatus(200);
    }

    public function test_lead_index_filters_by_search(): void
    {
        $this->actingAsAdmin();
        $this->createLead(['first_name' => 'UniqueTestName']);

        $response = $this->get('/leads?search=UniqueTestName');
        $response->assertStatus(200);
    }

    public function test_agent_only_sees_own_leads(): void
    {
        $this->createTenantWithAdmin();
        $agent = $this->actingAsRole('agent');

        $myLead = Lead::factory()->create([
            'tenant_id' => $this->tenant->id,
            'agent_id' => $agent->id,
        ]);

        $otherLead = Lead::factory()->create([
            'tenant_id' => $this->tenant->id,
            'agent_id' => $this->adminUser->id,
        ]);

        $response = $this->get('/leads');
        $response->assertStatus(200);
        $response->assertSee($myLead->first_name);
        $response->assertDontSee($otherLead->first_name);
    }

    public function test_agent_cannot_access_other_agents_lead(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAsRole('agent');

        $otherLead = Lead::factory()->create([
            'tenant_id' => $this->tenant->id,
            'agent_id' => $this->adminUser->id,
        ]);

        $response = $this->get("/leads/{$otherLead->id}");
        $response->assertStatus(403);
    }

    public function test_lead_creation_requires_first_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/leads', [
            'last_name' => 'Doe',
            'lead_source' => 'website',
            'status' => 'new',
            'temperature' => 'warm',
        ]);

        $response->assertSessionHasErrors('first_name');
    }

    public function test_lead_creation_sets_tenant_id(): void
    {
        $this->actingAsAdmin();

        $this->post('/leads', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'lead_source' => 'website',
            'status' => 'new',
            'temperature' => 'warm',
            'agent_id' => $this->adminUser->id,
        ]);

        $lead = Lead::first();
        $this->assertEquals($this->tenant->id, $lead->tenant_id);
    }
}
