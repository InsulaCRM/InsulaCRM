<?php
namespace App\Services;

use App\Models\Plugin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use RuntimeException;

class PluginManager
{
    protected array $loadedPlugins = [];

    public function bootAll(): void
    {
        try {
            $plugins = Plugin::withoutGlobalScopes()->where('is_active', true)->get();
        } catch (\Throwable $e) {
            // Database might not be set up yet (e.g. during install)
            return;
        }

        foreach ($plugins as $plugin) {
            $this->boot($plugin);
        }
    }

    public function boot(Plugin $plugin): void
    {
        $pluginDir = base_path("plugins/{$plugin->slug}");
        $bootFile = $pluginDir . '/boot.php';
        $manifestFile = $pluginDir . '/plugin.json';

        if (!File::exists($pluginDir)) {
            Log::warning("Plugin {$plugin->slug}: directory not found at {$pluginDir}");
            return;
        }

        try {
            // Load manifest
            $manifest = [];
            if (File::exists($manifestFile)) {
                $manifest = json_decode(File::get($manifestFile), true) ?? [];
            }

            // Register plugin views namespace
            $viewsDir = $pluginDir . '/views';
            if (File::isDirectory($viewsDir)) {
                View::addNamespace("plugin-{$plugin->slug}", $viewsDir);
            }

            // Load routes
            $routesFile = $pluginDir . '/routes/web.php';
            if (File::exists($routesFile)) {
                Route::middleware(['web', 'auth', 'tenant'])
                    ->prefix("plugin/{$plugin->slug}")
                    ->group($routesFile);
            }

            // Try to instantiate the plugin class if entry_class is defined or src/Plugin.php exists
            $entryClass = $manifest['entry_class'] ?? null;
            $pluginInstance = null;

            if ($entryClass) {
                $this->autoloadPluginSrc($pluginDir, $manifest['slug'] ?? $plugin->slug);
                if (class_exists($entryClass)) {
                    $pluginInstance = new $entryClass(
                        app(HookManager::class),
                        $pluginDir,
                        $manifest
                    );
                }
            } else {
                // Auto-detect: look for src/ directory and try to find a class extending BasePlugin
                $srcDir = $pluginDir . '/src';
                if (File::isDirectory($srcDir)) {
                    foreach (File::files($srcDir) as $file) {
                        if ($file->getExtension() === 'php') {
                            require_once $file->getPathname();
                        }
                    }
                    // Try common class naming patterns
                    $slug = $manifest['slug'] ?? $plugin->slug;
                    $className = str_replace('-', '', ucwords($slug, '-'));
                    $possibleClasses = [
                        $className . '\\' . $className . 'Plugin',
                        'HelloWorld\\HelloWorldPlugin',
                        $className . 'Plugin',
                    ];
                    foreach ($possibleClasses as $class) {
                        if (class_exists($class) && is_subclass_of($class, \App\Plugins\BasePlugin::class)) {
                            $pluginInstance = new $class(
                                app(HookManager::class),
                                $pluginDir,
                                $manifest
                            );
                            break;
                        }
                    }
                }
            }

            // Call register() and boot() on the plugin class
            if ($pluginInstance) {
                $pluginInstance->register();
                $pluginInstance->boot();
            }

            // Also load boot.php for simple hook registration
            if (File::exists($bootFile)) {
                require_once $bootFile;
            }

            $this->loadedPlugins[$plugin->slug] = $plugin;

        } catch (\Throwable $e) {
            Log::error("Plugin {$plugin->slug} failed to boot: " . $e->getMessage());
        }
    }

    /**
     * Autoload plugin src files.
     */
    protected function autoloadPluginSrc(string $pluginDir, string $slug): void
    {
        $srcDir = $pluginDir . '/src';
        if (File::isDirectory($srcDir)) {
            foreach (File::allFiles($srcDir) as $file) {
                if ($file->getExtension() === 'php') {
                    require_once $file->getPathname();
                }
            }
        }
    }

    public function getLoaded(): array
    {
        return $this->loadedPlugins;
    }

    public function getInstalledPluginsForTenant(int $tenantId): array
    {
        return Plugin::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('slug')
            ->map(fn (Plugin $plugin) => $plugin->toArray())
            ->all();
    }

    public function isBundled(string $slug): bool
    {
        return File::isDirectory($this->pluginDirectory($slug));
    }

    public function getManifest(string $slug): ?array
    {
        $manifestPath = $this->pluginDirectory($slug) . '/plugin.json';

        if (! File::exists($manifestPath)) {
            return null;
        }

        $manifest = json_decode(File::get($manifestPath), true);

        return is_array($manifest) ? $manifest : null;
    }

    public function installBundledPlugin(string $slug, int $tenantId): Plugin
    {
        $pluginDir = $this->pluginDirectory($slug);
        $manifest = $this->getManifest($slug);

        if (! File::isDirectory($pluginDir) || ! $manifest) {
            throw new RuntimeException('This plugin is not bundled with the current InsulaCRM release.');
        }

        if (empty($manifest['name']) || empty($manifest['slug']) || empty($manifest['version'])) {
            throw new RuntimeException('The bundled plugin manifest is invalid.');
        }

        if (($manifest['slug'] ?? null) !== $slug) {
            throw new RuntimeException('The bundled plugin manifest slug does not match the requested plugin.');
        }

        $this->runPluginMigrations($slug, $pluginDir);
        $this->publishPluginAssets($slug, $pluginDir);

        return Plugin::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'slug' => $slug,
            ],
            [
                'name' => $manifest['name'],
                'version' => $manifest['version'],
                'author' => $manifest['author'] ?? null,
                'description' => $manifest['description'] ?? null,
                'is_active' => false,
                'installed_at' => now(),
            ]
        );
    }

    private function pluginDirectory(string $slug): string
    {
        return base_path("plugins/{$slug}");
    }

    private function runPluginMigrations(string $slug, string $pluginDir): void
    {
        $migrationsPath = $pluginDir . '/migrations';

        if (! File::isDirectory($migrationsPath)) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--path' => 'plugins/' . $slug . '/migrations',
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Plugin {$slug} migrations failed: " . $e->getMessage());
        }
    }

    private function publishPluginAssets(string $slug, string $pluginDir): void
    {
        $assetsPath = $pluginDir . '/assets';

        if (! File::isDirectory($assetsPath)) {
            return;
        }

        $publicPath = public_path("plugins/{$slug}");
        if (! File::isDirectory($publicPath)) {
            File::makeDirectory($publicPath, 0755, true);
        }

        File::copyDirectory($assetsPath, $publicPath);
    }
}
