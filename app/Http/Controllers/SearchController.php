<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            if ($request->expectsJson()) {
                return response()->json(['results' => []]);
            }
            return view('search.results', ['query' => $q, 'results' => collect()]);
        }

        $user = auth()->user();
        $results = collect();

        // Search leads (if user can manage leads)
        if ($user->canManageLeads()) {
            $leads = Lead::where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->when(!$user->isAdmin(), fn ($query) => $query->where('agent_id', $user->id))
            ->limit(5)
            ->get()
            ->map(fn ($lead) => [
                'type' => 'lead',
                'title' => $lead->full_name,
                'subtitle' => $lead->email ?? $lead->phone ?? '',
                'url' => route('leads.show', $lead),
            ]);

            $results = $results->merge($leads);
        }

        // Search deals
        if (!$user->isFieldScout()) {
            $deals = Deal::where(function ($outer) use ($q) {
                $outer->whereHas('lead', function ($query) use ($q) {
                    $query->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%");
                })
                ->orWhere('title', 'like', "%{$q}%")
                ->orWhere('notes', 'like', "%{$q}%");
            })
            ->when(!$user->isAdmin(), fn ($query) => $query->where('agent_id', $user->id))
            ->limit(5)
            ->get()
            ->map(fn ($deal) => [
                'type' => 'deal',
                'title' => $deal->lead->full_name ?? $deal->title,
                'subtitle' => \App\Models\Deal::stageLabel($deal->stage),
                'url' => route('deals.show', $deal),
            ]);

            $results = $results->merge($deals);
        }

        // Search buyers (if user can manage buyers)
        if ($user->canManageBuyers()) {
            $buyers = Buyer::where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get()
            ->map(fn ($buyer) => [
                'type' => 'buyer',
                'title' => $buyer->full_name,
                'subtitle' => $buyer->company ?? $buyer->email ?? '',
                'url' => route('buyers.show', $buyer),
            ]);

            $results = $results->merge($buyers);
        }

        // Search properties
        $properties = Property::where(function ($query) use ($q) {
            $query->where('address', 'like', "%{$q}%")
                ->orWhere('city', 'like', "%{$q}%")
                ->orWhere('zip_code', 'like', "%{$q}%");
        })
        ->limit(5)
        ->get()
        ->map(fn ($property) => [
            'type' => 'property',
            'title' => $property->address,
            'subtitle' => trim(($property->city ?? '') . ', ' . ($property->state ?? '') . ' ' . ($property->zip_code ?? ''), ', '),
            'url' => route('properties.show', $property),
        ]);

        $results = $results->merge($properties);

        if ($request->expectsJson()) {
            return response()->json(['results' => $results->values()]);
        }

        return view('search.results', ['query' => $q, 'results' => $results]);
    }
}
