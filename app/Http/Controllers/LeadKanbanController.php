<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\CustomFieldService;
use Illuminate\Http\Request;

class LeadKanbanController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $statuses = CustomFieldService::getOptions('lead_status');

        $query = Lead::with(['agent', 'tags']);

        if (!$user->isAdmin()) {
            $query->where('agent_id', $user->id);
        } elseif ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        $leads = $query->get()->groupBy('status');

        $agents = $user->isAdmin()
            ? \App\Models\User::where('tenant_id', $user->tenant_id)->where('is_active', true)->get()
            : collect();

        return view('leads.kanban', compact('statuses', 'leads', 'agents'));
    }
}
