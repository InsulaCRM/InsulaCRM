<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('sequences:process')->daily();
        $schedule->command('deals:check-due-diligence')->daily();
        $schedule->command('leads:assign-unclaimed')->everyMinute();
        $schedule->command('backup:clean')->daily()->at('01:00');
        $schedule->command('ai:pipeline-digest')->dailyAt('07:00');
        $schedule->command('ai:suggest-follow-ups')->dailyAt('08:00');
        $schedule->command('ai:stale-deal-alerts')->dailyAt('09:00');
        $schedule->command('workflow:process-delays')->everyMinute();
        $schedule->command('notifications:send-digest')->dailyAt('07:00');
        $schedule->command('digest:morning-summary')->dailyAt('07:30');
        $schedule->command('digest:expiring-contingencies')->dailyAt('08:00');
        $schedule->command('digest:inactive-clients')->weeklyOn(1, '09:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
                | Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->prependToGroup('web', \App\Http\Middleware\CheckInstalled::class);

        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'mode' => \App\Http\Middleware\RequireBusinessMode::class,
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'require2fa' => \App\Http\Middleware\Require2faSetup::class,
            'api.log' => \App\Http\Middleware\ApiRequestLog::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            try {
                if (!app()->bound('db') || !Illuminate\Support\Facades\Schema::hasTable('error_logs')) {
                    return;
                }

                $request = request();
                $user = auth()->check() ? auth()->user() : null;

                Illuminate\Support\Facades\DB::table('error_logs')->insert([
                    'tenant_id' => $user?->tenant_id,
                    'user_id' => $user?->id,
                    'level' => $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                        ? 'warning' : 'error',
                    'message' => Illuminate\Support\Str::limit($e->getMessage(), 1000),
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => Illuminate\Support\Str::limit($e->getTraceAsString(), 10000),
                    'url' => $request?->fullUrl(),
                    'method' => $request?->method(),
                    'ip_address' => $request?->ip(),
                    'user_agent' => Illuminate\Support\Str::limit($request?->userAgent() ?? '', 500),
                    'context' => json_encode([
                        'input' => $request?->except(['password', 'password_confirmation', '_token']),
                    ]),
                    'is_resolved' => false,
                    'created_at' => now(),
                ]);
            } catch (\Throwable) {
                // Silently fail — don't let error logging cause more errors
            }
        });
    })->create();
