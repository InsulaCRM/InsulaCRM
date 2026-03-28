<?php

namespace App\Http\Requests;

use App\Models\Showing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', Rule::exists('properties', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'lead_id' => ['nullable', Rule::exists('leads', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'deal_id' => ['nullable', Rule::exists('deals', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'agent_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', auth()->user()->tenant_id)],
            'showing_date' => 'required|date',
            'showing_time' => 'required',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'status' => ['nullable', Rule::in(array_keys(Showing::STATUSES))],
            'feedback' => 'nullable|string',
            'outcome' => ['nullable', Rule::in(array_keys(Showing::OUTCOMES))],
            'listing_agent_name' => 'nullable|string|max:255',
            'listing_agent_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ];
    }
}
