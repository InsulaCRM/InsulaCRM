<?php

namespace App\Http\Requests;

use App\Services\BusinessModeService;
use App\Services\CustomFieldService;
use Illuminate\Foundation\Http\FormRequest;

class PropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $propertyTypes = implode(',', CustomFieldService::getValidSlugs('property_type'));
        $conditions = implode(',', CustomFieldService::getValidSlugs('property_condition'));
        $distressMarkers = implode(',', CustomFieldService::getValidSlugs('distress_markers'));

        $rules = [
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:2',
            'zip_code' => 'required|string|max:10',
            'property_type' => "required|in:{$propertyTypes}",
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'square_footage' => 'nullable|integer|min:0',
            'year_built' => 'nullable|integer|min:1800|max:' . date('Y'),
            'lot_size' => 'nullable|numeric|min:0',
            'estimated_value' => 'nullable|numeric|min:0',
            'asking_price' => 'nullable|numeric|min:0',
            'condition' => "nullable|in:{$conditions}",
            'notes' => 'nullable|string',
        ];

        if (BusinessModeService::isRealEstate()) {
            $rules += [
                'list_price' => 'nullable|numeric|min:0',
                'listing_status' => 'nullable|in:active,pending,sold,withdrawn,expired',
                'listed_at' => 'nullable|date',
                'sold_at' => 'nullable|date',
                'sold_price' => 'nullable|numeric|min:0',
                'mls_number' => 'nullable|string|max:50',
            ];
        } else {
            $rules += [
                'repair_estimate' => 'nullable|numeric|min:0',
                'after_repair_value' => 'nullable|numeric|min:0|gte:repair_estimate',
                'our_offer' => 'nullable|numeric|min:0',
                'distress_markers' => 'nullable|array',
                'distress_markers.*' => "in:{$distressMarkers}",
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'after_repair_value.gte' => 'ARV must be greater than or equal to the repair estimate.',
        ];
    }
}
