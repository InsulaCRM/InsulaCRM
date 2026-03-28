<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    public const METRICS = [
        'deals_closed',
        'revenue_earned',
        'leads_generated',
        'activities_logged',
        'calls_made',
        'offers_sent',
    ];

    public const METRIC_LABELS = [
        'deals_closed' => 'Deals Closed',
        'revenue_earned' => 'Revenue Earned',
        'leads_generated' => 'Leads Generated',
        'activities_logged' => 'Activities Logged',
        'calls_made' => 'Calls Made',
        'offers_sent' => 'Offers Sent',
    ];

    public const PERIODS = [
        'weekly',
        'monthly',
        'quarterly',
        'yearly',
    ];

    protected $fillable = [
        'tenant_id',
        'user_id',
        'metric',
        'target_value',
        'period',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get translated metric label.
     */
    public static function metricLabel(string $metric): string
    {
        return __(self::METRIC_LABELS[$metric] ?? ucwords(str_replace('_', ' ', $metric)));
    }

    /**
     * Get translated metric labels.
     */
    public static function metricLabels(): array
    {
        return array_map(fn($label) => __($label), self::METRIC_LABELS);
    }

    /**
     * Calculate the current value for this goal's metric within the date range.
     */
    public function getCurrentValue(): float
    {
        $start = $this->start_date;
        $end = $this->end_date;

        return match ($this->metric) {
            'deals_closed' => $this->countDealsClosedWon($start, $end),
            'revenue_earned' => $this->sumRevenue($start, $end),
            'leads_generated' => $this->countLeadsGenerated($start, $end),
            'activities_logged' => $this->countActivities($start, $end),
            'calls_made' => $this->countCalls($start, $end),
            'offers_sent' => $this->countOffersSent($start, $end),
            default => 0,
        };
    }

    /**
     * Get progress as a percentage (capped at 100).
     */
    public function getProgressPercentage(): float
    {
        if ($this->target_value <= 0) {
            return 0;
        }

        $percentage = ($this->getCurrentValue() / (float) $this->target_value) * 100;

        return min($percentage, 100);
    }

    /**
     * Determine pace status: 'ahead', 'on_track', or 'behind'.
     */
    public function getPaceStatus(): string
    {
        $totalDays = $this->start_date->diffInDays($this->end_date);
        if ($totalDays <= 0) {
            return 'on_track';
        }

        $daysElapsed = $this->start_date->diffInDays(now()->startOfDay());
        $daysElapsed = max(0, min($daysElapsed, $totalDays));

        $timeElapsedPct = ($daysElapsed / $totalDays) * 100;
        $progressPct = $this->getProgressPercentage();

        $difference = $progressPct - $timeElapsedPct;

        if ($difference >= 10) {
            return 'ahead';
        } elseif ($difference >= -10) {
            return 'on_track';
        } else {
            return 'behind';
        }
    }

    /**
     * Count deals with stage = closed_won within date range.
     */
    private function countDealsClosedWon($start, $end): float
    {
        $query = Deal::where('stage', 'closed_won')
            ->whereBetween('updated_at', [$start->startOfDay(), $end->endOfDay()]);

        if ($this->user_id) {
            $query->where('agent_id', $this->user_id);
        }

        return (float) $query->count();
    }

    /**
     * Sum assignment fees from closed_won deals within date range.
     */
    private function sumRevenue($start, $end): float
    {
        $query = Deal::where('stage', 'closed_won')
            ->whereBetween('updated_at', [$start->startOfDay(), $end->endOfDay()]);

        if ($this->user_id) {
            $query->where('agent_id', $this->user_id);
        }

        return (float) $query->sum(\App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column']);
    }

    /**
     * Count leads created within date range.
     */
    private function countLeadsGenerated($start, $end): float
    {
        $query = Lead::whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()]);

        if ($this->user_id) {
            $query->where('agent_id', $this->user_id);
        }

        return (float) $query->count();
    }

    /**
     * Count all activities within date range.
     */
    private function countActivities($start, $end): float
    {
        $query = Activity::whereBetween('logged_at', [$start->startOfDay(), $end->endOfDay()]);

        if ($this->user_id) {
            $query->where('agent_id', $this->user_id);
        }

        return (float) $query->count();
    }

    /**
     * Count activities of type 'call' within date range.
     */
    private function countCalls($start, $end): float
    {
        $query = Activity::where('type', 'call')
            ->whereBetween('logged_at', [$start->startOfDay(), $end->endOfDay()]);

        if ($this->user_id) {
            $query->where('agent_id', $this->user_id);
        }

        return (float) $query->count();
    }

    /**
     * Count deals past offer_presented stage within date range.
     */
    private function countOffersSent($start, $end): float
    {
        $isRealEstate = \App\Services\BusinessModeService::isRealEstate();
        $offerStage = $isRealEstate ? 'offer_received' : 'offer_presented';
        $postOfferStages = $isRealEstate
            ? ['under_contract', 'inspection', 'appraisal', 'closing', 'closed_won', 'closed_lost']
            : ['under_contract', 'dispositions', 'assigned', 'closing', 'closed_won', 'closed_lost'];

        $query = Deal::whereIn('stage', array_merge([$offerStage], $postOfferStages))
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()]);

        if ($this->user_id) {
            $query->where('agent_id', $this->user_id);
        }

        return (float) $query->count();
    }
}
