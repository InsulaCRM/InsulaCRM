<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Buyer;
use App\Models\DealBuyerMatch;
use App\Services\BusinessModeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BuyerApiController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $query = Buyer::withoutGlobalScopes()->where('tenant_id', $tenant->id);

        if ($request->filled('search')) {
            $s=$request->search;
            $query->where(fn($q)=>$q->where('first_name','like',"%{$s}%")->orWhere('last_name','like',"%{$s}%")->orWhere('company','like',"%{$s}%")->orWhere('email','like',"%{$s}%"));
        }
        if ($request->filled('state')) $query->whereJsonContains('preferred_states', strtoupper($request->state));

        return response()->json($query->latest()->paginate($request->integer('per_page',25)));
    }

    public function show(Request $request, int $id)
    {
        $tenant=$request->attributes->get('tenant');
        $buyer=Buyer::withoutGlobalScopes()->where('tenant_id',$tenant->id)->with('dealMatches')->findOrFail($id);
        return response()->json($buyer);
    }

    public function store(Request $request)
    {
        $tenant=$request->attributes->get('tenant');
        $terms=BusinessModeService::getTerminology($tenant);
        $isRE=BusinessModeService::isRealEstate($tenant);

        $rules=[
            'first_name'=>'required|string|max:255','last_name'=>'required|string|max:255',
            'company'=>'nullable|string|max:255','phone'=>'nullable|string|max:20','email'=>'nullable|email|max:255',
            'max_purchase_price'=>'nullable|numeric|min:0','max_all_in_percent'=>'nullable|numeric|min:0|max:100',
            'target_profit'=>'nullable|numeric|min:0',
            'preferred_property_types'=>'nullable|array','preferred_strategies'=>'nullable|array',
            'preferred_conditions'=>'nullable|array','preferred_states'=>'nullable|array',
            'preferred_zip_codes'=>'nullable|array','transaction_cost_percent'=>'nullable|numeric|min:0|max:100',
            'holding_cost_percent'=>'nullable|numeric|min:0|max:100','financing_cost_percent'=>'nullable|numeric|min:0|max:100',
            'risk_buffer_percent'=>'nullable|numeric|min:0|max:100','notes'=>'nullable|string',
        ];
        if(!$isRE) $rules['asset_classes']='nullable|array';

        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()) return response()->json(['error'=>'Validation failed.','details'=>$validator->errors()],422);

        $data=$validator->validated(); $data['tenant_id']=$tenant->id;

        if(!empty($data['email'])){
            $existing=Buyer::withoutGlobalScopes()->where('tenant_id',$tenant->id)->where('email',$data['email'])->first();
            if($existing) return response()->json(['success'=>true,'duplicate'=>true,'message'=>__(':label with this email already exists.',['label'=>$terms['buyer_singular']]),'buyer_id'=>$existing->id]);
        }

        $buyer=Buyer::withoutGlobalScopes()->create($data);
        AuditLog::log('buyer.created_via_api',$buyer);
        return response()->json(['success'=>true,'buyer_id'=>$buyer->id],201);
    }

    public function dispositionMatches(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');
        $buyer = Buyer::withoutGlobalScopes()->where('tenant_id', $tenant->id)->findOrFail($id);

        $matches = DealBuyerMatch::where('buyer_id', $buyer->id)
            ->with('deal.lead.property')
            ->latest()
            ->paginate($request->integer('per_page', 25));

        return response()->json($matches);
    }

    public function updateMatch(Request $request, int $id, int $matchId)
    {
        $tenant = $request->attributes->get('tenant');
        $buyer = Buyer::withoutGlobalScopes()->where('tenant_id', $tenant->id)->findOrFail($id);

        $match = DealBuyerMatch::where('buyer_id', $buyer->id)->findOrFail($matchId);

        $validator = Validator::make($request->all(), [
            'response_intent' => 'nullable|in:interested,question,not_interested,opt_out,wrong_number,callback',
            'commitment_status' => 'nullable|in:none,interested,reviewing,declined',
            'response_body' => 'nullable|string|max:10000',
            'outreach_status' => 'nullable|in:queued,sent,delivered,replied,failed,stopped',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed.', 'details' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (($data['response_intent'] ?? null) === 'opt_out') {
            $data['commitment_status'] = 'declined';
            $data['outreach_status'] = 'stopped';
        }

        // Buyer commitment is a deal-level state transition and must go
        // through DealApiController::commitBuyer so the contract gate,
        // eligible response, single-buyer rule, and audit/webhook flow
        // cannot be bypassed by editing a match directly.
        if (($data['commitment_status'] ?? null) === 'committed') {
            return response()->json([
                'error' => 'Buyer commitment must be recorded through the deal buyer-commitment endpoint.',
            ], 422);
        }

        if (array_key_exists('response_body', $data)) {
            $data['response_received_at'] = now();
            $data['responded_at'] = now();
        }

        $match->update($data);

        return response()->json(['success' => true, 'match' => $match->fresh()]);
    }

    public function update(Request $request, int $id)
    {
        $tenant=$request->attributes->get('tenant'); $isRE=BusinessModeService::isRealEstate($tenant);
        $buyer=Buyer::withoutGlobalScopes()->where('tenant_id',$tenant->id)->findOrFail($id);

        $rules=[
            'first_name'=>'nullable|string|max:255','last_name'=>'nullable|string|max:255',
            'company'=>'nullable|string|max:255','phone'=>'nullable|string|max:20','email'=>'nullable|email|max:255',
            'max_purchase_price'=>'nullable|numeric|min:0','max_all_in_percent'=>'nullable|numeric|min:0|max:100',
            'target_profit'=>'nullable|numeric|min:0','preferred_property_types'=>'nullable|array',
            'preferred_strategies'=>'nullable|array','preferred_conditions'=>'nullable|array',
            'preferred_states'=>'nullable|array','preferred_zip_codes'=>'nullable|array',
            'transaction_cost_percent'=>'nullable|numeric|min:0|max:100','holding_cost_percent'=>'nullable|numeric|min:0|max:100',
            'financing_cost_percent'=>'nullable|numeric|min:0|max:100','risk_buffer_percent'=>'nullable|numeric|min:0|max:100',
            'notes'=>'nullable|string',
        ];
        if(!$isRE) $rules['asset_classes']='nullable|array';

        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()) return response()->json(['error'=>'Validation failed.','details'=>$validator->errors()],422);

        $buyer->update($validator->validated());
        return response()->json(['success'=>true,'buyer'=>$buyer->fresh()]);
    }
}
