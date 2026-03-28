<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class OpenHouse extends Model
{
    public const STATUSES = [
        'scheduled' => 'Scheduled',
        'active'    => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'tenant_id',
        'property_id',
        'agent_id',
        'event_date',
        'start_time',
        'end_time',
        'status',
        'description',
        'notes',
        'attendee_count',
    ];

    protected $casts = [
        'event_date' => 'date',
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

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function attendees()
    {
        return $this->hasMany(OpenHouseAttendee::class);
    }

    public static function statusLabel(string $status): string
    {
        return __(self::STATUSES[$status] ?? ucwords(str_replace('_', ' ', $status)));
    }
}
