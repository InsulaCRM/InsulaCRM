<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'version',
        'author',
        'description',
        'is_active',
        'installed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'installed_at' => 'datetime',
        ];
    }
}
