<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'buyer_id',
        'deal_id',
        'property_address',
        'purchase_price',
        'close_date',
        'days_to_close',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'close_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
