<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Plugin;
use App\Services\PluginManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class PluginController extends Controller
{
    /**
     * List installed plugins with name, version, author, and status.
     */
    public function index()
    {
        $plugins = Plugin::where('tenant_id', auth()->user()->tenant_id)->latest()->get();

        return view('plugins.index', compact('plugins'));
    }

    /**
     * Activate or deactivate a plugin.
     */
    public function toggle(Plugin $plugin)
    {
        abort_unless($plugin->tenant_id === auth()->user()->tenant_id, 403);

        $plugin->update([
            'is_active' => ! $plugin->is_active,
        ]);

        AuditLog::log('plugin.toggled', $plugin);

        // Clear caches so plugin routes/config take effect
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
        } catch (\Throwable $e) {
            Log::warning('Cache clear failed during plugin toggle', ['error' => $e->getMessage()]);
        }

        $status = $plugin->is_active ? 'activated' : 'deactivated';

        return redirect()->route('plugins.index')->with('success', "Plugin {$status} successfully.");
    }

    /**
     * Handle ZIP upload, extract to plugins/{slug}/, validate plugin.json, register in database.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'plugin' => 'required|file|mimes:zip|max:51200',
        ]);

        $file = $request->file('plugin');
        $tempPath = $file->getRealPath();

        $zip = new ZipArchive();
        if ($zip->open($tempPath) !== true) {
            return redirect()->back()->with('error', 'Unable to open the ZIP file.');
        }

        // Check decompressed size to prevent ZIP bombs (100 MB limit)
        $maxExtractedSize = 100 * 1024 * 1024;
        $totalSize = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat) {
                $totalSize += $stat['size'];
            }
            if ($totalSize > $maxExtractedSize) {
                $zip->close();
                return redirect()->back()->with('error', 'Plugin archive is too large when extracted.');
            }
        }

        // Extract to a temporary directory first
        $tempDir = storage_path('app/tmp/plugin_' . uniqid());
        if (! $this->extractZipSafely($zip, $tempDir)) {
            $zip->close();
            File::deleteDirectory($tempDir);

            return redirect()->back()->with('error', 'Invalid plugin archive structure.');
        }
        $zip->close();

        // Look for plugin.json in extracted contents
        $pluginJsonPath = $this->findPluginJson($tempDir);

        if (! $pluginJsonPath) {
            File::deleteDirectory($tempDir);
            return redirect()->back()->with('error', 'Invalid plugin: plugin.json not found.');
        }

        $pluginMeta = json_decode(file_get_contents($pluginJsonPath), true);

        if (! $pluginMeta || empty($pluginMeta['name']) || empty($pluginMeta['slug']) || empty($pluginMeta['version'])) {
            File::deleteDirectory($tempDir);
            return redirect()->back()->with('error', 'Invalid plugin.json: name, slug, and version are required.');
        }

        $slug = $pluginMeta['slug'];

        // Validate slug contains only lowercase letters, numbers, and hyphens
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            File::deleteDirectory($tempDir);
            return redirect()->back()->with('error', 'Invalid plugin slug: must contain only lowercase letters, numbers, and hyphens.');
        }

        // Validate that extracted directory slug matches plugin.json slug
        $extractedDirName = basename(dirname($pluginJsonPath));
        if ($extractedDirName !== $slug && dirname($pluginJsonPath) !== $tempDir) {
            // The folder name doesn't match the slug in plugin.json
            // This is a warning but we proceed using the slug from plugin.json
        }

        $pluginDir = base_path("plugins/{$slug}");

        // Move extracted files to the plugins directory
        if (File::exists($pluginDir)) {
            File::deleteDirectory($pluginDir);
        }

        $sourceDir = dirname($pluginJsonPath);
        File::moveDirectory($sourceDir, $pluginDir);
        File::deleteDirectory($tempDir);

        // Run plugin migrations if they exist
        $migrationsPath = $pluginDir . '/migrations';
        if (File::isDirectory($migrationsPath)) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', [
                    '--path' => 'plugins/' . $slug . '/migrations',
                    '--force' => true,
                ]);
            } catch (\Throwable $e) {
                // Log but don't fail installation
                \Illuminate\Support\Facades\Log::warning("Plugin {$slug} migrations failed: " . $e->getMessage());
            }
        }

        // Publish plugin assets if they exist
        $assetsPath = $pluginDir . '/assets';
        if (File::isDirectory($assetsPath)) {
            $publicPath = public_path("plugins/{$slug}");
            if (!File::isDirectory($publicPath)) {
                File::makeDirectory($publicPath, 0755, true);
            }
            File::copyDirectory($assetsPath, $publicPath);
        }

        // Register in the plugins table
        Plugin::updateOrCreate(
            [
                'tenant_id' => auth()->user()->tenant_id,
                'slug' => $slug,
            ],
            [
                'name' => $pluginMeta['name'],
                'version' => $pluginMeta['version'],
                'author' => $pluginMeta['author'] ?? null,
                'description' => $pluginMeta['description'] ?? null,
                'is_active' => false,
                'installed_at' => now(),
            ]
        );

        $installedPlugin = Plugin::where('tenant_id', auth()->user()->tenant_id)->where('slug', $slug)->first();
        AuditLog::log('plugin.installed', $installedPlugin);

        return redirect()->route('plugins.index')->with('success', 'Plugin uploaded and installed successfully.');
    }

    /**
     * Remove plugin files and database record.
     */
    public function uninstall(Plugin $plugin)
    {
        abort_unless($plugin->tenant_id === auth()->user()->tenant_id, 403);

        AuditLog::log('plugin.uninstalled', $plugin);

        $pluginDir = base_path("plugins/{$plugin->slug}");

        // Rollback plugin migrations if they exist
        $migrationsPath = $pluginDir . '/migrations';
        if (File::isDirectory($migrationsPath)) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate:rollback', [
                    '--path' => 'plugins/' . $plugin->slug . '/migrations',
                    '--force' => true,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Plugin {$plugin->slug} migration rollback failed: " . $e->getMessage());
            }
        }

        // Remove published assets
        $publicPath = public_path("plugins/{$plugin->slug}");
        if (File::exists($publicPath)) {
            File::deleteDirectory($publicPath);
        }

        // Remove plugin directory
        if (File::exists($pluginDir)) {
            File::deleteDirectory($pluginDir);
        }

        $plugin->delete();

        // Clear caches
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
        } catch (\Throwable $e) {
            Log::warning('Cache clear failed during plugin uninstall', ['error' => $e->getMessage()]);
        }

        return redirect()->route('plugins.index')->with('success', 'Plugin uninstalled successfully.');
    }

    private function installZipFromPath(string $zipPath, int $tenantId): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'message' => 'Unable to open the downloaded ZIP file.'];
        }

        $tempDir = storage_path('app/tmp/plugin_' . uniqid());
        if (! $this->extractZipSafely($zip, $tempDir)) {
            $zip->close();
            File::deleteDirectory($tempDir);

            return ['success' => false, 'message' => 'Invalid plugin archive structure.'];
        }
        $zip->close();

        $pluginJsonPath = $this->findPluginJson($tempDir);

        if (! $pluginJsonPath) {
            File::deleteDirectory($tempDir);
            return ['success' => false, 'message' => 'Invalid plugin package: plugin.json not found.'];
        }

        $pluginMeta = json_decode(file_get_contents($pluginJsonPath), true);

        if (! $pluginMeta || empty($pluginMeta['name']) || empty($pluginMeta['slug']) || empty($pluginMeta['version'])) {
            File::deleteDirectory($tempDir);
            return ['success' => false, 'message' => 'Invalid plugin.json: name, slug, and version are required.'];
        }

        $slug = $pluginMeta['slug'];

        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            File::deleteDirectory($tempDir);
            return ['success' => false, 'message' => 'Invalid plugin slug: must contain only lowercase letters, numbers, and hyphens.'];
        }

        $pluginDir = base_path("plugins/{$slug}");
        if (File::exists($pluginDir)) {
            File::deleteDirectory($pluginDir);
        }

        $sourceDir = dirname($pluginJsonPath);
        File::moveDirectory($sourceDir, $pluginDir);
        File::deleteDirectory($tempDir);

        $installedPlugin = app(PluginManager::class)->installBundledPlugin($slug, $tenantId);

        return ['success' => true, 'plugin' => $installedPlugin];
    }

    /**
     * Recursively find plugin.json in extracted directory.
     */
    private function findPluginJson(string $directory): ?string
    {
        $path = $directory . '/plugin.json';
        if (file_exists($path)) {
            return $path;
        }

        // Check one level deep (common ZIP structure with wrapper directory)
        $dirs = File::directories($directory);
        foreach ($dirs as $dir) {
            $path = $dir . '/plugin.json';
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Extract a ZIP archive while rejecting absolute paths and traversal entries.
     */
    private function extractZipSafely(ZipArchive $zip, string $destination): bool
    {
        File::makeDirectory($destination, 0755, true, true);
        $destinationRoot = rtrim(str_replace('\\', '/', realpath($destination) ?: $destination), '/');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->statIndex($i);
            if (! $entry || empty($entry['name'])) {
                return false;
            }

            $entryName = str_replace('\\', '/', $entry['name']);
            $normalized = trim($entryName, '/');
            if ($normalized === '') {
                continue;
            }

            $segments = array_filter(explode('/', $normalized), fn ($segment) => $segment !== '.');
            if (str_starts_with($entryName, '/') || preg_match('/^[A-Za-z]:\//', $entryName) || in_array('..', $segments, true)) {
                Log::warning('Rejected plugin archive entry', ['entry' => $entryName]);
                return false;
            }

            $targetPath = $destinationRoot . '/' . implode('/', $segments);
            $targetDir = dirname($targetPath);

            if (! str_starts_with(str_replace('\\', '/', $targetPath), $destinationRoot . '/')) {
                Log::warning('Rejected plugin archive entry outside destination', ['entry' => $entryName]);
                return false;
            }

            if (str_ends_with($entryName, '/')) {
                File::makeDirectory($targetPath, 0755, true, true);
                continue;
            }

            File::makeDirectory($targetDir, 0755, true, true);
            $stream = $zip->getStream($entry['name']);
            if ($stream === false) {
                return false;
            }

            $contents = stream_get_contents($stream);
            fclose($stream);

            if ($contents === false) {
                return false;
            }

            file_put_contents($targetPath, $contents);
        }

        return true;
    }
}
