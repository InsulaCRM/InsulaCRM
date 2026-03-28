<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class LeadSourceCost extends Model
{
    protected $fillable = [
        'tenant_id',
        'lead_source',
        'monthly_budget',
    ];

    protected function casts(): array
    {
        return [
            'monthly_budget' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}
