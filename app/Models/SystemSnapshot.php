<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class SystemSnapshot extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'label',
        'version',
        'backup_filename',
        'snapshot_archive_path',
        'snapshot_manifest_path',
        'env_snapshot_path',
        'status',
        'summary',
        'error_message',
        'restored_at',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected function casts(): array
    {
        return [
            'restored_at' => 'datetime',
        ];
    }
}
