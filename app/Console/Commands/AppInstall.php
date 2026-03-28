<?php

namespace App\Console\Commands;

use App\Services\InstallerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppInstall extends Command
{
    protected $signature = 'app:install
        {--app-name= : Application name to store in .env}
        {--app-url= : Public application URL to store in .env}
        {--company-name= : Company name for the first tenant}
        {--admin-name= : Administrator full name}
        {--admin-email= : Administrator email address}
        {--admin-password= : Administrator password}
        {--db-host= : Database host}
        {--db-port= : Database port}
        {--db-database= : Database name}
        {--db-username= : Database username}
        {--db-password= : Database password}
        {--business-mode=wholesale : Business mode (wholesale or realestate)}
        {--load-demo-data : Seed optional demo data after install}
        {--force : Continue even if the app already appears installed}';

    protected $description = 'Install InsulaCRM from the command line using the current or supplied environment settings';

    public function handle(InstallerService $installer): int
    {
        if (! $this->option('force') && $this->isAlreadyInstalled()) {
            $this->error('InsulaCRM already appears to be installed. Use --force only if you intend to rerun the installer logic.');

            return self::FAILURE;
        }

        $payload = [
            'app_name' => $this->option('app-name') ?: $this->ask('Application name', 'InsulaCRM'),
            'app_url' => $this->option('app-url') ?: $this->ask('Application URL', config('app.url')),
            'company_name' => $this->option('company-name') ?: $this->ask('Company name'),
            'admin_name' => $this->option('admin-name') ?: $this->ask('Administrator name'),
            'admin_email' => $this->option('admin-email') ?: $this->ask('Administrator email'),
            'admin_password' => $this->option('admin-password') ?: $this->secret('Administrator password'),
            'business_mode' => $this->option('business-mode') ?: 'wholesale',
            'load_demo_data' => (bool) $this->option('load-demo-data'),
        ];

        if (! in_array($payload['business_mode'], ['wholesale', 'realestate'])) {
            $this->error('Business mode must be "wholesale" or "realestate".');

            return self::FAILURE;
        }

        foreach (['db-host', 'db-port', 'db-database', 'db-username', 'db-password'] as $option) {
            $value = $this->option($option);
            if ($value !== null) {
                $payload[str_replace('db-', 'db_', $option)] = $value;
            }
        }

        if (! filter_var($payload['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $this->error('Administrator email must be a valid email address.');

            return self::FAILURE;
        }

        if (Str::length((string) $payload['admin_password']) < 8) {
            $this->error('Administrator password must be at least 8 characters.');

            return self::FAILURE;
        }

        if (! empty($payload['db_database'])) {
            $installer->updateEnvValues([
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $this->quoteEnvValue((string) ($payload['db_host'] ?? '127.0.0.1')),
                'DB_PORT' => $this->quoteEnvValue((string) ($payload['db_port'] ?? '3306')),
                'DB_DATABASE' => $this->quoteEnvValue((string) $payload['db_database']),
                'DB_USERNAME' => $this->quoteEnvValue((string) ($payload['db_username'] ?? 'root')),
                'DB_PASSWORD' => $this->quoteEnvValue((string) ($payload['db_password'] ?? '')),
            ]);
        }

        $installer->updateEnvValues([
            'APP_NAME' => $this->quoteEnvValue((string) $payload['app_name']),
            'APP_URL' => (string) $payload['app_url'],
        ]);

        $this->info('Running migrations, seeding base data, and creating the first administrator...');

        try {
            $result = $installer->installApplication($payload);
        } catch (\Throwable $e) {
            $this->error('Installation failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $tenant = $result['tenant'];
        $this->newLine();
        $this->info('InsulaCRM installation completed.');
        $this->table(
            ['Item', 'Value'],
            [
                ['Version', config('app.version', '1.0.0')],
                ['Tenant', $tenant->name],
                ['Tenant slug', $tenant->slug],
                ['Admin email', $payload['admin_email']],
                ['Database', config('database.connections.mysql.database')],
                ['Installed marker', storage_path('installed.lock')],
            ]
        );

        if ($result['demo_data_loaded']) {
            $this->info('Demo data was loaded successfully.');
        } elseif ($result['demo_data_warning']) {
            $this->warn($result['demo_data_warning']);
        }

        if ($result['storage_link_missing']) {
            $this->warn('public/storage could not be created automatically. Run: php artisan storage:link');
        }

        return self::SUCCESS;
    }

    private function isAlreadyInstalled(): bool
    {
        if (file_exists(storage_path('installed.lock'))) {
            return true;
        }

        try {
            return DB::table('tenants')->exists() && DB::table('users')->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private function quoteEnvValue(string $value): string
    {
        return '"' . addcslashes($value, '"\\') . '"';
    }
}
