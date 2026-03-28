<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\ActivityLogged;
use App\Facades\Hooks;
use App\Models\Activity;
use App\Models\Lead;
use App\Services\CustomFieldService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActivityApiController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $query = Activity::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with(['lead', 'agent']);

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->filled('since')) {
            $query->where('created_at', '>=', $request->since);
        }

        return response()->json(
            $query->latest('logged_at')->paginate($request->integer('per_page', 25))
        );
    }

    public function store(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $validTypes = CustomFieldService::getValidSlugs('activity_type', $tenant);
        $validTypes[] = 'stage_change';

        $validator = Validator::make($request->all(), [
            'lead_id'  => 'required|integer',
            'agent_id' => 'nullable|integer',
            'type'     => 'required|in:' . implode(',', $validTypes),
            'subject'  => 'nullable|string|max:255',
            'body'     => 'nullable|string',
            'logged_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Verify lead belongs to tenant
        Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($data['lead_id']);

        $activity = Activity::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'lead_id'   => $data['lead_id'],
            'agent_id'  => $data['agent_id'] ?? null,
            'type'      => $data['type'],
            'subject'   => $data['subject'] ?? null,
            'body'      => $data['body'] ?? null,
            'logged_at' => $data['logged_at'] ?? now(),
        ]);

        event(new ActivityLogged($activity));
        Hooks::doAction('activity.logged', $activity);

        return response()->json(['success' => true, 'activity_id' => $activity->id], 201);
    }
}
