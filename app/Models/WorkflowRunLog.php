<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowRunLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_id',
        'model_type',
        'model_id',
        'status',
        'result',
        'scheduled_at',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    /**
     * The workflow this log entry belongs to.
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * The step this log entry is for.
     */
    public function step()
    {
        return $this->belongsTo(WorkflowStep::class, 'step_id');
    }

    /**
     * Get the related model (polymorphic manual lookup).
     */
    public function getModelAttribute()
    {
        if ($this->model_type && $this->model_id) {
            $class = $this->model_type;
            if (class_exists($class)) {
                return $class::withoutGlobalScopes()->find($this->model_id);
            }
        }
        return null;
    }

    /**
     * Status badge color mapping.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'green',
            'failed' => 'red',
            'skipped' => 'yellow',
            'waiting' => 'blue',
            'started' => 'cyan',
            default => 'secondary',
        };
    }

    /**
     * Status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return __(ucfirst($this->status ?? 'unknown'));
    }
}
