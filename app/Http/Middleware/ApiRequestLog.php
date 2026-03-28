<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLog
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000);

        try {
            DB::table('api_logs')->insert([
                'tenant_id' => $request->attributes->get('tenant')?->id ?? null,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                'duration_ms' => $duration,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silently fail — logging should not break API requests
        }

        return $response;
    }
}
