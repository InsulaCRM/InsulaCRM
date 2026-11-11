<?php

use App\Http\Controllers\Api\ActivityApiController;
use App\Http\Controllers\Api\BuyerApiController;
use App\Http\Controllers\Api\DealApiController;
use App\Http\Controllers\Api\DealSignatureController;
use App\Http\Controllers\Api\LeadIngestController;
use App\Http\Controllers\Api\OpenSignWebhookController;
use App\Http\Controllers\Api\PropertyApiController;
use App\Http\Controllers\Api\StatsApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Authenticated via X-API-Key header.
| All routes prefixed with /api/v1/
|
*/

// OpenSign calls this endpoint directly. It is authenticated with the
// OpenSign HMAC webhook signature instead of the InsulaCRM tenant API key.
Route::post('/v1/webhooks/opensign', [OpenSignWebhookController::class, 'handle'])
    ->middleware(['throttle:api']);

Route::prefix('v1')->middleware(['api.key', 'throttle:api', 'api.log'])->group(function () {

    // Leads
    Route::get('/leads', [LeadIngestController::class, 'index']);
    Route::post('/leads', [LeadIngestController::class, 'store']);
    Route::get('/leads/{id}', [LeadIngestController::class, 'show']);
    Route::put('/leads/{id}', [LeadIngestController::class, 'update']);

    // Deals
    Route::get('/deals', [DealApiController::class, 'index']);
    Route::post('/deals', [DealApiController::class, 'store']);
    Route::get('/deals/stages', [DealApiController::class, 'stages']);
    Route::get('/deals/{id}', [DealApiController::class, 'show']);
    Route::put('/deals/{id}', [DealApiController::class, 'update']);
    Route::get('/deals/{id}/disposition-package', [DealApiController::class, 'dispositionPackage']);
    Route::post('/deals/{id}/buyer-commitment', [DealApiController::class, 'commitBuyer']);
    Route::get('/deals/{id}/closing-readiness', [DealApiController::class, 'closingReadiness']);
    Route::put('/deals/{id}/checklist/{itemId}', [DealApiController::class, 'markChecklistItem']);
    Route::post('/deals/{id}/advance-closing', [DealApiController::class, 'advanceClosing']);

    // Deal e-signature
    Route::get('/deals/{id}/signature-requests', [DealSignatureController::class, 'index']);
    Route::post('/deals/{id}/signature-requests', [DealSignatureController::class, 'store']);

    // Buyers
    Route::get('/buyers', [BuyerApiController::class, 'index']);
    Route::post('/buyers', [BuyerApiController::class, 'store']);
    Route::get('/buyers/{id}', [BuyerApiController::class, 'show']);
    Route::put('/buyers/{id}', [BuyerApiController::class, 'update']);
    Route::get('/buyers/{id}/disposition-matches', [BuyerApiController::class, 'dispositionMatches']);
    Route::put('/buyers/{id}/disposition-matches/{matchId}', [BuyerApiController::class, 'updateMatch']);

    // Properties
    Route::get('/properties', [PropertyApiController::class, 'index']);
    Route::post('/properties', [PropertyApiController::class, 'store']);
    Route::get('/properties/{id}', [PropertyApiController::class, 'show']);
    Route::put('/properties/{id}', [PropertyApiController::class, 'update']);

    // Activities
    Route::get('/activities', [ActivityApiController::class, 'index']);
    Route::post('/activities', [ActivityApiController::class, 'store']);

    // Stats / KPIs
    Route::get('/stats', [StatsApiController::class, 'index']);
});
