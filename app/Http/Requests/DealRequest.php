<?php

namespace App\Http\Requests;

use App\Models\Deal;
use Illuminate\Foundation\Http\FormRequest;

class DealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'property_state' => 'nullable|string|size:2',
            'property_county' => 'nullable|string|max:255',
            'buyer_type' => 'nullable|in:individual,entity',
            'buyer_legal_name' => 'nullable|string|max:255',
            'transaction_structure' => 'nullable|in:assignment,double_close,novation',
            'attorney_approved_template_id' => 'nullable|string|max:255',
            'executed_contract_status' => 'nullable|in:not_executed,pending_review,executed',
            'assignment_rights_confirmed' => 'nullable|boolean',
            'emd_required' => 'nullable|boolean',
            'emd_source' => 'nullable|in:wholesaler,end_buyer,other_approved',
            'buyer_covers_emd' => 'nullable|boolean',
            'emd_funding_confirmed' => 'nullable|boolean',
            'emd_exception_reason' => 'nullable|string|max:500',
            'closing_provider_type' => 'nullable|in:closing_attorney,title_company,escrow_company,settlement_company,other_permitted',
            'buyer_preferred_closing_provider' => 'nullable|string|max:255',
            'seller_preferred_closing_provider' => 'nullable|string|max:255',
            'confirmed_closing_provider' => 'nullable|string|max:255',
            'contract_review_status' => 'nullable|in:pending,approved,needs_attention',
            'no_buyer_status' => 'nullable|in:not_applicable,active,review,extension_review,termination_review',
            'extension_termination_review_status' => 'nullable|in:not_needed,pending,approved',
            'stage' => 'sometimes|required|in:' . implode(',', array_keys(Deal::stages())),
            'contract_price' => 'nullable|numeric|min:0',
            'assignment_fee' => 'nullable|numeric|min:0',
            'earnest_money' => 'nullable|numeric|min:0',
            'inspection_period_days' => 'nullable|integer|min:0',
            'listing_commission_pct' => 'nullable|numeric|min:0|max:100',
            'buyer_commission_pct' => 'nullable|numeric|min:0|max:100',
            'total_commission' => 'nullable|numeric|min:0',
            'brokerage_split_pct' => 'nullable|numeric|min:0|max:100',
            'mls_number' => 'nullable|string|max:30',
            'listing_date' => 'nullable|date',
            'days_on_market' => 'nullable|integer|min:0',
            'contract_date' => 'nullable|date',
            'closing_date' => 'nullable|date',
            'due_diligence_end_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
