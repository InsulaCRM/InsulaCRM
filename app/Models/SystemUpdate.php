<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class SystemUpdate extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'version_from',
        'version_to',
        'package_name',
        'package_sha256',
        'stage_path',
        'env_snapshot_path',
        'backup_filename',
        'snapshot_archive_path',
        'snapshot_manifest_path',
        'snapshot_created_at',
        'restored_at',
        'restore_summary',
        'restore_error_message',
        'status',
        'warnings',
        'summary',
        'error_message',
        'applied_at',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected function casts(): array
    {
        return [
            'warnings' => 'array',
            'applied_at' => 'datetime',
            'snapshot_created_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }
}
