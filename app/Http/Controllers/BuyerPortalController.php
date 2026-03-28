<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\Deal;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class BuyerPortalController extends Controller
{
    /**
     * Show the public portal landing page for a tenant (buyer portal or client portal).
     */
    public function show(string $slug)
    {
        $tenant = Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        if (! $tenant->buyer_portal_enabled) {
            abort(404);
        }

        $properties = $this->getAvailableProperties($tenant);

        return view('buyer-portal.show', [
            'tenant' => $tenant,
            'properties' => $properties,
        ]);
    }

    /**
     * Handle buyer self-registration from the portal.
     */
    public function register(Request $request, string $slug)
    {
        $tenant = Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        if (! $tenant->buyer_portal_enabled) {
            abort(404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:20',
            'company'    => 'nullable|string|max:255',
            'max_purchase_price' => 'nullable|numeric|min:0',
            'preferred_property_types' => 'nullable|array',
            'preferred_property_types.*' => 'string|max:50',
            'preferred_zip_codes' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Check if buyer with this email already exists for this tenant
        $existingBuyer = Buyer::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $validated['email'])
            ->first();

        if ($existingBuyer) {
            return redirect()->route('buyer-portal.show', $slug)
                ->withInput()
                ->withErrors(['email' => __('A buyer with this email address is already registered.')]);
        }

        // Parse zip codes from textarea (comma or newline separated)
        $zipCodes = [];
        if (! empty($validated['preferred_zip_codes'])) {
            $zipCodes = array_values(array_filter(array_map(
                'trim',
                preg_split('/[\s,]+/', $validated['preferred_zip_codes'])
            )));
        }

        $buyer = Buyer::withoutGlobalScopes()->create([
            'tenant_id'               => $tenant->id,
            'first_name'              => $validated['first_name'],
            'last_name'               => $validated['last_name'],
            'email'                   => $validated['email'],
            'phone'                   => $validated['phone'] ?? null,
            'company'                 => $validated['company'] ?? null,
            'max_purchase_price'      => $validated['max_purchase_price'] ?? null,
            'preferred_property_types' => $validated['preferred_property_types'] ?? [],
            'preferred_zip_codes'     => $zipCodes,
            'notes'                   => $validated['notes'] ?? null,
        ]);

        // Notify tenant admin(s) about the new buyer registration
        $admins = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereHas('role', fn ($q) => $q->where('slug', 'admin'))
            ->get();

        foreach ($admins as $admin) {
            $admin->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\BuyerPortalRegistration',
                'data' => [
                    'message' => __('New buyer registered via portal: :name (:email)', [
                        'name' => $buyer->full_name,
                        'email' => $buyer->email,
                    ]),
                    'buyer_id' => $buyer->id,
                    'url' => route('buyers.show', $buyer->id),
                    'icon' => 'user-plus',
                    'color' => 'bg-green-lt',
                ],
            ]);
        }

        return redirect()->route('buyer-portal.registered', $slug);
    }

    /**
     * AJAX endpoint returning available properties as JSON for filtering.
     */
    public function properties(string $slug)
    {
        $tenant = Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        if (! $tenant->buyer_portal_enabled) {
            return response()->json(['error' => 'Portal not enabled'], 404);
        }

        $properties = $this->getAvailableProperties($tenant);

        return response()->json([
            'properties' => $properties->map(function ($property) {
                return [
                    'id'            => $property->id,
                    'address'       => $property->address,
                    'city'          => $property->city,
                    'state'         => $property->state,
                    'zip_code'      => $property->zip_code,
                    'property_type' => $property->property_type,
                    'bedrooms'      => $property->bedrooms,
                    'bathrooms'     => $property->bathrooms,
                    'square_footage' => $property->square_footage,
                    'year_built'    => $property->year_built,
                    'estimated_value' => $property->estimated_value,
                    'condition'     => $property->condition,
                ];
            }),
        ]);
    }

    /**
     * Show the registration confirmation page.
     */
    public function registered(string $slug)
    {
        $tenant = Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        if (! $tenant->buyer_portal_enabled) {
            abort(404);
        }

        return view('buyer-portal.registered', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Fetch available properties from deals in dispositions or assigned stage.
     */
    private function getAvailableProperties(Tenant $tenant)
    {
        $inventoryStages = \App\Services\BusinessModeService::isRealEstate($tenant)
            ? ['active_listing', 'showing', 'offer_received']
            : ['dispositions', 'assigned'];

        $dealIds = Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereIn('stage', $inventoryStages)
            ->pluck('lead_id');

        return Property::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereIn('lead_id', $dealIds)
            ->get();
    }
}
