<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\AddressNormalizationService;
use App\Services\AiService;
use App\Services\LeadDistributionService;
use App\Services\ZipTimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebFormController extends Controller
{
    /**
     * Show an embeddable lead capture form.
     * GET /forms/{api_key}
     */
    public function show(string $apiKey)
    {
        $tenant = Tenant::where('api_key', $apiKey)
            ->where('api_enabled', true)
            ->where('status', 'active')
            ->firstOrFail();

        return view('forms.lead-capture', [
            'tenant' => $tenant,
            'apiKey' => $apiKey,
        ]);
    }

    /**
     * Handle web form submission.
     * POST /forms/{api_key}
     */
    public function submit(Request $request, string $apiKey)
    {
        $tenant = Tenant::where('api_key', $apiKey)
            ->where('api_enabled', true)
            ->where('status', 'active')
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:255',
            'property_address' => 'nullable|string|max:255',
            'property_city'    => 'nullable|string|max:100',
            'property_state'   => 'nullable|string|max:2',
            'property_zip'     => 'nullable|string|max:10',
            'message'          => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Determine source from UTM or default to 'website'
        $source = 'website';
        if ($request->filled('utm_source')) {
            $source = strtolower($request->utm_source) === 'google' ? 'ppc' : 'website';
        }

        // Build notes
        $notes = $data['message'] ?? '';
        $tracking = [];
        if ($request->filled('utm_source')) $tracking[] = "utm_source={$request->utm_source}";
        if ($request->filled('utm_medium')) $tracking[] = "utm_medium={$request->utm_medium}";
        if ($request->filled('utm_campaign')) $tracking[] = "utm_campaign={$request->utm_campaign}";
        if ($tracking) {
            $notes .= ($notes ? "\n" : '') . '[Web Form: ' . implode(', ', $tracking) . ']';
        }

        // Duplicate check
        $existing = null;
        if (!empty($data['phone'])) {
            $existing = Lead::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('phone', $data['phone'])->first();
        }
        if (!$existing && !empty($data['email'])) {
            $existing = Lead::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('email', $data['email'])->first();
        }

        if ($existing) {
            return view('forms.lead-capture-thanks', [
                'tenant' => $tenant,
                'message' => 'Thank you! We already have your information and will be in touch.',
            ]);
        }

        $lead = Lead::withoutGlobalScopes()->create([
            'tenant_id'   => $tenant->id,
            'first_name'  => $data['first_name'],
            'last_name'   => $data['last_name'],
            'phone'       => $data['phone'] ?? null,
            'email'       => $data['email'] ?? null,
            'lead_source' => $source,
            'status'      => 'new',
            'temperature' => 'warm',
            'notes'       => $notes ?: null,
        ]);

        // Create property if address provided
        if (!empty($data['property_address'])) {
            $propData = AddressNormalizationService::normalizeAll([
                'tenant_id' => $tenant->id,
                'lead_id'   => $lead->id,
                'address'   => $data['property_address'],
                'city'      => $data['property_city'] ?? null,
                'state'     => $data['property_state'] ?? null,
                'zip_code'  => $data['property_zip'] ?? null,
            ]);
            Property::create($propData);

            // Auto-detect timezone
            if (!empty($data['property_zip'])) {
                $tz = ZipTimezoneService::detect($data['property_zip']);
                if ($tz) $lead->update(['timezone' => $tz]);
            }
        }

        // AI Lead Qualification
        try {
            if ($tenant->ai_enabled) {
                $aiService = new AiService($tenant);
                $qualification = $aiService->qualifyLead($lead);
                if (!empty($qualification['temperature'])) {
                    $lead->update(['temperature' => $qualification['temperature']]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Web form AI qualification failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
        }

        // Auto-distribute
        try {
            app(LeadDistributionService::class)->distribute($lead, $tenant);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Web form lead distribution failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
        }

        AuditLog::log('lead.created_via_webform', $lead);

        return view('forms.lead-capture-thanks', [
            'tenant' => $tenant,
            'message' => 'Thank you! We\'ll be in touch shortly.',
        ]);
    }
}
