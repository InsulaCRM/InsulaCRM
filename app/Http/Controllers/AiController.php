<?php

namespace App\Http\Controllers;

use App\Models\AiLog;
use App\Models\Buyer;
use App\Models\Campaign;
use App\Models\ComparableSale;
use App\Models\Deal;
use App\Models\DealBuyerMatch;
use App\Models\Goal;
use App\Models\Lead;
use App\Models\Property;
use App\Services\AiService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected function ai(): AiService
    {
        return new AiService(auth()->user()->tenant);
    }

    /**
     * POST /ai/draft-followup
     * Draft a follow-up message for a lead.
     */
    public function draftFollowUp(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
            'type' => 'required|in:sms,email,voicemail,call,direct_mail,note,meeting',
        ]);

        $ai = $this->ai();
        if (!$ai->isAvailable()) {
            return response()->json(['error' => 'AI is not configured. Go to Settings > AI to set up.'], 422);
        }

        $lead = Lead::findOrFail($request->lead_id);
        $this->authorize('view', $lead);

        return $this->runAiRequest(fn () => ['message' => $ai->draftFollowUp($lead, $request->type)]);
    }

    /**
     * POST /ai/summarize-notes
     * Summarize activity history for a lead.
     */
    public function summarizeNotes(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
        ]);

        $ai = $this->ai();
        if (!$ai->isAvailable()) {
            return response()->json(['error' => 'AI is not configured. Go to Settings > AI to set up.'], 422);
        }

        $lead = Lead::findOrFail($request->lead_id);
        $this->authorize('view', $lead);

        return $this->runAiRequest(fn () => ['summary' => $ai->summarizeNotes($lead)]);
    }

    /**
     * POST /ai/analyze-deal
     * Analyze a deal for risk/opportunity.
     */
    public function analyzeDeal(Request $request)
    {
        $request->validate([
            'deal_id' => 'required|integer',
        ]);

        $ai = $this->ai();
        if (!$ai->isAvailable()) {
            return response()->json(['error' => 'AI is not configured. Go to Settings > AI to set up.'], 422);
        }

        $deal = Deal::findOrFail($request->deal_id);
        $this->authorize('view', $deal);

        return $this->runAiRequest(function () use ($ai, $deal) {
            $result = $ai->analyzeDealWithActions($deal);
            AiLog::record('deal_analysis', $result['text'], [
                'model_type' => Deal::class, 'model_id' => $deal->id,
                'prompt_summary' => "Deal analysis for {$deal->title}",
            ]);
            return ['analysis' => $result['text'], 'actions' => $result['actions'], 'lead_id' => $deal->lead_id];
        });
    }

    /**
     * POST /ai/draft-buyer-message
     * Draft outreach message for a matched buyer.
     */
    public function draftBuyerMessage(Request $request)
    {
        $request->validate([
            'deal_id' => 'required|integer',
            'buyer_id' => 'required|integer',
        ]);

        $ai = $this->ai();
        if (!$ai->isAvailable()) {
            return response()->json(['error' => 'AI is not configured. Go to Settings > AI to set up.'], 422);
        }

        $deal = Deal::findOrFail($request->deal_id);
        $buyer = Buyer::findOrFail($request->buyer_id);
        $this->authorize('view', $deal);
        $this->authorize('view', $buyer);

        return $this->runAiRequest(fn () => ['message' => $ai->draftBuyerMessage($deal, $buyer)]);
    }

    /**
     * POST /ai/score-lead
     * AI-enhanced motivation scoring.
     */
    public function scoreLead(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
        ]);

        $ai = $this->ai();
        if (!$ai->isAvailable()) {
            return response()->json(['error' => 'AI is not configured. Go to Settings > AI to set up.'], 422);
        }

        $lead = Lead::findOrFail($request->lead_id);
        $this->authorize('view', $lead);

        return $this->runAiRequest(function () use ($ai, $lead) {
            $scoring = $ai->scoreLeadMotivation($lead);
            AiLog::record('score', json_encode($scoring), [
                'model_type' => Lead::class, 'model_id' => $lead->id,
                'prompt_summary' => "AI score for {$lead->full_name}",
                'metadata' => ['score' => $scoring['score'], 'confidence' => $scoring['confidence']],
            ]);
            return ['scoring' => $scoring];
        });
    }

    /**
     * POST /ai/draft-sequence-step
     * Generate a message template for a drip sequence step.
     */
    public function draftSequenceStep(Request $request)
    {
        $request->validate([
            'sequence_name' => 'required|string|max:255',
            'step_number' => 'required|integer|min:1',
            'total_steps' => 'required|integer|min:1',
            'action_type' => 'required|in:sms,email,voicemail,task,direct_mail',
            'delay_days' => 'required|integer|min:0',
            'previous_step' => 'nullable|string',
        ]);

        $ai = $this->ai();
        if (!$ai->isAvailable()) {
            return response()->json(['error' => 'AI is not configured. Go to Settings > AI to set up.'], 422);
        }

        return $this->runAiRequest(fn () => ['message' => $ai->draftSequenceStep(
                $request->sequence_name,
                $request->step_number,
                $request->total_steps,
                $request->action_type,
                $request->delay_days,
                $request->previous_step,
            )]);
    }

    /**
     * POST /ai/test-connection
     * Test the AI provider connection.
     */
    public function testConnection()
    {
        $ai = $this->ai();
        if (!$ai->isAvailable()) {
            return response()->json(['error' => 'AI is not configured.'], 422);
        }

        try {
            $ok = $ai->testConnection();
            return response()->json(['success' => $ok, 'message' => $ok ? __('Connection successful!') : __('Connection failed. Check your API key and provider settings.')]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AI connection test failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('Connection failed. Check your API key and provider settings.')]);
        }
    }

    /**
     * POST /ai/list-models
     * Fetch available models from the configured (or specified) provider.
     */
    public function listModels(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:openai,anthropic,gemini,ollama,custom',
            'api_key' => 'nullable|string',
            'ollama_url' => 'nullable|string',
            'custom_url' => 'nullable|string',
        ]);

        $tenant = auth()->user()->tenant;

        // Use provided values or fall back to saved tenant config
        $provider = $request->provider;
        $apiKey = $request->api_key ?: $tenant->ai_api_key;
        $ollamaUrl = $request->ollama_url ?: $tenant->ai_ollama_url;
        $customUrl = $request->custom_url ?: $tenant->ai_custom_url;

        try {
            $providerInstance = AiService::createProvider($provider, $apiKey, null, $ollamaUrl, $customUrl);
            $models = $providerInstance->listModels();
            return response()->json(['success' => true, 'models' => $models]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AI model listing failed', ['provider' => $provider, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'models' => [], 'error' => __('Could not fetch models. Check your API key and provider URL.')]);
        }
    }

    // ── Feature 1: Property Offer Strategy ──────────────────────

    public function offerStrategy(Request $request)
    {
        $request->validate(['property_id' => 'required|integer']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $property = Property::findOrFail($request->property_id);
        $this->authorize('view', $property);

        return $this->runAiRequest(fn () => ['strategy' => $ai->suggestOfferStrategy($property)]);
    }

    // ── Feature 3: Property Description Generator ──────────────────────

    public function propertyDescription(Request $request)
    {
        $request->validate(['property_id' => 'required|integer']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $property = Property::findOrFail($request->property_id);
        $this->authorize('view', $property);

        return $this->runAiRequest(fn () => ['description' => $ai->generatePropertyDescription($property)]);
    }

    // ── Feature 4: Deal Stage Advisor ──────────────────────

    public function dealStageAdvice(Request $request)
    {
        $request->validate(['deal_id' => 'required|integer']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $deal = Deal::findOrFail($request->deal_id);
        $this->authorize('view', $deal);

        return $this->runAiRequest(function () use ($ai, $deal) {
            $result = $ai->adviseDealStageWithActions($deal);
            AiLog::record('stage_advice', $result['text'], [
                'model_type' => Deal::class, 'model_id' => $deal->id,
                'prompt_summary' => "Stage advice for {$deal->title} ({$deal->stage})",
            ]);
            return ['advice' => $result['text'], 'actions' => $result['actions'], 'lead_id' => $deal->lead_id];
        });
    }

    // ── Feature 5: CSV Column Auto-Mapping ──────────────────────

    public function suggestCsvMapping(Request $request)
    {
        $request->validate([
            'headers' => 'required|array',
            'sample_rows' => 'required|array|max:5',
        ]);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        return $this->runAiRequest(fn () => ['mapping' => $ai->suggestCsvMapping($request->headers, $request->sample_rows)]);
    }

    // ── Feature 6: Bulk Sequence Template Generation ──────────────────────

    public function generateAllSequenceSteps(Request $request)
    {
        $request->validate([
            'sequence_name' => 'required|string|max:255',
            'steps' => 'required|array|min:1',
            'steps.*.action_type' => 'required|string',
            'steps.*.delay_days' => 'required|integer|min:0',
        ]);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        return $this->runAiRequest(fn () => ['templates' => $ai->generateAllSequenceSteps($request->sequence_name, $request->steps)]);
    }

    // ── Feature 7: Buyer Match Explanation ──────────────────────

    public function explainBuyerMatch(Request $request)
    {
        $request->validate(['deal_id' => 'required|integer', 'buyer_id' => 'required|integer']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $deal = Deal::findOrFail($request->deal_id);
        $buyer = Buyer::findOrFail($request->buyer_id);
        $match = DealBuyerMatch::where('deal_id', $deal->id)->where('buyer_id', $buyer->id)->first();
        $this->authorize('view', $deal);
        $this->authorize('view', $buyer);

        return $this->runAiRequest(fn () => ['explanation' => $ai->explainBuyerMatch($deal, $buyer, $match)]);
    }

    // ── Feature 8: Dashboard Weekly Digest ──────────────────────

    public function weeklyDigest()
    {
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $tenantId = auth()->user()->tenant_id;
        $now = now();
        $weekAgo = $now->copy()->subWeek();
        $monthAgo = $now->copy()->subMonth();

        $kpiData = [
            'total_leads' => Lead::count(),
            'leads_this_week' => Lead::where('created_at', '>=', $weekAgo)->count(),
            'leads_this_month' => Lead::where('created_at', '>=', $monthAgo)->count(),
            'hot_leads' => Lead::where('temperature', 'hot')->count(),
            'active_deals' => \App\Models\Deal::whereNotIn('stage', ['closed_won', 'closed_lost', 'dead'])->count(),
            'deals_closed_this_month' => \App\Models\Deal::where('stage', 'closed_won')->where('updated_at', '>=', $monthAgo)->count(),
            'total_pipeline_value' => \Fmt::currency(\App\Models\Deal::whereNotIn('stage', ['closed_won', 'closed_lost', 'dead'])->sum('contract_price')),
            'total_fees_this_month' => \Fmt::currency(\App\Models\Deal::where('stage', 'closed_won')->where('updated_at', '>=', $monthAgo)->sum(\App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column'])),
            'total_buyers' => Buyer::count(),
            'pending_tasks' => \App\Models\Task::where('is_completed', false)->count(),
            'overdue_tasks' => \App\Models\Task::where('is_completed', false)->where('due_date', '<', $now)->count(),
        ];

        return $this->runAiRequest(function () use ($ai, $kpiData) {
            $digest = $ai->generateWeeklyDigest($kpiData);
            AiLog::record('digest', $digest, [
                'prompt_summary' => 'Weekly AI digest (dashboard)',
            ]);
            return ['digest' => $digest];
        });
    }

    // ── Feature 9: DNC Risk Flagging ──────────────────────

    public function dncRiskCheck(Request $request)
    {
        $request->validate(['lead_id' => 'required|integer']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $lead = Lead::findOrFail($request->lead_id);
        $this->authorize('view', $lead);

        return $this->runAiRequest(function () use ($ai, $lead) {
            $risk = $ai->flagDncRisks($lead);
            AiLog::record('dnc_risk', $risk, [
                'model_type' => Lead::class, 'model_id' => $lead->id,
                'prompt_summary' => "DNC risk check for {$lead->full_name}",
            ]);
            return ['risk' => $risk];
        });
    }

    /**
     * POST /ai/apply-score
     * Save an AI-generated motivation score to the lead.
     */
    public function applyScore(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
            'score' => 'required|integer|min:0|max:100',
        ]);

        $lead = Lead::findOrFail($request->lead_id);
        $this->authorize('update', $lead);
        $lead->update(['ai_motivation_score' => $request->score]);

        return response()->json(['success' => true]);
    }

    // ── Feature 11: AI Task Suggestions ──────────────────────

    public function suggestTasks(Request $request)
    {
        $request->validate(['lead_id' => 'required|integer']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $lead = Lead::findOrFail($request->lead_id);
        $this->authorize('view', $lead);

        try {
            $tasks = $ai->suggestTasks($lead);
            if (empty($tasks)) {
                return response()->json(['error' => 'AI could not generate task suggestions. Try again.'], 422);
            }
            return response()->json(['success' => true, 'tasks' => $tasks]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'AI request failed: ' . $e->getMessage()], 500);
        }
    }

    // ── Feature 10: Objection Library ──────────────────────

    public function objectionResponses(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
            'objection' => 'nullable|string|max:500',
        ]);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $lead = Lead::findOrFail($request->lead_id);
        $this->authorize('view', $lead);

        return $this->runAiRequest(fn () => ['responses' => $ai->generateObjectionResponses($lead, $request->objection)]);
    }

    // ── Feature 12: AI Lead Summary ──────────────────────

    public function leadSummary(Request $request)
    {
        $request->validate(['lead_id' => 'required|integer']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured. Go to Settings > AI to set up.'], 422);

        $lead = Lead::findOrFail($request->lead_id);
        $this->authorize('view', $lead);

        return $this->runAiRequest(function () use ($ai, $lead) {
            $summary = $ai->leadSummary($lead);
            AiLog::record('lead_snapshot', $summary, [
                'model_type' => Lead::class, 'model_id' => $lead->id,
                'prompt_summary' => "Lead summary for {$lead->full_name}",
            ]);
            return ['summary' => $summary];
        });
    }

    // ── Feature 13: AI Comparable Sales Analysis ──────────────────────

    public function comparableSales(Request $request)
    {
        $request->validate(['property_id' => 'required|integer']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured. Go to Settings > AI to set up.'], 422);

        $property = Property::findOrFail($request->property_id);
        $this->authorize('view', $property);

        return $this->runAiRequest(fn () => ['analysis' => $ai->comparableSalesAnalysis($property)]);
    }

    // ── Feature 14: AI Email Subject Lines ──────────────────────

    public function emailSubjectLines(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
            'body' => 'required|string|max:5000',
        ]);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured. Go to Settings > AI to set up.'], 422);

        $lead = Lead::findOrFail($request->lead_id);
        $this->authorize('view', $lead);

        return $this->runAiRequest(fn () => ['subject_lines' => $ai->generateEmailSubjectLines($lead, $request->body)]);
    }

    // ── AI Draft Full Email ──────────────────────

    public function draftEmail(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|integer',
        ]);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $lead = Lead::findOrFail($request->lead_id);
        $this->authorize('view', $lead);

        return $this->runAiRequest(function () use ($ai, $lead) {
            $body = $ai->draftFollowUp($lead, 'email');
            $subjects = $ai->generateEmailSubjectLines($lead, $body);
            AiLog::record('draft_email', $body, [
                'model_type' => Lead::class,
                'model_id' => $lead->id,
                'prompt_summary' => "Draft email for {$lead->full_name}",
            ]);
            return [
                'body' => $body,
                'subject' => $subjects[0]['subject'] ?? '',
                'subject_lines' => $subjects,
            ];
        });
    }

    // ── Feature 15: AI Pipeline Health ──────────────────────

    public function pipelineHealth()
    {
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured. Go to Settings > AI to set up.'], 422);

        $deals = Deal::all();
        $stages = Deal::stageLabels();
        $metrics = [];

        foreach ($stages as $key => $label) {
            $stageDeals = $deals->where('stage', $key);
            $count = $stageDeals->count();
            $avgDays = $count > 0
                ? round($stageDeals->avg(fn ($d) => $d->stage_changed_at ? (int) now()->diffInDays($d->stage_changed_at, true) : 0))
                : 0;
            $value = \Fmt::currency($stageDeals->sum('contract_price'));
            $metrics[] = "{$label}: {$count} deals, avg {$avgDays}d in stage, value {$value}";
        }

        // Summary stats
        $activeDeals = $deals->whereNotIn('stage', ['closed_won', 'closed_lost']);
        $metrics[] = "";
        $metrics[] = "SUMMARY:";
        $metrics[] = "Total active deals: {$activeDeals->count()}";
        $metrics[] = "Total pipeline value: " . \Fmt::currency($activeDeals->sum('contract_price'));
        $feeCol = \App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column'];
        $feeLabel = \App\Services\BusinessModeService::isRealEstate() ? 'Total commissions' : 'Total assignment fees';
        $metrics[] = "{$feeLabel}: " . \Fmt::currency($activeDeals->sum($feeCol));

        $stuckDeals = $activeDeals->filter(fn ($d) => $d->stage_changed_at && now()->diffInDays($d->stage_changed_at, true) > 7)->count();
        $metrics[] = "Deals stuck >7 days: {$stuckDeals}";

        $closedThisMonth = $deals->where('stage', 'closed_won')
            ->filter(fn ($d) => $d->updated_at && $d->updated_at->isCurrentMonth())
            ->count();
        $metrics[] = "Deals closed this month: {$closedThisMonth}";

        $closedTotal = $deals->where('stage', 'closed_won')->count();
        $totalDeals = $deals->count();
        $conversionRate = $totalDeals > 0 ? round(($closedTotal / $totalDeals) * 100, 1) : 0;
        $metrics[] = "Overall conversion rate: {$conversionRate}%";

        return $this->runAiRequest(function () use ($ai, $metrics) {
            $analysis = $ai->pipelineHealth($metrics);
            AiLog::record('pipeline_health', $analysis, [
                'prompt_summary' => 'Pipeline health analysis (dashboard)',
            ]);
            return ['analysis' => $analysis];
        });
    }

    // ── ARV Analysis (Comps Worksheet) ──────────────────────

    public function arvAnalysis(Request $request)
    {
        $request->validate(['property_id' => 'required|integer']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $property = Property::findOrFail($request->property_id);
        $this->authorize('view', $property);

        $comps = ComparableSale::where('property_id', $property->id)->get()->toArray();
        if (empty($comps)) {
            return response()->json(['error' => __('Add at least one comparable sale before running AI analysis.')], 422);
        }

        return $this->runAiRequest(function () use ($ai, $property, $comps) {
            $analysis = $ai->analyzeArv($property, $comps);
            AiLog::record('arv_analysis', $analysis, [
                'model_type' => Property::class, 'model_id' => $property->id,
                'prompt_summary' => "ARV analysis for {$property->address} ({$property->comparableSales->count()} comps)",
            ]);
            return ['analysis' => $analysis];
        });
    }

    // ── Document AI Draft ──────────────────────

    public function draftDocument(Request $request)
    {
        $request->validate([
            'template_type' => 'required|string|max:50',
            'prompt' => 'nullable|string|max:2000',
            'deal_id' => 'nullable|integer',
        ]);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $dealContext = null;
        if ($request->deal_id) {
            $deal = Deal::with('lead.property')->findOrFail($request->deal_id);
            $this->authorize('view', $deal);
            $dealContext = [
                'deal_title' => $deal->title,
                'deal_stage' => $deal->stage,
                'contract_price' => \Fmt::currency($deal->contract_price ?? 0),
                (\App\Services\BusinessModeService::isRealEstate() ? 'commission' : 'assignment_fee') => \Fmt::currency(
                    \App\Services\BusinessModeService::isRealEstate() ? ($deal->total_commission ?? 0) : ($deal->assignment_fee ?? 0)
                ),
                'closing_date' => $deal->closing_date?->format('M d, Y') ?? 'TBD',
            ];
            if ($deal->lead) {
                $dealContext['seller_name'] = $deal->lead->full_name;
            }
            if ($deal->lead?->property) {
                $dealContext['property_address'] = $deal->lead->property->full_address;
            }
        }

        return $this->runAiRequest(function () use ($ai, $request, $dealContext) {
            $content = $ai->draftDocumentContent($request->template_type, $request->prompt ?? '', $dealContext);
            AiLog::record('document_draft', $content, [
                'prompt_summary' => "Document draft: {$request->template_type}",
            ]);
            return ['content' => $content];
        });
    }

    // ── Campaign Insights ──────────────────────

    public function campaignInsights(Request $request)
    {
        $request->validate(['campaign_id' => 'required|integer']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $campaign = Campaign::findOrFail($request->campaign_id);

        $leadCount = Lead::where('campaign_id', $campaign->id)->count();
        $dealCount = Deal::whereHas('lead', fn ($q) => $q->where('campaign_id', $campaign->id))->count();
        $closedDeals = Deal::where('stage', 'closed_won')
            ->whereHas('lead', fn ($q) => $q->where('campaign_id', $campaign->id))->count();
        $revenue = Deal::where('stage', 'closed_won')
            ->whereHas('lead', fn ($q) => $q->where('campaign_id', $campaign->id))->sum(\App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column']);

        $campaignData = [
            'name' => $campaign->name,
            'type' => $campaign->type,
            'status' => $campaign->status,
            'budget' => \Fmt::currency($campaign->budget ?? 0),
            'actual_spend' => \Fmt::currency($campaign->actual_spend ?? 0),
            'start_date' => $campaign->start_date?->format('M d, Y') ?? 'N/A',
            'end_date' => $campaign->end_date?->format('M d, Y') ?? 'N/A',
            'lead_count' => $leadCount,
            'lead_target' => $campaign->target_lead_count ?? 'N/A',
            'deal_count' => $dealCount,
            'closed_deals' => $closedDeals,
            'revenue' => \Fmt::currency($revenue),
            'cost_per_lead' => $leadCount > 0 ? \Fmt::currency(($campaign->actual_spend ?? 0) / $leadCount) : 'N/A',
            'conversion_rate' => $leadCount > 0 ? round(($dealCount / $leadCount) * 100, 1) . '%' : 'N/A',
            'close_rate' => $dealCount > 0 ? round(($closedDeals / $dealCount) * 100, 1) . '%' : 'N/A',
            'roi' => ($campaign->actual_spend ?? 0) > 0 ? round((($revenue - ($campaign->actual_spend ?? 0)) / ($campaign->actual_spend ?? 1)) * 100, 1) . '%' : 'N/A',
        ];

        return $this->runAiRequest(function () use ($ai, $campaign, $campaignData) {
            $analysis = $ai->analyzeCampaign($campaignData);
            AiLog::record('campaign_insights', $analysis, [
                'model_type' => Campaign::class, 'model_id' => $campaign->id,
                'prompt_summary' => "Campaign insights for {$campaign->name}",
            ]);
            return ['analysis' => $analysis];
        });
    }

    // ── Buyer Risk Assessment ──────────────────────

    public function buyerRiskAssessment(Request $request)
    {
        $request->validate(['buyer_id' => 'required|integer']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $buyer = Buyer::with('transactions', 'dealMatches')->findOrFail($request->buyer_id);
        $this->authorize('view', $buyer);

        $buyerData = [
            'name' => $buyer->full_name,
            'company' => $buyer->company ?? 'N/A',
            'email' => $buyer->email ?? 'N/A',
            'phone' => $buyer->phone ?? 'N/A',
            'max_purchase_price' => \Fmt::currency($buyer->max_purchase_price ?? 0),
            'preferred_property_types' => $buyer->preferred_property_types ?? [],
            'preferred_states' => $buyer->preferred_states ?? [],
            'preferred_zip_codes' => $buyer->preferred_zip_codes ?? [],
            'pof_verified' => $buyer->pof_verified ? 'Yes (verified ' . ($buyer->pof_verified_at?->format('M d, Y') ?? '') . ')' : 'No',
            'pof_amount' => $buyer->pof_amount ? \Fmt::currency($buyer->pof_amount) : 'N/A',
            'buyer_score' => $buyer->buyer_score ?? 'N/A',
            'total_purchases' => $buyer->total_purchases ?? 0,
            'avg_close_days' => $buyer->avg_close_days ?? 'N/A',
            'last_purchase_at' => $buyer->last_purchase_at?->format('M d, Y') ?? 'Never',
            'deal_matches' => $buyer->dealMatches->count(),
            'interested_responses' => $buyer->dealMatches->where('response', 'interested')->count(),
            'transaction_count' => $buyer->transactions->count(),
            'transaction_total' => \Fmt::currency($buyer->transactions->sum('purchase_price')),
        ];

        return $this->runAiRequest(function () use ($ai, $buyer, $buyerData) {
            $assessment = $ai->assessBuyerRisk($buyerData);
            AiLog::record('buyer_risk', $assessment, [
                'model_type' => Buyer::class, 'model_id' => $buyer->id,
                'prompt_summary' => "Buyer risk assessment for {$buyer->full_name}",
            ]);
            return ['assessment' => $assessment];
        });
    }

    // ── Goal Recommendations ──────────────────────

    public function goalRecommendations()
    {
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $now = now();
        $monthAgo = $now->copy()->subMonth();

        $currentMetrics = [
            'total_leads' => Lead::count(),
            'leads_this_month' => Lead::where('created_at', '>=', $monthAgo)->count(),
            'active_deals' => Deal::whereNotIn('stage', ['closed_won', 'closed_lost'])->count(),
            'deals_closed_this_month' => Deal::where('stage', 'closed_won')->where('updated_at', '>=', $monthAgo)->count(),
            'revenue_this_month' => \Fmt::currency(Deal::where('stage', 'closed_won')->where('updated_at', '>=', $monthAgo)->sum(\App\Services\BusinessModeService::getDashboardKpiConfig()['fee_column'])),
            'total_buyers' => Buyer::count(),
            'active_goals' => Goal::where('is_active', true)->count(),
            'activities_this_month' => \App\Models\Activity::where('created_at', '>=', $monthAgo)->count(),
            'avg_days_to_close' => Deal::where('stage', 'closed_won')->whereNotNull('contract_date')->whereNotNull('closing_date')->count() > 0
                ? round(Deal::where('stage', 'closed_won')->get()->avg(fn ($d) => $d->contract_date && $d->closing_date ? $d->contract_date->diffInDays($d->closing_date) : 0))
                : 'N/A',
            'pipeline_value' => \Fmt::currency(Deal::whereNotIn('stage', ['closed_won', 'closed_lost'])->sum('contract_price')),
        ];

        return $this->runAiRequest(function () use ($ai, $currentMetrics) {
            $result = $ai->recommendGoals($currentMetrics);
            AiLog::record('goal_recommendations', $result['analysis'] ?? '', [
                'prompt_summary' => 'AI goal recommendations',
                'metadata' => ['goals_count' => count($result['goals'] ?? [])],
            ]);
            return [
                'analysis' => $result['analysis'] ?? '',
                'goals' => $result['goals'] ?? [],
            ];
        });
    }

    // ── Portal Property Description ──────────────────────

    public function portalDescription(Request $request)
    {
        $request->validate(['property_id' => 'required']);
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        if ($request->property_id === 'first_portal') {
            $tenant = auth()->user()->tenant;
            $inventoryStages = \App\Services\BusinessModeService::isRealEstate()
                ? ['active_listing', 'showing', 'offer_received']
                : ['dispositions', 'assigned'];
            $dealLeadIds = Deal::whereIn('stage', $inventoryStages)->pluck('lead_id');
            $property = Property::whereIn('lead_id', $dealLeadIds)->first();

            if (!$property) {
                $stageNames = implode(' or ', $inventoryStages);
                return response()->json(['error' => __("No portal properties available. Add deals in the {$stageNames} stage first.")], 422);
            }
        } else {
            $property = Property::findOrFail((int) $request->property_id);
        }

        $this->authorize('view', $property);

        return $this->runAiRequest(function () use ($ai, $property) {
            $description = $ai->generatePortalDescription($property);
            AiLog::record('portal_description', $description, [
                'model_type' => Property::class, 'model_id' => $property->id,
                'prompt_summary' => "Portal description for {$property->address}",
            ]);
            return ['description' => $description, 'property_address' => $property->address];
        });
    }

    // ── AI Apply Actions ──────────────────────

    /**
     * POST /ai/apply-property-field
     * Update a property field from AI suggestion.
     */
    public function applyPropertyField(Request $request)
    {
        $request->validate([
            'property_id' => 'required|integer',
            'field' => 'required|string|in:notes,after_repair_value,our_offer,list_price',
            'value' => 'required|string',
            'append' => 'boolean',
        ]);

        $property = Property::findOrFail($request->property_id);
        $this->authorize('update', $property);

        $field = $request->field;
        $value = $request->value;

        if ($request->boolean('append') && $field === 'notes') {
            $existing = $property->notes ?? '';
            $value = $existing ? $existing . "\n\n--- " . __('AI Generated') . " ---\n" . $value : $value;
        }

        if (in_array($field, ['after_repair_value', 'our_offer', 'list_price'])) {
            $value = (float) preg_replace('/[^0-9.]/', '', $value);
        }

        $property->update([$field => $value]);

        return response()->json(['success' => true, 'message' => __('Property updated successfully.')]);
    }

    /**
     * POST /ai/apply-lead-dnc
     * Mark a lead as Do Not Contact.
     */
    public function applyLeadDnc(Request $request)
    {
        $request->validate(['lead_id' => 'required|integer']);

        $lead = Lead::findOrFail($request->lead_id);
        $this->authorize('update', $lead);

        $lead->update(['do_not_contact' => true]);

        \App\Models\AuditLog::log('lead.marked_dnc', $lead, null, ['do_not_contact' => true]);

        return response()->json(['success' => true, 'message' => __('Lead marked as Do Not Contact.')]);
    }

    /**
     * POST /ai/apply-buyer-notes
     * Append AI text to buyer notes.
     */
    public function applyBuyerNotes(Request $request)
    {
        $request->validate([
            'buyer_id' => 'required|integer',
            'notes' => 'required|string',
        ]);

        $buyer = Buyer::findOrFail($request->buyer_id);
        $this->authorize('update', $buyer);

        $existing = $buyer->notes ?? '';
        $buyer->update([
            'notes' => $existing ? $existing . "\n\n--- " . __('AI Generated') . " ---\n" . $request->notes : $request->notes,
        ]);

        return response()->json(['success' => true, 'message' => __('Buyer notes updated.')]);
    }

    /**
     * POST /ai/apply-campaign-notes
     * Append AI text to campaign notes.
     */
    public function applyCampaignNotes(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|integer',
            'notes' => 'required|string',
        ]);

        $campaign = Campaign::findOrFail($request->campaign_id);

        $existing = $campaign->notes ?? '';
        $campaign->update([
            'notes' => $existing ? $existing . "\n\n--- " . __('AI Generated') . " ---\n" . $request->notes : $request->notes,
        ]);

        return response()->json(['success' => true, 'message' => __('Campaign notes updated.')]);
    }

    /**
     * POST /ai/generate-buyer-notes
     * AI-generate buyer criteria notes.
     */
    public function generateBuyerNotes(Request $request)
    {
        $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'company' => 'nullable|string',
            'max_purchase_price' => 'nullable|numeric',
            'preferred_property_types' => 'nullable|array',
            'preferred_states' => 'nullable|string',
            'preferred_zip_codes' => 'nullable|string',
            'asset_classes' => 'nullable|array',
        ]);

        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        return $this->runAiRequest(function () use ($ai, $request) {
            $notes = $ai->generateBuyerCriteria($request->only([
                'first_name', 'last_name', 'company', 'max_purchase_price',
                'preferred_property_types', 'preferred_states', 'preferred_zip_codes', 'asset_classes',
            ]));
            return ['notes' => $notes];
        });
    }

    /**
     * POST /ai/generate-campaign-notes
     * AI-generate campaign plan notes.
     */
    public function generateCampaignNotes(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'budget' => 'nullable|numeric',
        ]);

        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        return $this->runAiRequest(function () use ($ai, $request) {
            $notes = $ai->generateCampaignPlan($request->name, $request->type, $request->budget ? (float) $request->budget : null);
            return ['notes' => $notes];
        });
    }

    // ── Auto-Briefings (cached) ──────────────────────────────

    public function leadBriefing(Request $request)
    {
        $request->validate(['lead_id' => 'required|integer']);
        if (!auth()->user()->tenant->ai_briefings_enabled) {
            return response()->json(['error' => 'disabled'], 422);
        }
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $lead = Lead::with(['deals', 'property'])->findOrFail($request->lead_id);
        $this->authorize('view', $lead);

        $cacheKey = "tenant." . auth()->user()->tenant_id . ".briefing.lead.{$lead->id}";
        if (!$request->has('refresh')) {
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($cached && is_array($cached)) {
                return response()->json(['success' => true, 'briefing' => $cached['briefing'], 'links' => $cached['links'] ?? [], 'cached' => true]);
            }
        }

        return $this->runAiRequest(function () use ($ai, $lead, $cacheKey) {
            $briefing = $ai->leadBriefing($lead);
            $links = [];
            foreach ($lead->deals as $deal) {
                $links[] = ['type' => 'deal', 'label' => $deal->title, 'stage' => Deal::stageLabel($deal->stage), 'url' => url("/pipeline/{$deal->id}"), 'price' => $deal->contract_price];
            }
            if ($lead->property) {
                $p = $lead->property;
                $pricePayload = \App\Services\BusinessModeService::isRealEstate()
                    ? ['list_price' => $p->list_price]
                    : ['arv' => $p->after_repair_value];
                $links[] = array_merge(['type' => 'property', 'label' => $p->address . ($p->city ? ", {$p->city}" : ''), 'url' => url("/leads/{$lead->id}#property-details")], $pricePayload);
            }
            $data = ['briefing' => $briefing, 'links' => $links];
            \Illuminate\Support\Facades\Cache::put($cacheKey, $data, now()->addHours(3));
            return $data + ['cached' => false];
        });
    }

    public function dealBriefing(Request $request)
    {
        $request->validate(['deal_id' => 'required|integer']);
        if (!auth()->user()->tenant->ai_briefings_enabled) {
            return response()->json(['error' => 'disabled'], 422);
        }
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $deal = Deal::with(['lead.property', 'buyerMatches.buyer'])->findOrFail($request->deal_id);
        $this->authorize('view', $deal);

        $cacheKey = "tenant." . auth()->user()->tenant_id . ".briefing.deal.{$deal->id}";
        if (!$request->has('refresh')) {
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($cached && is_array($cached)) {
                return response()->json(['success' => true, 'briefing' => $cached['briefing'], 'links' => $cached['links'] ?? [], 'cached' => true]);
            }
        }

        return $this->runAiRequest(function () use ($ai, $deal, $cacheKey) {
            $briefing = $ai->dealBriefing($deal);
            $links = [];
            if ($deal->lead) {
                $links[] = ['type' => 'lead', 'label' => $deal->lead->full_name, 'temp' => $deal->lead->temperature, 'url' => url("/leads/{$deal->lead->id}")];
            }
            if ($deal->lead && $deal->lead->property) {
                $p = $deal->lead->property;
                $pricePayload = \App\Services\BusinessModeService::isRealEstate()
                    ? ['list_price' => $p->list_price]
                    : ['arv' => $p->after_repair_value];
                $links[] = array_merge(['type' => 'property', 'label' => $p->address . ($p->city ? ", {$p->city}" : ''), 'url' => url("/leads/{$deal->lead->id}#property-details")], $pricePayload);
            }
            foreach ($deal->buyerMatches->sortByDesc('match_score')->take(3) as $match) {
                if ($match->buyer) {
                    $links[] = ['type' => 'buyer', 'label' => $match->buyer->full_name, 'score' => $match->match_score, 'url' => url("/buyers/{$match->buyer->id}")];
                }
            }
            $data = ['briefing' => $briefing, 'links' => $links];
            \Illuminate\Support\Facades\Cache::put($cacheKey, $data, now()->addHours(3));
            return $data + ['cached' => false];
        });
    }

    public function buyerBriefing(Request $request)
    {
        $request->validate(['buyer_id' => 'required|integer']);
        if (!auth()->user()->tenant->ai_briefings_enabled) {
            return response()->json(['error' => 'disabled'], 422);
        }
        $ai = $this->ai();
        if (!$ai->isAvailable()) return response()->json(['error' => 'AI is not configured.'], 422);

        $buyer = Buyer::with(['dealMatches.deal.lead'])->findOrFail($request->buyer_id);
        $this->authorize('view', $buyer);

        $cacheKey = "tenant." . auth()->user()->tenant_id . ".briefing.buyer.{$buyer->id}";
        if (!$request->has('refresh')) {
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($cached && is_array($cached)) {
                return response()->json(['success' => true, 'briefing' => $cached['briefing'], 'links' => $cached['links'] ?? [], 'cached' => true]);
            }
        }

        return $this->runAiRequest(function () use ($ai, $buyer, $cacheKey) {
            $briefing = $ai->buyerBriefing($buyer);
            $links = [];
            foreach ($buyer->dealMatches->sortByDesc('match_score')->take(3) as $match) {
                if ($match->deal) {
                    $links[] = [
                        'type' => 'deal',
                        'label' => $match->deal->title,
                        'stage' => Deal::stageLabel($match->deal->stage),
                        'score' => $match->match_score,
                        'url' => url("/pipeline/{$match->deal_id}"),
                        'lead' => $match->deal->lead ? $match->deal->lead->full_name : null,
                    ];
                }
            }
            $data = ['briefing' => $briefing, 'links' => $links];
            \Illuminate\Support\Facades\Cache::put($cacheKey, $data, now()->addHours(3));
            return $data + ['cached' => false];
        });
    }

    public function marketingKit(Request $request)
    {
        $request->validate(['deal_id' => 'required|integer']);

        $ai = $this->ai();
        if (!$ai->isAvailable()) {
            return response()->json(['error' => 'AI is not configured. Go to Settings > AI to set up.'], 422);
        }

        $deal = Deal::findOrFail($request->deal_id);
        $this->authorize('view', $deal);

        return $this->runAiRequest(function () use ($ai, $deal) {
            $drafting = new \App\Services\Ai\AiDraftingService($ai->getProvider());
            return $drafting->generateMarketingKit($deal);
        });
    }

    private function runAiRequest(callable $callback)
    {
        try {
            return response()->json(['success' => true] + $callback());
        } catch (\Throwable $e) {
            return response()->json(['error' => 'AI request failed: ' . $e->getMessage()], 500);
        }
    }
}
