<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Events\DealStageChanged;
use App\Facades\Hooks;
use App\Services\BusinessModeService;
use App\Services\BuyerMatchService;
use App\Services\DispositionPackageService;
use App\Services\WebhookService;
use App\Services\WholesaleClosingReadinessService;
use App\Services\WholesaleCompletionService;
use App\Models\TransactionChecklist;
use App\Models\DealBuyerMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DealApiController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $query = Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with(['lead.property', 'property', 'agent']);

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
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

        $deal = Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with(['lead.property', 'property', 'agent', 'documents', 'signatureRequests', 'buyerMatches.buyer'])
            ->findOrFail($id);

        return response()->json($deal);
    }

    public function store(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $isRE = BusinessModeService::isRealEstate($tenant);

        $rules = [
            'lead_id'              => 'required|integer',
            'property_id'          => 'nullable|integer',
            'agent_id'             => 'nullable|integer',
            'title'                => 'nullable|string|max:255',
            'stage'                => 'nullable|in:' . implode(',', array_keys(Deal::stages())),
            'contract_price'       => 'nullable|numeric|min:0',
            'earnest_money'        => 'nullable|numeric|min:0',
            'contract_date'        => 'nullable|date',
            'closing_date'         => 'nullable|date',
            'notes'                => 'nullable|string',
            'property_state' => 'nullable|string|size:2',
            'property_county' => 'nullable|string|max:255',
            'buyer_type' => 'nullable|in:individual,entity',
            'buyer_legal_name' => 'nullable|string|max:255',
            'transaction_structure' => 'nullable|in:assignment,double_close,novation',
            'attorney_approved_template_id' => 'nullable|string|max:255',
            'executed_contract_status' => 'nullable|in:not_executed,pending_review,executed',
            'assignment_rights_confirmed' => 'nullable|boolean',
            'emd_required' => 'nullable|boolean',
            'emd_source' => 'nullable|in:wholesaler,end_buyer,other_approved',
            'buyer_covers_emd' => 'nullable|boolean',
            'emd_funding_confirmed' => 'nullable|boolean',
            'emd_exception_reason' => 'nullable|string|max:500',
            'closing_provider_type' => 'nullable|in:closing_attorney,title_company,escrow_company,settlement_company,other_permitted',
            'buyer_preferred_closing_provider' => 'nullable|string|max:255',
            'seller_preferred_closing_provider' => 'nullable|string|max:255',
            'confirmed_closing_provider' => 'nullable|string|max:255',
            'contract_review_status' => 'nullable|in:pending,approved,needs_attention',
            'no_buyer_status' => 'nullable|in:not_applicable,active,review,extension_review,termination_review',
            'extension_termination_review_status' => 'nullable|in:not_needed,pending,approved',
            'contract_gate_status' => 'nullable|in:pending,approved,blocked',
            'contract_gate_reason' => 'nullable|string|max:2000',
            'contract_gate_blockers' => 'nullable|array',
            'contract_gate_checked_at' => 'nullable|date',
            'due_diligence_end_date' => 'nullable|date',

        ];

        if ($isRE) {
            $rules += [
                'listing_commission_pct' => 'nullable|numeric|min:0|max:100',
                'buyer_commission_pct'   => 'nullable|numeric|min:0|max:100',
                'total_commission'       => 'nullable|numeric|min:0',
                'brokerage_split_pct'    => 'nullable|numeric|min:0|max:100',
                'mls_number'             => 'nullable|string|max:50',
                'listing_date'           => 'nullable|date',
                'days_on_market'         => 'nullable|integer|min:0',
            ];
        } else {
            $rules += [
                'assignment_fee'         => 'nullable|numeric|min:0',
                'inspection_period_days' => 'nullable|integer|min:0',
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['tenant_id'] = $tenant->id;
        $data['stage'] = $data['stage'] ?? \App\Services\BusinessModeService::getDefaultStage();
        $data['stage_changed_at'] = now();

        $deal = DB::transaction(function () use ($tenant, $data) {
            // Lock the lead row before checking for an active deal. This makes
            // duplicate-deal prevention safe when two requests arrive at once.
            $lead = \App\Models\Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->findOrFail($data['lead_id']);

            $activeDealExists = Deal::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('lead_id', $lead->id)
                ->whereNotIn('stage', ['closed_won', 'closed_lost'])
                ->exists();

            if ($activeDealExists) {
                return response()->json([
                    'error' => 'An active deal already exists for this lead. Close or otherwise resolve the existing deal before creating another.',
                ], 409);
            }

            if (isset($data['property_id'])) {
                \App\Models\Property::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->where('lead_id', $lead->id)
                    ->findOrFail($data['property_id']);
            } else {
                $property = $lead->property()->first();
                if ($property) {
                    $data['property_id'] = $property->id;
                }
            }

            if (empty($data['title'])) {
                $data['title'] = $lead->full_name . ' Deal';
            }

            $deal = Deal::withoutGlobalScopes()->create($data);
            AuditLog::log('deal.created_via_api', $deal);

            return $deal;
        });

        if ($deal instanceof \Illuminate\Http\JsonResponse) {
            return $deal;
        }

        return response()->json(['success' => true, 'deal_id' => $deal->id], 201);
    }

    public function update(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');

        $deal = Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        $isRE = BusinessModeService::isRealEstate($tenant);

        $rules = [
            'property_id'          => 'nullable|integer',
            'stage'                => 'nullable|in:' . implode(',', array_keys(Deal::stages())),
            'contract_price'       => 'nullable|numeric|min:0',
            'earnest_money'        => 'nullable|numeric|min:0',
            'contract_date'        => 'nullable|date',
            'closing_date'         => 'nullable|date',
            'notes'                => 'nullable|string',
            'property_state' => 'nullable|string|size:2',
            'property_county' => 'nullable|string|max:255',
            'buyer_type' => 'nullable|in:individual,entity',
            'buyer_legal_name' => 'nullable|string|max:255',
            'transaction_structure' => 'nullable|in:assignment,double_close,novation',
            'attorney_approved_template_id' => 'nullable|string|max:255',
            'executed_contract_status' => 'nullable|in:not_executed,pending_review,executed',
            'assignment_rights_confirmed' => 'nullable|boolean',
            'emd_required' => 'nullable|boolean',
            'emd_source' => 'nullable|in:wholesaler,end_buyer,other_approved',
            'buyer_covers_emd' => 'nullable|boolean',
            'emd_funding_confirmed' => 'nullable|boolean',
            'emd_exception_reason' => 'nullable|string|max:500',
            'closing_provider_type' => 'nullable|in:closing_attorney,title_company,escrow_company,settlement_company,other_permitted',
            'buyer_preferred_closing_provider' => 'nullable|string|max:255',
            'seller_preferred_closing_provider' => 'nullable|string|max:255',
            'confirmed_closing_provider' => 'nullable|string|max:255',
            'contract_review_status' => 'nullable|in:pending,approved,needs_attention',
            'no_buyer_status' => 'nullable|in:not_applicable,active,review,extension_review,termination_review',
            'extension_termination_review_status' => 'nullable|in:not_needed,pending,approved',
            'contract_gate_status' => 'nullable|in:pending,approved,blocked',
            'contract_gate_reason' => 'nullable|string|max:2000',
            'contract_gate_blockers' => 'nullable|array',
            'contract_gate_checked_at' => 'nullable|date',
            'due_diligence_end_date' => 'nullable|date',
        ];

        if ($isRE) {
            $rules += [
                'listing_commission_pct' => 'nullable|numeric|min:0|max:100',
                'buyer_commission_pct'   => 'nullable|numeric|min:0|max:100',
                'total_commission'       => 'nullable|numeric|min:0',
                'brokerage_split_pct'    => 'nullable|numeric|min:0|max:100',
                'mls_number'             => 'nullable|string|max:50',
                'listing_date'           => 'nullable|date',
                'days_on_market'         => 'nullable|integer|min:0',
            ];
        } else {
            $rules += [
                'assignment_fee'         => 'nullable|numeric|min:0',
                'inspection_period_days' => 'nullable|integer|min:0',
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (array_key_exists('property_id', $data)) {
            if ($data['property_id'] === null) {
                $data['property_id'] = null;
            } else {
                \App\Models\Property::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->where('lead_id', $deal->lead_id)
                    ->findOrFail($data['property_id']);
            }
        }

        // Protected wholesale lifecycle stages must be reached through their
        // dedicated state-transition endpoints so contract, buyer, closing,
        // completion, audit, and webhook gates cannot be bypassed by a
        // generic deal update.
        if (!$isRE && isset($data['stage']) && in_array($data['stage'], [
            'buyer_committed',
            'assigned',
            'closing',
            'closed_won',
        ], true)) {
            $endpoint = match ($data['stage']) {
                'buyer_committed' => 'POST /api/v1/deals/{id}/buyer-commitment',
                'closing' => 'POST /api/v1/deals/{id}/advance-closing',
                'assigned' => 'POST /api/v1/deals/{id}/assignment-complete',
                'closed_won' => 'POST /api/v1/deals/{id}/closing-complete',
            };

            return response()->json([
                'error' => 'This wholesale lifecycle stage must be reached through its dedicated workflow endpoint.',
                'required_endpoint' => $endpoint,
            ], 422);
        }

        if (($data['stage'] ?? null) === 'under_contract'
            && !$isRE
            && ($data['contract_gate_status'] ?? $deal->contract_gate_status) !== 'approved') {
            return response()->json([
                'error' => 'Contract Protection Gate approval is required before moving a wholesale deal to under_contract.',
                'contract_gate_status' => $deal->contract_gate_status,
            ], 422);
        }

        // Handle stage change
        if (isset($data['stage']) && $data['stage'] !== $deal->stage) {
            $oldStage = $deal->stage;
            $data['stage_changed_at'] = now();
            $deal->update($data);

            Activity::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'lead_id' => $deal->lead_id,
                'agent_id' => $deal->agent_id,
                'type' => 'stage_change',
                'subject' => 'Deal stage changed via API',
                'body' => 'Stage changed from "' . Deal::stageLabel($oldStage) . '" to "' . Deal::stageLabel($data['stage']) . '"',
                'logged_at' => now(),
            ]);

            event(new DealStageChanged($deal, $oldStage));
            Hooks::doAction('deal.stage_changed', $deal, $oldStage);

            if ($data['stage'] === \App\Services\BusinessModeService::getBuyerMatchTriggerStage()) {
                app(BuyerMatchService::class)->matchForDeal($deal);
                if (!$isRE && $data['stage'] === 'dispositions') {
                    WebhookService::dispatch('deal.disposition.ready', [
                        'deal_id' => $deal->id,
                        'lead_id' => $deal->lead_id,
                        'stage' => $deal->stage,
                    ], $tenant->id);
                }
            }
        } else {
            $deal->update($data);
        }

        return response()->json(['success' => true, 'deal' => $deal->fresh()]);
    }

    public function commitBuyer(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');

        $deal = Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        if (!BusinessModeService::isWholesale($tenant)) {
            return response()->json(['error' => 'Buyer commitment is only available in wholesale mode.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'buyer_id' => 'required|integer',
            'match_id' => 'required|integer',
            'commitment_status' => 'required|in:committed',
            'buyer_confirmation' => 'required|boolean|accepted',
            'assignment_fee' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        return DB::transaction(function () use ($tenant, $deal, $data) {
            $deal = Deal::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereKey($deal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($deal->stage !== 'dispositions' && $deal->stage !== 'buyer_committed') {
                return response()->json(['error' => 'Deal must be in dispositions before a buyer can be committed.'], 422);
            }

            if ($deal->contract_gate_status !== 'approved') {
                return response()->json([
                    'error' => 'Contract Protection Gate approval is required before committing a buyer.',
                    'contract_gate_status' => $deal->contract_gate_status,
                ], 422);
            }

            $match = DealBuyerMatch::withoutGlobalScopes()
                ->where('id', $data['match_id'])
                ->where('deal_id', $deal->id)
                ->where('buyer_id', $data['buyer_id'])
                ->firstOrFail();

            if (!in_array($match->response_intent, ['interested', 'callback'], true)) {
                return response()->json(['error' => 'Buyer must have an eligible response before commitment.'], 422);
            }

            if ($match->outreach_status === 'stopped' || $match->response_intent === 'opt_out') {
                return response()->json(['error' => 'This buyer has opted out of outreach.'], 422);
            }

            if ($deal->committed_buyer_id === $match->buyer_id && $match->commitment_status === 'committed') {
                return response()->json([
                    'success' => true,
                    'deal' => $deal->fresh(['committedBuyer', 'buyerMatches.buyer']),
                    'message' => 'Buyer commitment is already recorded.',
                ]);
            }

            $alreadyCommitted = DealBuyerMatch::withoutGlobalScopes()
                ->where('deal_id', $deal->id)
                ->where('commitment_status', 'committed')
                ->where('id', '!=', $match->id)
                ->exists();

            if ($alreadyCommitted) {
                return response()->json(['error' => 'Another buyer is already committed to this deal. Review before replacing the commitment.'], 422);
            }

            $match->update([
                'commitment_status' => 'committed',
                'outreach_status' => 'replied',
                'responded_at' => $match->responded_at ?: now(),
            ]);

            $deal->update([
                'committed_buyer_id' => $match->buyer_id,
                'buyer_committed_at' => now(),
                'assignment_fee' => array_key_exists('assignment_fee', $data) ? $data['assignment_fee'] : $deal->assignment_fee,
                'stage' => 'buyer_committed',
                'stage_changed_at' => now(),
            ]);

            Activity::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'lead_id' => $deal->lead_id,
                'agent_id' => $deal->agent_id,
                'type' => 'stage_change',
                'subject' => 'Buyer commitment recorded',
                'body' => 'Buyer #' . $match->buyer_id . ' explicitly confirmed commitment for deal #' . $deal->id . '.',
                'logged_at' => now(),
            ]);

            WebhookService::dispatch('deal.buyer.committed', [
                'deal_id' => $deal->id,
                'lead_id' => $deal->lead_id,
                'buyer_id' => $match->buyer_id,
                'match_id' => $match->id,
                'stage' => 'buyer_committed',
            ], $tenant->id);

            return response()->json([
                'success' => true,
                'deal' => $deal->fresh(['committedBuyer', 'buyerMatches.buyer']),
                'message' => 'Buyer commitment recorded. Continue through the authorized assignment and closing process.',
            ]);
        });
    }

    public function closingReadiness(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');
        $deal = Deal::withoutGlobalScopes()->where('tenant_id', $tenant->id)->findOrFail($id);

        if (!BusinessModeService::isWholesale($tenant)) {
            return response()->json(['error' => 'Closing readiness is only available in wholesale mode.'], 422);
        }

        return response()->json(app(WholesaleClosingReadinessService::class)->assess($deal));
    }

    public function markChecklistItem(Request $request, int $id, int $itemId)
    {
        $tenant = $request->attributes->get('tenant');
        $deal = Deal::withoutGlobalScopes()->where('tenant_id', $tenant->id)->findOrFail($id);
        $item = TransactionChecklist::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('deal_id', $deal->id)
            ->findOrFail($itemId);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed,waived,failed',
            'notes' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $item->update([
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $item->notes,
            'completed_at' => in_array($data['status'], ['completed', 'waived'], true) ? now() : null,
        ]);

        return response()->json(['success' => true, 'item' => $item->fresh()]);
    }

    public function advanceClosing(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');
        $deal = Deal::withoutGlobalScopes()->where('tenant_id', $tenant->id)->findOrFail($id);

        if (!BusinessModeService::isWholesale($tenant)) {
            return response()->json(['error' => 'Closing advancement is only available in wholesale mode.'], 422);
        }

        if (!in_array($deal->stage, ['buyer_committed', 'assigned', 'closing'], true)) {
            return response()->json(['error' => 'Deal must have a committed buyer before closing advancement.'], 422);
        }

        $readiness = app(WholesaleClosingReadinessService::class)->assess($deal);
        if (!$readiness['ready']) {
            return response()->json([
                'error' => 'Closing readiness gate is not satisfied.',
                'blockers' => $readiness['blockers'],
            ], 422);
        }

        if ($deal->stage !== 'closing') {
            $oldStage = $deal->stage;
            $deal->update(['stage' => 'closing', 'stage_changed_at' => now()]);
            Activity::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'lead_id' => $deal->lead_id,
                'agent_id' => $deal->agent_id,
                'type' => 'stage_change',
                'subject' => 'Deal advanced to closing',
                'body' => 'Closing readiness gate passed for deal #' . $deal->id . '.',
                'logged_at' => now(),
            ]);
            event(new DealStageChanged($deal, $oldStage));
            Hooks::doAction('deal.stage_changed', $deal, $oldStage);
            WebhookService::dispatch('deal.closing.ready', [
                'deal_id' => $deal->id,
                'lead_id' => $deal->lead_id,
                'buyer_id' => $deal->committed_buyer_id,
                'transaction_structure' => $deal->transaction_structure,
                'closing_provider' => $deal->confirmed_closing_provider,
            ], $tenant->id);
        }

        return response()->json(['success' => true, 'deal' => $deal->fresh(['committedBuyer'])]);
    }

    public function completeAssignment(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');
        $deal = Deal::withoutGlobalScopes()->where('tenant_id', $tenant->id)->findOrFail($id);

        if (!BusinessModeService::isWholesale($tenant)) {
            return response()->json(['error' => 'Assignment completion is only available in wholesale mode.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'final_assignment_fee' => 'nullable|numeric|min:0',
            'closing_result' => 'nullable|string|max:255',
            'closing_result_notes' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

        try {
            $deal = app(WholesaleCompletionService::class)->markAssigned(
                $deal,
                $validator->validated(),
                auth()->id()
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'deal' => $deal,
            'message' => 'Assignment completion recorded. Closing is still a separate explicit step.',
        ]);
    }

    public function completeClosing(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');
        $deal = Deal::withoutGlobalScopes()->where('tenant_id', $tenant->id)->findOrFail($id);

        if (!BusinessModeService::isWholesale($tenant)) {
            return response()->json(['error' => 'Closing completion is only available in wholesale mode.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'final_assignment_fee' => 'nullable|numeric|min:0',
            'final_closing_amount' => 'nullable|numeric|min:0',
            'closing_result' => 'nullable|string|max:255',
            'closing_result_notes' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

        try {
            $deal = app(WholesaleCompletionService::class)->markClosedWon(
                $deal,
                $validator->validated(),
                auth()->id()
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'deal' => $deal,
            'message' => 'Wholesale closing completion recorded.',
        ]);
    }

    public function dispositionPackage(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');

        $deal = Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        return response()->json(
            app(DispositionPackageService::class)->build($deal)
        );
    }

    /**
     * GET /api/v1/deals/stages
     */
    public function stages()
    {
        return response()->json(Deal::stages());
    }
}
