<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRunLog;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WorkflowEngine
{
    /**
     * Trigger all active workflows matching the given trigger type.
     * Called from event listeners, observers, or manually.
     */
    public function trigger(string $triggerType, Model $model, array $context = []): void
    {
        $tenantId = $model->tenant_id ?? ($model->tenant ? $model->tenant->id : null);

        if (!$tenantId) {
            Log::warning("WorkflowEngine: No tenant_id on model " . get_class($model) . " #{$model->id}");
            return;
        }

        $workflows = Workflow::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('trigger_type', $triggerType)
            ->with('steps')
            ->get();

        foreach ($workflows as $workflow) {
            if ($this->matchesTriggerConfig($workflow, $model, $context)) {
                try {
                    $this->executeWorkflow($workflow, $model);
                } catch (\Throwable $e) {
                    Log::error("WorkflowEngine: Failed executing workflow #{$workflow->id}: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Check if a workflow's trigger_config conditions are met.
     */
    protected function matchesTriggerConfig(Workflow $workflow, Model $model, array $context = []): bool
    {
        $config = $workflow->trigger_config ?? [];

        if (empty($config)) {
            return true;
        }

        switch ($workflow->trigger_type) {
            case 'lead.status_changed':
                if (isset($config['status'])) {
                    $newStatus = $context['new_status'] ?? $model->status ?? null;
                    return $newStatus === $config['status'];
                }
                return true;

            case 'deal.stage_changed':
                if (isset($config['stage'])) {
                    $newStage = $context['new_stage'] ?? $model->stage ?? null;
                    return $newStage === $config['stage'];
                }
                return true;

            case 'lead.score_above':
                if (isset($config['threshold'])) {
                    $score = $model->motivation_score ?? 0;
                    return $score >= (int) $config['threshold'];
                }
                return true;

            default:
                return true;
        }
    }

    /**
     * Execute a workflow from its first step through all sequential steps.
     */
    public function executeWorkflow(Workflow $workflow, Model $model): void
    {
        $steps = $workflow->steps()->orderBy('position')->get();

        if ($steps->isEmpty()) {
            return;
        }

        // Update workflow run stats
        Workflow::withoutGlobalScopes()->where('id', $workflow->id)->update([
            'last_run_at' => now(),
            'run_count' => ($workflow->run_count ?? 0) + 1,
        ]);

        $this->executeFromStep($workflow, $steps->first(), $model, $steps);
    }

    /**
     * Execute a specific step and continue to subsequent steps.
     */
    protected function executeFromStep(Workflow $workflow, WorkflowStep $step, Model $model, $allSteps = null): void
    {
        if (!$allSteps) {
            $allSteps = $workflow->steps()->orderBy('position')->get();
        }

        $currentStep = $step;

        while ($currentStep) {
            $log = WorkflowRunLog::create([
                'workflow_id' => $workflow->id,
                'step_id' => $currentStep->id,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'status' => 'started',
                'executed_at' => now(),
            ]);

            try {
                switch ($currentStep->type) {
                    case 'delay':
                        $log->update([
                            'status' => 'waiting',
                            'scheduled_at' => now()->addMinutes($currentStep->delay_minutes ?? 0),
                            'executed_at' => null,
                        ]);
                        // Stop execution here; delayed steps are picked up by the scheduler
                        return;

                    case 'condition':
                        $result = $this->evaluateCondition($currentStep, $model);
                        $log->update([
                            'status' => 'completed',
                            'result' => $result ? 'condition: true' : 'condition: false',
                        ]);

                        if ($result && $currentStep->next_step_id) {
                            $currentStep = $allSteps->firstWhere('id', $currentStep->next_step_id);
                        } elseif (!$result && $currentStep->alt_step_id) {
                            $currentStep = $allSteps->firstWhere('id', $currentStep->alt_step_id);
                        } else {
                            // No branch target; move to next position
                            $currentStep = $this->getNextPositionStep($currentStep, $allSteps);
                        }
                        continue 2;

                    case 'action':
                        $actionResult = $this->executeAction($currentStep, $model);
                        $log->update([
                            'status' => 'completed',
                            'result' => $actionResult,
                        ]);
                        break;

                    default:
                        $log->update([
                            'status' => 'skipped',
                            'result' => 'Unknown step type: ' . $currentStep->type,
                        ]);
                        break;
                }
            } catch (\Throwable $e) {
                $log->update([
                    'status' => 'failed',
                    'result' => Str::limit($e->getMessage(), 500),
                ]);
                Log::error("WorkflowEngine step #{$currentStep->id} failed: {$e->getMessage()}");
            }

            // Move to next sequential step
            $currentStep = $this->getNextPositionStep($currentStep, $allSteps);
        }
    }

    /**
     * Get the next step by position order.
     */
    protected function getNextPositionStep(WorkflowStep $current, $allSteps): ?WorkflowStep
    {
        return $allSteps->where('position', '>', $current->position)->sortBy('position')->first();
    }

    /**
     * Evaluate a condition step against the model.
     */
    protected function evaluateCondition(WorkflowStep $step, Model $model): bool
    {
        $field = $step->condition_field;
        $operator = $step->condition_operator;
        $expected = $step->condition_value;

        // Support dotted field names for relations: e.g., property.condition
        $actual = $this->resolveFieldValue($model, $field);

        switch ($operator) {
            case 'equals':
                return (string) $actual === (string) $expected;
            case 'not_equals':
                return (string) $actual !== (string) $expected;
            case 'contains':
                return Str::contains((string) $actual, (string) $expected);
            case 'not_contains':
                return !Str::contains((string) $actual, (string) $expected);
            case 'greater_than':
                return (float) $actual > (float) $expected;
            case 'less_than':
                return (float) $actual < (float) $expected;
            case 'is_empty':
                return empty($actual);
            case 'is_not_empty':
                return !empty($actual);
            default:
                return false;
        }
    }

    /**
     * Execute an action step.
     */
    public function executeAction(WorkflowStep $step, Model $model): string
    {
        $config = $step->config ?? [];

        switch ($step->action_type) {
            case 'send_email':
                return $this->actionSendEmail($config, $model);
            case 'send_sms':
                return $this->actionSendSms($config, $model);
            case 'create_task':
                return $this->actionCreateTask($config, $model);
            case 'update_field':
                return $this->actionUpdateField($config, $model);
            case 'assign_agent':
                return $this->actionAssignAgent($config, $model);
            case 'add_tag':
                return $this->actionAddTag($config, $model);
            case 'notify_user':
                return $this->actionNotifyUser($config, $model);
            case 'webhook':
                return $this->actionWebhook($config, $model);
            case 'ai_qualify_lead':
                return $this->actionAiQualifyLead($config, $model);
            default:
                return "Unknown action type: {$step->action_type}";
        }
    }

    /**
     * Action: Send an email.
     */
    protected function actionSendEmail(array $config, Model $model): string
    {
        $to = $config['to'] ?? $model->email ?? null;
        $subject = $this->replaceMergeFields($config['subject'] ?? 'Notification', $model);
        $body = $this->replaceMergeFields($config['body'] ?? '', $model);

        if (!$to) {
            return 'No email address available';
        }

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
            return "Email sent to {$to}";
        } catch (\Throwable $e) {
            throw new \RuntimeException("Email send failed: {$e->getMessage()}");
        }
    }

    /**
     * Action: Send an SMS via SmsService.
     */
    protected function actionSendSms(array $config, Model $model): string
    {
        $to = $config['to'] ?? $model->phone ?? null;
        $message = $this->replaceMergeFields($config['message'] ?? '', $model);

        if (!$to) {
            return 'No phone number available';
        }

        try {
            $smsService = app(SmsService::class);
            $smsService->send($to, $message);
            return "SMS sent to {$to}";
        } catch (\Throwable $e) {
            throw new \RuntimeException("SMS send failed: {$e->getMessage()}");
        }
    }

    /**
     * Action: Create a task linked to the model.
     */
    protected function actionCreateTask(array $config, Model $model): string
    {
        $tenantId = $model->tenant_id ?? null;
        $title = $this->replaceMergeFields($config['title'] ?? 'Workflow Task', $model);
        $description = $this->replaceMergeFields($config['description'] ?? '', $model);
        $dueInDays = (int) ($config['due_in_days'] ?? 1);

        $taskData = [
            'tenant_id' => $tenantId,
            'title' => $title,
            'due_date' => now()->addDays($dueInDays),
            'is_completed' => false,
        ];

        // Link to lead if applicable
        if ($model instanceof \App\Models\Lead) {
            $taskData['lead_id'] = $model->id;
            $taskData['agent_id'] = $model->agent_id;
        } elseif ($model instanceof \App\Models\Deal) {
            $taskData['lead_id'] = $model->lead_id;
            $taskData['agent_id'] = $model->agent_id;
        }

        $task = Task::create($taskData);
        return "Task created: #{$task->id} - {$title}";
    }

    /**
     * Action: Update a field on the model.
     */
    protected function actionUpdateField(array $config, Model $model): string
    {
        $field = $config['field'] ?? null;
        $value = $config['value'] ?? null;

        if (!$field) {
            return 'No field specified';
        }

        // Prevent updating sensitive fields
        $protected = ['id', 'tenant_id', 'password', 'remember_token'];
        if (in_array($field, $protected)) {
            return "Cannot update protected field: {$field}";
        }

        $oldValue = $model->{$field};
        $model->{$field} = $value;
        $model->save();

        return "Updated {$field}: {$oldValue} -> {$value}";
    }

    /**
     * Action: Assign the model to an agent.
     */
    protected function actionAssignAgent(array $config, Model $model): string
    {
        $agentId = $config['agent_id'] ?? null;

        if (!$agentId) {
            return 'No agent_id specified';
        }

        $agent = User::find($agentId);
        if (!$agent) {
            return "Agent #{$agentId} not found";
        }

        if (isset($model->agent_id)) {
            $model->agent_id = $agentId;
            $model->save();
            return "Assigned to agent: {$agent->name}";
        }

        return 'Model does not support agent assignment';
    }

    /**
     * Action: Add a tag to the model.
     */
    protected function actionAddTag(array $config, Model $model): string
    {
        $tagName = $config['tag_name'] ?? null;

        if (!$tagName) {
            return 'No tag name specified';
        }

        if (!method_exists($model, 'tags')) {
            return 'Model does not support tags';
        }

        $tenantId = $model->tenant_id ?? null;

        // Find or create the tag
        $tag = Tag::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('name', $tagName)
            ->first();

        if (!$tag) {
            $tag = Tag::create([
                'tenant_id' => $tenantId,
                'name' => $tagName,
                'color' => 'blue',
            ]);
        }

        // Attach tag if not already attached
        if (!$model->tags()->where('tags.id', $tag->id)->exists()) {
            $model->tags()->attach($tag->id);
        }

        return "Tag added: {$tagName}";
    }

    /**
     * Action: Send a database notification to a user.
     */
    protected function actionNotifyUser(array $config, Model $model): string
    {
        $userId = $config['user_id'] ?? ($model->agent_id ?? null);
        $message = $this->replaceMergeFields($config['message'] ?? 'Workflow notification', $model);

        if (!$userId) {
            return 'No user to notify';
        }

        $user = User::find($userId);
        if (!$user) {
            return "User #{$userId} not found";
        }

        // Create a simple database notification
        $user->notify(new \App\Notifications\WorkflowNotification($message, $model));

        return "Notification sent to {$user->name}";
    }

    /**
     * Action: Send a webhook POST request.
     */
    protected function actionWebhook(array $config, Model $model): string
    {
        $url = $config['url'] ?? null;

        if (!$url) {
            return 'No webhook URL specified';
        }

        try {
            $payload = [
                'workflow_event' => true,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'data' => $model->toArray(),
                'timestamp' => now()->toIso8601String(),
            ];

            $response = Http::timeout(10)->post($url, $payload);

            return "Webhook sent to {$url} - Status: {$response->status()}";
        } catch (\Throwable $e) {
            throw new \RuntimeException("Webhook failed: {$e->getMessage()}");
        }
    }

    /**
     * Action: AI Qualify Lead — auto-set temperature via AI.
     */
    protected function actionAiQualifyLead(array $config, Model $model): string
    {
        // Only works on Lead models
        if (!($model instanceof \App\Models\Lead)) {
            return 'AI Qualify Lead only works on Lead models';
        }

        $tenant = \App\Models\Tenant::find($model->tenant_id);
        if (!$tenant || !$tenant->ai_enabled) {
            return 'AI not enabled for this tenant';
        }

        try {
            $aiService = new \App\Services\AiService($tenant);
            if (!$aiService->isAvailable()) {
                return 'AI service not available';
            }

            $result = $aiService->qualifyLeadForWorkflow($model);
            $newTemperature = $result['temperature'] ?? 'warm';
            $reasoning = $result['reasoning'] ?? '';

            $oldTemp = $model->temperature;
            $model->temperature = $newTemperature;
            $model->save();

            // Log the AI action
            \App\Models\AiLog::withoutGlobalScopes()->create([
                'tenant_id' => $model->tenant_id,
                'type' => 'workflow_qualify',
                'model_type' => \App\Models\Lead::class,
                'model_id' => $model->id,
                'result' => "Temperature: {$newTemperature}. {$reasoning}",
                'prompt_summary' => "Workflow AI qualification for {$model->first_name} {$model->last_name}",
            ]);

            return "AI qualified lead: {$oldTemp} → {$newTemperature}. {$reasoning}";
        } catch (\Throwable $e) {
            Log::error("AI qualify lead failed: {$e->getMessage()}");
            return "AI qualification failed: {$e->getMessage()}";
        }
    }

    /**
     * Process all delayed steps that are ready to execute.
     * Called by the scheduler every minute.
     */
    public function processDelayedSteps(): int
    {
        $readyLogs = WorkflowRunLog::where('status', 'waiting')
            ->where('scheduled_at', '<=', now())
            ->with(['workflow.steps', 'step'])
            ->get();

        $processed = 0;

        foreach ($readyLogs as $log) {
            if (!$log->workflow || !$log->step) {
                $log->update(['status' => 'failed', 'result' => 'Missing workflow or step']);
                continue;
            }

            // Mark this delay as completed
            $log->update([
                'status' => 'completed',
                'executed_at' => now(),
                'result' => 'Delay completed',
            ]);

            // Resolve the model
            $model = null;
            if ($log->model_type && $log->model_id) {
                $class = $log->model_type;
                if (class_exists($class)) {
                    $model = $class::withoutGlobalScopes()->find($log->model_id);
                }
            }

            if (!$model) {
                $log->update(['result' => 'Delay completed, but model not found - skipping remaining steps']);
                continue;
            }

            // Continue from the step after the delay
            $allSteps = $log->workflow->steps()->orderBy('position')->get();
            $nextStep = $allSteps->where('position', '>', $log->step->position)->sortBy('position')->first();

            if ($nextStep) {
                try {
                    $this->executeFromStep($log->workflow, $nextStep, $model, $allSteps);
                } catch (\Throwable $e) {
                    Log::error("WorkflowEngine: Failed resuming after delay for workflow #{$log->workflow_id}: {$e->getMessage()}");
                }
            }

            $processed++;
        }

        return $processed;
    }

    /**
     * Replace merge fields like {{field_name}} in text.
     * Supports nested relations: {{lead.first_name}}, {{property.address}}, etc.
     */
    public function replaceMergeFields(string $text, Model $model): string
    {
        return preg_replace_callback('/\{\{([a-zA-Z0-9_.]+)\}\}/', function ($matches) use ($model) {
            $fieldPath = $matches[1];
            $value = $this->resolveFieldValue($model, $fieldPath);
            return $value !== null ? (string) $value : '';
        }, $text);
    }

    /**
     * Resolve a dotted field path against a model.
     * E.g., "property.address" -> $model->property->address
     */
    protected function resolveFieldValue(Model $model, string $fieldPath)
    {
        $parts = explode('.', $fieldPath);
        $current = $model;

        foreach ($parts as $part) {
            if ($current === null) {
                return null;
            }

            if ($current instanceof Model) {
                // Check if it's a direct attribute or an accessor
                $value = $current->{$part} ?? null;

                if ($value instanceof Model || $value instanceof \Illuminate\Database\Eloquent\Collection) {
                    $current = $value;
                } else {
                    return $value;
                }
            } elseif (is_array($current)) {
                return $current[$part] ?? null;
            } else {
                return null;
            }
        }

        // If we ended on a Model, try to return a meaningful string
        if ($current instanceof Model) {
            return $current->name ?? $current->title ?? $current->id;
        }

        return $current;
    }
}
