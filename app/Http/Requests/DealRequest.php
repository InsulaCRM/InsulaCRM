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
