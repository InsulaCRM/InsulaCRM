<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\DealSignatureRequest;
use App\Models\GeneratedDocument;
use App\Services\BusinessModeService;
use App\Services\OpenSignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class DealSignatureController extends Controller
{
    public function index(Request $request, int $id)
    {
        $tenant = $request->attributes->get('tenant');

        $deal = Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        return response()->json([
            'deal_id' => $deal->id,
            'signature_requests' => DealSignatureRequest::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('deal_id', $deal->id)
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request, int $id, OpenSignService $service)
    {
        $tenant = $request->attributes->get('tenant');
        $deal = Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        if (!BusinessModeService::isWholesale($tenant)) {
            return response()->json(['error' => 'E-signature requests are only available in wholesale mode.'], 422);
        }

        if ($deal->contract_gate_status !== 'approved') {
            return response()->json([
                'error' => 'Contract Protection Gate approval is required before sending a contract for signature.',
                'contract_gate_status' => $deal->contract_gate_status,
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'generated_document_id' => 'required|integer',
            'signers' => 'required|array|min:1',
            'signers.*.name' => 'required|string|max:255',
            'signers.*.email' => 'required|email|max:255',
            'signers.*.phone' => 'nullable|string|max:30',
            'signers.*.role' => 'nullable|string|max:100',
            'signers.*.widgets' => 'nullable|array',
            'widgets' => 'nullable|array',
            'options' => 'nullable|array',
            'options.force_resend' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed.',
                'details' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $document = GeneratedDocument::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('deal_id', $deal->id)
            ->findOrFail($data['generated_document_id']);

        $forceResend = (bool) ($data['options']['force_resend'] ?? false);

        if (!$forceResend) {
            $activeRequest = DealSignatureRequest::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('deal_id', $deal->id)
                ->where('generated_document_id', $document->id)
                ->whereIn('status', ['sending', 'sent', 'viewed', 'partially_signed'])
                ->latest('id')
                ->first();

            if ($activeRequest) {
                return response()->json([
                    'success' => true,
                    'signature_request' => $activeRequest,
                    'message' => 'An active OpenSign signature request already exists for this document. Set options.force_resend=true to create another request.',
                ], 200);
            }
        }

        try {
            $signatureRequest = $service->createSignatureRequest(
                $deal,
                $document,
                $data['signers'],
                $data['widgets'] ?? [],
                $data['options'] ?? []
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'signature_request' => $signatureRequest,
            'message' => 'Signature request sent through OpenSign.',
        ], 201);
    }
}
