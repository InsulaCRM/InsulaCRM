<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\LeadClaim;
use App\Models\User;
use Illuminate\Support\Collection;

class AssignmentHistoryService
{
    public function getHistory(Lead $lead): Collection
    {
        $entries = collect();

        // Get agent_id changes from audit log
        $auditEntries = AuditLog::where('model_type', 'App\\Models\\Lead')
            ->where('model_id', $lead->id)
            ->whereIn('action', ['lead.created', 'lead.updated'])
            ->orderBy('created_at')
            ->get();

        foreach ($auditEntries as $log) {
            $newValues = $log->new_values;
            if (!$newValues) continue;

            // Check for agent_id in new values
            if (isset($newValues['agent_id'])) {
                $newAgentId = $newValues['agent_id'];
                $newAgent = $newAgentId ? User::find($newAgentId) : null;
                $performer = $log->user_id ? User::find($log->user_id) : null;

                $entries->push((object) [
                    'type' => 'assignment',
                    'timestamp' => $log->created_at,
                    'new_agent' => $newAgent?->name ?? __('Unassigned'),
                    'performed_by' => $performer?->name ?? __('System'),
                    'action' => $log->action,
                ]);
            }
        }

        // Get claim records
        $claims = LeadClaim::where('lead_id', $lead->id)
            ->orderBy('created_at')
            ->get();

        foreach ($claims as $claim) {
            $agent = User::find($claim->agent_id);
            $entries->push((object) [
                'type' => $claim->claimed ? 'claim_success' : 'claim_attempt',
                'timestamp' => $claim->created_at,
                'new_agent' => $agent?->name ?? __('Unknown'),
                'performed_by' => $agent?->name ?? __('Unknown'),
                'action' => $claim->claimed ? __('Claimed from shark tank') : __('Claim attempt (failed)'),
            ]);
        }

        return $entries->sortBy('timestamp')->values();
    }
}
