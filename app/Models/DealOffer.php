<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class DealOffer extends Model
{
    protected $table = 'deal_offers';

    public const STATUSES = [
        'pending' => 'Pending',
        'countered' => 'Countered',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
        'expired' => 'Expired',
    ];

    public const FINANCING_TYPES = [
        'cash' => 'Cash',
        'conventional' => 'Conventional',
        'fha' => 'FHA',
        'va' => 'VA',
        'other' => 'Other',
    ];

    protected $fillable = [
        'tenant_id',
        'deal_id',
        'buyer_name',
        'buyer_agent_name',
        'buyer_agent_phone',
        'buyer_agent_email',
        'offer_price',
        'earnest_money',
        'financing_type',
        'contingencies',
        'expiration_date',
        'status',
        'counter_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'offer_price' => 'decimal:2',
            'earnest_money' => 'decimal:2',
            'counter_price' => 'decimal:2',
            'contingencies' => 'array',
            'expiration_date' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expiration_date) {
            return false;
        }

        return $this->expiration_date->isPast() && $this->status === 'pending';
    }
}
