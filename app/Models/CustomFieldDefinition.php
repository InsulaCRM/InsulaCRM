<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class CustomFieldDefinition extends Model
{
    protected $fillable = [
        'tenant_id',
        'entity_type',
        'name',
        'slug',
        'field_type',
        'options',
        'required',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'required' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function forEntity(string $entityType = 'lead'): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('entity_type', $entityType)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
