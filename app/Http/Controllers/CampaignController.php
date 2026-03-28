<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Deal;
use App\Models\Lead;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::with('createdBy');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $campaigns = $query->latest()->paginate(20);

        // Preload stats for each campaign to avoid N+1
        $feeColumn = \App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column'];
        $campaignIds = $campaigns->pluck('id');
        $leadCounts = Lead::whereIn('campaign_id', $campaignIds)
            ->selectRaw('campaign_id, COUNT(*) as count')
            ->groupBy('campaign_id')
            ->pluck('count', 'campaign_id');

        $leadIdsByCampaign = Lead::whereIn('campaign_id', $campaignIds)
            ->select('id', 'campaign_id')
            ->get()
            ->groupBy('campaign_id');

        $allLeadIds = $leadIdsByCampaign->flatten()->pluck('id');

        $dealCounts = [];
        $revenues = [];

        if ($allLeadIds->isNotEmpty()) {
            $dealStats = Deal::whereIn('lead_id', $allLeadIds)
                ->join('leads', 'deals.lead_id', '=', 'leads.id')
                ->selectRaw("leads.campaign_id, COUNT(*) as deal_count, SUM(CASE WHEN deals.stage = ? THEN deals.{$feeColumn} ELSE 0 END) as revenue", ['closed_won'])
                ->groupBy('leads.campaign_id')
                ->get()
                ->keyBy('campaign_id');

            foreach ($dealStats as $campaignId => $stat) {
                $dealCounts[$campaignId] = $stat->deal_count;
                $revenues[$campaignId] = (float) $stat->revenue;
            }
        }

        return view('campaigns.index', compact(
            'campaigns',
            'leadCounts',
            'dealCounts',
            'revenues'
        ));
    }

    public function create()
    {
        return view('campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:direct_mail,ppc,cold_call,bandit_sign,seo,social,email,ringless_voicemail,other',
            'status' => 'required|string|in:draft,active,paused,completed',
            'budget' => 'nullable|numeric|min:0',
            'actual_spend' => 'nullable|numeric|min:0',
            'target_count' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['created_by'] = auth()->id();
        $validated['actual_spend'] = $validated['actual_spend'] ?? 0;
        $validated['target_count'] = $validated['target_count'] ?? 0;

        $campaign = Campaign::create($validated);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', __('Campaign created successfully.'));
    }

    public function show(Campaign $campaign)
    {
        $campaign->load('createdBy');

        // KPI calculations
        $feeColumn = \App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column'];
        $leadCount = $campaign->leads()->count();
        $leadIds = $campaign->leads()->pluck('id');

        $dealCount = 0;
        $closedDealCount = 0;
        $revenue = 0;

        if ($leadIds->isNotEmpty()) {
            $dealCount = Deal::whereIn('lead_id', $leadIds)->count();
            $closedDealCount = Deal::whereIn('lead_id', $leadIds)->where('stage', 'closed_won')->count();
            $revenue = (float) Deal::whereIn('lead_id', $leadIds)->where('stage', 'closed_won')->sum($feeColumn);
        }

        $spend = (float) $campaign->actual_spend;
        $roi = ($spend > 0) ? round(($revenue - $spend) / $spend * 100, 2) : null;
        $costPerLead = ($leadCount > 0) ? round($spend / $leadCount, 2) : null;

        // Lead status breakdown for chart
        $statusBreakdown = $campaign->leads()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Lead temperature breakdown for chart
        $temperatureBreakdown = $campaign->leads()
            ->selectRaw('temperature, COUNT(*) as count')
            ->groupBy('temperature')
            ->pluck('count', 'temperature')
            ->toArray();

        // Paginated leads table
        $leads = $campaign->leads()
            ->with('agent')
            ->latest()
            ->paginate(15);

        return view('campaigns.show', compact(
            'campaign',
            'leadCount',
            'dealCount',
            'closedDealCount',
            'revenue',
            'roi',
            'costPerLead',
            'spend',
            'statusBreakdown',
            'temperatureBreakdown',
            'leads'
        ));
    }

    public function edit(Campaign $campaign)
    {
        return view('campaigns.edit', compact('campaign'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:direct_mail,ppc,cold_call,bandit_sign,seo,social,email,ringless_voicemail,other',
            'status' => 'required|string|in:draft,active,paused,completed',
            'budget' => 'nullable|numeric|min:0',
            'actual_spend' => 'nullable|numeric|min:0',
            'target_count' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $validated['actual_spend'] = $validated['actual_spend'] ?? 0;
        $validated['target_count'] = $validated['target_count'] ?? 0;

        $campaign->update($validated);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', __('Campaign updated successfully.'));
    }

    public function destroy(Campaign $campaign)
    {
        // Nullify campaign_id on related leads before deleting
        Lead::where('campaign_id', $campaign->id)->update(['campaign_id' => null]);

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', __('Campaign deleted successfully.'));
    }
}
