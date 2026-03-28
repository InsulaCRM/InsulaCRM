<?php

namespace Tests\Feature;

use App\Integrations\IntegrationManager;
use App\Models\Integration;
use App\Models\Tenant;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    public function test_integrations_tab_visible_in_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Integrations');
        $response->assertSee('Require Two-Factor Authentication for All Users');
    }

    public function test_admin_can_enable_2fa_enforcement(): void
    {
        $this->actingAsAdmin();

        $response = $this->put(route('settings.updateSecurity'), [
            'require_2fa' => 1,
        ]);

        $response->assertRedirect();
        $this->assertTrue($this->tenant->fresh()->require_2fa);
    }

    public function test_admin_can_disable_2fa_enforcement(): void
    {
        $this->actingAsAdmin();
        $this->tenant->update(['require_2fa' => true]);
        $this->assertTrue($this->tenant->fresh()->require_2fa);

        $response = $this->put(route('settings.updateSecurity'), [
            'require_2fa' => '0',
        ]);

        $response->assertRedirect();
        $this->assertFalse((bool) $this->tenant->fresh()->require_2fa);
    }

    public function test_2fa_enforcement_redirects_user_without_2fa(): void
    {
        $this->actingAsAdmin();
        $this->tenant->update(['require_2fa' => true]);
        $this->adminUser->update(['two_factor_enabled' => false]);

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('two-factor.setup'));
    }

    public function test_2fa_enforcement_allows_user_with_2fa(): void
    {
        $this->actingAsAdmin();
        $this->tenant->update(['require_2fa' => true]);
        $this->adminUser->update(['two_factor_enabled' => true]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_2fa_enforcement_allows_setup_routes(): void
    {
        $this->actingAsAdmin();
        $this->tenant->update(['require_2fa' => true]);
        $this->adminUser->update(['two_factor_enabled' => false]);

        $response = $this->get(route('two-factor.setup'));

        $response->assertStatus(200);
    }

    public function test_cannot_disable_2fa_when_enforcement_is_on(): void
    {
        $this->actingAsAdmin();
        $this->tenant->update(['require_2fa' => true]);

        $response = $this->delete(route('two-factor.disable'), [
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_integration_manager_returns_totp_by_default(): void
    {
        $manager = new IntegrationManager();
        $provider = $manager->get2faProvider();

        $this->assertEquals('totp', $provider->driver());
        $this->assertEquals('Authenticator App (TOTP)', $provider->name());
    }

    public function test_integration_manager_lists_available_2fa_drivers(): void
    {
        $manager = new IntegrationManager();
        $drivers = $manager->getAvailableDrivers('2fa');

        $this->assertArrayHasKey('totp', $drivers);
        $this->assertEquals('Authenticator App (TOTP)', $drivers['totp']['name']);
        $this->assertFalse($drivers['totp']['requires_config']);
    }

    public function test_integration_crud(): void
    {
        $this->actingAsAdmin();

        // Store (will fail since 'test-driver' is not registered)
        $response = $this->post(route('integrations.store'), [
            'category' => '2fa',
            'driver' => 'nonexistent',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_non_admin_cannot_access_security_settings(): void
    {
        $this->actingAsRole('agent');

        $response = $this->put(route('settings.updateSecurity'), [
            'require_2fa' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_sso_routes_exist(): void
    {
        // SSO redirect without tenant should redirect to login
        $response = $this->get(route('sso.redirect', ['driver' => 'google-oauth']));

        $response->assertRedirect(route('login'));
    }

    public function test_sso_callback_rejects_invalid_state(): void
    {
        $this->createTenantWithAdmin();

        Integration::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'sso',
            'driver' => 'google-oauth',
            'name' => 'Google',
            'config' => ['client_id' => 'test', 'client_secret' => 'secret'],
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'sso_tenant_id' => $this->tenant->id,
            'sso_driver' => 'google-oauth',
            '_token' => 'expected-state',
        ])->get(route('sso.callback', ['driver' => 'google-oauth', 'state' => 'invalid-state', 'code' => 'abc']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'SSO authentication failed: invalid state.');
    }

    public function test_profile_shows_enforcement_message_when_2fa_required(): void
    {
        $this->actingAsAdmin();
        $this->tenant->update(['require_2fa' => true]);
        $this->adminUser->update(['two_factor_enabled' => true]);

        $response = $this->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Your organization requires 2FA');
    }

    public function test_admin_can_reset_2fa_for_user(): void
    {
        $this->actingAsAdmin();
        $agent = $this->actingAsRole('agent');

        // Give the agent 2FA
        $agent->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => \Illuminate\Support\Facades\Crypt::encryptString('TESTSECRET'),
            'two_factor_recovery_codes' => encrypt(['CODE1', 'CODE2']),
            'two_factor_provider' => 'totp',
        ]);

        // Switch back to admin
        $this->actingAs($this->adminUser);

        $response = $this->delete(route('settings.reset2fa', $agent));

        $response->assertRedirect();
        $fresh = $agent->fresh();
        $this->assertFalse($fresh->two_factor_enabled);
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_recovery_codes);
    }

    public function test_non_admin_cannot_reset_2fa(): void
    {
        $this->actingAsAdmin();
        $agent = $this->actingAsRole('agent');

        $response = $this->delete(route('settings.reset2fa', $this->adminUser));

        $response->assertStatus(403);
    }

    public function test_reset_2fa_shows_error_when_not_enabled(): void
    {
        $this->actingAsAdmin();
        $agent = $this->actingAsRole('agent');
        $this->actingAs($this->adminUser);

        $response = $this->delete(route('settings.reset2fa', $agent));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_team_table_shows_2fa_status(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('2FA');
    }
}
