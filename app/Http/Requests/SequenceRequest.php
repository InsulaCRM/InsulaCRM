<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'steps' => 'required|array|min:1',
            'steps.*.order' => 'required|integer|min:1',
            'steps.*.delay_days' => 'required|integer|min:0',
            'steps.*.action_type' => 'required|string|max:255',
            'steps.*.message_template' => 'nullable|string',
        ];
    }
}
