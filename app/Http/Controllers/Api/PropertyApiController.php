<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Property;
use App\Services\AddressNormalizationService;
use App\Services\CustomFieldService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropertyApiController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $query = Property::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('lead');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('address', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%")
                  ->orWhere('zip_code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('property_type')) {
            $query->where('property_type', $request->property_type);
        }

        if ($request->filled('state')) {
            $query->where('state', strtoupper($request->state));
        }

        if ($request->filled('zip_code')) {
            $query->where('zip_code', $request->zip_code);
        }

        if ($request->filled('since')) {
            $query->where('created_at', '>=', $request->since);
        }

        return response()->json(
            $query->latest()->paginate($request->integer('per_page', 25))
        );
    }

    public function show(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');

        $property = Property::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('lead')
            ->findOrFail($id);

        return response()->json($property);
    }

    public function store(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $validator = Validator::make($request->all(), [
            'lead_id'            => 'nullable|integer',
            'address'            => 'required|string|max:255',
            'city'               => 'nullable|string|max:100',
            'state'              => 'nullable|string|max:2',
            'zip_code'           => 'nullable|string|max:10',
            'property_type'      => 'nullable|string|max:100',
            'bedrooms'           => 'nullable|integer|min:0',
            'bathrooms'          => 'nullable|numeric|min:0',
            'square_footage'     => 'nullable|integer|min:0',
            'year_built'         => 'nullable|integer|min:1800|max:2100',
            'lot_size'           => 'nullable|string|max:50',
            'estimated_value'    => 'nullable|numeric|min:0',
            'repair_estimate'    => 'nullable|numeric|min:0',
            'after_repair_value' => 'nullable|numeric|min:0',
            'asking_price'       => 'nullable|numeric|min:0',
            'our_offer'          => 'nullable|numeric|min:0',
            'condition'          => 'nullable|string|max:100',
            'distress_markers'   => 'nullable|array',
            'notes'              => 'nullable|string',
            'listing_status'     => 'nullable|string|max:50',
            'listed_at'          => 'nullable|date',
            'sold_at'            => 'nullable|date',
            'sold_price'         => 'nullable|numeric|min:0',
            'mls_number'         => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['tenant_id'] = $tenant->id;
        $data = AddressNormalizationService::normalizeAll($data);

        // Validate lead_id belongs to tenant
        if (!empty($data['lead_id'])) {
            \App\Models\Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->findOrFail($data['lead_id']);
        }

        // Compute MAO if applicable (wholesale mode only)
        if (!\App\Services\BusinessModeService::isRealEstate($tenant) && !empty($data['after_repair_value']) && !empty($data['repair_estimate'])) {
            $data['maximum_allowable_offer'] = ($data['after_repair_value'] * 0.70) - $data['repair_estimate'];
        }

        $property = Property::withoutGlobalScopes()->create($data);

        AuditLog::log('property.created_via_api', $property);

        return response()->json(['success' => true, 'property_id' => $property->id], 201);
    }

    public function update(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');

        $property = Property::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'address'            => 'nullable|string|max:255',
            'city'               => 'nullable|string|max:100',
            'state'              => 'nullable|string|max:2',
            'zip_code'           => 'nullable|string|max:10',
            'property_type'      => 'nullable|string|max:100',
            'bedrooms'           => 'nullable|integer|min:0',
            'bathrooms'          => 'nullable|numeric|min:0',
            'square_footage'     => 'nullable|integer|min:0',
            'year_built'         => 'nullable|integer|min:1800|max:2100',
            'lot_size'           => 'nullable|string|max:50',
            'estimated_value'    => 'nullable|numeric|min:0',
            'repair_estimate'    => 'nullable|numeric|min:0',
            'after_repair_value' => 'nullable|numeric|min:0',
            'asking_price'       => 'nullable|numeric|min:0',
            'our_offer'          => 'nullable|numeric|min:0',
            'condition'          => 'nullable|string|max:100',
            'distress_markers'   => 'nullable|array',
            'notes'              => 'nullable|string',
            'listing_status'     => 'nullable|string|max:50',
            'listed_at'          => 'nullable|date',
            'sold_at'            => 'nullable|date',
            'sold_price'         => 'nullable|numeric|min:0',
            'mls_number'         => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data = AddressNormalizationService::normalizeAll($data);

        if (!\App\Services\BusinessModeService::isRealEstate($tenant) && !empty($data['after_repair_value']) && !empty($data['repair_estimate'])) {
            $data['maximum_allowable_offer'] = ($data['after_repair_value'] * 0.70) - $data['repair_estimate'];
        }

        $property->update($data);

        return response()->json(['success' => true, 'property' => $property->fresh()]);
    }
}
