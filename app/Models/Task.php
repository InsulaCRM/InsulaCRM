<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'agent_id',
        'title',
        'due_date',
        'is_completed',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_completed' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Check if this task is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return !$this->is_completed && $this->due_date && $this->due_date->isPast();
    }

    /**
     * Get the lead this task belongs to.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the agent assigned to this task.
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
