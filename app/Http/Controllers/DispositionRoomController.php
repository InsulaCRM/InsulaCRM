<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealBuyerMatch;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DispositionRoomController extends Controller
{
    public function show(Deal $deal)
    {
        $this->authorize('view', $deal);

        $deal->load(['lead.property', 'agent']);

        $matchesQuery = DealBuyerMatch::where('deal_id', $deal->id)
            ->with('buyer')
            ->orderByDesc('match_score');

        $matches = $matchesQuery->get();

        return view('deals.disposition', compact('deal', 'matches'));
    }

    public function updateStatus(Request $request, DealBuyerMatch $match)
    {
        // Authorize via parent deal — DealBuyerMatch has no tenant_id column
        $deal = $match->deal;
        if (!$deal || $deal->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        $this->authorize('view', $deal);

        $request->validate([
            'outreach_status' => 'required|in:pending,contacted,interested,passed,offered,closed',
            'buyer_notes' => 'nullable|string|max:2000',
        ]);

        $match->update([
            'outreach_status' => $request->outreach_status,
            'buyer_notes' => $request->buyer_notes,
            'last_contacted_at' => in_array($request->outreach_status, ['contacted', 'interested', 'offered'])
                ? now() : $match->last_contacted_at,
        ]);

        return response()->json(['success' => true]);
    }

    public function massOutreach(Request $request, Deal $deal)
    {
        $this->authorize('view', $deal);

        $request->validate([
            'channel' => 'required|in:email,sms',
            'match_ids' => 'nullable|array',
            'match_ids.*' => 'integer',
        ]);

        $query = DealBuyerMatch::where('deal_id', $deal->id)
            ->where('outreach_status', 'pending')
            ->with('buyer');

        if ($request->filled('match_ids')) {
            $query->whereIn('id', $request->match_ids);
        }

        $matches = $query->get();
        $deal->load('lead.property');
        $property = $deal->lead?->property;
        $count = 0;

        foreach ($matches as $match) {
            $buyer = $match->buyer;
            if (!$buyer) continue;

            if ($request->channel === 'email' && $buyer->email) {
                Mail::raw(
                    __("Hi :name,\n\nWe have a new deal that matches your criteria: :title\n\nProperty: :address\nContract Price: :price\n\nPlease let us know if you're interested.", [
                        'name' => $buyer->first_name,
                        'title' => $deal->title,
                        'address' => $property?->address ?? 'N/A',
                        'price' => $deal->contract_price ? \Fmt::currency($deal->contract_price) : 'N/A',
                    ]),
                    function ($message) use ($buyer, $deal) {
                        $message->to($buyer->email)
                            ->subject(__('New Deal Opportunity: :title', ['title' => $deal->title]));
                    }
                );
                $count++;
            } elseif ($request->channel === 'sms' && $buyer->phone) {
                try {
                    app(SmsService::class)->send(
                        $buyer->phone,
                        __("New deal: :title at :address. Interested? Reply YES.", [
                            'title' => $deal->title,
                            'address' => $property?->address ?? 'N/A',
                        ])
                    );
                    $count++;
                } catch (\Exception $e) {
                    // Continue with other buyers
                }
            }

            $match->update([
                'outreach_status' => 'contacted',
                'last_contacted_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __(':count buyer(s) contacted via :channel.', ['count' => $count, 'channel' => $request->channel]),
        ]);
    }
}
