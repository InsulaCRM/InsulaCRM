<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ComparableSale;
use App\Models\Property;
use App\Services\BusinessModeService;
use Illuminate\Http\Request;

class ComparableSaleController extends Controller
{
    /**
     * Store a new comparable sale for a property.
     */
    public function store(Request $request, Property $property)
    {
        $this->authorize('view', $property);

        $data = $request->validate([
            'address' => 'required|string|max:255',
            'sale_price' => 'required|numeric|min:0',
            'sale_date' => 'required|date',
            'sqft' => 'nullable|integer|min:0',
            'beds' => 'nullable|integer|min:0',
            'baths' => 'nullable|numeric|min:0',
            'lot_size' => 'nullable|numeric|min:0',
            'year_built' => 'nullable|integer|min:1800|max:' . (date('Y') + 1),
            'distance_miles' => 'nullable|numeric|min:0',
            'condition' => 'nullable|string|max:30',
            'adjustments' => 'nullable|array',
            'adjustments.*' => 'numeric',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['property_id'] = $property->id;
        $data['adjustments'] = $data['adjustments'] ?? [];

        // Auto-calculate adjusted price
        $adjustmentTotal = array_sum(array_values($data['adjustments']));
        $data['adjusted_price'] = (float) $data['sale_price'] + $adjustmentTotal;

        $comp = ComparableSale::create($data);

        AuditLog::log('comparable_sale.created', $comp);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Comparable sale added.'),
                'comp' => $comp,
            ]);
        }

        return redirect()->route('properties.show', $property)->with('success', __('Comparable sale added.'));
    }

    /**
     * Update an existing comparable sale.
     */
    public function update(Request $request, ComparableSale $comp)
    {
        $property = $comp->property;
        $this->authorize('view', $property);

        $data = $request->validate([
            'address' => 'required|string|max:255',
            'sale_price' => 'required|numeric|min:0',
            'sale_date' => 'required|date',
            'sqft' => 'nullable|integer|min:0',
            'beds' => 'nullable|integer|min:0',
            'baths' => 'nullable|numeric|min:0',
            'lot_size' => 'nullable|numeric|min:0',
            'year_built' => 'nullable|integer|min:1800|max:' . (date('Y') + 1),
            'distance_miles' => 'nullable|numeric|min:0',
            'condition' => 'nullable|string|max:30',
            'adjustments' => 'nullable|array',
            'adjustments.*' => 'numeric',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data['adjustments'] = $data['adjustments'] ?? [];

        // Recalculate adjusted price
        $adjustmentTotal = array_sum(array_values($data['adjustments']));
        $data['adjusted_price'] = (float) $data['sale_price'] + $adjustmentTotal;

        $comp->update($data);

        AuditLog::log('comparable_sale.updated', $comp);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Comparable sale updated.'),
                'comp' => $comp->fresh(),
            ]);
        }

        return redirect()->route('properties.show', $property)->with('success', __('Comparable sale updated.'));
    }

    /**
     * Delete a comparable sale.
     */
    public function destroy(ComparableSale $comp)
    {
        $property = $comp->property;
        $this->authorize('view', $property);

        AuditLog::log('comparable_sale.deleted', $comp);

        $comp->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Comparable sale removed.'),
            ]);
        }

        return redirect()->route('properties.show', $property)->with('success', __('Comparable sale removed.'));
    }

    /**
     * Get ARV summary data (AJAX).
     */
    public function arvSummary(Property $property)
    {
        $this->authorize('view', $property);

        $comps = ComparableSale::where('property_id', $property->id)->get();

        if ($comps->isEmpty()) {
            return response()->json([
                'avg_arv' => 0,
                'median_arv' => 0,
                'comp_count' => 0,
                'mao_70' => 0,
                'mao_75' => 0,
                'spread' => 0,
            ]);
        }

        $adjustedPrices = $comps->pluck('adjusted_price')->sort()->values();
        $compCount = $adjustedPrices->count();
        $avgArv = $adjustedPrices->avg();
        $repairEstimate = (float) ($property->repair_estimate ?? 0);

        // Calculate median
        if ($compCount % 2 === 0) {
            $medianArv = ($adjustedPrices[$compCount / 2 - 1] + $adjustedPrices[$compCount / 2]) / 2;
        } else {
            $medianArv = $adjustedPrices[intdiv($compCount, 2)];
        }

        // MAO calculations
        $mao70 = ($avgArv * 0.70) - $repairEstimate;
        $mao75 = ($avgArv * 0.75) - $repairEstimate;

        // Spread (range)
        $spread = $adjustedPrices->max() - $adjustedPrices->min();

        $data = [
            'avg_arv' => round($avgArv, 2),
            'median_arv' => round($medianArv, 2),
            'comp_count' => $compCount,
            'mao_70' => round($mao70, 2),
            'mao_75' => round($mao75, 2),
            'spread' => round($spread, 2),
            'repair_estimate' => $repairEstimate,
            'asking_price' => (float) ($property->asking_price ?? 0),
        ];

        // Add CMA-specific fields for real estate mode
        if (BusinessModeService::isRealEstate()) {
            $data['suggested_list_low'] = round($medianArv * 0.97, 2);
            $data['suggested_list_high'] = round($medianArv * 1.03, 2);

            // Price per sqft: avg adjusted_price / avg sqft (only if sqft data available)
            $compSqfts = $comps->pluck('sqft')->filter();
            if ($compSqfts->count() > 0) {
                $avgSqft = $compSqfts->avg();
                $data['price_per_sqft'] = $avgSqft > 0 ? round($avgArv / $avgSqft, 2) : 0;
            } else {
                $data['price_per_sqft'] = 0;
            }
        }

        return response()->json($data);
    }
}
