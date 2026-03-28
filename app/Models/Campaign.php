<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    public const TYPES = [
        'direct_mail' => 'Direct Mail',
        'ppc' => 'PPC',
        'cold_call' => 'Cold Call',
        'bandit_sign' => 'Bandit Sign',
        'seo' => 'SEO',
        'social' => 'Social Media',
        'email' => 'Email',
        'ringless_voicemail' => 'Ringless Voicemail',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'active' => 'Active',
        'paused' => 'Paused',
        'completed' => 'Completed',
    ];

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'type',
        'status',
        'budget',
        'actual_spend',
        'target_count',
        'start_date',
        'end_date',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'actual_spend' => 'decimal:2',
            'metadata' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get translated type labels.
     */
    public static function typeLabels(): array
    {
        return array_map(fn($label) => __($label), self::TYPES);
    }

    /**
     * Get translated label for a single type.
     */
    public static function typeLabel(string $type): string
    {
        return __(self::TYPES[$type] ?? ucwords(str_replace('_', ' ', $type)));
    }

    /**
     * Get translated status labels.
     */
    public static function statusLabels(): array
    {
        return array_map(fn($label) => __($label), self::STATUSES);
    }

    /**
     * Get translated label for a single status.
     */
    public static function statusLabel(string $status): string
    {
        return __(self::STATUSES[$status] ?? ucwords(str_replace('_', ' ', $status)));
    }

    // ── Relationships ───────────────────────────────────────

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Accessors ───────────────────────────────────────────

    public function getLeadCountAttribute(): int
    {
        return $this->leads()->count();
    }

    public function getDealCountAttribute(): int
    {
        return Deal::whereIn('lead_id', $this->leads()->pluck('id'))->count();
    }

    public function getClosedDealCountAttribute(): int
    {
        return Deal::whereIn('lead_id', $this->leads()->pluck('id'))
            ->where('stage', 'closed_won')
            ->count();
    }

    public function getRevenueAttribute(): float
    {
        return (float) Deal::whereIn('lead_id', $this->leads()->pluck('id'))
            ->where('stage', 'closed_won')
            ->sum('assignment_fee');
    }

    public function getRoiAttribute(): ?float
    {
        $spend = (float) $this->actual_spend;

        if ($spend <= 0) {
            return null;
        }

        return round(($this->revenue - $spend) / $spend * 100, 2);
    }

    public function getCostPerLeadAttribute(): ?float
    {
        $leadCount = $this->lead_count;

        if ($leadCount <= 0) {
            return null;
        }

        return round((float) $this->actual_spend / $leadCount, 2);
    }
}
