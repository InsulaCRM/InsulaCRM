<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'position',
        'type',
        'action_type',
        'config',
        'delay_minutes',
        'condition_field',
        'condition_operator',
        'condition_value',
        'next_step_id',
        'alt_step_id',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'delay_minutes' => 'integer',
            'position' => 'integer',
            'next_step_id' => 'integer',
            'alt_step_id' => 'integer',
        ];
    }

    /**
     * The workflow this step belongs to.
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * The next step (for conditions: the "yes" branch).
     */
    public function nextStep()
    {
        return $this->belongsTo(self::class, 'next_step_id');
    }

    /**
     * The alternate step (for conditions: the "no" branch).
     */
    public function altStep()
    {
        return $this->belongsTo(self::class, 'alt_step_id');
    }

    /**
     * Available step types.
     */
    public static function stepTypes(): array
    {
        return [
            'action' => __('Action'),
            'condition' => __('Condition'),
            'delay' => __('Delay'),
        ];
    }

    /**
     * Available action types.
     */
    public static function actionTypes(): array
    {
        return [
            'send_email' => __('Send Email'),
            'send_sms' => __('Send SMS'),
            'create_task' => __('Create Task'),
            'update_field' => __('Update Field'),
            'assign_agent' => __('Assign Agent'),
            'add_tag' => __('Add Tag'),
            'notify_user' => __('Notify User'),
            'webhook' => __('Send Webhook'),
            'ai_qualify_lead' => __('AI Qualify Lead'),
        ];
    }

    /**
     * Available condition operators.
     */
    public static function conditionOperators(): array
    {
        return [
            'equals' => __('Equals'),
            'not_equals' => __('Not Equals'),
            'contains' => __('Contains'),
            'not_contains' => __('Does Not Contain'),
            'greater_than' => __('Greater Than'),
            'less_than' => __('Less Than'),
            'is_empty' => __('Is Empty'),
            'is_not_empty' => __('Is Not Empty'),
        ];
    }

    /**
     * Get a human-readable summary of this step.
     */
    public function getSummaryAttribute(): string
    {
        switch ($this->type) {
            case 'delay':
                $minutes = $this->delay_minutes ?? 0;
                if ($minutes >= 1440) {
                    $days = floor($minutes / 1440);
                    return __(':count day(s)', ['count' => $days]);
                } elseif ($minutes >= 60) {
                    $hours = floor($minutes / 60);
                    return __(':count hour(s)', ['count' => $hours]);
                }
                return __(':count minute(s)', ['count' => $minutes]);

            case 'condition':
                $ops = self::conditionOperators();
                $op = $ops[$this->condition_operator] ?? $this->condition_operator;
                return "{$this->condition_field} {$op} {$this->condition_value}";

            case 'action':
                $types = self::actionTypes();
                $label = $types[$this->action_type] ?? $this->action_type;
                $config = $this->config ?? [];
                $detail = '';
                switch ($this->action_type) {
                    case 'send_email':
                        $detail = $config['subject'] ?? '';
                        break;
                    case 'send_sms':
                        $detail = \Illuminate\Support\Str::limit($config['message'] ?? '', 40);
                        break;
                    case 'create_task':
                        $detail = $config['title'] ?? '';
                        break;
                    case 'update_field':
                        $detail = ($config['field'] ?? '') . ' = ' . ($config['value'] ?? '');
                        break;
                    case 'add_tag':
                        $detail = $config['tag_name'] ?? '';
                        break;
                    case 'webhook':
                        $detail = $config['url'] ?? '';
                        break;
                }
                return $detail ? "{$label}: {$detail}" : $label;

            default:
                return $this->type;
        }
    }
}
