<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class SavedMapping extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'column_mapping',
    ];

    protected function casts(): array
    {
        return [
            'column_mapping' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}
