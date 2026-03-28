<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'due_date' => 'required|date|after_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a task title.',
            'due_date.required' => 'Please select a due date.',
            'due_date.after_or_equal' => 'Due date must be today or later.',
        ];
    }
}
