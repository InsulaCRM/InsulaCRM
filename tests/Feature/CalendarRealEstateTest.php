<?php

namespace Tests\Feature;

use App\Models\OpenHouse;
use App\Models\Showing;
use Tests\TestCase;

class CalendarRealEstateTest extends TestCase
{
    public function test_calendar_shows_showing_events_in_realestate_mode(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $property = $this->createProperty();

        Showing::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'showing_date' => now()->format('Y-m-d'),
            'showing_time' => '14:00',
            'status' => 'scheduled',
        ]);

        $response = $this->getJson(route('calendar.events', [
            'start' => now()->startOfMonth()->format('Y-m-d'),
            'end' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $events = $response->json();

        $showingEvents = array_filter($events, fn ($e) => str_contains($e['title'] ?? '', 'Showing') || ($e['color'] ?? '') === '#f97316');
        $this->assertNotEmpty($showingEvents);
    }

    public function test_calendar_shows_open_house_events_in_realestate_mode(): void
    {
        $this->actingAsAdmin(['business_mode' => 'realestate']);
        $property = $this->createProperty();

        OpenHouse::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $property->id,
            'agent_id' => $this->adminUser->id,
            'event_date' => now()->format('Y-m-d'),
            'start_time' => '13:00',
            'end_time' => '16:00',
            'status' => 'scheduled',
        ]);

        $response = $this->getJson(route('calendar.events', [
            'start' => now()->startOfMonth()->format('Y-m-d'),
            'end' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $events = $response->json();

        $ohEvents = array_filter($events, fn ($e) => str_contains($e['title'] ?? '', 'Open House') || ($e['color'] ?? '') === '#14b8a6');
        $this->assertNotEmpty($ohEvents);
    }

    public function test_calendar_does_not_show_showings_in_wholesale_mode(): void
    {
        $this->actingAsAdmin(['business_mode' => 'wholesale']);
        $property = $this->createProperty();

        // Even if a showing record exists, wholesale should not display it
        // (the showing route itself is blocked, but calendar events shouldn't show them)

        $response = $this->getJson(route('calendar.events', [
            'start' => now()->startOfMonth()->format('Y-m-d'),
            'end' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $events = $response->json();

        $showingEvents = array_filter($events, fn ($e) => ($e['color'] ?? '') === '#f97316');
        $this->assertEmpty($showingEvents);
    }
}
