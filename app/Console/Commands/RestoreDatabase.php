<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RestoreDatabase extends Command
{
    protected $signature = 'backup:restore
        {filename : Backup filename relative to storage/app/backups/}
        {--force : Skip confirmation prompt}';
    protected $description = 'Restore the database from a backup file';

    public function handle(): int
    {
        $filename = $this->argument('filename');
        $filepath = storage_path('app/backups/' . $filename);

        if (! file_exists($filepath)) {
            $this->error("Backup file not found: {$filepath}");
            return Command::FAILURE;
        }

        $db = config('database.connections.mysql');
        $database = $db['database'] ?? 'forge';

        if (! $this->option('force')) {
            $this->warn("This will overwrite all data in the '{$database}' database.");
            if (! $this->confirm('Are you sure you want to continue?')) {
                $this->info('Restore cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info("Restoring from {$filename}...");

        $tempSqlPath = null;

        if (str_ends_with($filename, '.sql.gz')) {
            $tempSqlPath = $this->decompressGzipBackup($filepath);
            $importPath = $tempSqlPath;
        } elseif (str_ends_with($filename, '.sql')) {
            $importPath = $filepath;
        } else {
            $this->error('Unsupported file format. Use .sql or .sql.gz files.');
            return Command::FAILURE;
        }

        try {
            $process = new Process([
                $this->mysqlBinary(),
                '--host=' . ($db['host'] ?? '127.0.0.1'),
                '--port=' . ($db['port'] ?? '3306'),
                '--user=' . ($db['username'] ?? 'root'),
                '--password=' . ($db['password'] ?? ''),
                $database,
            ]);

            $process->setTimeout(600);
            $stream = fopen($importPath, 'rb');
            if ($stream === false) {
                $this->error('Restore failed.');
                $this->error('The SQL backup file could not be opened for reading.');
                return Command::FAILURE;
            }

            $process->setInput($stream);
            $process->run();

            fclose($stream);

            if (! $process->isSuccessful()) {
                $this->error('Restore failed.');
                if ($process->getErrorOutput()) {
                    $this->error($process->getErrorOutput());
                }
                return Command::FAILURE;
            }
        } finally {
            if ($tempSqlPath && file_exists($tempSqlPath)) {
                @unlink($tempSqlPath);
            }
        }

        $this->info("Database restored successfully from {$filename}.");
        return Command::SUCCESS;
    }

    private function decompressGzipBackup(string $filepath): string
    {
        if (! function_exists('gzopen')) {
            throw new \RuntimeException('This PHP environment does not support gzip decompression. Enable zlib before restoring .sql.gz backups.');
        }

        $tempPath = storage_path('app/tmp/restore-' . uniqid() . '.sql');
        File::ensureDirectoryExists(dirname($tempPath));

        $source = gzopen($filepath, 'rb');
        if ($source === false) {
            throw new \RuntimeException('The gzip backup could not be opened for reading.');
        }

        $target = fopen($tempPath, 'wb');
        if ($target === false) {
            gzclose($source);
            throw new \RuntimeException('A temporary SQL file could not be created for restore.');
        }

        while (! gzeof($source)) {
            $chunk = gzread($source, 1024 * 1024);
            if ($chunk === false) {
                fclose($target);
                gzclose($source);
                @unlink($tempPath);
                throw new \RuntimeException('The gzip backup could not be decompressed.');
            }

            fwrite($target, $chunk);
        }

        fclose($target);
        gzclose($source);

        return $tempPath;
    }

    private function mysqlBinary(): string
    {
        $configured = env('MYSQL_CLIENT_BINARY');
        if (is_string($configured) && $configured !== '' && file_exists($configured)) {
            return $configured;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = [
                base_path('..\\..\\mysql\\bin\\mysql.exe'),
                base_path('..\\mysql\\bin\\mysql.exe'),
                'C:\\xampp\\mysql\\bin\\mysql.exe',
                'C:\\laragon\\bin\\mysql\\mysql-8.0\\bin\\mysql.exe',
                'C:\\laragon\\bin\\mysql\\mysql-8.4\\bin\\mysql.exe',
                'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
                'C:\\Program Files\\MariaDB 10.4\\bin\\mysql.exe',
                'C:\\Program Files\\MariaDB 10.5\\bin\\mysql.exe',
                'C:\\Program Files\\MariaDB 10.6\\bin\\mysql.exe',
                'C:\\Program Files\\MariaDB 10.11\\bin\\mysql.exe',
            ];

            foreach ($candidates as $candidate) {
                if (file_exists($candidate)) {
                    return $candidate;
                }
            }

            return 'mysql.exe';
        }

        return 'mysql';
    }
}
