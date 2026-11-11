<?php

namespace App\Services;

use App\Models\Deal;

class DispositionPackageService
{
    public function build(Deal $deal): array
    {
        $deal->loadMissing(['lead.property','buyerMatches.buyer']);
        $property=$deal->lead?->property;

        $matches=$deal->buyerMatches
            ->filter(fn($m)=>is_array($m->underwriting_snapshot) && ($m->underwriting_snapshot['underwriting_pass'] ?? false))
            ->sortByDesc(fn($m)=>[$m->match_score, (float)($m->underwriting_snapshot['estimated_spread'] ?? -PHP_FLOAT_MAX)])
            ->values();

        $package=[
            'deal_id'=>$deal->id,
            'stage'=>$deal->stage,
            'transaction_structure'=>$deal->transaction_structure,
            'contract_price'=>$deal->contract_price !== null ? (float)$deal->contract_price : null,
            'assignment_fee'=>$deal->assignment_fee !== null ? (float)$deal->assignment_fee : null,
            'property'=>$property ? [
                'address'=>$property->full_address,
                'property_type'=>$property->property_type,
                'bedrooms'=>$property->bedrooms,
                'bathrooms'=>$property->bathrooms,
                'square_footage'=>$property->square_footage,
                'condition'=>$property->condition,
                'arv'=>$property->after_repair_value ?? $property->estimated_value,
                'repairs'=>$property->repair_estimate,
            ] : null,
            'buyer_matches'=>$matches->map(fn($m)=>[
                'match_id'=>$m->id,
                'buyer_id'=>$m->buyer_id,
                'buyer_name'=>$m->buyer?->company ?: trim(($m->buyer?->first_name ?? '').' '.($m->buyer?->last_name ?? '')),
                'match_score'=>$m->match_score,
                'match_reason'=>$m->match_reason,
                'underwriting'=>$m->underwriting_snapshot,
                'outreach_status'=>$m->outreach_status,
                'status'=>$m->status,
            ])->all(),
            'disposition_ready'=> $deal->stage === 'dispositions' && $matches->isNotEmpty(),
            'buyer_count'=> $matches->count(),
            'generated_at'=>now()->toISOString(),
        ];

        return $package;
    }
}
