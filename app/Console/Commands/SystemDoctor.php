<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SystemDoctor extends Command
{
    protected $signature = 'system:doctor {--json : Output the report as JSON} {--strict : Return a non-zero exit code when any critical check fails}';

    protected $description = 'Run installation and runtime health checks for this InsulaCRM instance';

    public function handle(): int
    {
        $checks = $this->checks();

        if ($this->option('json')) {
            $this->line(json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $rows = array_map(function (array $check) {
                return [
                    $check['label'],
                    $check['status'] ? 'OK' : 'FAIL',
                    $check['severity'],
                    $check['detail'],
                ];
            }, $checks);

            $this->table(['Check', 'Status', 'Severity', 'Detail'], $rows);
        }

        $criticalFailure = collect($checks)->contains(fn (array $check) => ! $check['status'] && $check['severity'] === 'critical');
        $warningFailure = collect($checks)->contains(fn (array $check) => ! $check['status'] && $check['severity'] !== 'critical');

        if ($criticalFailure) {
            $this->error('Critical issues were detected.');
        } elseif ($warningFailure) {
            $this->warn('Warnings were detected.');
        } else {
            $this->info('System health checks passed.');
        }

        return ($this->option('strict') && $criticalFailure) ? self::FAILURE : self::SUCCESS;
    }

    private function checks(): array
    {
        $checks = [
            $this->check('Version', true, 'info', config('app.version', '1.0.0')),
            $this->check('.env present', File::exists(base_path('.env')), 'critical', base_path('.env')),
            $this->check('APP_KEY configured', $this->appKeyConfigured(), 'critical', $this->appKeyConfigured() ? 'Application key is configured.' : 'APP_KEY is missing or still a placeholder.'),
            $this->check('storage/logs writable', is_writable(storage_path('logs')), 'critical', storage_path('logs')),
            $this->check('storage/framework writable', is_writable(storage_path('framework')), 'critical', storage_path('framework')),
            $this->check('bootstrap/cache writable', is_writable(base_path('bootstrap/cache')), 'critical', base_path('bootstrap/cache')),
            $this->check('plugins writable', is_writable(base_path('plugins')) || (! file_exists(base_path('plugins')) && is_writable(base_path())), 'warning', base_path('plugins')),
            $this->check('public/storage link', file_exists(public_path('storage')), 'warning', public_path('storage')),
            $this->check('installed.lock', file_exists(storage_path('installed.lock')), 'warning', storage_path('installed.lock')),
        ];

        foreach (['bcmath', 'curl', 'fileinfo', 'gd', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'tokenizer', 'xml'] as $extension) {
            $checks[] = $this->check(
                "PHP extension: {$extension}",
                extension_loaded($extension),
                in_array($extension, ['pdo', 'pdo_mysql'], true) ? 'critical' : 'warning',
                extension_loaded($extension) ? 'Loaded' : 'Missing'
            );
        }

        try {
            DB::connection()->getPdo();
            $checks[] = $this->check('Database connection', true, 'critical', config('database.connections.mysql.database'));
            $checks[] = $this->check('tenants table present', Schema::hasTable('tenants'), 'critical', Schema::hasTable('tenants') ? 'Found' : 'Missing');
            $checks[] = $this->check('users table present', Schema::hasTable('users'), 'critical', Schema::hasTable('users') ? 'Found' : 'Missing');
        } catch (\Throwable $e) {
            $checks[] = $this->check('Database connection', false, 'critical', $e->getMessage());
        }

        return $checks;
    }

    private function appKeyConfigured(): bool
    {
        $appKey = (string) config('app.key');

        return $appKey !== ''
            && $appKey !== 'base64:'
            && $appKey !== \App\Services\InstallerService::PLACEHOLDER_APP_KEY;
    }

    private function check(string $label, bool $status, string $severity, string $detail): array
    {
        return [
            'label' => $label,
            'status' => $status,
            'severity' => $severity,
            'detail' => $detail,
        ];
    }
}
