<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'company',
        'phone',
        'email',
        'max_purchase_price',
        'preferred_property_types',
        'preferred_zip_codes',
        'preferred_states',
        'asset_classes',
        'total_deals_closed',
        'notes',
        'pof_verified',
        'pof_document_path',
        'pof_amount',
        'pof_verified_at',
        'buyer_score',
        'total_purchases',
        'avg_close_days',
        'last_purchase_at',
    ];

    protected function casts(): array
    {
        return [
            'max_purchase_price' => 'decimal:2',
            'preferred_property_types' => 'array',
            'preferred_zip_codes' => 'array',
            'preferred_states' => 'array',
            'asset_classes' => 'array',
            'pof_verified' => 'boolean',
            'pof_amount' => 'decimal:2',
            'pof_verified_at' => 'datetime',
            'buyer_score' => 'integer',
            'total_purchases' => 'integer',
            'avg_close_days' => 'decimal:1',
            'last_purchase_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function dealMatches()
    {
        return $this->hasMany(DealBuyerMatch::class);
    }

    public function transactions()
    {
        return $this->hasMany(BuyerTransaction::class)->latest('close_date');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
