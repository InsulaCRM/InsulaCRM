<?php
namespace App\Providers;

use App\Services\HookManager;
use App\Services\PluginManager;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HookManager::class, function () {
            return new HookManager();
        });

        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager();
        });
    }

    public function boot(): void
    {
        try {
            app(PluginManager::class)->bootAll();
        } catch (\Throwable $e) {
            // Don't crash the app if plugin loading fails
            \Illuminate\Support\Facades\Log::error('Plugin boot failed: ' . $e->getMessage());
        }
    }
}
