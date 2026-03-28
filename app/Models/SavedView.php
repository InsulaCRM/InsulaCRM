<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class SavedView extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'entity_type',
        'name',
        'filters',
        'is_shared',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_shared' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public const ENTITY_TYPES = ['leads', 'deals', 'buyers', 'properties', 'showings'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForEntity($query, string $type)
    {
        return $query->where('entity_type', $type);
    }

    public function scopeAccessibleBy($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('is_shared', true);
        });
    }
}
