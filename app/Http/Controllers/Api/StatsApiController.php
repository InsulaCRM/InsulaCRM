<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Buyer;
use App\Models\Property;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsApiController extends Controller
{
    /**
     * GET /api/v1/stats
     *
     * Returns KPIs and summary statistics.
     */
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $tid = $tenant->id;
        $feeColumn = \App\Services\BusinessModeService::getDashboardKpiConfig($tenant)['fee_column'];

        $leadsThisMonth = Lead::withoutGlobalScopes()->where('tenant_id', $tid)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $leadsLastMonth = Lead::withoutGlobalScopes()->where('tenant_id', $tid)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $activeDeals = Deal::withoutGlobalScopes()->where('tenant_id', $tid)
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->count();

        $activePipelineValue = Deal::withoutGlobalScopes()->where('tenant_id', $tid)
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->sum('contract_price');

        $closedWonThisMonth = Deal::withoutGlobalScopes()->where('tenant_id', $tid)
            ->where('stage', 'closed_won')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $revenueThisMonth = Deal::withoutGlobalScopes()->where('tenant_id', $tid)
            ->where('stage', 'closed_won')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum($feeColumn);

        $totalLeads = Lead::withoutGlobalScopes()->where('tenant_id', $tid)->count();
        $totalBuyers = Buyer::withoutGlobalScopes()->where('tenant_id', $tid)->count();
        $totalProperties = Property::withoutGlobalScopes()->where('tenant_id', $tid)->count();

        // Pipeline by stage
        $pipeline = Deal::withoutGlobalScopes()->where('tenant_id', $tid)
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->select('stage', DB::raw('count(*) as count'), DB::raw('sum(contract_price) as total_value'), DB::raw("sum({$feeColumn}) as total_fees"))
            ->groupBy('stage')
            ->get();

        // Lead sources breakdown
        $sources = Lead::withoutGlobalScopes()->where('tenant_id', $tid)
            ->select('lead_source', DB::raw('count(*) as count'))
            ->groupBy('lead_source')
            ->get();

        // Monthly trends (last 6 months)
        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthly[] = [
                'month' => $date->format('Y-m'),
                'leads' => Lead::withoutGlobalScopes()->where('tenant_id', $tid)
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
                'deals_closed' => Deal::withoutGlobalScopes()->where('tenant_id', $tid)
                    ->where('stage', 'closed_won')
                    ->whereMonth('updated_at', $date->month)
                    ->whereYear('updated_at', $date->year)
                    ->count(),
                'revenue' => (float) Deal::withoutGlobalScopes()->where('tenant_id', $tid)
                    ->where('stage', 'closed_won')
                    ->whereMonth('updated_at', $date->month)
                    ->whereYear('updated_at', $date->year)
                    ->sum($feeColumn),
            ];
        }

        return response()->json([
            'kpi' => [
                'leads_this_month' => $leadsThisMonth,
                'leads_last_month' => $leadsLastMonth,
                'lead_change_pct' => $leadsLastMonth > 0 ? round((($leadsThisMonth - $leadsLastMonth) / $leadsLastMonth) * 100, 1) : 0,
                'active_deals' => $activeDeals,
                'active_pipeline_value' => (float) $activePipelineValue,
                'closed_won_this_month' => $closedWonThisMonth,
                'revenue_this_month' => (float) $revenueThisMonth,
            ],
            'totals' => [
                'leads' => $totalLeads,
                'buyers' => $totalBuyers,
                'properties' => $totalProperties,
            ],
            'pipeline' => $pipeline,
            'sources' => $sources,
            'monthly' => $monthly,
        ]);
    }
}
