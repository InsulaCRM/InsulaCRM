<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;

    /**
     * Wholesale stages constant — kept for backward compatibility.
     */
    public const STAGES = [
        'prospecting' => 'Prospecting',
        'contacting' => 'Contacting',
        'engaging' => 'Engaging',
        'offer_presented' => 'Offer Presented',
        'under_contract' => 'Under Contract',
        'dispositions' => 'Dispositions',
        'assigned' => 'Assigned',
        'closing' => 'Closing',
        'closed_won' => 'Closed Won',
        'closed_lost' => 'Closed Lost',
    ];

    /**
     * Get pipeline stages for the current tenant's business mode.
     */
    public static function stages(?Tenant $tenant = null): array
    {
        return \App\Services\BusinessModeService::getStages($tenant);
    }

    /**
     * Get translated stage labels for the current tenant's business mode.
     */
    public static function stageLabels(?Tenant $tenant = null): array
    {
        return \App\Services\BusinessModeService::getStageLabels($tenant);
    }

    /**
     * Get translated label for a single stage.
     */
    public static function stageLabel(string $stage, ?Tenant $tenant = null): string
    {
        return \App\Services\BusinessModeService::getStageLabel($stage, $tenant);
    }

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'agent_id',
        'title',
        'stage',
        'stage_changed_at',
        'contract_price',
        'assignment_fee',
        'earnest_money',
        'inspection_period_days',
        'contract_date',
        'due_diligence_end_date',
        'closing_date',
        'listing_commission_pct',
        'buyer_commission_pct',
        'total_commission',
        'brokerage_split_pct',
        'mls_number',
        'listing_date',
        'days_on_market',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'contract_price' => 'decimal:2',
            'assignment_fee' => 'decimal:2',
            'earnest_money' => 'decimal:2',
            'contract_date' => 'date',
            'due_diligence_end_date' => 'date',
            'closing_date' => 'date',
            'listing_commission_pct' => 'decimal:2',
            'buyer_commission_pct' => 'decimal:2',
            'total_commission' => 'decimal:2',
            'brokerage_split_pct' => 'decimal:2',
            'listing_date' => 'date',
            'stage_changed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Deal $deal) {
            if (!$deal->stage_changed_at) {
                $deal->stage_changed_at = now();
            }
        });
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function documents()
    {
        return $this->hasMany(DealDocument::class);
    }

    public function generatedDocuments()
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function buyerMatches()
    {
        return $this->hasMany(DealBuyerMatch::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function checklistItems()
    {
        return $this->hasMany(TransactionChecklist::class)->orderBy('sort_order');
    }

    public function offers()
    {
        return $this->hasMany(DealOffer::class);
    }

    public function getDueDiligenceDaysRemainingAttribute(): ?int
    {
        if ($this->due_diligence_end_date) {
            return (int) now()->startOfDay()->diffInDays($this->due_diligence_end_date, false);
        }
        return null;
    }

    public function getIsDueDiligenceUrgentAttribute(): bool
    {
        $days = $this->due_diligence_days_remaining;
        return $days !== null && $days <= 2 && $days >= 0;
    }
}
