<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use App\Models\Role;
use App\Services\CustomFieldService;
use Illuminate\Http\Request;

class ActivityInboxController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Activity::with(['lead', 'deal', 'agent']);

        // Role scoping - agents see only their own activities
        if ($user->isAgent()) {
            $query->where('agent_id', $user->id);
        }

        // Filters
        if ($request->filled('type')) {
            $types = is_array($request->type) ? $request->type : [$request->type];
            $query->whereIn('type', $types);
        }

        if ($request->filled('agent_id') && $user->isAdmin()) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->filled('date_from')) {
            $query->where('logged_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('logged_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('entity_type')) {
            if ($request->entity_type === 'lead') {
                $query->whereNotNull('lead_id')->whereNull('deal_id');
            } elseif ($request->entity_type === 'deal') {
                $query->whereNotNull('deal_id');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $activities = $query->orderByDesc('logged_at')->paginate(50);

        // Agent list for admin filter
        $agents = collect();
        if ($user->isAdmin()) {
            $agentRoleNames = \App\Services\BusinessModeService::getAgentRoleNames();
            $agentRoleIds = Role::whereIn('name', $agentRoleNames)->pluck('id');
            $agents = User::where('tenant_id', $user->tenant_id)
                ->whereIn('role_id', $agentRoleIds)
                ->get();
        }

        // Activity types for filter
        $activityTypes = CustomFieldService::getOptions('activity_type');
        $activityTypes['stage_change'] = __('Stage Change');

        return view('activities.inbox', compact('activities', 'agents', 'activityTypes'));
    }
}
