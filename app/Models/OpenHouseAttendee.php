<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class OpenHouseAttendee extends Model
{
    protected $fillable = [
        'tenant_id',
        'open_house_id',
        'lead_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'notes',
        'interested',
    ];

    protected $casts = [
        'interested' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function openHouse()
    {
        return $this->belongsTo(OpenHouse::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
