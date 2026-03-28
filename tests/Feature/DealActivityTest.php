<?php

namespace Tests\Feature;

use App\Models\Activity;
use Tests\TestCase;

class DealActivityTest extends TestCase
{
    public function test_log_activity_on_deal(): void
    {
        $this->actingAsAdmin();
        $deal = $this->createDeal();

        $response = $this->post(route('deals.activities.store', $deal), [
            'type' => 'note',
            'subject' => 'Test Note',
            'body' => 'This is a test activity on a deal.',
        ]);

        $response->assertRedirect(route('deals.show', $deal));

        $this->assertDatabaseHas('activities', [
            'deal_id' => $deal->id,
            'type' => 'note',
            'subject' => 'Test Note',
            'agent_id' => $this->adminUser->id,
        ]);
    }

    public function test_deal_show_page_shows_activities(): void
    {
        $this->actingAsAdmin();
        $deal = $this->createDeal();

        Activity::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'agent_id' => $this->adminUser->id,
            'type' => 'call',
            'subject' => 'Called buyer',
            'body' => 'Discussed terms',
            'logged_at' => now(),
        ]);

        $response = $this->get(route('deals.show', $deal));

        $response->assertStatus(200);
        $response->assertSee('Called buyer');
        $response->assertSee('Discussed terms');
    }

    public function test_deal_activity_has_no_lead_id(): void
    {
        $this->actingAsAdmin();
        $deal = $this->createDeal();

        $this->post(route('deals.activities.store', $deal), [
            'type' => 'meeting',
            'subject' => 'Closing meeting',
            'body' => 'Met with title company',
        ]);

        $activity = Activity::where('deal_id', $deal->id)->first();
        $this->assertNotNull($activity);
        $this->assertNull($activity->lead_id);
    }

    public function test_agents_cannot_log_activity_on_others_deal(): void
    {
        $this->actingAsAdmin();
        $deal = $this->createDeal();

        $agent = $this->actingAsRole('agent');

        $response = $this->post(route('deals.activities.store', $deal), [
            'type' => 'note',
            'body' => 'Should not work',
        ]);

        $response->assertStatus(403);
    }
}
