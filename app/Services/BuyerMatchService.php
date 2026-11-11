<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\Deal;
use App\Models\DealBuyerMatch;
use Illuminate\Support\Collection;

class BuyerMatchService
{
    public function __construct(private BuyerBuyBoxService $buyBox) {}

    public function matchForDeal(Deal $deal): Collection
    {
        $lead=$deal->lead;
        $property=$lead?->property;
        $buyers=Buyer::where('tenant_id',$deal->tenant_id)->get();
        $isRealEstate=BusinessModeService::isRealEstate();

        $matches=$buyers->map(function(Buyer $buyer) use($deal,$property,$isRealEstate){
            $underwriting=$this->buyBox->underwrite($buyer,[
                'arv'=>$property?->after_repair_value ?? $property?->estimated_value,
                'repairs'=>$property?->repair_estimate ?? 0,
                'contract_price'=>$deal->contract_price,
                'assignment_fee'=>$deal->assignment_fee ?? 0,
                'condition'=>$property?->condition ?? 'unknown',
                'strategy'=>$property?->mao_strategy ?? 'flip',
            ]);

            $score=$isRealEstate
                ? $this->scoreRealEstate($buyer,$deal,$property)
                : $this->scoreWholesale($buyer,$deal,$property,$underwriting);

            $reason=$this->buildReason($buyer,$property,$underwriting,$score);

            return compact('buyer','score','underwriting','reason');
        })
        ->filter(fn($match)=>$match['score']>0 && ($match['underwriting']['status'] ?? null)==='calculated')
        ->sortByDesc('score')->values();

        $matchedBuyerIds = $matches->pluck('buyer.id')->all();

        // A deal can be re-matched after price, ARV, repairs, property, or
        // buyer buy-box changes. Remove stale matches so disposition never
        // surfaces a buyer who no longer passes underwriting.
        $staleMatches = DealBuyerMatch::withoutGlobalScopes()
            ->where('deal_id', $deal->id);

        if ($matchedBuyerIds) {
            $staleMatches->whereNotIn('buyer_id', $matchedBuyerIds);
        }

        $staleMatches->delete();

        foreach($matches as $match){
            DealBuyerMatch::updateOrCreate(
                ['deal_id'=>$deal->id,'buyer_id'=>$match['buyer']->id],
                [
                    'match_score'=>$match['score'],
                    'underwriting_snapshot'=>$match['underwriting'],
                    'match_reason'=>$match['reason'],
                ]
            );
        }

        return $matches;
    }

    protected function scoreWholesale(Buyer $buyer, Deal $deal, $property, array $underwriting): int
    {
        if (($underwriting['contract_price'] ?? null)!==null && !$underwriting['underwriting_pass']) return 0;

        $score=0;
        if($property?->zip_code && is_array($buyer->preferred_zip_codes) && in_array($property->zip_code,$buyer->preferred_zip_codes)) $score+=25;
        if($property?->property_type && is_array($buyer->preferred_property_types) && in_array($property->property_type,$buyer->preferred_property_types)) $score+=20;
        if($property?->state && is_array($buyer->preferred_states) && in_array(strtoupper($property->state),array_map('strtoupper',$buyer->preferred_states))) $score+=15;

        $strategy=$property?->mao_strategy ?? 'flip';
        if(is_array($buyer->preferred_strategies) && in_array($strategy,$buyer->preferred_strategies)) $score+=10;

        $condition=$property?->condition ?? 'unknown';
        if(is_array($buyer->preferred_conditions) && in_array($condition,$buyer->preferred_conditions)) $score+=10;

        if(($underwriting['underwriting_pass'] ?? false)) $score+=20;

        return $score;
    }

    protected function scoreRealEstate(Buyer $buyer, Deal $deal, $property): int
    {
        $score=0;
        $listPrice=$property?->list_price ?? $deal->contract_price;
        if($listPrice && $buyer->max_purchase_price && $listPrice <= $buyer->max_purchase_price) $score+=25;
        if($property?->property_type && is_array($buyer->preferred_property_types) && in_array($property->property_type,$buyer->preferred_property_types)) $score+=25;
        if($property?->zip_code && is_array($buyer->preferred_zip_codes) && in_array($property->zip_code,$buyer->preferred_zip_codes)) $score+=20;
        if($property?->state && is_array($buyer->preferred_states) && in_array(strtoupper($property->state),array_map('strtoupper',$buyer->preferred_states))) $score+=15;
        return $score;
    }

    private function buildReason(Buyer $buyer,$property,array $underwriting,int $score): string
    {
        $parts=[];
        if($property?->zip_code && is_array($buyer->preferred_zip_codes) && in_array($property->zip_code,$buyer->preferred_zip_codes)) $parts[]='ZIP match';
        if($property?->property_type && is_array($buyer->preferred_property_types) && in_array($property->property_type,$buyer->preferred_property_types)) $parts[]='property-type match';
        if(($underwriting['underwriting_pass'] ?? false)) {
            $ceiling=number_format((float)($underwriting['wholesale_contract_ceiling'] ?? 0),2);
            $parts[]="buyer underwriting supports up to {$ceiling}";
        } elseif (($underwriting['contract_price'] ?? null)!==null) {
            $parts[]='contract price exceeds buyer underwriting ceiling';
        }
        return $parts ? implode('; ',$parts)." (score {$score}/100)." : "General buyer match (score {$score}/100).";
    }
}
