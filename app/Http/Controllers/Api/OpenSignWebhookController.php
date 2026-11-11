<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenSignService;
use App\Models\DealSignatureRequest;
use Illuminate\Http\Request;

class OpenSignWebhookController extends Controller
{
    public function handle(Request $request, OpenSignService $service)
    {
        $raw = $request->getContent();
        $payload = json_decode($raw, true);

        if (!is_array($payload)) {
            return response()->json(['error' => 'Invalid JSON payload.'], 422);
        }

        $objectId = $payload['objectId'] ?? null;
        $record = $objectId
            ? DealSignatureRequest::withoutGlobalScopes()->where('opensign_object_id', $objectId)->first()
            : null;

        $secret = $record
            ? ($service->settings($record->tenant_id)['webhook_secret'] ?? '')
            : (string) config('services.opensign.webhook_secret');

        if ($secret === '') {
            return response()->json(['error' => 'OpenSign webhook secret is not configured.'], 503);
        }

        $provided = (string) $request->header('x-webhook-signature');
        $expected = hash_hmac('sha256', $raw, $secret);

        if ($provided === '' || !hash_equals($expected, $provided)) {
            return response()->json(['error' => 'Invalid OpenSign webhook signature.'], 401);
        }

        $record = $service->handleWebhook($payload);

        if (!$record) {
            return response()->json(['success' => true, 'ignored' => true]);
        }

        return response()->json([
            'success' => true,
            'signature_request_id' => $record->id,
            'status' => $record->status,
        ]);
    }
}
