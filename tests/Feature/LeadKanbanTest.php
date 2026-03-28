<?php

namespace Tests\Feature;

use Tests\TestCase;

class LeadKanbanTest extends TestCase
{
    public function test_kanban_page_loads(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('leads.kanban'));

        $response->assertStatus(200);
        $response->assertSee('Lead Kanban Board');
    }

    public function test_kanban_shows_leads_grouped_by_status(): void
    {
        $this->actingAsAdmin();

        $this->createLead(['first_name' => 'KanbanLead', 'status' => 'new']);
        $this->createLead(['first_name' => 'ContactedLead', 'status' => 'contacted']);

        $response = $this->get(route('leads.kanban'));

        $response->assertSee('KanbanLead');
        $response->assertSee('ContactedLead');
    }

    public function test_agents_see_only_their_leads_in_kanban(): void
    {
        $this->actingAsAdmin();
        $agent = $this->actingAsRole('agent');

        $this->createLead(['first_name' => 'AdminLead', 'agent_id' => $this->adminUser->id, 'status' => 'new']);
        $this->createLead(['first_name' => 'AgentLead', 'agent_id' => $agent->id, 'status' => 'new']);

        $response = $this->get(route('leads.kanban'));

        $response->assertSee('AgentLead');
        $response->assertDontSee('AdminLead');
    }
}
