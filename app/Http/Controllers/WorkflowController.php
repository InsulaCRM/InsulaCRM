<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Deal;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRunLog;
use App\Models\WorkflowStep;
use App\Services\CustomFieldService;
use App\Services\WorkflowEngine;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    /**
     * List all workflows with step count and run stats.
     */
    public function index()
    {
        $workflows = Workflow::withCount('steps')
            ->latest()
            ->get();

        return view('workflows.index', compact('workflows'));
    }

    /**
     * Show the create workflow form.
     */
    public function create()
    {
        $triggerTypes = Workflow::triggerTypes();
        $leadStatuses = CustomFieldService::getOptions('lead_status');
        $dealStages = Deal::stageLabels();

        return view('workflows.create', compact('triggerTypes', 'leadStatuses', 'dealStages'));
    }

    /**
     * Store a new workflow.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|string|in:' . implode(',', array_keys(Workflow::triggerTypes())),
            'trigger_config' => 'nullable|array',
            'trigger_config.status' => 'nullable|string',
            'trigger_config.stage' => 'nullable|string',
            'trigger_config.threshold' => 'nullable|integer|min:1|max:100',
        ]);

        $workflow = Workflow::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger_type' => $data['trigger_type'],
            'trigger_config' => $data['trigger_config'] ?? [],
            'is_active' => false,
            'run_count' => 0,
        ]);

        AuditLog::log('workflow.created', $workflow);

        return redirect()->route('workflows.edit', $workflow)
            ->with('success', __('Workflow created. Now add your steps.'));
    }

    /**
     * Show the workflow editor with steps builder.
     */
    public function edit(Workflow $workflow)
    {
        $workflow->load('steps');

        $triggerTypes = Workflow::triggerTypes();
        $leadStatuses = CustomFieldService::getOptions('lead_status');
        $dealStages = Deal::stageLabels();
        $stepTypes = WorkflowStep::stepTypes();
        $actionTypes = WorkflowStep::actionTypes();
        $conditionOperators = WorkflowStep::conditionOperators();

        // Agents for assign_agent action
        $agents = User::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('workflows.edit', compact(
            'workflow',
            'triggerTypes',
            'leadStatuses',
            'dealStages',
            'stepTypes',
            'actionTypes',
            'conditionOperators',
            'agents'
        ));
    }

    /**
     * Update workflow details.
     */
    public function update(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|string|in:' . implode(',', array_keys(Workflow::triggerTypes())),
            'trigger_config' => 'nullable|array',
            'trigger_config.status' => 'nullable|string',
            'trigger_config.stage' => 'nullable|string',
            'trigger_config.threshold' => 'nullable|integer|min:1|max:100',
        ]);

        $workflow->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger_type' => $data['trigger_type'],
            'trigger_config' => $data['trigger_config'] ?? [],
        ]);

        AuditLog::log('workflow.updated', $workflow);

        return redirect()->route('workflows.edit', $workflow)
            ->with('success', __('Workflow updated successfully.'));
    }

    /**
     * Delete a workflow and all its steps/logs.
     */
    public function destroy(Workflow $workflow)
    {
        AuditLog::log('workflow.deleted', $workflow);

        $workflow->runLogs()->delete();
        $workflow->steps()->delete();
        $workflow->delete();

        return redirect()->route('workflows.index')
            ->with('success', __('Workflow deleted successfully.'));
    }

    /**
     * Toggle workflow active/inactive via AJAX.
     */
    public function toggle(Workflow $workflow)
    {
        $workflow->update(['is_active' => !$workflow->is_active]);

        AuditLog::log('workflow.toggled', $workflow, null, ['is_active' => $workflow->is_active]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $workflow->is_active,
            ]);
        }

        return redirect()->route('workflows.index')
            ->with('success', __('Workflow :status.', ['status' => $workflow->is_active ? __('activated') : __('deactivated')]));
    }

    /**
     * Add a step to a workflow via AJAX.
     */
    public function storeStep(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'type' => 'required|string|in:action,condition,delay',
            'action_type' => 'nullable|required_if:type,action|string|in:' . implode(',', array_keys(WorkflowStep::actionTypes())),
            'config' => 'nullable|array',
            'delay_minutes' => 'nullable|required_if:type,delay|integer|min:1',
            'condition_field' => 'nullable|required_if:type,condition|string|max:100',
            'condition_operator' => 'nullable|required_if:type,condition|string|in:' . implode(',', array_keys(WorkflowStep::conditionOperators())),
            'condition_value' => 'nullable|string|max:255',
            'next_step_id' => 'nullable|integer|exists:workflow_steps,id',
            'alt_step_id' => 'nullable|integer|exists:workflow_steps,id',
        ]);

        // Determine position (append to end)
        $maxPosition = $workflow->steps()->max('position') ?? 0;

        $step = WorkflowStep::create([
            'workflow_id' => $workflow->id,
            'position' => $maxPosition + 1,
            'type' => $data['type'],
            'action_type' => $data['action_type'] ?? null,
            'config' => $data['config'] ?? null,
            'delay_minutes' => $data['delay_minutes'] ?? null,
            'condition_field' => $data['condition_field'] ?? null,
            'condition_operator' => $data['condition_operator'] ?? null,
            'condition_value' => $data['condition_value'] ?? null,
            'next_step_id' => $data['next_step_id'] ?? null,
            'alt_step_id' => $data['alt_step_id'] ?? null,
        ]);

        if ($request->expectsJson()) {
            $step->load('nextStep', 'altStep');
            return response()->json([
                'success' => true,
                'step' => $step,
                'summary' => $step->summary,
            ]);
        }

        return redirect()->route('workflows.edit', $workflow)
            ->with('success', __('Step added.'));
    }

    /**
     * Update a workflow step via AJAX.
     */
    public function updateStep(Request $request, WorkflowStep $step)
    {
        $data = $request->validate([
            'type' => 'required|string|in:action,condition,delay',
            'action_type' => 'nullable|required_if:type,action|string|in:' . implode(',', array_keys(WorkflowStep::actionTypes())),
            'config' => 'nullable|array',
            'delay_minutes' => 'nullable|required_if:type,delay|integer|min:1',
            'condition_field' => 'nullable|required_if:type,condition|string|max:100',
            'condition_operator' => 'nullable|required_if:type,condition|string|in:' . implode(',', array_keys(WorkflowStep::conditionOperators())),
            'condition_value' => 'nullable|string|max:255',
            'next_step_id' => 'nullable|integer|exists:workflow_steps,id',
            'alt_step_id' => 'nullable|integer|exists:workflow_steps,id',
        ]);

        $step->update([
            'type' => $data['type'],
            'action_type' => $data['action_type'] ?? null,
            'config' => $data['config'] ?? null,
            'delay_minutes' => $data['delay_minutes'] ?? null,
            'condition_field' => $data['condition_field'] ?? null,
            'condition_operator' => $data['condition_operator'] ?? null,
            'condition_value' => $data['condition_value'] ?? null,
            'next_step_id' => $data['next_step_id'] ?? null,
            'alt_step_id' => $data['alt_step_id'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'step' => $step->fresh(),
                'summary' => $step->summary,
            ]);
        }

        return redirect()->route('workflows.edit', $step->workflow)
            ->with('success', __('Step updated.'));
    }

    /**
     * Delete a workflow step.
     */
    public function destroyStep(WorkflowStep $step)
    {
        $workflow = $step->workflow;

        // Clear references to this step from other steps
        WorkflowStep::where('workflow_id', $workflow->id)
            ->where('next_step_id', $step->id)
            ->update(['next_step_id' => null]);
        WorkflowStep::where('workflow_id', $workflow->id)
            ->where('alt_step_id', $step->id)
            ->update(['alt_step_id' => null]);

        $deletedPosition = $step->position;
        $step->delete();

        // Reorder remaining steps
        WorkflowStep::where('workflow_id', $workflow->id)
            ->where('position', '>', $deletedPosition)
            ->decrement('position');

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('workflows.edit', $workflow)
            ->with('success', __('Step removed.'));
    }

    /**
     * Reorder steps via AJAX (drag and drop).
     */
    public function reorderSteps(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:workflow_steps,id',
        ]);

        foreach ($data['order'] as $position => $stepId) {
            WorkflowStep::where('id', $stepId)
                ->where('workflow_id', $workflow->id)
                ->update(['position' => $position + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * View workflow run logs.
     */
    public function logs(Workflow $workflow)
    {
        $query = WorkflowRunLog::where('workflow_id', $workflow->id)
            ->with('step')
            ->latest('created_at');

        if (request('status')) {
            $query->where('status', request('status'));
        }

        $logs = $query->paginate(25);

        return view('workflows.logs', compact('workflow', 'logs'));
    }

    /**
     * Manually trigger a workflow (for 'manual' trigger type).
     */
    public function triggerManual(Request $request, Workflow $workflow)
    {
        if ($workflow->trigger_type !== 'manual') {
            return response()->json(['error' => __('This workflow cannot be triggered manually.')], 422);
        }

        $data = $request->validate([
            'model_type' => 'required|string|in:lead,deal',
            'model_id' => 'required|integer',
        ]);

        $modelClass = $data['model_type'] === 'lead' ? \App\Models\Lead::class : \App\Models\Deal::class;
        $model = $modelClass::find($data['model_id']);

        if (!$model) {
            return response()->json(['error' => __('Record not found.')], 404);
        }

        $engine = app(WorkflowEngine::class);
        $engine->executeWorkflow($workflow, $model);

        return response()->json(['success' => true, 'message' => __('Workflow triggered.')]);
    }

    public function templates()
    {
        $templates = \App\Services\WorkflowTemplateService::getTemplates();

        return response()->json($templates);
    }

    public function createFromTemplate(Request $request)
    {
        $request->validate(['template_key' => 'required|string']);

        $templates = \App\Services\WorkflowTemplateService::getTemplates();

        if (!isset($templates[$request->template_key])) {
            return response()->json(['error' => __('Template not found.')], 404);
        }

        $template = $templates[$request->template_key];
        $workflow = \App\Models\Workflow::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $template['name'],
            'description' => $template['description'] ?? null,
            'trigger_type' => $template['trigger_type'],
            'trigger_config' => $template['trigger_config'] ?? null,
            'is_active' => false,
        ]);

        foreach ($template['steps'] as $step) {
            \App\Models\WorkflowStep::create([
                'workflow_id' => $workflow->id,
                'type' => $step['type'],
                'config' => $step['config'],
                'position' => $step['position'],
            ]);
        }

        return response()->json(['success' => true, 'redirect' => route('workflows.edit', $workflow)]);
    }
}
