<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    protected $signature = 'backup:run {--keep=7 : Number of recent backups to keep}';
    protected $description = 'Create a database backup using PHP (no external tools required)';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup-' . date('Y-m-d-His') . '.sql';
        $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $this->info('Starting database backup...');

        try {
            $pdo = DB::connection()->getPdo();
            $dbName = DB::getDatabaseName();

            $handle = fopen($filepath, 'w');
            if (! $handle) {
                $this->error('Could not create backup file.');
                return Command::FAILURE;
            }

            // Header
            fwrite($handle, "-- InsulaCRM Database Backup\n");
            fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Database: {$dbName}\n");
            fwrite($handle, "-- Server: " . $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION) . "\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($handle, "SET AUTOCOMMIT=0;\n");
            fwrite($handle, "START TRANSACTION;\n\n");

            // Get all tables
            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
            $tableCount = count($tables);

            $this->info("Found {$tableCount} tables to backup.");
            $bar = $this->output->createProgressBar($tableCount);
            $bar->start();

            foreach ($tables as $table) {
                // Table structure
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                $createSql = $createStmt['Create Table'] ?? $createStmt['Create View'] ?? null;

                if (! $createSql) {
                    $bar->advance();
                    continue;
                }

                fwrite($handle, "-- Table: {$table}\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createSql . ";\n\n");

                // Table data (skip views)
                if (isset($createStmt['Create View'])) {
                    $bar->advance();
                    continue;
                }

                $rows = $pdo->query("SELECT * FROM `{$table}`");
                $columns = null;
                $batch = [];
                $batchSize = 100;

                while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                    if ($columns === null) {
                        $columns = '`' . implode('`, `', array_keys($row)) . '`';
                    }

                    $values = array_map(function ($value) use ($pdo) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return $pdo->quote($value);
                    }, array_values($row));

                    $batch[] = '(' . implode(', ', $values) . ')';

                    if (count($batch) >= $batchSize) {
                        fwrite($handle, "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }

                // Remaining rows
                if (! empty($batch)) {
                    fwrite($handle, "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n");
                }

                fwrite($handle, "\n");
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // Footer
            fwrite($handle, "COMMIT;\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            // Compress if gzip is available
            $gzFilepath = $filepath . '.gz';
            if (function_exists('gzopen')) {
                $gz = gzopen($gzFilepath, 'wb9');
                $fp = fopen($filepath, 'rb');
                while (! feof($fp)) {
                    gzwrite($gz, fread($fp, 524288));
                }
                fclose($fp);
                gzclose($gz);
                unlink($filepath);
                $filepath = $gzFilepath;
                $filename .= '.gz';
            }

            $size = $this->formatBytes(filesize($filepath));
            $this->info("Backup created: {$filename} ({$size})");

            // Prune old backups
            $keep = (int) $this->option('keep');
            if ($keep > 0) {
                $this->pruneBackups($backupDir, $keep);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }
            return Command::FAILURE;
        }
    }

    protected function pruneBackups(string $dir, int $keep): void
    {
        $files = array_merge(
            glob($dir . DIRECTORY_SEPARATOR . 'backup-*.sql.gz') ?: [],
            glob($dir . DIRECTORY_SEPARATOR . 'backup-*.sql') ?: []
        );

        if (empty($files)) {
            return;
        }

        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        $toDelete = array_slice($files, $keep);

        foreach ($toDelete as $file) {
            unlink($file);
            $this->line('Pruned old backup: ' . basename($file));
        }

        if (count($toDelete) > 0) {
            $this->info('Pruned ' . count($toDelete) . ' old backup(s).');
        }
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
