<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Get tenant-scoped cache key.
     */
    public function key(string $key): string
    {
        $tenantId = auth()->user()->tenant_id ?? 0;

        return "tenant.{$tenantId}.{$key}";
    }

    /**
     * Get a cached value or compute it.
     */
    public function remember(string $key, int $seconds, callable $callback): mixed
    {
        return Cache::remember($this->key($key), $seconds, $callback);
    }

    /**
     * Forget a cached value.
     */
    public function forget(string $key): bool
    {
        return Cache::forget($this->key($key));
    }

    /**
     * Flush all cache for the current tenant.
     * Note: This only works effectively with tagged caches (Redis/Memcached).
     */
    public function flushTenant(): void
    {
        $tenantId = auth()->user()->tenant_id ?? 0;

        // Forget known cached keys
        $keys = [
            'dashboard.kpis',
            'dashboard.pipeline',
            'dashboard.leaderboard',
            'reports.leads_by_source',
            'reports.funnel',
        ];

        foreach ($keys as $key) {
            Cache::forget("tenant.{$tenantId}.{$key}");
        }
    }
}
