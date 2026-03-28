<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-API-Key');

        if (!$key) {
            return response()->json(['error' => 'API key required. Pass via X-API-Key header.'], 401);
        }

        $tenant = Tenant::where('api_key', $key)->where('api_enabled', true)->first();

        if (!$tenant) {
            return response()->json(['error' => 'Invalid or disabled API key.'], 403);
        }

        if ($tenant->status !== 'active') {
            return response()->json(['error' => 'Account is suspended.'], 403);
        }

        // Store tenant on request for use in controllers
        $request->merge(['_tenant' => $tenant]);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
