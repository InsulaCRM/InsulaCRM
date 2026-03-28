<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'agent_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'lead_source',
        'campaign_id',
        'status',
        'contact_type',
        'temperature',
        'motivation_score',
        'ai_motivation_score',
        'do_not_contact',
        'timezone',
        'notes',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'do_not_contact' => 'boolean',
            'motivation_score' => 'integer',
            'ai_motivation_score' => 'integer',
            'custom_fields' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property()
    {
        return $this->hasOne(Property::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function photos()
    {
        return $this->hasMany(LeadPhoto::class)->latest();
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function lists()
    {
        return $this->belongsToMany(LeadList::class, 'list_leads', 'lead_id', 'list_id');
    }

    public function sequenceEnrollments()
    {
        return $this->hasMany(SequenceEnrollment::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function getListCountAttribute(): int
    {
        return $this->lists()->count();
    }

    /**
     * Check if lead is on the DNC list.
     */
    public function isOnDncList(): bool
    {
        if ($this->do_not_contact) {
            return true;
        }

        return DoNotContact::where('tenant_id', $this->tenant_id)
            ->where(function ($q) {
                if ($this->phone) {
                    $q->orWhere('phone', $this->phone);
                }
                if ($this->email) {
                    $q->orWhere('email', $this->email);
                }
            })->exists();
    }
}
