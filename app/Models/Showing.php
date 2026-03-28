<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Showing extends Model
{
    public const STATUSES = [
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show'   => 'No Show',
    ];

    public const OUTCOMES = [
        'interested'            => 'Interested',
        'not_interested'        => 'Not Interested',
        'made_offer'            => 'Made Offer',
        'needs_second_showing'  => 'Needs Second Showing',
    ];

    protected $fillable = [
        'tenant_id',
        'deal_id',
        'property_id',
        'lead_id',
        'agent_id',
        'showing_date',
        'showing_time',
        'duration_minutes',
        'status',
        'feedback',
        'outcome',
        'listing_agent_name',
        'listing_agent_phone',
        'notes',
    ];

    protected $casts = [
        'showing_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public static function statusLabel(string $status): string
    {
        return __(self::STATUSES[$status] ?? ucwords(str_replace('_', ' ', $status)));
    }

    public static function outcomeLabel(string $outcome): string
    {
        return __(self::OUTCOMES[$outcome] ?? ucwords(str_replace('_', ' ', $outcome)));
    }
}
