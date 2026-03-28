<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'is_active',
        'last_run_at',
        'run_count',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'run_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get all steps for this workflow, ordered by position.
     */
    public function steps()
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('position');
    }

    /**
     * Get all run logs for this workflow.
     */
    public function runLogs()
    {
        return $this->hasMany(WorkflowRunLog::class);
    }

    /**
     * Scope: only active workflows.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the first step (position = 1).
     */
    public function firstStep()
    {
        return $this->steps()->orderBy('position')->first();
    }

    /**
     * Available trigger types with labels.
     */
    public static function triggerTypes(): array
    {
        return [
            'lead.created' => __('Lead Created'),
            'lead.status_changed' => __('Lead Status Changed'),
            'deal.stage_changed' => __('Deal Stage Changed'),
            'activity.logged' => __('Activity Logged'),
            'task.overdue' => __('Task Overdue'),
            'lead.score_above' => __('Lead Score Above Threshold'),
            'manual' => __('Manual Trigger'),
        ];
    }

    /**
     * Get translated trigger label.
     */
    public function getTriggerLabelAttribute(): string
    {
        $types = self::triggerTypes();
        return $types[$this->trigger_type] ?? ucwords(str_replace(['.', '_'], ' ', $this->trigger_type));
    }
}
