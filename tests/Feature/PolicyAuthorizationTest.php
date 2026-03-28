<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

class PolicyAuthorizationTest extends TestCase
{
    public function test_agent_cannot_view_another_agents_lead(): void
    {
        $this->createTenantWithAdmin();
        $agentA = $this->createUserWithRole('agent');
        $agentB = $this->createUserWithRole('agent');
        $lead = $this->createLead(['agent_id' => $agentB->id]);

        $this->actingAs($agentA);

        $this->get("/leads/{$lead->id}")->assertStatus(403);
    }

    public function test_disposition_agent_can_view_pipeline_deal_assigned_to_another_user(): void
    {
        $this->createTenantWithAdmin();
        $deal = $this->createDeal();
        $dispositionAgent = $this->createUserWithRole('disposition_agent');

        $this->actingAs($dispositionAgent);

        $this->get("/pipeline/{$deal->id}")->assertStatus(200);
    }

    public function test_agent_cannot_view_property_for_another_agents_lead(): void
    {
        $this->createTenantWithAdmin();
        $agentA = $this->createUserWithRole('agent');
        $agentB = $this->createUserWithRole('agent');
        $lead = $this->createLead(['agent_id' => $agentB->id]);
        $property = $this->createProperty(['lead_id' => $lead->id]);

        $this->actingAs($agentA);

        $this->get("/properties/{$property->id}")->assertStatus(403);
    }

    public function test_admin_cannot_toggle_team_member_from_other_tenant(): void
    {
        $this->createTenantWithAdmin();
        $this->actingAs($this->adminUser);

        $tenantB = Tenant::create([
            'name' => 'Company B',
            'slug' => 'company-b',
            'email' => 'b@test.com',
            'status' => 'active',
        ]);

        $agentRole = Role::where('name', 'agent')->first();
        $otherTenantUser = User::factory()->create([
            'tenant_id' => $tenantB->id,
            'role_id' => $agentRole->id,
            'is_active' => true,
        ]);

        $this->patch("/settings/agents/{$otherTenantUser->id}/toggle")->assertStatus(403);
    }
}
