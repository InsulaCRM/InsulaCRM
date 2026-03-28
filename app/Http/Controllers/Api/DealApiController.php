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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DealApiController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $query = Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with(['lead', 'agent']);

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
            ->with(['lead.property', 'agent', 'documents', 'buyerMatches.buyer'])
            ->findOrFail($id);

        return response()->json($deal);
    }

    public function store(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $isRE = BusinessModeService::isRealEstate($tenant);

        $rules = [
            'lead_id'              => 'required|integer',
            'agent_id'             => 'nullable|integer',
            'title'                => 'nullable|string|max:255',
            'stage'                => 'nullable|in:' . implode(',', array_keys(Deal::stages())),
            'contract_price'       => 'nullable|numeric|min:0',
            'earnest_money'        => 'nullable|numeric|min:0',
            'contract_date'        => 'nullable|date',
            'closing_date'         => 'nullable|date',
            'notes'                => 'nullable|string',
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

        // Verify lead belongs to tenant
        $lead = \App\Models\Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($data['lead_id']);

        if (empty($data['title'])) {
            $data['title'] = $lead->full_name . ' Deal';
        }

        $deal = Deal::withoutGlobalScopes()->create($data);

        AuditLog::log('deal.created_via_api', $deal);

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
            'stage'                => 'nullable|in:' . implode(',', array_keys(Deal::stages())),
            'contract_price'       => 'nullable|numeric|min:0',
            'earnest_money'        => 'nullable|numeric|min:0',
            'contract_date'        => 'nullable|date',
            'closing_date'         => 'nullable|date',
            'notes'                => 'nullable|string',
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
            }
        } else {
            $deal->update($data);
        }

        return response()->json(['success' => true, 'deal' => $deal->fresh()]);
    }

    /**
     * GET /api/v1/deals/stages
     */
    public function stages()
    {
        return response()->json(Deal::stages());
    }
}
