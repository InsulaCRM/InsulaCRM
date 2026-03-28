<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Notifications\LeadAssigned;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_notifications_index_page_loads(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('All Notifications');
    }

    public function test_recent_notifications_returns_json(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('notifications.recent'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['notifications', 'unread_count']);
    }

    public function test_notification_is_stored_in_database(): void
    {
        $this->actingAsAdmin();

        $lead = $this->createLead();
        $this->adminUser->notify(new LeadAssigned($lead, $this->tenant));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->adminUser->id,
            'notifiable_type' => get_class($this->adminUser),
        ]);
    }

    public function test_mark_notification_as_read(): void
    {
        $this->actingAsAdmin();

        $lead = $this->createLead();
        $this->adminUser->notify(new LeadAssigned($lead, $this->tenant));

        $notification = $this->adminUser->unreadNotifications()->first();
        $this->assertNotNull($notification);

        $response = $this->postJson(route('notifications.markAsRead', $notification->id));

        $response->assertJson(['success' => true]);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_notifications_as_read(): void
    {
        $this->actingAsAdmin();

        $lead = $this->createLead();
        $this->adminUser->notify(new LeadAssigned($lead, $this->tenant));
        $this->adminUser->notify(new LeadAssigned($lead, $this->tenant));

        $this->assertEquals(2, $this->adminUser->unreadNotifications()->count());

        $response = $this->postJson(route('notifications.markAllRead'));

        $response->assertJson(['success' => true]);
        $this->assertEquals(0, $this->adminUser->fresh()->unreadNotifications()->count());
    }

    public function test_recent_returns_unread_count(): void
    {
        $this->actingAsAdmin();

        $lead = $this->createLead();
        $this->adminUser->notify(new LeadAssigned($lead, $this->tenant));

        $response = $this->getJson(route('notifications.recent'));

        $response->assertJson(['unread_count' => 1]);
        $this->assertCount(1, $response->json('notifications'));
    }

    public function test_notification_data_has_required_fields(): void
    {
        $this->actingAsAdmin();

        $lead = $this->createLead();
        $this->adminUser->notify(new LeadAssigned($lead, $this->tenant));

        $notification = $this->adminUser->notifications()->first();
        $data = $notification->data;

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('icon', $data);
        $this->assertArrayHasKey('color', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('body', $data);
        $this->assertArrayHasKey('url', $data);
    }

    public function test_cannot_mark_other_users_notification_as_read(): void
    {
        $this->actingAsAdmin();

        // Create notification for a different user
        $agent = $this->createUserWithRole('agent');
        $lead = $this->createLead();
        $agent->notify(new LeadAssigned($lead, $this->tenant));

        $notification = $agent->unreadNotifications()->first();

        // Acting as admin, try to mark agent's notification
        $response = $this->postJson(route('notifications.markAsRead', $notification->id));

        // Should succeed but not actually mark it (since it's not our notification)
        $response->assertJson(['success' => true]);
        // The notification should still be unread since we queried by auth user
        $this->assertNull($notification->fresh()->read_at);
    }
}
