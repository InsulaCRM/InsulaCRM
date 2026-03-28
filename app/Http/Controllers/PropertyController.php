<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyRequest;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Property;
use App\Services\AddressNormalizationService;
use App\Services\CustomFieldService;
use App\Services\ZipTimezoneService;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Property::class);

        $query = Property::with('lead');

        if (auth()->user()->isAgent()) {
            $query->whereHas('lead', function ($q) {
                $q->where('agent_id', auth()->id());
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('zip_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('property_type')) {
            $query->where('property_type', $request->property_type);
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        if ($request->filled('distress')) {
            $distressFilters = (array) $request->distress;
            foreach ($distressFilters as $marker) {
                $query->whereJsonContains('distress_markers', $marker);
            }
        }

        if ($request->filled('listing_status')) {
            $query->where('listing_status', $request->listing_status);
        }

        $properties = $query->latest()->paginate(25);

        return view('properties.index', compact('properties'));
    }

    /**
     * Store a property for a lead (AJAX or form).
     */
    public function store(PropertyRequest $request, Lead $lead)
    {
        $this->authorize('update', $lead);
        $this->authorize('create', Property::class);

        $data = $request->validated();
        $data = AddressNormalizationService::normalizeAll($data);
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['lead_id'] = $lead->id;

        // Compute MAO: (ARV x 0.70) - Repair Estimate (wholesale only)
        if (!\App\Services\BusinessModeService::isRealEstate()
            && !empty($data['after_repair_value']) && !empty($data['repair_estimate'])) {
            $data['maximum_allowable_offer'] = ($data['after_repair_value'] * 0.70) - $data['repair_estimate'];
        }

        $property = Property::updateOrCreate(
            ['lead_id' => $lead->id],
            $data
        );

        AuditLog::log('property.created', $property);

        // Auto-detect lead timezone from property zip code
        $timezone = ZipTimezoneService::detect($property->zip_code);
        if ($timezone && !$lead->timezone) {
            $lead->update(['timezone' => $timezone]);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'property' => $property]);
        }

        return redirect()->route('leads.show', $lead)->with('success', 'Property saved successfully.');
    }

    /**
     * Store a property submitted by a field scout (standalone, no lead required).
     */
    public function fieldScoutStore(Request $request)
    {
        $this->authorize('createFieldScout', Property::class);

        $data = $request->validate([
            'address'          => 'required|string|max:255',
            'city'             => 'required|string|max:100',
            'state'            => 'required|string|max:2',
            'zip_code'         => 'required|string|max:10',
            'property_type'    => 'required|in:' . implode(',', CustomFieldService::getValidSlugs('property_type')),
            'distress_markers' => 'nullable|array',
            'distress_markers.*' => 'string|in:' . implode(',', CustomFieldService::getValidSlugs('distress_markers')),
            'notes'            => 'nullable|string|max:2000',
        ]);

        $data = AddressNormalizationService::normalizeAll($data);
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['distress_markers'] = $data['distress_markers'] ?? [];

        $property = Property::create($data);

        AuditLog::log('property.created', $property);

        return redirect()->route('dashboard')->with('success', 'Property submitted successfully.');
    }

    /**
     * Show a property detail page.
     */
    public function show(Property $property)
    {
        $this->authorize('view', $property);
        $property->load('lead');

        return view('properties.show', compact('property'));
    }
}
