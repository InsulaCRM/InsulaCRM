<?php

namespace App\Plugins;

use App\Services\HookManager;
use Illuminate\Support\Facades\Route;

abstract class BasePlugin
{
    protected HookManager $hooks;
    protected string $basePath;
    protected array $manifest;

    public function __construct(HookManager $hooks, string $basePath, array $manifest)
    {
        $this->hooks = $hooks;
        $this->basePath = $basePath;
        $this->manifest = $manifest;
    }

    /**
     * Register plugin services, bindings, hooks.
     * Called before boot().
     */
    abstract public function register(): void;

    /**
     * Boot the plugin after all plugins are registered.
     * Called after register().
     */
    abstract public function boot(): void;

    /**
     * Add a menu item to the CRM sidebar.
     */
    public function addMenuItem(string $label, string $route, string $icon = 'fas fa-puzzle-piece'): void
    {
        $this->hooks->registerMenuItem($label, $route, $icon);
    }

    /**
     * Add a dashboard widget.
     */
    public function addDashboardWidget(string $view, int $position = 100): void
    {
        $this->hooks->registerDashboardWidget($view, $position);
    }

    /**
     * Add a settings tab.
     */
    public function addSettingsTab(string $label, string $view): void
    {
        $this->hooks->registerSettingsTab($label, $view);
    }

    /**
     * Register migrations from a given path.
     */
    public function registerMigrations(string $path): void
    {
        $absolutePath = str_starts_with($path, '/')
            ? $path
            : $this->basePath . '/' . ltrim($path, '/');

        if (is_dir($absolutePath)) {
            app()->afterResolving('migrator', function ($migrator) use ($absolutePath) {
                $migrator->path($absolutePath);
            });
        }
    }

    /**
     * Load routes from a file within the plugin.
     */
    public function loadRoutes(string $routesFile): void
    {
        $slug = $this->manifest['slug'] ?? basename($this->basePath);

        Route::middleware(['web', 'auth', 'tenant'])
            ->prefix("plugin/{$slug}")
            ->group($routesFile);
    }

    /**
     * Get the plugin slug.
     */
    public function getSlug(): string
    {
        return $this->manifest['slug'] ?? basename($this->basePath);
    }

    /**
     * Get the plugin manifest.
     */
    public function getManifest(): array
    {
        return $this->manifest;
    }

    /**
     * Get the base path of the plugin.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }
}
