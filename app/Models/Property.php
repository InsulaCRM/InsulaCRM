<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Services\AddressNormalizationService;
use App\Services\WholesaleOfferStrategyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'address',
        'city',
        'state',
        'zip_code',
        'property_type',
        'bedrooms',
        'bathrooms',
        'square_footage',
        'year_built',
        'lot_size',
        'estimated_value',
        'repair_estimate',
        'after_repair_value',
        'asking_price',
        'our_offer',
        'maximum_allowable_offer',
        'mao_strategy',
        'buyer_max_all_in_percent',
        'buyer_profit_target',
        'transaction_costs',
        'holding_costs',
        'financing_costs',
        'risk_buffer',
        'assignment_fee_target',
        'condition',
        'distress_markers',
        'list_price',
        'listing_status',
        'listed_at',
        'sold_at',
        'sold_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'repair_estimate' => 'decimal:2',
            'after_repair_value' => 'decimal:2',
            'asking_price' => 'decimal:2',
            'our_offer' => 'decimal:2',
            'maximum_allowable_offer' => 'decimal:2',
            'buyer_max_all_in_percent' => 'decimal:4',
            'buyer_profit_target' => 'decimal:2',
            'transaction_costs' => 'decimal:2',
            'holding_costs' => 'decimal:2',
            'financing_costs' => 'decimal:2',
            'risk_buffer' => 'decimal:2',
            'assignment_fee_target' => 'decimal:2',
            'distress_markers' => 'array',
            'list_price' => 'decimal:2',
            'sold_price' => 'decimal:2',
            'listed_at' => 'date',
            'sold_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::saving(function (Property $property) {
            if ($property->isDirty('address') && $property->address) {
                $property->address = AddressNormalizationService::normalize($property->address);
            }
            if ($property->isDirty('city') && $property->city) {
                $property->city = AddressNormalizationService::normalizeCity($property->city);
            }
            if ($property->isDirty('state') && $property->state) {
                $property->state = AddressNormalizationService::normalizeState($property->state);
            }
            if ($property->isDirty('zip_code') && $property->zip_code) {
                $property->zip_code = AddressNormalizationService::normalizeZipCode($property->zip_code);
            }
        });
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function comparableSales()
    {
        return $this->hasMany(ComparableSale::class)->latest('sale_date');
    }

    public function showings()
    {
        return $this->hasMany(Showing::class);
    }

    public function openHouses()
    {
        return $this->hasMany(OpenHouse::class);
    }

    public function getFullAddressAttribute(): string
    {
        return "{$this->address}, {$this->city}, {$this->state} {$this->zip_code}";
    }

    public function getAssignmentFeeAttribute(): ?float
    {
        if (!$this->our_offer || !$this->after_repair_value) {
            return null;
        }

        $analysis = $this->mao_analysis;

        if (!$analysis || !isset($analysis['buyer_purchase_ceiling'])) {
            return null;
        }

        return round(max(0, $analysis['buyer_purchase_ceiling'] - (float) $this->our_offer), 2);
    }

    public function getMaoAttribute(): ?float
    {
        if (!$this->after_repair_value) {
            return null;
        }

        $result = app(WholesaleOfferStrategyService::class)->calculate([
            'after_repair_value' => $this->after_repair_value,
            'repair_estimate' => $this->repair_estimate ?? 0,
            'assignment_fee' => $this->assignment_fee_target ?? 0,
            'buyer_max_all_in_percent' => $this->buyer_max_all_in_percent,
            'buyer_profit_target' => $this->buyer_profit_target,
            'transaction_costs' => $this->transaction_costs,
            'holding_costs' => $this->holding_costs,
            'financing_costs' => $this->financing_costs,
            'risk_buffer' => $this->risk_buffer,
            'strategy' => $this->mao_strategy ?? 'flip',
            'condition' => $this->condition ?? 'unknown',
        ]);

        return $result['seller_contract_ceiling'] ?? null;
    }

    public function getMaoAnalysisAttribute(): ?array
    {
        if (!$this->after_repair_value) {
            return null;
        }

        return app(WholesaleOfferStrategyService::class)->calculate([
            'after_repair_value' => $this->after_repair_value,
            'repair_estimate' => $this->repair_estimate ?? 0,
            'assignment_fee' => $this->assignment_fee_target ?? 0,
            'buyer_max_all_in_percent' => $this->buyer_max_all_in_percent,
            'buyer_profit_target' => $this->buyer_profit_target,
            'transaction_costs' => $this->transaction_costs,
            'holding_costs' => $this->holding_costs,
            'financing_costs' => $this->financing_costs,
            'risk_buffer' => $this->risk_buffer,
            'strategy' => $this->mao_strategy ?? 'flip',
            'condition' => $this->condition ?? 'unknown',
        ]);
    }
}
