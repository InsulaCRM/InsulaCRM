<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Showing;
use App\Services\BusinessModeService;
use Illuminate\Http\Request;

class ListingDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $listingStages = ['listing_agreement', 'active_listing', 'showing', 'offer_received'];

        $query = Deal::with(['lead.property', 'agent'])
            ->whereIn('stage', $listingStages);

        if (!$user->isAdmin()) {
            $query->where('agent_id', $user->id);
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        if ($request->filled('agent')) {
            $query->where('agent_id', $request->agent);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('lead.property', fn ($pq) => $pq->where('address', 'like', "%{$search}%"));
            });
        }

        $listings = (clone $query)->latest('stage_changed_at')->paginate(25);

        // KPIs
        $activeCount = (clone $query)->count();
        $avgDom = (clone $query)->whereNotNull('days_on_market')->avg('days_on_market');
        $totalVolume = (clone $query)->whereHas('lead.property', fn ($q) => $q->whereNotNull('list_price'))
            ->get()
            ->sum(fn ($deal) => $deal->lead?->property?->list_price ?? 0);

        $showingsThisWeek = 0;
        $pendingOffers = 0;

        if (class_exists(Showing::class)) {
            $showingsQuery = Showing::where('status', 'scheduled')
                ->whereBetween('showing_date', [now()->startOfWeek(), now()->endOfWeek()]);
            if (!$user->isAdmin()) {
                $showingsQuery->where('agent_id', $user->id);
            }
            $showingsThisWeek = $showingsQuery->count();
        }

        if (class_exists(\App\Models\DealOffer::class)) {
            try {
                $offerQuery = \App\Models\DealOffer::where('status', 'pending');
                if (!$user->isAdmin()) {
                    $dealIds = Deal::where('agent_id', $user->id)->pluck('id');
                    $offerQuery->whereIn('deal_id', $dealIds);
                }
                $pendingOffers = $offerQuery->count();
            } catch (\Exception $e) {
                $pendingOffers = 0;
            }
        }

        $stages = BusinessModeService::getStages();
        $listingStageLabels = collect($listingStages)->mapWithKeys(fn ($s) => [$s => BusinessModeService::getStageLabel($s)]);

        $agents = collect();
        if ($user->isAdmin()) {
            $agents = \App\Models\User::where('tenant_id', $user->tenant_id)
                ->whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'agent', 'listing_agent', 'buyers_agent']))
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('listings.index', compact(
            'listings', 'activeCount', 'avgDom', 'totalVolume',
            'showingsThisWeek', 'pendingOffers', 'listingStageLabels', 'agents'
        ));
    }
}
