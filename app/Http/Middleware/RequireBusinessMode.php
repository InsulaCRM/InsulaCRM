<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireBusinessMode
{
    public function handle(Request $request, Closure $next, string $mode): Response
    {
        $tenant = $request->user()?->tenant;

        if ($tenant && ($tenant->business_mode ?? 'wholesale') !== $mode) {
            abort(404);
        }

        return $next($request);
    }
}
