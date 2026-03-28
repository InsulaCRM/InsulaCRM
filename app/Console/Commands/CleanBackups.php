<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanBackups extends Command
{
    protected $signature = 'backup:clean {--days=30 : Remove backups older than this many days}';
    protected $description = 'Remove old database backups';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            $this->info('No backups directory found. Nothing to clean.');
            return Command::SUCCESS;
        }

        $files = glob($backupDir . DIRECTORY_SEPARATOR . 'backup-*.sql*');
        if ($files === false || count($files) === 0) {
            $this->info('No backup files found.');
            return Command::SUCCESS;
        }

        $cutoff = now()->subDays($days)->timestamp;
        $deleted = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                $size = $this->formatBytes(filesize($file));
                $age = round((time() - filemtime($file)) / 86400);
                unlink($file);
                $this->line("Deleted: " . basename($file) . " ({$size}, {$age} days old)");
                $deleted++;
            }
        }

        if ($deleted === 0) {
            $this->info("No backups older than {$days} days found.");
        } else {
            $this->info("Cleaned up {$deleted} old backup(s).");
        }

        return Command::SUCCESS;
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
