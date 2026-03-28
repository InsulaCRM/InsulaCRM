<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Require2faSetup
{
    /**
     * Redirect users who haven't set up 2FA when the tenant enforces it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (!$user) {
            return $next($request);
        }

        $tenant = $user->tenant;
        if (!$tenant || !$tenant->require_2fa) {
            return $next($request);
        }

        // Allow 2FA setup routes, logout, profile, and security settings
        if ($request->routeIs('two-factor.*') || $request->routeIs('logout')
            || $request->routeIs('profile.edit') || $request->routeIs('profile.update')
            || $request->routeIs('settings.updateSecurity')) {
            return $next($request);
        }

        // If user hasn't enabled 2FA yet, force them to set it up
        if (!$user->two_factor_enabled) {
            return redirect()->route('two-factor.setup')
                ->with('warning', __('Your organization requires two-factor authentication. Please set it up to continue.'));
        }

        return $next($request);
    }
}
