<?php

namespace Tests\Feature;

use App\Models\Task;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    public function test_calendar_page_loads(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('calendar.index'));

        $response->assertStatus(200);
        $response->assertSee('Calendar');
    }

    public function test_calendar_events_returns_json(): void
    {
        $this->actingAsAdmin();

        $lead = $this->createLead();
        Task::create([
            'tenant_id' => $this->tenant->id,
            'lead_id' => $lead->id,
            'agent_id' => $this->adminUser->id,
            'title' => 'Follow up call',
            'due_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->getJson(route('calendar.events', [
            'start' => now()->startOfMonth()->format('Y-m-d'),
            'end' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $events = $response->json();
        $this->assertNotEmpty($events);
        $this->assertEquals('Follow up call', $events[0]['title']);
    }

    public function test_field_scout_cannot_access_calendar(): void
    {
        $this->actingAsRole('field_scout');

        $response = $this->get(route('calendar.index'));
        $response->assertStatus(403);
    }
}
