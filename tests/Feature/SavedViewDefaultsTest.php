<?php

namespace Tests\Feature;

use App\Models\SavedView;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SavedViewDefaultsTest extends TestCase
{
    private function createSavedView(array $overrides = []): SavedView
    {
        return SavedView::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->adminUser->id,
            'entity_type' => 'leads',
            'name' => 'Test View',
            'filters' => ['status' => 'hot'],
            'is_shared' => false,
        ], $overrides));
    }

    public function test_non_admin_can_set_shared_view_as_default(): void
    {
        $this->createTenantWithAdmin();

        // Admin creates a shared view
        $sharedView = $this->createSavedView([
            'name' => 'Admin Shared View',
            'is_shared' => true,
        ]);

        // Agent (non-admin) sets it as default
        $agent = $this->createUserWithRole('agent');

        $response = $this->actingAs($agent)
            ->postJson("/saved-views/{$sharedView->id}/default");

        $response->assertOk()
            ->assertJson(['success' => true]);

        // Verify per-user default was stored
        $this->assertDatabaseHas('saved_view_defaults', [
            'user_id' => $agent->id,
            'entity_type' => 'leads',
            'saved_view_id' => $sharedView->id,
        ]);
    }

    public function test_user_default_does_not_affect_other_users(): void
    {
        $this->createTenantWithAdmin();

        $sharedView = $this->createSavedView([
            'name' => 'Shared View',
            'is_shared' => true,
        ]);

        $agentA = $this->createUserWithRole('agent');
        $agentB = $this->createUserWithRole('agent');

        // Agent A sets shared view as default
        $this->actingAs($agentA)
            ->postJson("/saved-views/{$sharedView->id}/default")
            ->assertOk();

        // Agent B's index should NOT show it as default
        $response = $this->actingAs($agentB)
            ->getJson('/saved-views?entity_type=leads');

        $response->assertOk();

        $views = $response->json();
        $target = collect($views)->firstWhere('id', $sharedView->id);

        $this->assertNotNull($target);
        $this->assertFalse($target['is_user_default']);

        // Agent A's index should show it as default
        $response = $this->actingAs($agentA)
            ->getJson('/saved-views?entity_type=leads');

        $views = $response->json();
        $target = collect($views)->firstWhere('id', $sharedView->id);

        $this->assertNotNull($target);
        $this->assertTrue($target['is_user_default']);
    }

    public function test_setting_default_replaces_previous_default_for_same_entity(): void
    {
        $this->createTenantWithAdmin();

        $viewA = $this->createSavedView(['name' => 'View A']);
        $viewB = $this->createSavedView(['name' => 'View B']);

        // Set View A as default
        $this->actingAs($this->adminUser)
            ->postJson("/saved-views/{$viewA->id}/default")
            ->assertOk();

        $this->assertDatabaseHas('saved_view_defaults', [
            'user_id' => $this->adminUser->id,
            'entity_type' => 'leads',
            'saved_view_id' => $viewA->id,
        ]);

        // Set View B as default — should replace A
        $this->actingAs($this->adminUser)
            ->postJson("/saved-views/{$viewB->id}/default")
            ->assertOk();

        $this->assertDatabaseHas('saved_view_defaults', [
            'user_id' => $this->adminUser->id,
            'entity_type' => 'leads',
            'saved_view_id' => $viewB->id,
        ]);

        // Only one default row per user+entity
        $count = DB::table('saved_view_defaults')
            ->where('user_id', $this->adminUser->id)
            ->where('entity_type', 'leads')
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_cannot_set_default_on_inaccessible_view(): void
    {
        $this->createTenantWithAdmin();

        // Create a private view owned by admin (not shared)
        $privateView = $this->createSavedView([
            'name' => 'Admin Private',
            'is_shared' => false,
        ]);

        // Agent cannot access it
        $agent = $this->createUserWithRole('agent');

        $response = $this->actingAs($agent)
            ->postJson("/saved-views/{$privateView->id}/default");

        $response->assertForbidden();

        $this->assertDatabaseMissing('saved_view_defaults', [
            'user_id' => $agent->id,
            'saved_view_id' => $privateView->id,
        ]);
    }

    public function test_cross_tenant_view_default_is_forbidden(): void
    {
        $this->createTenantWithAdmin();

        $viewInTenantA = $this->createSavedView(['name' => 'Tenant A View']);

        // Create a second tenant with its own user
        $tenantB = \App\Models\Tenant::create([
            'name' => 'Other Company',
            'slug' => 'other-company',
            'email' => 'admin@other.com',
            'status' => 'active',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'date_format' => 'm/d/Y',
            'country' => 'US',
            'measurement_system' => 'imperial',
            'locale' => 'en',
            'distribution_method' => 'round_robin',
        ]);

        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        $otherUser = \App\Models\User::factory()->create([
            'tenant_id' => $tenantB->id,
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        // Other tenant's user tries to set our view as default
        $response = $this->actingAs($otherUser)
            ->postJson("/saved-views/{$viewInTenantA->id}/default");

        // Should be 403 or 404 (TenantScope hides the view)
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_index_returns_is_user_default_flag(): void
    {
        $this->createTenantWithAdmin();

        $viewA = $this->createSavedView(['name' => 'View A']);
        $viewB = $this->createSavedView(['name' => 'View B']);

        // Set View A as default
        $this->actingAs($this->adminUser)
            ->postJson("/saved-views/{$viewA->id}/default");

        $response = $this->actingAs($this->adminUser)
            ->getJson('/saved-views?entity_type=leads');

        $response->assertOk();

        $views = collect($response->json());
        $this->assertTrue($views->firstWhere('id', $viewA->id)['is_user_default']);
        $this->assertFalse($views->firstWhere('id', $viewB->id)['is_user_default']);
    }
}
