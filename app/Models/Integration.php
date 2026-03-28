<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Integration extends Model
{
    protected $fillable = [
        'tenant_id',
        'category',
        'driver',
        'name',
        'config',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the decrypted config array.
     */
    public function getConfigAttribute($value): array
    {
        if (!$value) {
            return [];
        }

        try {
            return json_decode(Crypt::decryptString($value), true) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Set the config as an encrypted JSON string.
     */
    public function setConfigAttribute($value): void
    {
        $this->attributes['config'] = $value
            ? Crypt::encryptString(json_encode($value))
            : null;
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
