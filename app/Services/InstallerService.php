<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Buyer;
use App\Models\Lead;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\BaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InstallerService
{
    public const PLACEHOLDER_APP_KEY = 'base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=';

    public function ensureEnvFileExists(): void
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (! File::exists($envPath) && File::exists($examplePath)) {
            File::copy($examplePath, $envPath);
        }
    }

    public function updateEnvValues(array $values): void
    {
        $this->ensureEnvFileExists();

        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        foreach ($values as $key => $value) {
            $pattern = '/^#?\s*' . preg_quote($key, '/') . '=.*/m';
            $line = $key . '=' . $value;

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $line, $envContent);
            } else {
                $envContent .= PHP_EOL . $line;
            }
        }

        File::put($envPath, $envContent);
    }

    public function syncRuntimeDatabaseConfig(): void
    {
        $envValues = $this->loadEnvValues();

        config([
            'database.connections.mysql.host' => $envValues['DB_HOST'] ?? '127.0.0.1',
            'database.connections.mysql.port' => $envValues['DB_PORT'] ?? '3306',
            'database.connections.mysql.database' => $envValues['DB_DATABASE'] ?? 'insulacrm',
            'database.connections.mysql.username' => $envValues['DB_USERNAME'] ?? 'root',
            'database.connections.mysql.password' => $envValues['DB_PASSWORD'] ?? '',
        ]);

        DB::purge('mysql');
    }

    public function loadEnvValues(): array
    {
        $this->ensureEnvFileExists();

        $values = [];
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return $values;
        }

        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value, '"\'');
        }

        return $values;
    }

    public function shouldGenerateAppKey(): bool
    {
        $appKey = config('app.key');

        return empty($appKey)
            || $appKey === 'base64:'
            || $appKey === self::PLACEHOLDER_APP_KEY;
    }

    public function installApplication(array $payload): array
    {
        $this->ensureEnvFileExists();
        $this->syncRuntimeDatabaseConfig();

        if ($this->shouldGenerateAppKey()) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', [
            '--class' => BaseSeeder::class,
            '--force' => true,
        ]);

        $tenant = DB::transaction(function () use ($payload) {
            $slug = Str::slug($payload['company_name']) ?: 'tenant';
            $tenant = Tenant::withoutGlobalScopes()->firstOrNew([
                'slug' => $slug,
            ]);

            $tenant->fill([
                'name' => $payload['company_name'],
                'email' => $payload['admin_email'],
                'status' => 'active',
            ]);
            $tenant->business_mode = $payload['business_mode'] ?? 'wholesale';
            $tenant->save();

            $adminRole = Role::where('name', 'admin')->firstOrFail();
            $admin = User::withoutGlobalScopes()->firstOrNew([
                'email' => $payload['admin_email'],
            ]);

            $admin->fill([
                'tenant_id' => $tenant->id,
                'role_id' => $adminRole->id,
                'name' => $payload['admin_name'],
                'password' => Hash::make($payload['admin_password']),
                'onboarding_completed' => false,
            ]);
            $admin->save();

            return $tenant;
        });

        $demoDataLoaded = false;
        $demoDataWarning = null;
        if (! empty($payload['load_demo_data'])) {
            if (! class_exists(FakerFactory::class)) {
                $demoDataWarning = 'Demo data was skipped because the sample-data package is not available in this build.';
            } elseif ($this->tenantHasDemoData($tenant->id)) {
                $demoDataWarning = 'Partial demo data from an earlier install attempt was detected, so sample data was not seeded again.';
            } else {
                try {
                    app(DemoDataSeeder::class)->run($tenant->id);
                    $demoDataLoaded = true;
                } catch (\Throwable $e) {
                    $demoDataWarning = 'The base CRM was installed, but demo data could not be loaded. You can continue without it.';
                    Log::warning('Demo data seeding failed during installation.', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $storageLinkMissing = false;
        if (! file_exists(public_path('storage'))) {
            try {
                Artisan::call('storage:link');
            } catch (\Throwable $e) {
                $storageLinkMissing = true;
                Log::warning('Storage symlink could not be created during installation.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! file_exists(public_path('storage'))) {
            $storageLinkMissing = true;
            Log::warning('Storage symlink could not be created. File uploads may not display correctly. You can create it manually: php artisan storage:link');
        }

        File::put(storage_path('installed.lock'), 'Installed on ' . now()->toDateTimeString());
        Artisan::call('config:clear');

        return [
            'tenant' => $tenant,
            'demo_data_loaded' => $demoDataLoaded,
            'demo_data_warning' => $demoDataWarning,
            'storage_link_missing' => $storageLinkMissing,
        ];
    }

    private function tenantHasDemoData(int $tenantId): bool
    {
        return Lead::withoutGlobalScopes()->where('tenant_id', $tenantId)->exists()
            || Deal::withoutGlobalScopes()->where('tenant_id', $tenantId)->exists()
            || Buyer::withoutGlobalScopes()->where('tenant_id', $tenantId)->exists()
            || User::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('email', 'like', '%@demo.com')
                ->exists();
    }
}
