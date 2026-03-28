<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistributionSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'distribution_method' => 'required|in:round_robin,shark_tank,hybrid,ai_smart',
            'claim_window_minutes' => 'nullable|integer|min:1|max:30',
            'timezone_restriction_enabled' => 'nullable|boolean',
        ];
    }
}
