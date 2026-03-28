<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Property;
use App\Services\AddressNormalizationService;
use App\Services\AiService;
use App\Services\CustomFieldService;
use App\Services\LeadDistributionService;
use App\Services\MotivationScoreService;
use App\Services\ZipTimezoneService;
use Illuminate\Http\Request;
use App\Notifications\LeadAssigned;
use Illuminate\Support\Facades\Validator;

class LeadIngestController extends Controller
{
    /**
     * POST /api/v1/leads
     *
     * Ingest a lead from an external source.
     * Authenticated via the X-API-Key header.
     */
    public function store(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $validator = Validator::make($request->all(), [
            // Lead fields
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'source'         => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:5000',

            // Optional property fields
            'property_address' => 'nullable|string|max:255',
            'property_city'    => 'nullable|string|max:100',
            'property_state'   => 'nullable|string|max:2',
            'property_zip'     => 'nullable|string|max:10',
            'property_type'    => 'nullable|string|max:100',

            // Tracking
            'utm_source'     => 'nullable|string|max:100',
            'utm_medium'     => 'nullable|string|max:100',
            'utm_campaign'   => 'nullable|string|max:100',
            'external_id'    => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed.',
                'details' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Resolve lead source: explicit source > utm_source > 'api'
        $leadSource = $this->resolveLeadSource($data, $tenant);

        // Check for duplicate (same phone or email within tenant)
        $existing = $this->findDuplicate($data, $tenant);
        if ($existing) {
            return response()->json([
                'success' => true,
                'duplicate' => true,
                'message' => 'Lead already exists.',
                'lead_id' => $existing->id,
            ], 200);
        }

        // Create the lead
        $lead = Lead::withoutGlobalScopes()->create([
            'tenant_id'  => $tenant->id,
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'phone'      => $data['phone'] ?? null,
            'email'      => $data['email'] ?? null,
            'lead_source' => $leadSource,
            'status'     => 'new',
            'temperature' => 'warm',
            'notes'      => $this->buildNotes($data),
        ]);

        // Auto-detect timezone from property zip
        $zip = $data['property_zip'] ?? null;
        if ($zip) {
            $timezone = ZipTimezoneService::detect($zip);
            if ($timezone) {
                $lead->update(['timezone' => $timezone]);
            }
        }

        // Create property if address provided
        $property = null;
        if (!empty($data['property_address'])) {
            $propData = [
                'tenant_id' => $tenant->id,
                'lead_id'   => $lead->id,
                'address'   => $data['property_address'],
                'city'      => $data['property_city'] ?? null,
                'state'     => $data['property_state'] ?? null,
                'zip_code'  => $data['property_zip'] ?? null,
                'property_type' => $this->resolvePropertyType($data['property_type'] ?? null, $tenant),
            ];
            $propData = AddressNormalizationService::normalizeAll($propData);
            $property = Property::create($propData);
        }

        // AI auto-qualify temperature (queued)
        if ($tenant->ai_enabled) {
            \App\Jobs\AutoQualifyLead::dispatch($lead, $tenant);
        }

        // Auto-distribute to an agent
        try {
            app(LeadDistributionService::class)->distribute($lead, $tenant);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Lead distribution failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
        }

        // Calculate initial motivation score
        try {
            app(MotivationScoreService::class)->recalculate($lead);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Motivation score calculation failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
        }

        // Notify assigned agent
        $lead->refresh();
        if ($lead->agent_id && $tenant->wantsNotification('lead_assigned')) {
            $lead->load('agent');
            $lead->agent->notify(new LeadAssigned($lead, $tenant));
        }

        AuditLog::log('lead.created_via_api', $lead);

        \App\Services\WebhookService::dispatch('lead.created', [
            'lead_id' => $lead->id,
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'source' => $leadSource,
            'status' => 'new',
        ], $tenant->id);

        return response()->json([
            'success' => true,
            'lead_id' => $lead->id,
            'source'  => $leadSource,
            'property_id' => $property?->id,
        ], 201);
    }

    /**
     * GET /api/v1/leads
     *
     * List leads for the tenant.
     */
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $query = Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('property');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('lead_source', $request->source);
        }

        if ($request->filled('since')) {
            $query->where('created_at', '>=', $request->since);
        }

        $leads = $query->latest()
            ->paginate($request->integer('per_page', 25));

        return response()->json($leads);
    }

    /**
     * GET /api/v1/leads/{id}
     */
    public function show(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');

        $lead = Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('property')
            ->findOrFail($id);

        return response()->json($lead);
    }

    /**
     * PUT /api/v1/leads/{id}
     */
    public function update(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');

        $lead = Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name'    => 'nullable|string|max:255',
            'last_name'     => 'nullable|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:255',
            'lead_source'   => 'nullable|string|max:100',
            'status'        => 'nullable|in:' . implode(',', CustomFieldService::getValidSlugs('lead_status')),
            'temperature'   => 'nullable|in:hot,warm,cold',
            'do_not_contact' => 'nullable|boolean',
            'notes'         => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

        $lead->update($validator->validated());

        return response()->json(['success' => true, 'lead' => $lead->fresh()->load('property')]);
    }

    /**
     * Resolve lead source from request data.
     */
    private function resolveLeadSource(array $data, $tenant): string
    {
        // Explicit source parameter
        if (!empty($data['source'])) {
            $slug = str_replace(' ', '_', strtolower(trim($data['source'])));
            $valid = CustomFieldService::getValidSlugs('lead_source', $tenant);
            if (in_array($slug, $valid)) {
                return $slug;
            }
            // If not a recognized slug, still use it (will be stored as-is)
            return $slug;
        }

        // Fall back to utm_source
        if (!empty($data['utm_source'])) {
            $mapping = [
                'google'   => 'ppc',
                'facebook' => 'ppc',
                'fb'       => 'ppc',
                'bing'     => 'ppc',
                'email'    => 'direct_mail',
                'sms'      => 'cold_call',
                'referral' => 'referral',
            ];
            $utm = strtolower($data['utm_source']);
            return $mapping[$utm] ?? 'website';
        }

        return 'api';
    }

    /**
     * Build notes string with tracking info.
     */
    private function buildNotes(array $data): ?string
    {
        $parts = [];

        if (!empty($data['notes'])) {
            $parts[] = $data['notes'];
        }

        $tracking = [];
        if (!empty($data['utm_source'])) $tracking[] = "utm_source={$data['utm_source']}";
        if (!empty($data['utm_medium'])) $tracking[] = "utm_medium={$data['utm_medium']}";
        if (!empty($data['utm_campaign'])) $tracking[] = "utm_campaign={$data['utm_campaign']}";
        if (!empty($data['external_id'])) $tracking[] = "external_id={$data['external_id']}";

        if ($tracking) {
            $parts[] = '[API Tracking: ' . implode(', ', $tracking) . ']';
        }

        return $parts ? implode("\n", $parts) : null;
    }

    /**
     * Check for duplicate lead in the tenant.
     */
    private function findDuplicate(array $data, $tenant): ?Lead
    {
        if (!empty($data['phone'])) {
            $match = Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('phone', $data['phone'])
                ->first();
            if ($match) return $match;
        }

        if (!empty($data['email'])) {
            $match = Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('email', $data['email'])
                ->first();
            if ($match) return $match;
        }

        return null;
    }

    /**
     * Resolve property type slug.
     */
    private function resolvePropertyType(?string $type, $tenant): ?string
    {
        if (!$type) return null;

        $slug = str_replace(' ', '_', strtolower(trim($type)));
        $valid = CustomFieldService::getValidSlugs('property_type', $tenant);

        return in_array($slug, $valid) ? $slug : null;
    }
}
