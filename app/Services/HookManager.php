<?php
namespace App\Services;

class HookManager
{
    protected array $actions = [];
    protected array $filters = [];
    protected array $menuItems = [];
    protected array $dashboardWidgets = [];
    protected array $settingsTabs = [];

    public function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        $this->actions[$hook][$priority][] = $callback;
    }

    public function doAction(string $hook, ...$args): void
    {
        if (!isset($this->actions[$hook])) return;
        ksort($this->actions[$hook]);
        foreach ($this->actions[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func($callback, ...$args);
            }
        }
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->filters[$hook][$priority][] = $callback;
    }

    public function applyFilter(string $hook, $value, ...$args)
    {
        if (!isset($this->filters[$hook])) return $value;
        ksort($this->filters[$hook]);
        foreach ($this->filters[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = call_user_func($callback, $value, ...$args);
            }
        }
        return $value;
    }

    /**
     * Register a sidebar menu item from a plugin.
     */
    public function registerMenuItem(string $label, string $route, string $icon = 'fas fa-puzzle-piece'): void
    {
        $this->menuItems[] = compact('label', 'route', 'icon');
    }

    /**
     * Get all registered plugin menu items.
     */
    public function getMenuItems(): array
    {
        return $this->menuItems;
    }

    /**
     * Register a dashboard widget from a plugin.
     */
    public function registerDashboardWidget(string $view, int $position = 100): void
    {
        $this->dashboardWidgets[] = compact('view', 'position');
    }

    /**
     * Get all registered dashboard widgets sorted by position.
     */
    public function getDashboardWidgets(): array
    {
        usort($this->dashboardWidgets, fn($a, $b) => $a['position'] <=> $b['position']);
        return $this->dashboardWidgets;
    }

    /**
     * Register a settings tab from a plugin.
     */
    public function registerSettingsTab(string $label, string $view): void
    {
        $this->settingsTabs[] = compact('label', 'view');
    }

    /**
     * Get all registered settings tabs.
     */
    public function getSettingsTabs(): array
    {
        return $this->settingsTabs;
    }
}
