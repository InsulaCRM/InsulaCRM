<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'deal_id',
        'agent_id',
        'type',
        'subject',
        'body',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the lead this activity belongs to.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the deal this activity belongs to.
     */
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Get the agent who logged this activity.
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
