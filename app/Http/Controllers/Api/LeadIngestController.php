<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Property;
use App\Models\Deal;
use App\Services\AddressNormalizationService;
use App\Services\AiService;
use App\Services\CustomFieldService;
use App\Services\LeadDistributionService;
use App\Services\MotivationScoreService;
use App\Services\AcquisitionPriorityService;
use App\Services\ZipTimezoneService;
use Illuminate\Http\Request;
use App\Notifications\LeadAssigned;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

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
            'property_county'  => 'nullable|string|max:100',
            'property_state'   => 'nullable|string|max:2',
            'property_zip'     => 'nullable|string|max:10',
            'property_type'    => 'nullable|string|max:100',
            'property_condition' => 'nullable|string|max:100',
            'property_estimated_value' => 'nullable|numeric|min:0',
            'property_repair_estimate' => 'nullable|numeric|min:0',
            'property_after_repair_value' => 'nullable|numeric|min:0',
            'property_asking_price' => 'nullable|numeric|min:0',
            'property_distress_markers' => 'nullable|array',
            'property_distress_markers.*' => 'string|max:100',
            'property_bedrooms' => 'nullable|integer|min:0',
            'property_bathrooms' => 'nullable|numeric|min:0',
            'property_square_footage' => 'nullable|integer|min:0',

            // Wholesale seller intelligence can arrive directly from list/data providers.
            'distress_markers' => 'nullable|array',
            'distress_markers.*' => 'string|max:100',
            'list_types' => 'nullable|array',
            'list_types.*' => 'string|max:100',
            'high_equity' => 'nullable|boolean',
            'vacant' => 'nullable|boolean',
            'absentee_owner' => 'nullable|boolean',
            'tax_delinquent' => 'nullable|boolean',
            'probate' => 'nullable|boolean',
            'pre_foreclosure' => 'nullable|boolean',
            'code_violation' => 'nullable|boolean',
            'tired_landlord' => 'nullable|boolean',

            // Tracking
            'utm_source'     => 'nullable|string|max:100',
            'utm_medium'     => 'nullable|string|max:100',
            'utm_campaign'   => 'nullable|string|max:100',
            'external_id'    => 'nullable|string|max:255',
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed.',
                'details' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Normalize identity fields before duplicate detection so formatting
        // differences do not create duplicate seller records.
        if (isset($data['phone']) && $data['phone'] !== null) {
            $data['phone'] = trim($data['phone']);
        }
        if (isset($data['email']) && $data['email'] !== null) {
            $data['email'] = strtolower(trim($data['email']));
        }

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
            'custom_fields' => array_merge(
                $data['custom_fields'] ?? [],
                !empty($data['external_id']) ? ['external_id' => $data['external_id']] : [],
                $this->wholesaleSignalFields($data)
            ),
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
                'county'    => $data['property_county'] ?? null,
                'state'     => $data['property_state'] ?? null,
                'zip_code'  => $data['property_zip'] ?? null,
                'property_type' => $this->resolvePropertyType($data['property_type'] ?? null, $tenant),
                'condition' => $data['property_condition'] ?? null,
                'estimated_value' => $data['property_estimated_value'] ?? null,
                'repair_estimate' => $data['property_repair_estimate'] ?? null,
                'after_repair_value' => $data['property_after_repair_value'] ?? null,
                'asking_price' => $data['property_asking_price'] ?? null,
                'distress_markers' => $this->resolveDistressMarkers($data),
                'bedrooms' => $data['property_bedrooms'] ?? null,
                'bathrooms' => $data['property_bathrooms'] ?? null,
                'square_footage' => $data['property_square_footage'] ?? null,
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
     * POST /api/v1/leads/{id}/convert-to-deal
     *
     * Converts an existing seller lead and its property into the first
     * wholesale deal record without duplicating active deals.
     */
    public function convertToDeal(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');

        $lead = Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('property')
            ->findOrFail($id);

        $requestedStage = $request->input('stage', 'prospecting');

        if (!in_array($requestedStage, ['prospecting', 'contacting', 'engaging', 'offer_presented'], true)) {
            return response()->json([
                'error' => 'Lead conversion may only create an initial acquisition-stage deal.',
                'allowed_stages' => ['prospecting', 'contacting', 'engaging', 'offer_presented'],
            ], 422);
        }

        $deal = DB::transaction(function () use ($tenant, $lead, $request, $requestedStage) {
            // Serialize conversion attempts for this seller so two concurrent
            // requests cannot both pass the active-deal check.
            $lockedLead = Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereKey($lead->id)
                ->lockForUpdate()
                ->with('property')
                ->firstOrFail();

            $activeDeal = Deal::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('lead_id', $lockedLead->id)
                ->whereNotIn('stage', ['closed_won', 'closed_lost'])
                ->first();

            if ($activeDeal) {
                return $activeDeal;
            }

            if (!$lockedLead->property) {
                return null;
            }

            return Deal::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'lead_id' => $lockedLead->id,
                'property_id' => $lockedLead->property->id,
                'agent_id' => $lockedLead->agent_id,
                'title' => $request->input('title', $lockedLead->full_name . ' Deal'),
                'stage' => $requestedStage,
                'contract_price' => $request->input('contract_price'),
                'contract_date' => $request->input('contract_date'),
                'notes' => $request->input('notes'),
                'stage_changed_at' => now(),
            ]);
        });

        if ($deal instanceof \Illuminate\Http\JsonResponse) {
            return $deal;
        }

        if ($deal === null) {
            return response()->json([
                'error' => 'A property is required before converting this seller lead into a wholesale deal.',
            ], 422);
        }

        if ($deal->wasRecentlyCreated === false) {
            return response()->json([
                'success' => true,
                'already_exists' => true,
                'deal_id' => $deal->id,
                'property_id' => $deal->property_id,
            ], 200);
        }

        AuditLog::log('lead.converted_to_deal_via_api', $deal);

        return response()->json([
            'success' => true,
            'already_exists' => false,
            'deal_id' => $deal->id,
            'lead_id' => $lead->id,
            'property_id' => $lead->property->id,
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

        if ($request->filled('state')) {
            $query->whereHas('property', function ($propertyQuery) use ($request) {
                $propertyQuery->where('state', strtoupper($request->state));
            });
        }

        if ($request->filled('county')) {
            $query->whereHas('property', function ($propertyQuery) use ($request) {
                $propertyQuery->where('county', $request->county);
            });
        }

        if ($request->filled('since')) {
            $query->where('created_at', '>=', $request->since);
        }

        $prioritySort = $request->input('sort') === 'acquisition_priority';
        $priorityFilter = $request->filled('min_priority_score')
            || $request->filled('max_priority_score')
            || $request->filled('priority_tier');

        // Priority filtering/sorting is calculated from live lead/property data,
        // so it must happen before pagination to return the true top opportunities.
        if ($prioritySort || $priorityFilter) {
            $allLeads = $query->latest()->get();
            $priorityService = app(AcquisitionPriorityService::class);

            $allLeads->transform(function ($lead) use ($priorityService) {
                $lead->acquisition_priority = $priorityService->score($lead);
                return $lead;
            });

            if ($request->filled('min_priority_score')) {
                $minimum = max(0, min(100, $request->integer('min_priority_score')));
                $allLeads = $allLeads->filter(
                    fn ($lead) => $lead->acquisition_priority['score'] >= $minimum
                );
            }

            if ($request->filled('max_priority_score')) {
                $maximum = max(0, min(100, $request->integer('max_priority_score')));
                $allLeads = $allLeads->filter(
                    fn ($lead) => $lead->acquisition_priority['score'] <= $maximum
                );
            }

            if ($request->filled('priority_tier')) {
                $tier = strtolower(trim($request->priority_tier));
                $allLeads = $allLeads->filter(
                    fn ($lead) => $lead->acquisition_priority['tier'] === $tier
                );
            }

            if ($prioritySort) {
                $allLeads = $allLeads->sortByDesc(
                    fn ($lead) => $lead->acquisition_priority['score']
                )->values();
            } else {
                $allLeads = $allLeads->values();
            }

            $perPage = max(1, min(100, $request->integer('per_page', 25)));
            $page = max(1, $request->integer('page', 1));
            $total = $allLeads->count();
            $items = $allLeads->slice(($page - 1) * $perPage, $perPage)->values();

            $leads = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $leads = $query->latest()
                ->paginate($request->integer('per_page', 25));

            // Add transparent acquisition-priority intelligence without storing
            // another mutable score on the lead record.
            $leads->getCollection()->transform(function ($lead) {
                $lead->acquisition_priority = app(AcquisitionPriorityService::class)->score($lead);
                return $lead;
            });
        }

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

        $lead->acquisition_priority = app(AcquisitionPriorityService::class)->score($lead);

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
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

         $validated = $validator->validated();
        if (array_key_exists('custom_fields', $validated)) {
            $validated['custom_fields'] = array_merge($lead->custom_fields ?? [], $validated['custom_fields']);
        }
        $lead->update($validated);

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
        if (!empty($data['external_id'])) {
            $match = Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('custom_fields->external_id', $data['external_id'])
                ->first();
            if ($match) return $match;
        }

        if (!empty($data['phone'])) {
            $normalizedPhone = preg_replace('/\D+/', '', $data['phone']);
            $match = Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where(function ($query) use ($data, $normalizedPhone) {
                    $query->where('phone', $data['phone']);
                    if ($normalizedPhone !== '') {
                        $query->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?",
                            [$normalizedPhone]
                        );
                    }
                })
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
     * Preserve provider-supplied wholesale signals on the seller record so
     * downstream workflows can audit what drove prioritization.
     */
    private function wholesaleSignalFields(array $data): array
    {
        $fields = [];

        if (!empty($data['list_types'])) {
            $fields['list_types'] = array_values(array_unique(array_map(
                fn ($value) => str_replace([' ', '-'], '_', strtolower(trim((string) $value))),
                $data['list_types']
            )));
        }

        foreach ([
            'high_equity',
            'vacant',
            'absentee_owner',
            'tax_delinquent',
            'probate',
            'pre_foreclosure',
            'code_violation',
            'tired_landlord',
        ] as $flag) {
            if (array_key_exists($flag, $data)) {
                $fields[$flag] = (bool) $data[$flag];
            }
        }

        return $fields;
    }

    /**
     * Normalize distress/list signals supplied by external lead providers.
     * Boolean flags are converted to the same marker vocabulary used by
     * acquisition-priority scoring.
     */
    private function resolveDistressMarkers(array $data): array
    {
        $markers = [];

        foreach (($data['distress_markers'] ?? []) as $marker) {
            $markers[] = str_replace([' ', '-'], '_', strtolower(trim((string) $marker)));
        }

        foreach (($data['property_distress_markers'] ?? []) as $marker) {
            $markers[] = str_replace([' ', '-'], '_', strtolower(trim((string) $marker)));
        }

        foreach (($data['list_types'] ?? []) as $marker) {
            $markers[] = str_replace([' ', '-'], '_', strtolower(trim((string) $marker)));
        }

        foreach ([
            'high_equity',
            'vacant',
            'absentee_owner',
            'tax_delinquent',
            'probate',
            'pre_foreclosure',
            'code_violation',
            'tired_landlord',
        ] as $flag) {
            if (!empty($data[$flag])) {
                $markers[] = $flag;
            }
        }

        return array_values(array_unique(array_filter($markers)));
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
