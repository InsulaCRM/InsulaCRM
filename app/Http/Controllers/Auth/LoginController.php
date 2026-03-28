<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Integrations\IntegrationManager;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(Request $request, IntegrationManager $manager)
    {
        $ssoProviders = [];

        // Resolve tenant for SSO: from query param, session, or single-tenant auto-detect
        $tenantId = $request->query('tenant') ?? session('sso_tenant_id');
        if (! $tenantId) {
            // Auto-detect if there's only one tenant (typical single-company install)
            $tenantCount = Tenant::count();
            if ($tenantCount === 1) {
                $tenantId = Tenant::first()->id;
            }
        }

        if ($tenantId) {
            session(['sso_tenant_id' => $tenantId]);
            try {
                $ssoProviders = $manager->getActiveSsoProviders((int) $tenantId);
            } catch (\Throwable $e) {
                // SSO plugin not loaded — silently skip
            }
        }

        return view('auth.login', compact('ssoProviders'));
    }

    /**
     * Handle a login request.
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // If 2FA is enabled, log out and redirect to challenge
            if ($user->two_factor_enabled && $user->two_factor_secret) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $request->session()->put('2fa_user_id', $user->id);
                $request->session()->put('2fa_remember', $request->boolean('remember'));
                $request->session()->put('2fa_provider', $user->two_factor_provider ?? 'totp');

                return redirect()->route('two-factor.challenge');
            }

            $request->session()->regenerate();

            // Redirect to onboarding if not completed (fresh install admin)
            if ($user->isAdmin() && !$user->onboarding_completed) {
                return redirect()->route('onboarding.index');
            }

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
