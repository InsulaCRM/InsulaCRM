<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\Deal;
use App\Models\DealBuyerMatch;
use Illuminate\Support\Collection;

class BuyerMatchService
{
    /**
     * Match buyers/clients to a deal based on property criteria and store the results.
     * Scoring adapts to the tenant's business mode.
     */
    public function matchForDeal(Deal $deal): Collection
    {
        $lead = $deal->lead;
        $property = $lead?->property;

        $buyers = Buyer::where('tenant_id', $deal->tenant_id)->get();

        $isRealEstate = BusinessModeService::isRealEstate();

        $matches = $buyers->map(function (Buyer $buyer) use ($deal, $property, $isRealEstate) {
            $score = $isRealEstate
                ? $this->scoreRealEstate($buyer, $deal, $property)
                : $this->scoreWholesale($buyer, $deal, $property);

            return [
                'buyer' => $buyer,
                'score' => $score,
            ];
        })
            ->filter(fn ($match) => $match['score'] > 0)
            ->sortByDesc('score')
            ->values();

        // Upsert top matches into the deal_buyer_matches table
        foreach ($matches as $match) {
            DealBuyerMatch::updateOrCreate(
                [
                    'deal_id' => $deal->id,
                    'buyer_id' => $match['buyer']->id,
                ],
                [
                    'match_score' => $match['score'],
                ]
            );
        }

        return $matches;
    }

    /**
     * Wholesale mode: cash buyer matching based on investment criteria.
     */
    protected function scoreWholesale(Buyer $buyer, Deal $deal, $property): int
    {
        $score = 0;

        // +30 if property zip_code is in buyer's preferred zip codes
        if ($property && $property->zip_code && is_array($buyer->preferred_zip_codes)) {
            if (in_array($property->zip_code, $buyer->preferred_zip_codes)) {
                $score += 30;
            }
        }

        // +25 if property type matches buyer's preferred property types
        if ($property && $property->property_type && is_array($buyer->preferred_property_types)) {
            if (in_array($property->property_type, $buyer->preferred_property_types)) {
                $score += 25;
            }
        }

        // +20 if contract price <= buyer's max purchase price
        if ($deal->contract_price && $buyer->max_purchase_price) {
            if ($deal->contract_price <= $buyer->max_purchase_price) {
                $score += 20;
            }
        }

        // +15 if property state is in buyer's preferred states
        if ($property && $property->state && is_array($buyer->preferred_states)) {
            if (in_array($property->state, $buyer->preferred_states)) {
                $score += 15;
            }
        }

        return $score;
    }

    /**
     * Real estate mode: client matching based on preferences and budget.
     */
    protected function scoreRealEstate(Buyer $buyer, Deal $deal, $property): int
    {
        $score = 0;

        // +25 if listing price is within client's budget
        $listPrice = $property?->list_price ?? $deal->contract_price;
        if ($listPrice && $buyer->max_purchase_price) {
            if ($listPrice <= $buyer->max_purchase_price) {
                $score += 25;
            }
        }

        // +25 if property type matches client preferences
        if ($property && $property->property_type && is_array($buyer->preferred_property_types)) {
            if (in_array($property->property_type, $buyer->preferred_property_types)) {
                $score += 25;
            }
        }

        // +20 if property location matches preferred zip codes
        if ($property && $property->zip_code && is_array($buyer->preferred_zip_codes)) {
            if (in_array($property->zip_code, $buyer->preferred_zip_codes)) {
                $score += 20;
            }
        }

        // +15 if property state matches
        if ($property && $property->state && is_array($buyer->preferred_states)) {
            if (in_array($property->state, $buyer->preferred_states)) {
                $score += 15;
            }
        }

        return $score;
    }
}
