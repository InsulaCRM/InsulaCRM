<?php

namespace App\Http\Controllers;

use App\Integrations\IntegrationManager;
use App\Models\Integration;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SsoController extends Controller
{
    /**
     * Redirect to the SSO provider.
     */
    public function redirect(string $driver, Request $request, IntegrationManager $manager)
    {
        // Find the tenant from the request (e.g., via session or query param)
        $tenantId = session('sso_tenant_id', $request->query('tenant'));
        if (!$tenantId) {
            return redirect()->route('login')->with('error', __('SSO is not configured.'));
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return redirect()->route('login')->with('error', __('Invalid tenant.'));
        }

        $integration = Integration::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('category', 'sso')
            ->where('driver', $driver)
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return redirect()->route('login')->with('error', __('SSO provider not configured.'));
        }

        if (!$manager->hasDriver('sso', $driver)) {
            return redirect()->route('login')->with('error', __('SSO provider not available.'));
        }

        $provider = $manager->getSsoProvider($driver);

        session(['sso_tenant_id' => $tenant->id, 'sso_driver' => $driver]);

        return redirect($provider->redirectUrl($tenant, $integration->config));
    }

    /**
     * Handle the SSO callback.
     */
    public function callback(string $driver, Request $request, IntegrationManager $manager)
    {
        $tenantId = session('sso_tenant_id');
        if (!$tenantId) {
            return redirect()->route('login')->with('error', __('SSO session expired.'));
        }

        $expectedState = session()->token();
        $incomingState = (string) $request->input('state', '');
        if ($incomingState === '' || ! hash_equals($expectedState, $incomingState)) {
            session()->forget(['sso_tenant_id', 'sso_driver']);

            return redirect()->route('login')->with('error', __('SSO authentication failed: invalid state.'));
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return redirect()->route('login')->with('error', __('Invalid tenant.'));
        }

        $integration = Integration::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('category', 'sso')
            ->where('driver', $driver)
            ->where('is_active', true)
            ->first();

        if (!$integration || !$manager->hasDriver('sso', $driver)) {
            return redirect()->route('login')->with('error', __('SSO provider not available.'));
        }

        try {
            $provider = $manager->getSsoProvider($driver);
            $result = $provider->handleCallback($tenant, $integration->config, $request);

            // Find the user by email within this tenant
            $user = User::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('email', $result->email)
                ->where('is_active', true)
                ->first();

            if (!$user) {
                return redirect()->route('login')->with('error', __('No account found for this email. Contact your administrator.'));
            }

            session()->forget(['sso_tenant_id', 'sso_driver']);

            // If 2FA is required and enabled, redirect to challenge
            if ($user->two_factor_enabled && $user->two_factor_secret) {
                $request->session()->put('2fa_user_id', $user->id);
                $request->session()->put('2fa_provider', $user->two_factor_provider ?? 'totp');
                return redirect()->route('two-factor.challenge');
            }

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', __('SSO authentication failed: :message', ['message' => $e->getMessage()]));
        }
    }
}
