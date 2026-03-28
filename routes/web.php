<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DncController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\PluginController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\SequenceController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AiLogController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\LeadKanbanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\ApiDocsController;
use App\Http\Controllers\PdfExportController;
use App\Http\Controllers\GdprController;
use App\Http\Controllers\CalendarSyncController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\Api\WebFormController;
use App\Http\Controllers\BuyerPortalController;
use App\Http\Controllers\BuyerPortalSettingsController;
use App\Http\Controllers\WebhookRecipeController;
use App\Http\Controllers\ShowingController;
use App\Http\Controllers\OpenHouseController;
use App\Http\Controllers\ListingDashboardController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\DocumentGeneratorController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\BuyerVerificationController;
use App\Http\Controllers\BuyerTransactionController;
use App\Http\Controllers\ComparableSaleController;
use App\Http\Controllers\ActivityInboxController;
use App\Http\Controllers\SavedViewController;
use App\Http\Controllers\DispositionRoomController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard or login
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
});

// Installer routes
Route::get('/install', [InstallController::class, 'index'])->name('install.index');
Route::post('/install/mode', function (\Illuminate\Http\Request $request) {
    $request->validate(['mode' => 'required|string|in:guided-web,vps-server,local-test']);
    $request->session()->put('install.mode', $request->string('mode')->toString());

    return redirect()->route('install.requirements');
})->name('install.mode');
Route::get('/install/requirements', [InstallController::class, 'requirements'])->name('install.requirements');
Route::get('/install/database', [InstallController::class, 'database'])->name('install.database');
Route::post('/install/database', [InstallController::class, 'saveDatabase'])->name('install.saveDatabase');
Route::get('/install/setup', [InstallController::class, 'setup'])->name('install.setup');
Route::post('/install/run', [InstallController::class, 'install'])->name('install.run');
Route::get('/install/complete', [InstallController::class, 'complete'])->name('install.complete');
Route::post('/install/complete/snapshot', [InstallController::class, 'createInitialSnapshot'])->name('install.complete.snapshot');
// Public lead capture web forms
Route::get('/forms/{api_key}', [WebFormController::class, 'show'])->name('forms.show');
Route::post('/forms/{api_key}', [WebFormController::class, 'submit'])->middleware('throttle:10,1')->name('forms.submit');

// Public buyer portal (no auth)
Route::get('/p/{slug}', [BuyerPortalController::class, 'show'])->name('buyer-portal.show');
Route::post('/p/{slug}/register', [BuyerPortalController::class, 'register'])->middleware('throttle:10,1')->name('buyer-portal.register');
Route::get('/p/{slug}/registered', [BuyerPortalController::class, 'registered'])->name('buyer-portal.registered');
Route::get('/p/{slug}/properties', [BuyerPortalController::class, 'properties'])->name('buyer-portal.properties');

// Offline fallback (PWA)
Route::get('/offline', fn () => view('offline'))->name('offline');

// 2FA challenge routes (no auth required — user not yet authenticated)
Route::get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
Route::post('/two-factor/verify', [TwoFactorController::class, 'verify'])->name('two-factor.verify')->middleware('throttle:5,1');

// Calendar iCal feed (no auth — uses token)
Route::get('/calendar/feed/{token}.ics', [CalendarSyncController::class, 'icalFeed'])->name('calendar.feed');

// SSO routes (no auth required — user is logging in)
Route::get('/sso/{driver}/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
Route::match(['get', 'post'], '/sso/{driver}/callback', [SsoController::class, 'callback'])->name('sso.callback');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update')->middleware('throttle:5,1');
});

// Authenticated routes
Route::middleware(['auth', 'tenant', 'require2fa'])->group(function () {
    // ── Open to ALL authenticated roles ──────────────────────────────
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/widgets', [DashboardController::class, 'updateWidgets'])->name('dashboard.updateWidgets');
    // Dashboard data API (field scouts have no charts, so exclude them)
    Route::get('/api/dashboard-data', [ReportController::class, 'dashboardData'])
        ->middleware('role:admin,agent,acquisition_agent,disposition_agent,listing_agent,buyers_agent')
        ->name('dashboard.data');

    // ── Profile & 2FA (all roles) ────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/two-factor/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::delete('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    // ── Theme Toggle (all roles) ────────────────────────
    Route::post('/theme/toggle', [ThemeController::class, 'toggle'])->name('theme.toggle');

    // ── Onboarding Wizard (all roles) ────────────────────────
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::get('/onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');

    // ── Calendar (all roles except field scouts) ────────────────────────
    Route::middleware('role:admin,agent,acquisition_agent,disposition_agent,listing_agent,buyers_agent')->group(function () {
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
        Route::get('/calendar/sync', [CalendarSyncController::class, 'settings'])->name('calendar.sync');
        Route::post('/calendar/sync/generate', [CalendarSyncController::class, 'generateFeed'])->name('calendar.sync.generate');
        Route::post('/calendar/sync/import', [CalendarSyncController::class, 'importFromUrl'])->name('calendar.sync.import');
        Route::delete('/calendar/sync/disconnect', [CalendarSyncController::class, 'disconnect'])->name('calendar.sync.disconnect');
    });

    // ── Activity Inbox (all roles except field scouts) ────────────────────────
    Route::middleware('role:admin,agent,acquisition_agent,disposition_agent,listing_agent,buyers_agent')->group(function () {
        Route::get('/activities', [ActivityInboxController::class, 'index'])->name('activities.index');
    });

    // ── Showings (real estate agent mode) ────────────────────────
    Route::middleware(['role:admin,agent,listing_agent,buyers_agent', 'mode:realestate'])->group(function () {
        Route::resource('showings', ShowingController::class);
    });

    // ── Open Houses (real estate agent mode) ──────────────────────
    Route::middleware(['role:admin,agent,listing_agent,buyers_agent', 'mode:realestate'])->group(function () {
        Route::resource('open-houses', OpenHouseController::class);
        Route::post('/open-houses/{openHouse}/attendees', [OpenHouseController::class, 'addAttendee'])->name('open-houses.addAttendee');
        Route::delete('/open-house-attendees/{attendee}', [OpenHouseController::class, 'removeAttendee'])->name('open-houses.removeAttendee');
    });

    // ── Listings Dashboard (real estate agent mode) ───────────────
    Route::middleware(['role:admin,agent,listing_agent,buyers_agent', 'mode:realestate'])->group(function () {
        Route::get('/listings', [ListingDashboardController::class, 'index'])->name('listings.index');
    });

    // ── Global Search (all roles) ────────────────────────
    Route::get('/search', [SearchController::class, 'search'])->name('search');

    // ── Notifications (all roles) ────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');

    // ── Saved Views (all authenticated roles) ────────────────────────
    Route::prefix('saved-views')->group(function () {
        Route::get('/', [SavedViewController::class, 'index'])->name('saved-views.index');
        Route::post('/', [SavedViewController::class, 'store'])->name('saved-views.store');
        Route::put('/{savedView}', [SavedViewController::class, 'update'])->name('saved-views.update');
        Route::delete('/{savedView}', [SavedViewController::class, 'destroy'])->name('saved-views.destroy');
        Route::post('/{savedView}/default', [SavedViewController::class, 'setDefault'])->name('saved-views.setDefault');
    });

    // ── AI endpoints (AJAX) ────────────────────────────
    Route::middleware('role:admin,agent,acquisition_agent,disposition_agent,listing_agent,buyers_agent')->prefix('ai')->group(function () {
        Route::post('/draft-followup', [AiController::class, 'draftFollowUp'])->name('ai.draftFollowUp');
        Route::post('/summarize-notes', [AiController::class, 'summarizeNotes'])->name('ai.summarizeNotes');
        Route::post('/analyze-deal', [AiController::class, 'analyzeDeal'])->name('ai.analyzeDeal');
        Route::post('/draft-buyer-message', [AiController::class, 'draftBuyerMessage'])->name('ai.draftBuyerMessage');
        Route::post('/score-lead', [AiController::class, 'scoreLead'])->name('ai.scoreLead');
        Route::post('/draft-sequence-step', [AiController::class, 'draftSequenceStep'])->name('ai.draftSequenceStep');
        Route::post('/offer-strategy', [AiController::class, 'offerStrategy'])->name('ai.offerStrategy');
        Route::post('/property-description', [AiController::class, 'propertyDescription'])->name('ai.propertyDescription');
        Route::post('/deal-stage-advice', [AiController::class, 'dealStageAdvice'])->name('ai.dealStageAdvice');
        Route::post('/suggest-csv-mapping', [AiController::class, 'suggestCsvMapping'])->name('ai.suggestCsvMapping');
        Route::post('/generate-all-sequence-steps', [AiController::class, 'generateAllSequenceSteps'])->name('ai.generateAllSequenceSteps');
        Route::post('/explain-buyer-match', [AiController::class, 'explainBuyerMatch'])->name('ai.explainBuyerMatch');
        Route::post('/weekly-digest', [AiController::class, 'weeklyDigest'])->name('ai.weeklyDigest');
        Route::post('/dnc-risk-check', [AiController::class, 'dncRiskCheck'])->name('ai.dncRiskCheck');
        Route::post('/objection-responses', [AiController::class, 'objectionResponses'])->name('ai.objectionResponses');
        Route::post('/suggest-tasks', [AiController::class, 'suggestTasks'])->name('ai.suggestTasks');
        Route::post('/apply-score', [AiController::class, 'applyScore'])->name('ai.applyScore');
        Route::post('/test-connection', [AiController::class, 'testConnection'])->name('ai.testConnection');
        Route::post('/list-models', [AiController::class, 'listModels'])->name('ai.listModels');
        Route::post('/lead-summary', [AiController::class, 'leadSummary'])->name('ai.leadSummary');
        Route::post('/comparable-sales', [AiController::class, 'comparableSales'])->name('ai.comparableSales');
        Route::post('/email-subject-lines', [AiController::class, 'emailSubjectLines'])->name('ai.emailSubjectLines');
        Route::post('/draft-email', [AiController::class, 'draftEmail'])->name('ai.draftEmail');
        Route::post('/pipeline-health', [AiController::class, 'pipelineHealth'])->name('ai.pipelineHealth');
        Route::post('/arv-analysis', [AiController::class, 'arvAnalysis'])->name('ai.arvAnalysis');
        Route::post('/draft-document', [AiController::class, 'draftDocument'])->name('ai.draftDocument');
        Route::post('/campaign-insights', [AiController::class, 'campaignInsights'])->name('ai.campaignInsights');
        Route::post('/buyer-risk', [AiController::class, 'buyerRiskAssessment'])->name('ai.buyerRisk');
        Route::post('/goal-recommendations', [AiController::class, 'goalRecommendations'])->name('ai.goalRecommendations');
        Route::post('/portal-description', [AiController::class, 'portalDescription'])->name('ai.portalDescription');
        Route::post('/apply-property-field', [AiController::class, 'applyPropertyField'])->name('ai.applyPropertyField');
        Route::post('/apply-lead-dnc', [AiController::class, 'applyLeadDnc'])->name('ai.applyLeadDnc');
        Route::post('/apply-buyer-notes', [AiController::class, 'applyBuyerNotes'])->name('ai.applyBuyerNotes');
        Route::post('/apply-campaign-notes', [AiController::class, 'applyCampaignNotes'])->name('ai.applyCampaignNotes');
        Route::post('/generate-buyer-notes', [AiController::class, 'generateBuyerNotes'])->name('ai.generateBuyerNotes');
        Route::post('/generate-campaign-notes', [AiController::class, 'generateCampaignNotes'])->name('ai.generateCampaignNotes');
        Route::post('/lead-briefing', [AiController::class, 'leadBriefing'])->name('ai.leadBriefing');
        Route::post('/deal-briefing', [AiController::class, 'dealBriefing'])->name('ai.dealBriefing');
        Route::post('/buyer-briefing', [AiController::class, 'buyerBriefing'])->name('ai.buyerBriefing');
        Route::post('/marketing-kit', [AiController::class, 'marketingKit'])->name('ai.marketingKit');
    });

    // ── Tags: admin ────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
        Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
        Route::post('/tags/attach', [TagController::class, 'attach'])->name('tags.attach');
        Route::post('/tags/detach', [TagController::class, 'detach'])->name('tags.detach');
    });

    // ── Leads: admin, agent, acquisition_agent, listing_agent, buyers_agent ──────────
    Route::middleware('role:admin,agent,acquisition_agent,listing_agent,buyers_agent')->group(function () {
        Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
        Route::get('/leads/kanban', [LeadKanbanController::class, 'index'])->name('leads.kanban');
        Route::post('/leads/bulk-action', [LeadController::class, 'bulkAction'])->name('leads.bulkAction');
        Route::resource('leads', LeadController::class);
        Route::patch('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.updateStatus');
        Route::post('/leads/{lead}/claim', [LeadController::class, 'claim'])->name('leads.claim');

        // Activities on leads
        Route::post('/leads/{lead}/activities', [ActivityController::class, 'store'])->name('leads.activities.store');
        Route::post('/leads/{lead}/send-email', [ActivityController::class, 'sendEmail'])->name('leads.sendEmail');
        Route::put('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

        // Tasks on leads
        Route::post('/leads/{lead}/tasks', [TaskController::class, 'store'])->name('leads.tasks.store');
        Route::patch('/tasks/{task}/toggle', [TaskController::class, 'toggleComplete'])->name('tasks.toggle');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        // Property for a lead (create/update from lead detail)
        Route::post('/leads/{lead}/property', [PropertyController::class, 'store'])->name('leads.property.store');

        // Lead photos
        Route::post('/leads/{lead}/photos', [LeadController::class, 'uploadPhoto'])->name('leads.photos.upload');
        Route::delete('/leads/{lead}/photos/{photo}', [LeadController::class, 'deletePhoto'])->name('leads.photos.delete');
    });

    // ── Properties: admin, agent, acquisition_agent, field_scout, listing_agent, buyers_agent ──
    Route::middleware('role:admin,agent,acquisition_agent,field_scout,listing_agent,buyers_agent')->group(function () {
        Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
    });

    // ── Field scout property submission: field_scout + admin ─────────
    Route::post('/properties', [PropertyController::class, 'fieldScoutStore'])
        ->middleware('role:admin,field_scout')
        ->name('properties.store');

    // ── Pipeline / Deals: all except field_scout ─────────────────────
    Route::middleware('role:admin,agent,acquisition_agent,disposition_agent,listing_agent,buyers_agent')->group(function () {
        Route::get('/pipeline/export', [DealController::class, 'export'])->name('deals.export');
        Route::get('/pipeline', [DealController::class, 'pipeline'])->name('pipeline');
        Route::get('/pipeline/{deal}', [DealController::class, 'show'])->name('deals.show');
        Route::put('/pipeline/{deal}', [DealController::class, 'update'])->name('deals.update');
        Route::patch('/pipeline/{deal}', [DealController::class, 'update'])->name('deals.quickUpdate');
        Route::patch('/pipeline/{deal}/stage', [DealController::class, 'updateStage'])->name('deals.updateStage');
        Route::post('/pipeline/{deal}/documents', [DealController::class, 'uploadDocument'])->name('deals.uploadDocument');
        Route::get('/pipeline/documents/{document}/download', [DealController::class, 'downloadDocument'])->name('deals.downloadDocument');
        Route::post('/pipeline/{deal}/notify-buyer/{match}', [DealController::class, 'notifyBuyer'])->name('deals.notifyBuyer');

        // Transaction Checklist
        Route::post('/pipeline/{deal}/checklist', [DealController::class, 'storeChecklist'])->name('deals.storeChecklist');
        Route::patch('/checklist/{item}', [DealController::class, 'updateChecklistItem'])->name('deals.updateChecklistItem');
        Route::post('/pipeline/{deal}/checklist/add', [DealController::class, 'addChecklistItem'])->name('deals.addChecklistItem');
        Route::delete('/checklist/{item}', [DealController::class, 'removeChecklistItem'])->name('deals.removeChecklistItem');

        // Offer Management
        Route::post('/pipeline/{deal}/offers', [DealController::class, 'storeOffer'])->name('deals.storeOffer');
        Route::patch('/offers/{offer}', [DealController::class, 'updateOffer'])->name('deals.updateOffer');
        Route::delete('/offers/{offer}', [DealController::class, 'destroyOffer'])->name('deals.destroyOffer');

        // Activities on deals
        Route::post('/pipeline/{deal}/activities', [ActivityController::class, 'storeDealActivity'])->name('deals.activities.store');

        // Document Generation (from deals)
        Route::get('/pipeline/{deal}/documents/generate', [DocumentGeneratorController::class, 'create'])->name('documents.generate');
        Route::post('/pipeline/{deal}/documents/generate', [DocumentGeneratorController::class, 'store'])->name('documents.store');
        Route::post('/documents/preview-deal/{deal}', [DocumentGeneratorController::class, 'previewWithDeal'])->name('documents.previewWithDeal');
        Route::get('/documents/{document}', [DocumentGeneratorController::class, 'show'])->name('documents.show');
        Route::get('/documents/{document}/print', [DocumentGeneratorController::class, 'print'])->name('documents.print');
        Route::delete('/documents/{document}', [DocumentGeneratorController::class, 'destroy'])->name('documents.destroy');

        // Disposition Room (WS mode)
        Route::get('/disposition/{deal}', [DispositionRoomController::class, 'show'])->name('disposition.show');
        Route::put('/disposition/{match}/status', [DispositionRoomController::class, 'updateStatus'])->name('disposition.updateStatus');
        Route::post('/disposition/{deal}/mass-outreach', [DispositionRoomController::class, 'massOutreach'])->name('disposition.massOutreach');

        // Investor Packet
        Route::get('/pipeline/{deal}/investor-packet', [DocumentGeneratorController::class, 'investorPacket'])->name('documents.investorPacket');
    });

    // ── Buyers: admin, disposition_agent, buyers_agent ────────────────
    Route::middleware('role:admin,disposition_agent,buyers_agent')->group(function () {
        Route::get('/buyers/export', [BuyerController::class, 'export'])->name('buyers.export');
        Route::post('/buyers/import', [BuyerController::class, 'import'])->name('buyers.import');
        Route::post('/buyers/bulk-action', [BuyerController::class, 'bulkAction'])->name('buyers.bulkAction');
        Route::resource('buyers', BuyerController::class);

        // Buyer POF & Verification
        Route::post('/buyers/{buyer}/upload-pof', [BuyerVerificationController::class, 'uploadPof'])->name('buyers.uploadPof');
        Route::delete('/buyers/{buyer}/remove-pof', [BuyerVerificationController::class, 'removePof'])->name('buyers.removePof');
        Route::get('/buyers/{buyer}/download-pof', [BuyerVerificationController::class, 'downloadPof'])->name('buyers.downloadPof');
        Route::post('/buyers/{buyer}/recalculate-score', [BuyerVerificationController::class, 'recalculateScore'])->name('buyers.recalculateScore');

        // Buyer Transactions
        Route::post('/buyers/{buyer}/transactions', [BuyerTransactionController::class, 'store'])->name('buyers.transactions.store');
        Route::delete('/buyer-transactions/{transaction}', [BuyerTransactionController::class, 'destroy'])->name('buyer-transactions.destroy');
    });

    // ── Goals: all except field_scout ────────────────────
    Route::middleware('role:admin,agent,acquisition_agent,disposition_agent')->group(function () {
        Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
        Route::post('/goals/forecast', [GoalController::class, 'forecast'])->name('goals.forecast');
    });
    Route::middleware('role:admin')->group(function () {
        Route::get('/goals/create', [GoalController::class, 'create'])->name('goals.create');
        Route::post('/goals', [GoalController::class, 'store'])->name('goals.store');
        Route::get('/goals/{goal}/edit', [GoalController::class, 'edit'])->name('goals.edit');
        Route::put('/goals/{goal}', [GoalController::class, 'update'])->name('goals.update');
        Route::delete('/goals/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');
        Route::post('/goals/store-from-ai', [GoalController::class, 'storeFromAi'])->name('goals.storeFromAi');
    });

    // ── Comparable Sales / ARV Worksheet ─────────────────
    Route::middleware('role:admin,agent,acquisition_agent,field_scout')->group(function () {
        Route::post('/properties/{property}/comps', [ComparableSaleController::class, 'store'])->name('comps.store');
        Route::put('/comps/{comp}', [ComparableSaleController::class, 'update'])->name('comps.update');
        Route::delete('/comps/{comp}', [ComparableSaleController::class, 'destroy'])->name('comps.destroy');
        Route::get('/properties/{property}/arv-summary', [ComparableSaleController::class, 'arvSummary'])->name('comps.arvSummary');
    });

    // ── Lists / CSV Import: admin ───────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::get('/lists', [ListController::class, 'index'])->name('lists.index');
        Route::get('/lists/import', [ListController::class, 'create'])->name('lists.create');
        Route::post('/lists/import', [ListController::class, 'import'])->name('lists.import');
        Route::get('/lists/import-status/{importLog}', [ListController::class, 'importStatus'])->name('lists.importStatus');
        Route::post('/lists/preview', [ListController::class, 'preview'])->name('lists.preview');
        Route::get('/lists/saved-mappings', [ListController::class, 'savedMappings'])->name('lists.savedMappings');
        Route::post('/lists/save-mapping', [ListController::class, 'saveMappingAction'])->name('lists.saveMapping');
        Route::delete('/lists/saved-mappings/{mapping}', [ListController::class, 'deleteMappingAction'])->name('lists.deleteMapping');
        Route::get('/lists/{leadList}', [ListController::class, 'show'])->name('lists.show');
        Route::delete('/lists/{leadList}', [ListController::class, 'destroy'])->name('lists.destroy');
    });

    // ── Sequences: admin ────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::resource('sequences', SequenceController::class);
        Route::post('/sequences/{sequence}/enroll', [SequenceController::class, 'enroll'])->name('sequences.enroll');
        Route::delete('/sequences/{sequence}/unenroll/{lead}', [SequenceController::class, 'unenroll'])->name('sequences.unenroll');
    });

    // ── Workflows: admin ────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::get('/workflows', [WorkflowController::class, 'index'])->name('workflows.index');
        Route::get('/workflows/create', [WorkflowController::class, 'create'])->name('workflows.create');
        Route::post('/workflows', [WorkflowController::class, 'store'])->name('workflows.store');
        Route::get('/workflows/templates', [WorkflowController::class, 'templates'])->name('workflows.templates');
        Route::post('/workflows/create-from-template', [WorkflowController::class, 'createFromTemplate'])->name('workflows.createFromTemplate');
        Route::get('/workflows/{workflow}/edit', [WorkflowController::class, 'edit'])->name('workflows.edit');
        Route::put('/workflows/{workflow}', [WorkflowController::class, 'update'])->name('workflows.update');
        Route::delete('/workflows/{workflow}', [WorkflowController::class, 'destroy'])->name('workflows.destroy');
        Route::post('/workflows/{workflow}/toggle', [WorkflowController::class, 'toggle'])->name('workflows.toggle');
        Route::post('/workflows/{workflow}/steps', [WorkflowController::class, 'storeStep'])->name('workflows.steps.store');
        Route::put('/workflows/steps/{step}', [WorkflowController::class, 'updateStep'])->name('workflows.steps.update');
        Route::delete('/workflows/steps/{step}', [WorkflowController::class, 'destroyStep'])->name('workflows.steps.destroy');
        Route::post('/workflows/{workflow}/reorder', [WorkflowController::class, 'reorderSteps'])->name('workflows.reorder');
        Route::get('/workflows/{workflow}/logs', [WorkflowController::class, 'logs'])->name('workflows.logs');
        Route::post('/workflows/{workflow}/trigger', [WorkflowController::class, 'triggerManual'])->name('workflows.trigger');
    });

    // ── Campaigns: admin ─────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::resource('campaigns', CampaignController::class);
    });

    // ── Reports: admin ──────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/leads-by-source', [ReportController::class, 'exportLeadsBySource'])->name('reports.exportLeadsBySource');
        Route::get('/reports/export/top-agents', [ReportController::class, 'exportTopAgents'])->name('reports.exportTopAgents');
        Route::get('/reports/export/funnel', [ReportController::class, 'exportFunnel'])->name('reports.exportFunnel');
        Route::get('/reports/export/team-performance', [ReportController::class, 'exportTeamPerformance'])->name('reports.exportTeamPerformance');
        Route::get('/reports/export/list-stacking', [ReportController::class, 'exportListStacking'])->name('reports.exportListStacking');
    });

    // ── Settings / Plugins / DNC: admin ─────
    Route::middleware('role:admin')->group(function () {
        // Audit Log
        Route::get('/audit-log/export', [AuditLogController::class, 'export'])->name('audit-log.export');
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

        // AI History
        Route::get('/ai-history', [AiLogController::class, 'index'])->name('ai-log.index');
        Route::get('/ai-history/{aiLog}', [AiLogController::class, 'show'])->name('ai-log.show');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.updateGeneral');
        Route::post('/settings/invite-agent', [SettingsController::class, 'inviteAgent'])->name('settings.inviteAgent');
        Route::patch('/settings/agents/{user}/toggle', [SettingsController::class, 'toggleAgent'])->name('settings.toggleAgent');
        Route::delete('/settings/agents/{user}/reset-2fa', [SettingsController::class, 'reset2fa'])->name('settings.reset2fa');
        Route::put('/settings/distribution', [SettingsController::class, 'updateDistribution'])->name('settings.updateDistribution');

        // DNC Management
        Route::get('/settings/dnc', [DncController::class, 'index'])->name('dnc.index');
        Route::post('/settings/dnc', [DncController::class, 'store'])->name('dnc.store');
        Route::post('/settings/dnc/import', [DncController::class, 'import'])->name('dnc.import');
        Route::delete('/settings/dnc/{doNotContact}', [DncController::class, 'destroy'])->name('dnc.destroy');

        // Lead Source Costs
        Route::put('/settings/lead-source-costs', [SettingsController::class, 'updateLeadSourceCosts'])->name('settings.updateLeadSourceCosts');

        // Lead Sources Management (legacy, redirects to custom options)
        Route::post('/settings/lead-sources', [SettingsController::class, 'addLeadSource'])->name('settings.addLeadSource');
        Route::delete('/settings/lead-sources', [SettingsController::class, 'removeLeadSource'])->name('settings.removeLeadSource');

        // Custom Field Options
        Route::post('/settings/custom-options', [SettingsController::class, 'addCustomOption'])->name('settings.addCustomOption');
        Route::delete('/settings/custom-options', [SettingsController::class, 'removeCustomOption'])->name('settings.removeCustomOption');

        // Custom Field Definitions
        Route::post('/settings/custom-fields', [SettingsController::class, 'storeCustomField'])->name('settings.storeCustomField');
        Route::delete('/settings/custom-fields/{customField}', [SettingsController::class, 'destroyCustomField'])->name('settings.destroyCustomField');

        // Plugin Management
        Route::get('/settings/plugins', [PluginController::class, 'index'])->name('plugins.index');
        Route::post('/settings/plugins/upload', [PluginController::class, 'upload'])->name('plugins.upload');
        Route::patch('/settings/plugins/{plugin}/toggle', [PluginController::class, 'toggle'])->name('plugins.toggle');
        Route::delete('/settings/plugins/{plugin}', [PluginController::class, 'uninstall'])->name('plugins.uninstall');
        // Roles & Permissions
        Route::get('/settings/roles', [SettingsController::class, 'roles'])->name('settings.roles');
        Route::post('/settings/roles', [SettingsController::class, 'createRole'])->name('settings.createRole');
        Route::put('/settings/roles/{role}/permissions', [SettingsController::class, 'updateRolePermissions'])->name('settings.updateRolePermissions');
        Route::delete('/settings/roles/{role}', [SettingsController::class, 'deleteRole'])->name('settings.deleteRole');

        // Impersonation
        Route::post('/settings/impersonate/{user}', [SettingsController::class, 'impersonate'])->name('settings.impersonate');
        Route::get('/stop-impersonation', [SettingsController::class, 'stopImpersonation'])->name('settings.stopImpersonation');

        // System Health
        Route::post('/settings/api/generate-key', [SettingsController::class, 'generateApiKey'])->name('settings.generateApiKey');
        Route::post('/settings/api/toggle', [SettingsController::class, 'toggleApi'])->name('settings.toggleApi');

        // Email / SMTP Settings
        Route::put('/settings/email', [SettingsController::class, 'updateMail'])->name('settings.updateMail');
        Route::post('/settings/email/test', [SettingsController::class, 'testEmail'])->name('settings.testEmail');

        // Notification Preferences
        Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.updateNotifications');

        // Dashboard Defaults
        Route::put('/settings/dashboard-defaults', [SettingsController::class, 'updateDashboardDefaults'])->name('settings.updateDashboardDefaults');

        // AI Settings
        Route::put('/settings/ai', [SettingsController::class, 'updateAiSettings'])->name('settings.updateAiSettings');
        Route::post('/settings/ai/toggle', [SettingsController::class, 'toggleAi'])->name('settings.toggleAi');
        Route::post('/settings/ai/toggle-briefings', [SettingsController::class, 'toggleAiBriefings'])->name('settings.toggleAiBriefings');

        Route::get('/settings/health', [SettingsController::class, 'health'])->name('settings.health');

        // Webhooks
        Route::post('/settings/webhooks', [SettingsController::class, 'storeWebhook'])->name('settings.storeWebhook');
        Route::patch('/settings/webhooks/{webhook}/toggle', [SettingsController::class, 'toggleWebhook'])->name('settings.toggleWebhook');
        Route::delete('/settings/webhooks/{webhook}', [SettingsController::class, 'destroyWebhook'])->name('settings.destroyWebhook');

        // Integrations & Security
        Route::put('/settings/security', [IntegrationController::class, 'updateSecurity'])->name('settings.updateSecurity');
        Route::post('/settings/integrations', [IntegrationController::class, 'store'])->name('integrations.store');
        Route::patch('/settings/integrations/{integration}/toggle', [IntegrationController::class, 'toggle'])->name('integrations.toggle');
        Route::delete('/settings/integrations/{integration}', [IntegrationController::class, 'destroy'])->name('integrations.destroy');

        // Document Templates (admin manages templates)
        Route::get('/document-templates', [DocumentTemplateController::class, 'index'])->name('document-templates.index');
        Route::get('/document-templates/create', [DocumentTemplateController::class, 'create'])->name('document-templates.create');
        Route::post('/document-templates', [DocumentTemplateController::class, 'store'])->name('document-templates.store');
        Route::post('/document-templates/preview-content', [DocumentTemplateController::class, 'previewContent'])->name('document-templates.previewContent');
        Route::get('/document-templates/{documentTemplate}/edit', [DocumentTemplateController::class, 'edit'])->name('document-templates.edit');
        Route::put('/document-templates/{documentTemplate}', [DocumentTemplateController::class, 'update'])->name('document-templates.update');
        Route::delete('/document-templates/{documentTemplate}', [DocumentTemplateController::class, 'destroy'])->name('document-templates.destroy');
        Route::post('/document-templates/{documentTemplate}/preview', [DocumentTemplateController::class, 'preview'])->name('document-templates.preview');

        // Email Templates
        Route::get('/settings/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::post('/settings/email-templates', [EmailTemplateController::class, 'store'])->name('email-templates.store');
        Route::get('/settings/email-templates/{id}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::put('/settings/email-templates/{id}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
        Route::delete('/settings/email-templates/{id}', [EmailTemplateController::class, 'destroy'])->name('email-templates.destroy');
        Route::get('/settings/email-templates/{id}/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');

        // API Documentation
        Route::get('/api-docs', [ApiDocsController::class, 'index'])->name('api-docs.index');
        Route::get('/api-docs/openapi.json', [ApiDocsController::class, 'json'])->name('api-docs.json');

        // PDF Report Export
        Route::get('/reports/pdf/leads', [PdfExportController::class, 'exportLeadReport'])->name('reports.pdf.leads');
        Route::get('/reports/pdf/pipeline', [PdfExportController::class, 'exportPipelineReport'])->name('reports.pdf.pipeline');
        Route::get('/reports/pdf/team', [PdfExportController::class, 'exportTeamReport'])->name('reports.pdf.team');

        // GDPR Data Export/Deletion
        Route::post('/settings/gdpr/export-user', [GdprController::class, 'exportData'])->name('gdpr.exportUser');
        Route::post('/settings/gdpr/delete-user', [GdprController::class, 'deleteData'])->name('gdpr.deleteUser');
        Route::post('/settings/gdpr/export-contact', [GdprController::class, 'exportContactData'])->name('gdpr.exportContact');
        Route::post('/settings/gdpr/delete-contact', [GdprController::class, 'deleteContactData'])->name('gdpr.deleteContact');

        // Storage Settings
        Route::put('/settings/storage', [SettingsController::class, 'updateStorage'])->name('settings.updateStorage');
        Route::post('/settings/storage/test', [SettingsController::class, 'testS3Connection'])->name('settings.testS3');

        // SMS Test
        Route::post('/settings/sms/test', [SettingsController::class, 'testSms'])->name('settings.testSms');

        // Backups
        Route::get('/settings/backups/list', [SettingsController::class, 'backupList'])->name('settings.backupList');
        Route::post('/settings/backups/create', [SettingsController::class, 'backupCreate'])->name('settings.backupCreate');
        Route::get('/settings/backups/download/{filename}', [SettingsController::class, 'backupDownload'])->name('settings.backupDownload');
        Route::delete('/settings/backups/{filename}', [SettingsController::class, 'backupDelete'])->name('settings.backupDelete');

        // In-app updates
        Route::post('/settings/updates/upload', [UpdateController::class, 'upload'])->name('settings.updates.upload');
        Route::post('/settings/updates/{update}/apply', [UpdateController::class, 'apply'])->name('settings.updates.apply');
        Route::post('/settings/updates/{update}/restore', [UpdateController::class, 'restore'])->name('settings.updates.restore');
        Route::delete('/settings/updates/{update}', [UpdateController::class, 'discard'])->name('settings.updates.discard');
        Route::get('/settings/snapshots', fn () => redirect()->route('settings.index', ['tab' => 'system']))->name('settings.snapshots.index');
        Route::get('/settings/snapshots/{snapshot}/status', [UpdateController::class, 'snapshotStatus'])->name('settings.snapshots.status');
        Route::post('/settings/snapshots', [UpdateController::class, 'createSnapshot'])->name('settings.snapshots.create');
        Route::post('/settings/snapshots/{snapshot}/restore', [UpdateController::class, 'restoreManualSnapshot'])->name('settings.snapshots.restore');
        Route::delete('/settings/snapshots/{snapshot}', [UpdateController::class, 'deleteManualSnapshot'])->name('settings.snapshots.delete');

        // API Logs
        Route::get('/settings/api-logs', [SettingsController::class, 'apiLogs'])->name('settings.apiLogs');

        // Factory Reset
        Route::post('/settings/factory-reset', [SettingsController::class, 'factoryReset'])->name('settings.factoryReset');

        // Language Management
        Route::get('/settings/languages', [SettingsController::class, 'getLanguages'])->name('settings.getLanguages');
        Route::post('/settings/languages/upload', [SettingsController::class, 'uploadLanguageFile'])->name('settings.uploadLanguageFile');
        Route::get('/settings/languages/{code}', [SettingsController::class, 'getLanguageFile'])->name('settings.getLanguageFile');
        Route::put('/settings/languages/{code}', [SettingsController::class, 'saveLanguageFile'])->name('settings.saveLanguageFile');

        // Buyer Portal Settings
        Route::put('/settings/buyer-portal', [BuyerPortalSettingsController::class, 'update'])->name('settings.updateBuyerPortal');

        // Webhook Integration Recipes
        Route::get('/webhooks/recipes', [WebhookRecipeController::class, 'index'])->name('webhooks.recipes');

        // Error / Bug Reports
        Route::get('/error-logs', [ErrorLogController::class, 'index'])->name('error-logs.index');
        Route::delete('/error-logs/clear', [ErrorLogController::class, 'clear'])->name('error-logs.clear');
        Route::get('/error-logs/{id}', [ErrorLogController::class, 'show'])->name('error-logs.show');
        Route::patch('/error-logs/{id}/toggle', [ErrorLogController::class, 'toggleResolved'])->name('error-logs.toggle');
        Route::get('/error-logs/{id}/export', [ErrorLogController::class, 'export'])->name('error-logs.export');
    });

    // ── Knowledge Base (all roles) ──────────────────────────
    Route::get('/help', [KnowledgeBaseController::class, 'index'])->name('help.index');
    Route::get('/help/{slug}', [KnowledgeBaseController::class, 'show'])->name('help.show');
});





