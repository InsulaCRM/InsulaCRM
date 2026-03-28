<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PdfExportController extends Controller
{
    /**
     * Export a printable lead report.
     */
    public function exportLeadReport(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $tenantId = $tenant->id;

        $from = $request->get('from', now()->subMonths(6)->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));

        $leadQuery = Lead::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59']);

        if (auth()->user()->isAgent()) {
            $leadQuery->where('agent_id', auth()->id());
        }

        // Leads by source
        $leadsBySource = (clone $leadQuery)
            ->select('lead_source', DB::raw('count(*) as count'))
            ->groupBy('lead_source')
            ->orderByDesc('count')
            ->get();

        // Leads by status
        $leadsByStatus = (clone $leadQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        $totalLeads = (clone $leadQuery)->count();

        return view('reports.pdf.lead-report', compact(
            'tenant', 'from', 'to', 'leadsBySource', 'leadsByStatus', 'totalLeads'
        ));
    }

    /**
     * Export a printable pipeline report.
     */
    public function exportPipelineReport(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $tenantId = $tenant->id;

        $from = $request->get('from', now()->subMonths(6)->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));

        // Deal stages with counts and values
        $feeColumn = \App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column'];
        $stageData = Deal::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->select(
                'stage',
                DB::raw('count(*) as deal_count'),
                DB::raw('sum(contract_price) as total_value'),
                DB::raw("sum({$feeColumn}) as total_fees"),
                DB::getDriverName() === 'sqlite'
                    ? DB::raw("avg(julianday('now') - julianday(stage_changed_at)) as avg_days_in_stage")
                    : DB::raw('avg(DATEDIFF(NOW(), stage_changed_at)) as avg_days_in_stage')
            )
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        // Order stages according to tenant's business mode
        $orderedStages = collect();
        foreach (Deal::stages() as $key => $label) {
            if ($stageData->has($key)) {
                $row = $stageData->get($key);
                $row->label = $label;
                $orderedStages->push($row);
            }
        }

        // Total pipeline value (excluding closed)
        $totalPipelineValue = Deal::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->sum('contract_price');

        $totalPipelineFees = Deal::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->sum($feeColumn);

        // Won/lost summary
        $closedWon = Deal::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->where('stage', 'closed_won')
            ->count();

        $closedLost = Deal::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->where('stage', 'closed_lost')
            ->count();

        $totalFeesClosed = Deal::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->where('stage', 'closed_won')
            ->sum($feeColumn);

        return view('reports.pdf.pipeline-report', compact(
            'tenant', 'from', 'to', 'orderedStages', 'totalPipelineValue',
            'totalPipelineFees', 'closedWon', 'closedLost', 'totalFeesClosed'
        ));
    }

    /**
     * Export a printable team performance report.
     */
    public function exportTeamReport(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $tenantId = $tenant->id;

        $from = $request->get('from', now()->subMonths(6)->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));

        $feeColumn = \App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column'];
        $agentRoleIds = Role::whereIn('name', \App\Services\BusinessModeService::getAgentRoleNames())->pluck('id');

        $teamPerformance = User::where('tenant_id', $tenantId)
            ->whereIn('role_id', $agentRoleIds)
            ->get()
            ->map(function ($agent) use ($from, $to, $tenantId, $feeColumn) {
                $leadCount = Lead::where('tenant_id', $tenantId)
                    ->where('agent_id', $agent->id)
                    ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
                    ->count();

                $dealCount = Deal::where('tenant_id', $tenantId)
                    ->where('agent_id', $agent->id)
                    ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
                    ->count();

                $dealsClosed = Deal::where('tenant_id', $tenantId)
                    ->where('agent_id', $agent->id)
                    ->where('stage', 'closed_won')
                    ->whereBetween('deals.created_at', [$from, $to . ' 23:59:59'])
                    ->count();

                $feesGenerated = Deal::where('tenant_id', $tenantId)
                    ->where('agent_id', $agent->id)
                    ->where('stage', 'closed_won')
                    ->whereBetween('deals.created_at', [$from, $to . ' 23:59:59'])
                    ->sum($feeColumn);

                $activitiesLogged = Activity::where('tenant_id', $tenantId)
                    ->where('agent_id', $agent->id)
                    ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
                    ->count();

                return (object) [
                    'agent' => $agent,
                    'leadCount' => $leadCount,
                    'dealCount' => $dealCount,
                    'dealsClosed' => $dealsClosed,
                    'feesGenerated' => $feesGenerated,
                    'activitiesLogged' => $activitiesLogged,
                ];
            })
            ->sortByDesc('dealsClosed')
            ->values();

        return view('reports.pdf.team-report', compact(
            'tenant', 'from', 'to', 'teamPerformance'
        ));
    }
}
