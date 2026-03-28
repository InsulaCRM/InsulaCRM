<?php

namespace App\Services\Settings;

use Illuminate\Support\Facades\Artisan;

class BackupService
{
    public function list(): array
    {
        $path = storage_path('app/backups');
        $backups = [];

        if (! is_dir($path)) {
            return [];
        }

        $files = glob($path . '/*.{sql,sql.gz}', GLOB_BRACE);
        if (! $files) {
            return [];
        }

        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        foreach ($files as $file) {
            $size = filesize($file);
            $backups[] = [
                'name' => basename($file),
                'size' => $size > 1048576 ? round($size / 1048576, 1) . ' MB' : round($size / 1024, 1) . ' KB',
                'date' => date('Y-m-d H:i', filemtime($file)),
            ];
        }

        return $backups;
    }

    public function create(int $keep = 10): bool
    {
        return Artisan::call('backup:run', ['--keep' => $keep]) === 0;
    }

    public function path(string $filename): ?string
    {
        $filepath = storage_path('app/backups/' . basename($filename));

        return file_exists($filepath) ? $filepath : null;
    }

    public function delete(string $filename): bool
    {
        $filepath = $this->path($filename);
        if (! $filepath) {
            return false;
        }

        unlink($filepath);

        return true;
    }
}
