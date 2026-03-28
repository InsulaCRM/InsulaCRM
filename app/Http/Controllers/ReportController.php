<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealBuyerMatch;
use App\Models\Lead;
use App\Models\LeadSourceCost;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Show the reports page with date range and agent filters.
     */
    public function index(Request $request)
    {
        $from = $request->get('from', now()->subMonths(6)->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $agentId = $request->get('agent_id');

        $leadQuery = Lead::whereBetween('created_at', [$from, $to . ' 23:59:59']);
        $dealQuery = Deal::whereBetween('created_at', [$from, $to . ' 23:59:59']);

        if (auth()->user()->isAgent()) {
            $leadQuery->where('agent_id', auth()->id());
            $dealQuery->where('agent_id', auth()->id());
        } elseif ($agentId) {
            $leadQuery->where('agent_id', $agentId);
            $dealQuery->where('agent_id', $agentId);
        }

        // Leads by source
        $leadsBySource = (clone $leadQuery)->select('lead_source', DB::raw('count(*) as count'))
            ->groupBy('lead_source')->get();

        // Conversion rate
        $totalLeads = (clone $leadQuery)->count();
        $closedDeals = (clone $dealQuery)->where('stage', 'closed_won')->count();
        $conversionRate = $totalLeads > 0 ? round(($closedDeals / $totalLeads) * 100, 1) : 0;

        // Top agents (admin only)
        $topAgents = [];
        if (auth()->user()->isAdmin()) {
            $topAgents = Deal::where('stage', 'closed_won')
                ->whereBetween('deals.created_at', [$from, $to . ' 23:59:59'])
                ->select('agent_id', DB::raw('count(*) as deals_closed'), DB::raw('sum(' . \App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column'] . ') as total_fees'))
                ->groupBy('agent_id')
                ->with('agent')
                ->orderByDesc('deals_closed')
                ->get();
        }

        $agentRoleNames = \App\Services\BusinessModeService::getAgentRoleNames();
        $agentRoleIds = \App\Models\Role::whereIn('name', $agentRoleNames)->pluck('id');
        $agents = auth()->user()->isAdmin()
            ? User::where('tenant_id', auth()->user()->tenant_id)->whereIn('role_id', $agentRoleIds)->get()
            : collect();

        // Pipeline bottleneck: avg days per stage
        $avgDaysExpr = DB::getDriverName() === 'sqlite'
            ? DB::raw("avg(julianday('now') - julianday(created_at)) as avg_days")
            : DB::raw('avg(DATEDIFF(NOW(), created_at)) as avg_days');
        $pipelineBottleneck = Deal::whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->select('stage', DB::raw('count(*) as deal_count'), $avgDaysExpr)
            ->groupBy('stage')
            ->orderByDesc('avg_days')
            ->get();

        // Conversion funnel — mode-aware statuses
        $isRealEstate = \App\Services\BusinessModeService::isRealEstate();
        $funnelStages = $isRealEstate
            ? ['new', 'inquiry', 'consultation', 'listing_signed', 'under_contract', 'closed_won']
            : ['new', 'contacted', 'negotiating', 'offer_presented', 'under_contract', 'closed_won'];
        $funnel = [];
        foreach ($funnelStages as $stage) {
            if (in_array($stage, ['closed_won'])) {
                $funnel[$stage] = (clone $dealQuery)->where('stage', $stage)->count();
            } else {
                $funnel[$stage] = (clone $leadQuery)->where('status', $stage)->count();
            }
        }

        // Lead source ROI (admin only)
        $leadSourceROI = [];
        if (auth()->user()->isAdmin()) {
            $tenantId = auth()->user()->tenant_id;
            $costs = LeadSourceCost::where('tenant_id', $tenantId)->pluck('monthly_budget', 'lead_source');
            foreach ($leadsBySource as $source) {
                $budget = $costs[$source->lead_source] ?? 0;
                $closedFromSource = Deal::where('stage', 'closed_won')
                    ->whereHas('lead', fn($q) => $q->where('lead_source', $source->lead_source))
                    ->whereBetween('deals.created_at', [$from, $to . ' 23:59:59'])
                    ->count();
                $leadSourceROI[] = (object)[
                    'source' => $source->lead_source,
                    'leads' => $source->count,
                    'closed' => $closedFromSource,
                    'budget' => $budget,
                    'cost_per_lead' => $source->count > 0 && $budget > 0 ? round($budget / $source->count, 2) : 0,
                    'cost_per_deal' => $closedFromSource > 0 && $budget > 0 ? round($budget / $closedFromSource, 2) : 0,
                ];
            }
        }

        // Buyer match rate (admin only) — scoped to current tenant
        $buyerMatchRate = [];
        if (auth()->user()->isAdmin()) {
            $tenantMatchQuery = DealBuyerMatch::whereHas('deal', fn ($q) => $q->where('tenant_id', auth()->user()->tenant_id));
            $totalMatches = (clone $tenantMatchQuery)->count();
            $notified = (clone $tenantMatchQuery)->whereNotNull('notified_at')->count();
            $responded = (clone $tenantMatchQuery)->whereNotNull('responded_at')->count();
            $interested = (clone $tenantMatchQuery)->where('status', 'interested')->count();
            $buyerMatchRate = compact('totalMatches', 'notified', 'responded', 'interested');
        }

        // Team performance leaderboard (admin only) — uses aggregate queries to avoid N+1
        $teamPerformance = [];
        if (auth()->user()->isAdmin()) {
            $tenantId = auth()->user()->tenant_id;
            $agentRoleIdsAll = \App\Models\Role::whereIn('name', $agentRoleNames)->pluck('id');
            $agentIds = User::where('tenant_id', $tenantId)->whereIn('role_id', $agentRoleIdsAll)->pluck('id');

            $leadsContactedMap = Lead::whereIn('agent_id', $agentIds)
                ->where('status', '!=', 'new')
                ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
                ->selectRaw('agent_id, count(*) as cnt')
                ->groupBy('agent_id')->pluck('cnt', 'agent_id');

            $offerStatuses = $isRealEstate
                ? ['offer_received', 'under_contract', 'pending', 'closed_won']
                : ['offer_presented', 'under_contract', 'closing', 'closed_won'];
            $offersMadeMap = Lead::whereIn('agent_id', $agentIds)
                ->whereIn('status', $offerStatuses)
                ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
                ->selectRaw('agent_id, count(*) as cnt')
                ->groupBy('agent_id')->pluck('cnt', 'agent_id');

            $dealsClosedMap = Deal::whereIn('agent_id', $agentIds)
                ->where('stage', 'closed_won')
                ->whereBetween('deals.created_at', [$from, $to . ' 23:59:59'])
                ->selectRaw('agent_id, count(*) as cnt, sum(' . \App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column'] . ') as fees')
                ->groupBy('agent_id')->get()->keyBy('agent_id');

            $teamPerformance = User::where('tenant_id', $tenantId)
                ->whereIn('role_id', $agentRoleIdsAll)
                ->get()
                ->map(function ($agent) use ($leadsContactedMap, $offersMadeMap, $dealsClosedMap) {
                    $closedRow = $dealsClosedMap->get($agent->id);
                    return (object) [
                        'agent' => $agent,
                        'leadsContacted' => $leadsContactedMap->get($agent->id, 0),
                        'offersMade' => $offersMadeMap->get($agent->id, 0),
                        'dealsClosed' => $closedRow->cnt ?? 0,
                        'feesGenerated' => $closedRow->fees ?? 0,
                    ];
                })
                ->sortByDesc('dealsClosed');
        }

        // List Stacking Report — scoped to current tenant
        $listStacking = DB::table('list_leads')
            ->join('leads', 'list_leads.lead_id', '=', 'leads.id')
            ->where('leads.tenant_id', auth()->user()->tenant_id)
            ->select('list_leads.lead_id', DB::raw('count(DISTINCT list_leads.list_id) as list_count'))
            ->groupBy('list_leads.lead_id')
            ->get();

        $stackDepth = [
            '1_list' => $listStacking->where('list_count', 1)->count(),
            '2_lists' => $listStacking->where('list_count', 2)->count(),
            '3_plus' => $listStacking->where('list_count', '>=', 3)->count(),
        ];

        // Conversion trend (monthly) — last 6 months of data within range
        $conversionTrend = [];
        $trendStart = max($from, now()->subMonths(6)->format('Y-m-d'));
        for ($m = 0; $m < 6; $m++) {
            $monthStart = now()->subMonths(5 - $m)->startOfMonth();
            $monthEnd = now()->subMonths(5 - $m)->endOfMonth();
            if ($monthEnd->format('Y-m-d') < $trendStart) continue;

            $monthLeads = Lead::whereBetween('created_at', [$monthStart, $monthEnd])->count();
            $monthClosed = Deal::where('stage', 'closed_won')->whereBetween('stage_changed_at', [$monthStart, $monthEnd])->count();
            $conversionTrend[] = (object) [
                'month' => $monthStart->format('M Y'),
                'leads' => $monthLeads,
                'closed' => $monthClosed,
                'rate' => $monthLeads > 0 ? round(($monthClosed / $monthLeads) * 100, 1) : 0,
            ];
        }

        // Lead-to-close velocity (avg days from lead creation to closed_won)
        $velocityData = Deal::where('stage', 'closed_won')
            ->whereBetween('deals.created_at', [$from, $to . ' 23:59:59'])
            ->join('leads', 'deals.lead_id', '=', 'leads.id')
            ->when(DB::getDriverName() === 'sqlite', function ($q) {
                $q->selectRaw('AVG(julianday(deals.stage_changed_at) - julianday(leads.created_at)) as avg_days')
                  ->selectRaw('MIN(julianday(deals.stage_changed_at) - julianday(leads.created_at)) as min_days')
                  ->selectRaw('MAX(julianday(deals.stage_changed_at) - julianday(leads.created_at)) as max_days');
            }, function ($q) {
                $q->selectRaw('AVG(DATEDIFF(deals.stage_changed_at, leads.created_at)) as avg_days')
                  ->selectRaw('MIN(DATEDIFF(deals.stage_changed_at, leads.created_at)) as min_days')
                  ->selectRaw('MAX(DATEDIFF(deals.stage_changed_at, leads.created_at)) as max_days');
            })
            ->first();

        $leadVelocity = (object) [
            'avg' => round($velocityData->avg_days ?? 0),
            'min' => round($velocityData->min_days ?? 0),
            'max' => round($velocityData->max_days ?? 0),
        ];

        // Agent comparison chart data (admin only)
        $agentComparison = [];
        if (auth()->user()->isAdmin() && count($topAgents)) {
            foreach ($topAgents->take(5) as $ta) {
                $agentComparison[] = (object) [
                    'name' => $ta->agent->name ?? 'Unknown',
                    'closed' => $ta->deals_closed,
                    'fees' => $ta->total_fees,
                ];
            }
        }

        return view('reports.index', compact(
            'leadsBySource', 'totalLeads', 'closedDeals', 'conversionRate',
            'topAgents', 'agents', 'from', 'to', 'agentId',
            'pipelineBottleneck', 'funnel', 'leadSourceROI', 'buyerMatchRate', 'teamPerformance',
            'stackDepth', 'conversionTrend', 'leadVelocity', 'agentComparison'
        ));
    }

    /**
     * Get dashboard chart data via AJAX.
     * Supports independent widget loading via ?widget= parameter.
     */
    public function dashboardData(Request $request)
    {
        $user = auth()->user();
        $widget = $request->get('widget');

        // Disposition agents can only access pipeline and KPI widgets
        $leadWidgets = ['monthly', 'sources', 'lead_source_roi'];
        if ($user->isDispositionAgent() && $widget && in_array($widget, $leadWidgets)) {
            return response()->json([]);
        }

        // Monthly chart data
        if (!$widget || $widget === 'monthly') {
            $months = [];
            $leadsPerMonth = [];
            $dealsPerMonth = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');

                $lq = Lead::whereMonth('created_at', $date->month)->whereYear('created_at', $date->year);
                if ($user->isAgent()) $lq->where('agent_id', $user->id);
                $leadsPerMonth[] = $lq->count();

                $dq = Deal::where('stage', 'closed_won')->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year);
                if ($user->isAgent()) $dq->where('agent_id', $user->id);
                $dealsPerMonth[] = $dq->count();
            }

            if ($widget === 'monthly') {
                return response()->json(compact('months', 'leadsPerMonth', 'dealsPerMonth'));
            }
        }

        // Lead sources data
        if (!$widget || $widget === 'sources') {
            $sourceQuery = Lead::select('lead_source', DB::raw('count(*) as count'))->groupBy('lead_source');
            if ($user->isAgent()) $sourceQuery->where('agent_id', $user->id);
            $leadSources = $sourceQuery->get();

            if ($widget === 'sources') {
                return response()->json(compact('leadSources'));
            }
        }

        // Lead Source ROI (admin only)
        if ($widget === 'lead_source_roi' && $user->isAdmin()) {
            $tenantId = $user->tenant_id;
            $costs = LeadSourceCost::where('tenant_id', $tenantId)->pluck('monthly_budget', 'lead_source');
            $sourceQuery = Lead::select('lead_source', DB::raw('count(*) as count'))->groupBy('lead_source');
            $leadSources = $sourceQuery->get();

            $roi = [];
            foreach ($leadSources as $source) {
                $budget = $costs[$source->lead_source] ?? 0;
                $closedFromSource = Deal::where('stage', 'closed_won')
                    ->whereHas('lead', fn($q) => $q->where('lead_source', $source->lead_source))
                    ->count();
                $roi[] = [
                    'source' => $source->lead_source,
                    'leads' => $source->count,
                    'closed' => $closedFromSource,
                    'budget' => $budget,
                    'cost_per_lead' => $source->count > 0 && $budget > 0 ? round($budget / $source->count, 2) : 0,
                    'cost_per_deal' => $closedFromSource > 0 && $budget > 0 ? round($budget / $closedFromSource, 2) : 0,
                ];
            }

            return response()->json(['leadSourceROI' => $roi]);
        }

        // Pipeline value by stage
        if (!$widget || $widget === 'pipeline') {
            $pipelineValue = Deal::whereNotIn('stage', ['closed_won', 'closed_lost'])
                ->select('stage', DB::raw('sum(contract_price) as total'));
            if ($user->isAgent()) $pipelineValue->where('agent_id', $user->id);
            $pipelineValue = $pipelineValue->groupBy('stage')->get();

            if ($widget === 'pipeline') {
                return response()->json(compact('pipelineValue'));
            }
        }

        // KPI data
        $leadQuery = Lead::query();
        $dealQuery = Deal::query();
        if ($user->isAgent()) {
            $leadQuery->where('agent_id', $user->id);
            $dealQuery->where('agent_id', $user->id);
        }

        $totalLeadsThisMonth = (clone $leadQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $totalLeadsLastMonth = (clone $leadQuery)->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
        $leadChange = $totalLeadsLastMonth > 0 ? round((($totalLeadsThisMonth - $totalLeadsLastMonth) / $totalLeadsLastMonth) * 100, 1) : 0;

        $activeDeals = (clone $dealQuery)->whereNotIn('stage', ['closed_won', 'closed_lost'])->count();
        $closedThisMonth = (clone $dealQuery)->where('stage', 'closed_won')->whereMonth('created_at', now()->month)->count();
        $feesThisMonth = (clone $dealQuery)->where('stage', 'closed_won')->whereMonth('created_at', now()->month)->sum(\App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column']);

        // Full response (backwards compatible)
        return response()->json([
            'leadSources' => $leadSources ?? [],
            'months' => $months ?? [],
            'leadsPerMonth' => $leadsPerMonth ?? [],
            'dealsPerMonth' => $dealsPerMonth ?? [],
            'pipelineValue' => $pipelineValue ?? [],
            'kpi' => [
                'totalLeadsThisMonth' => $totalLeadsThisMonth,
                'leadChange' => $leadChange,
                'activeDeals' => $activeDeals,
                'closedThisMonth' => $closedThisMonth,
                'feesThisMonth' => $feesThisMonth,
            ],
        ]);
    }

    /**
     * Export leads by source as CSV.
     */
    public function exportLeadsBySource(Request $request)
    {
        $from = $request->get('from', now()->subMonths(6)->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));

        $query = Lead::whereBetween('created_at', [$from, $to . ' 23:59:59']);
        if (auth()->user()->isAgent()) $query->where('agent_id', auth()->id());

        $leads = $query->select('lead_source', DB::raw('count(*) as count'))
            ->groupBy('lead_source')->get();

        return response()->streamDownload(function () use ($leads) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Source', 'Count']);
            foreach ($leads as $row) {
                fputcsv($handle, [ucfirst(str_replace('_', ' ', $row->lead_source)), $row->count]);
            }
            fclose($handle);
        }, 'leads-by-source.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export top agents as CSV.
     */
    public function exportTopAgents(Request $request)
    {
        $from = $request->get('from', now()->subMonths(6)->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));

        $agents = Deal::where('stage', 'closed_won')
            ->whereBetween('deals.created_at', [$from, $to . ' 23:59:59'])
            ->select('agent_id', DB::raw('count(*) as deals_closed'), DB::raw('sum(' . \App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column'] . ') as total_fees'))
            ->groupBy('agent_id')
            ->with('agent')
            ->orderByDesc('deals_closed')
            ->get();

        return response()->streamDownload(function () use ($agents) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Agent', 'Deals Closed', 'Total Fees']);
            foreach ($agents as $row) {
                fputcsv($handle, [$row->agent->name ?? '-', $row->deals_closed, \Fmt::currency($row->total_fees)]);
            }
            fclose($handle);
        }, 'top-agents.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export conversion funnel as CSV.
     */
    public function exportFunnel(Request $request)
    {
        $from = $request->get('from', now()->subMonths(6)->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));

        $isRealEstate = \App\Services\BusinessModeService::isRealEstate();
        $funnelStages = $isRealEstate
            ? ['new', 'inquiry', 'consultation', 'listing_signed', 'under_contract', 'closed_won']
            : ['new', 'contacted', 'negotiating', 'offer_presented', 'under_contract', 'closed_won'];
        $funnel = [];
        foreach ($funnelStages as $stage) {
            if ($stage === 'closed_won') {
                $funnel[$stage] = Deal::where('stage', $stage)->whereBetween('created_at', [$from, $to . ' 23:59:59'])->count();
            } else {
                $funnel[$stage] = Lead::where('status', $stage)->whereBetween('created_at', [$from, $to . ' 23:59:59'])->count();
            }
        }

        return response()->streamDownload(function () use ($funnel) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Stage', 'Count']);
            foreach ($funnel as $stage => $count) {
                fputcsv($handle, [ucfirst(str_replace('_', ' ', $stage)), $count]);
            }
            fclose($handle);
        }, 'conversion-funnel.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export team performance as CSV.
     */
    public function exportTeamPerformance(Request $request)
    {
        $from = $request->get('from', now()->subMonths(6)->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));

        $agentRoleIds = \App\Models\Role::whereIn('name', \App\Services\BusinessModeService::getAgentRoleNames())->pluck('id');
        $teamPerformance = User::where('tenant_id', auth()->user()->tenant_id)
            ->whereIn('role_id', $agentRoleIds)
            ->get()
            ->map(function ($agent) use ($from, $to) {
                $leadsContacted = Lead::where('agent_id', $agent->id)->where('status', '!=', 'new')->whereBetween('created_at', [$from, $to . ' 23:59:59'])->count();
                $dealsClosed = Deal::where('agent_id', $agent->id)->where('stage', 'closed_won')->whereBetween('deals.created_at', [$from, $to . ' 23:59:59'])->count();
                $feesGenerated = Deal::where('agent_id', $agent->id)->where('stage', 'closed_won')->whereBetween('deals.created_at', [$from, $to . ' 23:59:59'])->sum(\App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column']);
                return (object) compact('agent', 'leadsContacted', 'dealsClosed', 'feesGenerated');
            });

        return response()->streamDownload(function () use ($teamPerformance) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Agent', 'Leads Contacted', 'Deals Closed', 'Fees Generated']);
            foreach ($teamPerformance as $row) {
                fputcsv($handle, [$row->agent->name, $row->leadsContacted, $row->dealsClosed, \Fmt::currency($row->feesGenerated)]);
            }
            fclose($handle);
        }, 'team-performance.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export list stacking report as CSV.
     */
    public function exportListStacking()
    {
        $listStacking = DB::table('list_leads')
            ->join('leads', 'list_leads.lead_id', '=', 'leads.id')
            ->where('leads.tenant_id', auth()->user()->tenant_id)
            ->select('list_leads.lead_id', DB::raw('count(DISTINCT list_leads.list_id) as list_count'))
            ->groupBy('list_leads.lead_id')
            ->get();

        $stackDepth = [
            '1 List' => $listStacking->where('list_count', 1)->count(),
            '2 Lists' => $listStacking->where('list_count', 2)->count(),
            '3+ Lists' => $listStacking->where('list_count', '>=', 3)->count(),
        ];

        return response()->streamDownload(function () use ($stackDepth) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Stack Depth', 'Lead Count']);
            foreach ($stackDepth as $depth => $count) {
                fputcsv($handle, [$depth, $count]);
            }
            fclose($handle);
        }, 'list-stacking.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
