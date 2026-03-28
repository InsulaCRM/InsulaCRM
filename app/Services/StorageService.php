<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorageService
{
    /**
     * Get the storage disk for the current tenant.
     */
    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $diskName = $this->getDiskName();

        // If S3, configure the disk dynamically from tenant settings
        if ($diskName === 's3') {
            $this->configureTenantS3();
        }

        return Storage::disk($diskName);
    }

    /**
     * Store a file using the tenant's configured storage disk.
     */
    public function store(UploadedFile $file, string $directory, string $visibility = 'private'): string
    {
        $disk = $this->getDiskName();

        if ($disk === 's3') {
            $this->configureTenantS3();
        }

        return $file->store($directory, [
            'disk' => $disk,
            'visibility' => $visibility,
        ]);
    }

    /**
     * Get file URL from the tenant's storage disk.
     */
    public function url(string $path): string
    {
        return $this->disk()->url($path);
    }

    /**
     * Delete a file from the tenant's storage disk.
     */
    public function delete(string $path): bool
    {
        return $this->disk()->delete($path);
    }

    protected function getDiskName(): string
    {
        $user = auth()->user();

        if ($user && $user->tenant) {
            $disk = $user->tenant->storage_disk ?? 'local';

            if (in_array($disk, ['local', 'public', 's3'])) {
                return $disk;
            }
        }

        return config('filesystems.default', 'local');
    }

    /**
     * Configure the S3 disk at runtime using tenant-stored credentials.
     */
    protected function configureTenantS3(): void
    {
        $user = auth()->user();
        if (!$user || !$user->tenant) {
            return;
        }

        $options = $user->tenant->custom_options ?? [];

        if (!empty($options['s3_key']) && !empty($options['s3_bucket'])) {
            $secret = '';
            if (!empty($options['s3_secret'])) {
                try {
                    $secret = decrypt($options['s3_secret']);
                } catch (\Throwable $e) {
                    $secret = $options['s3_secret'];
                }
            }

            config([
                'filesystems.disks.s3.key' => $options['s3_key'],
                'filesystems.disks.s3.secret' => $secret,
                'filesystems.disks.s3.region' => $options['s3_region'] ?? 'us-east-1',
                'filesystems.disks.s3.bucket' => $options['s3_bucket'],
                'filesystems.disks.s3.url' => $options['s3_url'] ?? null,
            ]);
        }
    }
}
