<?php

namespace App\Services\Ai;

use App\Helpers\TenantFormatHelper;
use App\Models\Buyer;
use App\Models\Deal;
use App\Models\DealBuyerMatch;
use App\Models\Lead;
use App\Models\Property;

class AiInsightService extends BaseAiFeatureService
{
    /**
     * Get the business mode context string for AI prompts.
     */
    private static function modeLabel(): string
    {
        return \App\Services\BusinessModeService::isRealEstate() ? 'real estate agent' : 'real estate wholesaling';
    }

    public function summarizeNotes(Lead $lead): string
    {
        $lead->load(['activities', 'property']);

        $activities = $lead->activities->sortBy('logged_at')->map(function ($activity) {
            $date = $activity->logged_at ? $activity->logged_at->format('M d, Y') : 'unknown';
            return "[{$date}] {$activity->type}: {$activity->subject} - {$activity->body}";
        })->implode("\n");

        if (empty(trim($activities))) {
            return 'No activities to summarize.';
        }

        $system = "You are an assistant for a " . self::modeLabel() . " CRM. Analyze lead activity history and produce a concise summary. Include: key takeaways, seller motivation level (high/medium/low), objections raised, next recommended action. Use bullet points. Be brief. Output ONLY the summary - no introductions or preamble.";
        $prompt = "Lead: {$lead->first_name} {$lead->last_name}\nPhone: {$lead->phone}\n\nActivity history:\n{$activities}\n\nProvide a summary:";

        return $this->provider->chat($system, $prompt);
    }

    public function analyzeDeal(Deal $deal): string
    {
        $result = $this->analyzeDealWithActions($deal);
        return $result['text'];
    }

    public function analyzeDealWithActions(Deal $deal): array
    {
        $deal->load(['lead.property', 'agent', 'buyerMatches.buyer']);
        $property = $deal->lead?->property;

        $feeLabel = \App\Services\BusinessModeService::isRealEstate() ? 'Commission' : 'Assignment Fee';
        $feeValue = \App\Services\BusinessModeService::isRealEstate() ? ($deal->total_commission ?? 0) : ($deal->assignment_fee ?? 0);

        $dealInfo = "Deal: {$deal->title}\nStage: {$deal->stage}\n";
        $dealInfo .= "Contract Price: " . $this->fmt($deal->contract_price ?? 0) . "\n";
        $dealInfo .= "{$feeLabel}: " . $this->fmt($feeValue) . "\n";
        $dealInfo .= "Earnest Money: " . $this->fmt($deal->earnest_money ?? 0) . "\n";

        if ($deal->closing_date) {
            $dealInfo .= "Closing Date: {$deal->closing_date}\n";
        }
        if ($deal->due_diligence_end_date && !\App\Services\BusinessModeService::isRealEstate()) {
            $dealInfo .= "Due Diligence Ends: {$deal->due_diligence_end_date}\n";
        }

        $propertyInfo = '';
        if ($property) {
            $propertyInfo = "\nProperty: {$property->address}, {$property->city}, {$property->state} {$property->zip_code}\n";
            $propertyInfo .= "Type: {$property->property_type}, Condition: {$property->condition}\n";
            if ($property->after_repair_value && !\App\Services\BusinessModeService::isRealEstate()) {
                $propertyInfo .= "ARV: " . $this->fmt($property->after_repair_value) . "\n";
                $propertyInfo .= "Repair Estimate: " . $this->fmt($property->repair_estimate ?? 0) . "\n";
            }
            if (\App\Services\BusinessModeService::isRealEstate()) {
                if ($deal->mls_number) $propertyInfo .= "MLS#: {$deal->mls_number}\n";
                if ($property->list_price) $propertyInfo .= "List Price: " . $this->fmt($property->list_price) . "\n";
            }
            if (!\App\Services\BusinessModeService::isRealEstate()) {
                $propertyInfo .= "MAO: " . $this->fmt($property->maximum_allowable_offer ?? 0) . "\n";
                $markers = is_array($property->distress_markers) ? implode(', ', $property->distress_markers) : '';
                if ($markers) {
                    $propertyInfo .= "Distress Markers: {$markers}\n";
                }
            }
        }

        $matchInfo = '';
        if ($deal->buyerMatches->count()) {
            $buyerTerm = \App\Services\BusinessModeService::isRealEstate() ? 'Clients' : 'Buyers';
            $matchInfo = "\nMatched {$buyerTerm}: {$deal->buyerMatches->count()}\n";
            $topMatch = $deal->buyerMatches->sortByDesc('match_score')->first();
            $matchInfo .= "Top Match Score: {$topMatch->match_score}%\n";
        }

        $system = "You are a " . self::modeLabel() . " deal analyst. Focus on FINANCIAL ANALYSIS and RISK ASSESSMENT only. Do NOT recommend stage changes — that is handled by a separate Stage Advisor tool.\n\nProvide:\n1) **Risk Assessment** (high/medium/low with specific reasons)\n2) **Opportunity Score** (1-10 with justification)\n3) **Financial Analysis** (margins, ARV vs price, repair feasibility)\n4) **Key Concerns** (blockers, missing info, timeline risks)\n5) **Recommended Actions** (tasks to mitigate risks or capture opportunities)\n\nBe concise and actionable. Use bullet points.\n\nAfter your analysis, output a line containing only \"---ACTIONS---\" followed by a JSON array of recommended actions. Focus on tasks and notes — NOT stage changes. Each action is an object with: \"type\" (one of: create_task, add_note), \"label\" (short description), and type-specific fields:\n- create_task: \"title\" (task description), \"due_days\" (integer, days from now), \"priority\" (low/medium/high)\n- add_note: \"text\" (the note content)\nInclude 2-4 specific, actionable items. Example:\n---ACTIONS---\n[{\"type\":\"create_task\",\"label\":\"Schedule inspection\",\"title\":\"Schedule property inspection\",\"due_days\":3,\"priority\":\"high\"},{\"type\":\"create_task\",\"label\":\"Verify ARV comps\",\"title\":\"Pull comparable sales to verify ARV estimate\",\"due_days\":2,\"priority\":\"medium\"}]";

        $raw = $this->provider->chat($system, "{$dealInfo}{$propertyInfo}{$matchInfo}\n\nAnalyze this deal:", ['max_tokens' => 2000]);

        return self::parseActionsFromResponse($raw);
    }

    public function scoreLeadMotivation(Lead $lead): array
    {
        $lead->load(['activities', 'property', 'lists']);
        $activities = $lead->activities->sortByDesc('logged_at')->take(10)->map(fn ($activity) => "{$activity->type}: {$activity->body}")->implode("\n");

        $system = "You are a lead scoring analyst for " . self::modeLabel() . ". Analyze the lead data and activity history. Return ONLY a valid JSON object (no markdown, no code fences, no explanation) with exactly these keys: {\"score\": <0-100>, \"confidence\": \"high|medium|low\", \"factors\": [\"factor1\", \"factor2\"], \"recommendation\": \"brief action\"}. Keep factors to 3 items max, each under 15 words. Keep recommendation under 20 words. Score based on seller motivation signals.";
        $response = $this->provider->chat($system, $this->buildLeadContext($lead) . "\n\nActivity history:\n" . ($activities ?: 'No activities recorded.') . "\n\nScore this lead:", ['temperature' => 0.3, 'max_tokens' => 4096]);

        $parsed = $this->extractJsonObject($response, 'score');
        if ($parsed) {
            return [
                'score' => max(0, min(100, (int) $parsed['score'])),
                'confidence' => $parsed['confidence'] ?? 'medium',
                'factors' => $parsed['factors'] ?? [],
                'recommendation' => $parsed['recommendation'] ?? '',
            ];
        }

        // Regex fallback for truncated/malformed JSON — extract score directly
        if (preg_match('/"score"\s*:\s*(\d+)/', $response, $scoreMatch)) {
            $score = max(0, min(100, (int) $scoreMatch[1]));
            preg_match('/"confidence"\s*:\s*"(high|medium|low)"/i', $response, $confMatch);
            preg_match('/"recommendation"\s*:\s*"([^"]+)"/', $response, $recMatch);
            $factors = [];
            if (preg_match_all('/"factors"\s*:\s*\[([^\]]*)/s', $response, $fMatch)) {
                preg_match_all('/"([^"]{5,})"/', $fMatch[1], $items);
                $factors = array_slice($items[1] ?? [], 0, 5);
            }
            return [
                'score' => $score,
                'confidence' => $confMatch[1] ?? 'medium',
                'factors' => $factors ?: ['AI response was truncated'],
                'recommendation' => $recMatch[1] ?? '',
            ];
        }

        return ['score' => null, 'confidence' => 'low', 'factors' => ['Could not parse AI response'], 'recommendation' => $response];
    }

    public function suggestOfferStrategy(Property $property): string
    {
        $property->load('lead');
        $system = "You are a " . self::modeLabel() . " negotiation expert. Analyze the property data and provide: 1) Recommended offer price with justification, 2) Offer range (low/mid/high), 3) Key negotiation leverage points based on distress markers and condition, 4) Talking points for the seller conversation. Use bullet points. Output ONLY the analysis - no preamble.";

        return $this->provider->chat($system, $this->buildPropertyContext($property) . "\n\nProvide an offer strategy:", ['temperature' => 0.4]);
    }

    public function qualifyLead(Lead $lead, ?string $listType = null): array
    {
        $lead->load('property');

        $context = "Lead: {$lead->first_name} {$lead->last_name}\n";
        $context .= "Source: {$lead->lead_source}\n";
        if ($lead->notes) {
            $context .= "Notes: {$lead->notes}\n";
        }
        if ($listType) {
            $context .= "List Type: {$listType}\n";
        }

        if ($lead->property) {
            $property = $lead->property;
            $context .= "Property: {$property->address}, {$property->city}, {$property->state}\n";
            $context .= "Type: {$property->property_type}, Condition: " . ($property->condition ?? 'unknown') . "\n";
            $markers = is_array($property->distress_markers) ? implode(', ', $property->distress_markers) : '';
            if ($markers) {
                $context .= "Distress: {$markers}\n";
            }
        }

        $system = "You are a lead qualification analyst for " . self::modeLabel() . ". Classify the lead based on available data. Return ONLY a JSON object: {\"temperature\": \"hot|warm|cold\", \"reasoning\": \"one sentence\"}. Classify as hot if multiple distress signals or urgency indicators. Cold if minimal info or low-intent source. Warm for everything else.";
        $response = $this->provider->chat($system, $context, ['temperature' => 0.2]);

        $parsed = $this->extractJsonObject($response, 'temperature');
        if ($parsed && in_array($parsed['temperature'] ?? '', ['hot', 'warm', 'cold'], true)) {
            return [
                'temperature' => $parsed['temperature'],
                'reasoning' => $parsed['reasoning'] ?? '',
            ];
        }

        return ['temperature' => 'warm', 'reasoning' => 'Could not parse AI response'];
    }

    public function adviseDealStage(Deal $deal): string
    {
        $result = $this->adviseDealStageWithActions($deal);
        return $result['text'];
    }

    public function adviseDealStageWithActions(Deal $deal): array
    {
        $deal->load(['lead.property', 'lead.activities', 'agent', 'buyerMatches']);

        $daysInStage = $deal->stage_changed_at ? (int) now()->diffInDays($deal->stage_changed_at, true) : '?';
        $feeLabel = \App\Services\BusinessModeService::isRealEstate() ? 'Commission' : 'Assignment Fee';
        $feeValue = \App\Services\BusinessModeService::isRealEstate() ? ($deal->total_commission ?? 0) : ($deal->assignment_fee ?? 0);

        $context = "Deal: {$deal->title}\nCurrent Stage: {$deal->stage}\nDays in current stage: {$daysInStage}\n";
        $context .= "Contract Price: " . $this->fmt($deal->contract_price ?? 0) . "\n";
        $context .= "{$feeLabel}: " . $this->fmt($feeValue) . "\n";

        if ($deal->closing_date) {
            $daysToClose = (int) now()->diffInDays($deal->closing_date, false);
            $context .= "Closing Date: {$deal->closing_date} ({$daysToClose} days " . ($daysToClose >= 0 ? 'away' : 'overdue') . ")\n";
        }
        if ($deal->due_diligence_end_date && !\App\Services\BusinessModeService::isRealEstate()) {
            $ddDays = (int) now()->diffInDays($deal->due_diligence_end_date, false);
            $context .= "DD Deadline: {$deal->due_diligence_end_date} ({$ddDays} days " . ($ddDays >= 0 ? 'remaining' : 'past') . ")\n";
        }

        $buyerTerm = \App\Services\BusinessModeService::isRealEstate() ? 'Clients' : 'Buyers';
        $context .= "Matched {$buyerTerm}: {$deal->buyerMatches->count()}\n";
        $recentActivities = $deal->lead?->activities?->sortByDesc('logged_at')->take(5)->map(fn ($activity) => $activity->type . ': ' . $activity->subject)->implode(', ');
        if ($recentActivities) {
            $context .= "Recent Activity: {$recentActivities}\n";
        }

        $stageList = \App\Models\Deal::stages();
        $stages = implode(', ', array_keys($stageList));
        $stageOrder = array_keys($stageList);
        $currentIdx = array_search($deal->stage, $stageOrder);
        $nextStage = ($currentIdx !== false && isset($stageOrder[$currentIdx + 1])) ? $stageOrder[$currentIdx + 1] : null;
        $prevStage = ($currentIdx !== false && $currentIdx > 0) ? $stageOrder[$currentIdx - 1] : null;

        $context .= "\nAvailable stages in order: " . implode(' → ', array_map(fn($k) => "{$k} ({$stageList[$k]})", $stageOrder)) . "\n";
        if ($nextStage) $context .= "Next stage would be: {$nextStage} ({$stageList[$nextStage]})\n";
        if ($prevStage) $context .= "Previous stage was: {$prevStage} ({$stageList[$prevStage]})\n";

        $system = "You are a " . self::modeLabel() . " deal STAGE ADVISOR. Your primary job is to recommend whether this deal should MOVE TO A DIFFERENT STAGE. This is different from general deal analysis.\n\nYou MUST:\n1) Assess whether the deal is in the RIGHT stage or should move forward/backward\n2) ALWAYS recommend a specific stage change as your FIRST action — either advancing to the next stage, staying (explain why), or moving back if needed\n3) Provide 1-2 supporting tasks to prepare for the stage transition\n4) Flag any blockers preventing stage advancement\n\nBe concise and actionable. Use bullet points.\n\nAfter your advice, output a line containing only \"---ACTIONS---\" followed by a JSON array of recommended actions. The FIRST action MUST be a stage_change. Each action is an object with: \"type\" (one of: stage_change, create_task, add_note), \"label\" (short description), and type-specific fields:\n- stage_change: \"stage\" (one of: {$stages}) — this MUST be your first action\n- create_task: \"title\" (task description), \"due_days\" (integer, days from now), \"priority\" (low/medium/high)\n- add_note: \"text\" (the note content)\nInclude 2-4 items total, always starting with a stage_change. Example:\n---ACTIONS---\n[{\"type\":\"stage_change\",\"label\":\"Advance to Under Contract\",\"stage\":\"under_contract\"},{\"type\":\"create_task\",\"label\":\"Send contract to seller\",\"title\":\"Send purchase agreement to seller for signature\",\"due_days\":1,\"priority\":\"high\"}]";

        $raw = $this->provider->chat($system, $context);

        return self::parseStageAdviceResponse($raw, $deal->stage, $nextStage);
    }

    public function suggestCsvMapping(array $headers, array $sampleRows): array
    {
        $headerList = implode(', ', array_map(fn ($header, $index) => "[{$index}] \"{$header}\"", $headers, array_keys($headers)));
        $sampleText = '';
        foreach (array_slice($sampleRows, 0, 3) as $row) {
            $sampleText .= implode(' | ', $row) . "\n";
        }

        $system = "You are a data mapping expert. Given CSV headers and sample data, map each column to a CRM field. Available CRM fields: first_name, last_name, phone, email, address, city, state, zip_code, notes. Return ONLY a JSON object where keys are column indices (0-based) and values are CRM field names or null if no match. Example: {\"0\": \"first_name\", \"1\": null, \"2\": \"phone\"}";
        $response = $this->provider->chat($system, "CSV Headers: {$headerList}\n\nSample data:\n{$sampleText}\n\nReturn the mapping JSON:", ['temperature' => 0.1]);

        $cleaned = preg_replace('/```(?:json|JSON)?\s*\n?/i', '', $response);
        $cleaned = trim($cleaned);
        $parsed = json_decode($cleaned, true);
        if (!is_array($parsed)) {
            $start = strpos($cleaned, '{');
            $end = strrpos($cleaned, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $parsed = json_decode(substr($cleaned, $start, $end - $start + 1), true);
            }
        }
        if (is_array($parsed)) {
            $validFields = ['first_name', 'last_name', 'phone', 'email', 'address', 'city', 'state', 'zip_code', 'notes'];
            $result = [];
            foreach ($parsed as $index => $field) {
                if ($field !== null && in_array($field, $validFields, true)) {
                    $result[(int) $index] = $field;
                }
            }
            return $result;
        }

        return [];
    }

    public function explainBuyerMatch(Deal $deal, Buyer $buyer, ?DealBuyerMatch $match = null): string
    {
        $deal->load('lead.property');
        $property = $deal->lead?->property;

        $context = "Deal: {$deal->title}\nContract Price: " . $this->fmt($deal->contract_price ?? 0) . "\n";
        if ($property) {
            $context .= "Property: {$property->full_address}\nType: {$property->property_type}\nCondition: " . ($property->condition ?? 'N/A') . "\n";
            if (!\App\Services\BusinessModeService::isRealEstate()) {
                $context .= "ARV: " . $this->fmt($property->after_repair_value ?? 0) . "\n";
            } elseif ($property->list_price) {
                $context .= "List Price: " . $this->fmt($property->list_price) . "\n";
            }
        }

        $buyerTerm = \App\Services\BusinessModeService::isRealEstate() ? 'Client' : 'Buyer';
        $context .= "\n{$buyerTerm}: {$buyer->first_name} {$buyer->last_name}\n";
        $context .= "Company: " . ($buyer->company ?? 'N/A') . "\n";
        $context .= "Max Price: " . $this->fmt($buyer->max_purchase_price ?? 0) . "\n";

        $preferences = [];
        if ($buyer->preferred_property_types) {
            $preferences[] = "Property types: " . implode(', ', (array) $buyer->preferred_property_types);
        }
        if ($buyer->preferred_zip_codes) {
            $preferences[] = "Zip codes: " . implode(', ', (array) $buyer->preferred_zip_codes);
        }
        if ($buyer->preferred_states) {
            $preferences[] = "States: " . implode(', ', (array) $buyer->preferred_states);
        }
        if ($preferences) {
            $context .= "Preferences: " . implode('; ', $preferences) . "\n";
        }
        if ($match) {
            $context .= "Match Score: {$match->match_score}%\n";
        }

        $expertRole = \App\Services\BusinessModeService::isRealEstate() ? 'buyer matching specialist' : 'disposition expert';
        $system = "You are a " . self::modeLabel() . " {$expertRole}. Explain why this buyer is a good match for this deal. Cover: 1) Matching criteria breakdown, 2) Why this deal fits their buying profile, 3) Suggested pitch angle for outreach, 4) Key selling points to emphasize. Be concise. Output ONLY the explanation - no preamble.";

        return $this->provider->chat($system, $context);
    }

    public function generateWeeklyDigest(array $kpiData): string
    {
        $context = '';
        foreach ($kpiData as $key => $value) {
            $context .= ucwords(str_replace('_', ' ', $key)) . ": {$value}\n";
        }

        $system = "You are a " . self::modeLabel() . " operations analyst. Summarize the CRM performance data. Highlight: 1) Key wins, 2) Areas of concern, 3) Top 3 action items for the team. Be concise, use bullet points. Output ONLY the digest - no preamble.";

        return $this->provider->chat($system, "CRM Performance Data:\n{$context}\n\nGenerate the weekly digest:");
    }

    public function flagDncRisks(Lead $lead): array
    {
        $lead->load('activities');

        $dncService = app(\App\Services\DncService::class);
        $onDncList = $dncService->check($lead);
        $timezoneCheck = $dncService->checkTimezoneRestriction($lead);
        $recentActivities = $lead->activities->where('created_at', '>=', now()->subDays(7));
        $contactAttempts7d = $recentActivities->whereIn('type', ['sms', 'email', 'call', 'voicemail', 'direct_mail'])->count();
        $totalContactAttempts = $lead->activities->whereIn('type', ['sms', 'email', 'call', 'voicemail', 'direct_mail'])->count();

        $notes = $lead->notes ?? '';
        $activities = $lead->activities->map(fn ($activity) => "[{$activity->type} - {$activity->created_at->format('m/d/Y')}] {$activity->subject} {$activity->body}")->implode("\n");

        $complianceLaw = TenantFormatHelper::complianceLawName();
        $complianceData = "COMPLIANCE DATA (factual, from database):\n";
        $complianceData .= "- Lead DNC flag: " . ($lead->do_not_contact ? 'YES - marked do-not-contact' : 'No') . "\n";
        $complianceData .= "- On tenant DNC list (phone/email match): " . ($onDncList ? 'YES' : 'No') . "\n";
        $complianceData .= "- Lead status: {$lead->status}\n";
        $complianceData .= "- Lead temperature: " . ($lead->temperature ?? 'unknown') . "\n";
        $complianceData .= "- Timezone set: " . ($lead->timezone ? $lead->timezone : "NOT SET - cannot verify {$complianceLaw} calling hours") . "\n";
        $complianceData .= "- Current contact window: " . ($timezoneCheck['allowed'] ? 'Within hours (' . $timezoneCheck['local_time'] . ')' : 'OUTSIDE hours (' . $timezoneCheck['local_time'] . ')') . "\n";
        $complianceData .= "- Contact attempts in last 7 days: {$contactAttempts7d}\n";
        $complianceData .= "- Total contact attempts: {$totalContactAttempts}\n";
        $complianceData .= "- Phone on file: " . ($lead->phone ? 'Yes' : 'No') . "\n";
        $complianceData .= "- Email on file: " . ($lead->email ? 'Yes' : 'No') . "\n";
        $complianceData .= "- Lead source: " . ($lead->lead_source ?? 'unknown') . "\n";

        $system = "You are a {$complianceLaw}/DNC compliance analyst for a real estate company. You will receive factual compliance data from the CRM database AND the lead's notes/activity history. Assess the OVERALL compliance risk of continuing to contact this lead.\n\nRisk factors to evaluate:\n1. DNC list status (flag or phone/email match)\n2. Cease & desist language, attorney mentions, explicit stop requests, threats, or hostile tone in notes/activities\n3. Contact frequency (excessive = more than 3 attempts in 7 days without response)\n4. {$complianceLaw} calling hours compliance (8am-9pm in lead's timezone)\n5. Missing timezone (cannot verify {$complianceLaw} compliance)\n6. Lead status (dead, closed_lost = risky to re-contact)\n7. Missing consent indicators for cold outreach sources (list_import, cold_call)\n8. No phone/email on file (incomplete contact info)\n\nBe practical and err toward caution. Even one significant factor should elevate risk above 'none'.\n\nReturn ONLY a JSON object: {\"risk_level\": \"high|medium|low|none\", \"flags\": [\"specific finding\", ...], \"recommendation\": \"specific action to take\"}";
        $prompt = "Lead: {$lead->first_name} {$lead->last_name}\n\n{$complianceData}\nNotes: " . ($notes ?: '(none)') . "\n\nActivity history:\n" . ($activities ?: '(none)') . "\n\nAssess DNC/compliance risk:";
        $response = $this->provider->chat($system, $prompt, ['temperature' => 0.2]);

        $parsed = $this->extractJsonObject($response, 'risk_level');
        if ($parsed) {
            return [
                'risk_level' => $parsed['risk_level'],
                'flags' => $parsed['flags'] ?? [],
                'recommendation' => $parsed['recommendation'] ?? '',
            ];
        }

        return ['risk_level' => 'low', 'flags' => ['Could not parse AI response'], 'recommendation' => $response];
    }

    public function leadSummary(Lead $lead): string
    {
        $lead->load(['property', 'activities', 'deals', 'tasks']);

        $context = $this->buildLeadContext($lead);

        // Recent activities (last 10)
        $recentActivities = $lead->activities->sortByDesc('logged_at')->take(10)->map(function ($activity) {
            $date = $activity->logged_at ? $activity->logged_at->format('M d, Y') : ($activity->created_at ? $activity->created_at->format('M d, Y') : '?');
            return "[{$date}] {$activity->type}: {$activity->subject} {$activity->body}";
        })->implode("\n");

        // Deal status
        $dealInfo = '';
        if ($lead->deals->count() > 0) {
            $activeDeal = $lead->deals->whereNotIn('stage', ['closed_won', 'closed_lost', 'dead'])->first();
            if ($activeDeal) {
                $dealInfo = "Active Deal: {$activeDeal->title} (Stage: {$activeDeal->stage}, Contract: " . $this->fmt($activeDeal->contract_price ?? 0) . ")\n";
            }
            $dealInfo .= "Total Deals: {$lead->deals->count()}\n";
        } else {
            $dealInfo = "No deals yet.\n";
        }

        // Pending tasks
        $pendingTasks = $lead->tasks->where('is_completed', false)->map(function ($task) {
            $due = $task->due_date ? $task->due_date->format('M d') : '?';
            $overdue = ($task->due_date && $task->due_date->isPast()) ? ' [OVERDUE]' : '';
            return "- {$task->title} (due: {$due}){$overdue}";
        })->implode("\n");

        // Motivation scores
        $scores = "System Motivation Score: " . ($lead->motivation_score ?? 'N/A') . "/100\n";
        $scores .= "AI Motivation Score: " . ($lead->ai_motivation_score ?? 'N/A') . "/100\n";

        $system = "You are a " . self::modeLabel() . " analyst. Provide a concise situation briefing for a lead. Format your response EXACTLY as:\n\n**Situation:** (2-3 sentences summarizing the lead's current state)\n\n**Key Signals:**\n- bullet points of important indicators\n\n**Risk Factors:**\n- bullet points of concerns\n\n**Recommended Next Action:** (one specific, actionable recommendation)\n\nOutput ONLY the briefing - no introductions or preamble.";

        $prompt = "{$context}\n{$scores}\nRecent Activities (last 10):\n" . ($recentActivities ?: '(none)') . "\n\nDeal Status:\n{$dealInfo}\nPending Tasks:\n" . ($pendingTasks ?: '(none)') . "\n\nProvide the situation briefing:";

        return $this->provider->chat($system, $prompt, ['temperature' => 0.4, 'max_tokens' => 800]);
    }

    public function comparableSalesAnalysis(Property $property): string
    {
        $property->load('lead');

        $context = $this->buildPropertyContext($property);

        $isRE = \App\Services\BusinessModeService::isRealEstate();
        $system = $isRE
            ? "You are a real estate market analyst specializing in comparative market analysis (CMA). Based on the property data provided, deliver a comprehensive pricing analysis. Format your response EXACTLY as:\n\n**Market Position Assessment:** (assess where this property sits in the local market based on features and condition)\n\n**Suggested List Price Range:** (low/target/high with reasoning based on comparable data)\n\n**Price Per Square Foot Analysis:** (compare to area averages if data available)\n\n**Pricing Strategy:** (2-3 sentences on recommended approach — competitive, aspirational, or value-based)\n\n**Market Considerations:**\n- bullet points of factors affecting pricing\n\nUse actual dollar figures from the data. Output ONLY the analysis - no introductions or preamble."
            : "You are a real estate investment analyst specializing in wholesale deal evaluation. Based on the property data provided, deliver a comprehensive analysis. Format your response EXACTLY as:\n\n**ARV Confidence Assessment:** (assess confidence in the provided ARV based on property details)\n\n**Estimated Repair Range:** (low/mid/high repair cost estimates based on condition and distress markers)\n\n**Suggested MAO (70% Rule):** (calculate: ARV x 0.70 - Repair Estimate, explain the math)\n\n**Investment Thesis:** (2-3 sentences on whether this is a good wholesale deal and why)\n\n**Risk Factors:**\n- bullet points of investment risks\n\nUse actual dollar figures from the data. Output ONLY the analysis - no introductions or preamble.";

        return $this->provider->chat($system, $context . "\n\nProvide the comparable sales analysis:", ['temperature' => 0.4, 'max_tokens' => 1200]);
    }

    public function pipelineHealth(array $metrics): string
    {
        $metricsText = implode("\n", $metrics);

        $system = "You are a " . self::modeLabel() . " operations analyst reviewing pipeline health. Analyze the pipeline metrics and provide a structured assessment. Format your response EXACTLY as:\n\n**Pipeline Score:** X/10 (with one-sentence justification)\n\n**Top Concern:** (the single biggest issue that needs attention)\n\n**Top Opportunity:** (the single biggest growth opportunity)\n\n**Action Items:**\n1. [URGENT/IMPORTANT/ROUTINE] specific action\n2. [URGENT/IMPORTANT/ROUTINE] specific action\n3. [URGENT/IMPORTANT/ROUTINE] specific action\n\n**Stage-by-Stage Assessment:**\n- one line per stage\n\nOutput ONLY the assessment - no introductions or preamble.";

        return $this->provider->chat($system, "Pipeline Metrics:\n{$metricsText}\n\nProvide the pipeline health assessment:", ['temperature' => 0.4, 'max_tokens' => 1200]);
    }

    public function routeLead(Lead $lead, array $agentProfiles): ?int
    {
        $lead->load('property');

        $leadContext = "New Lead: {$lead->first_name} {$lead->last_name}\n";
        $leadContext .= "Source: {$lead->lead_source}\n";
        $leadContext .= "Temperature: " . ($lead->temperature ?? 'unknown') . "\n";
        if ($lead->property) {
            $leadContext .= "Property: {$lead->property->address}, {$lead->property->city}\n";
            $leadContext .= "Type: " . ($lead->property->property_type ?? 'unknown') . "\n";
        }

        $agentContext = collect($agentProfiles)->map(function ($a) {
            return "Agent #{$a['id']} {$a['name']} ({$a['role']}): {$a['active_leads']} active leads, {$a['closed_deals']} closed deals, {$a['activities_7d']} activities this week";
        })->implode("\n");

        $system = "You are a lead routing optimizer for a " . self::modeLabel() . " CRM. Given a new lead and the available agents with their workload, choose the best agent. Consider: workload balance (prefer agents with fewer active leads), agent engagement (recent activity), and role fit. Return ONLY a JSON object: {\"agent_id\": <integer>, \"reason\": \"one sentence\"}";
        $response = $this->provider->chat($system, "{$leadContext}\n\nAvailable Agents:\n{$agentContext}\n\nWhich agent should handle this lead?", ['temperature' => 0.2]);

        $parsed = $this->extractJsonObject($response, 'agent_id');
        if ($parsed && isset($parsed['agent_id'])) {
            $agentIds = collect($agentProfiles)->pluck('id')->toArray();
            $chosenId = (int) $parsed['agent_id'];
            if (in_array($chosenId, $agentIds, true)) {
                return $chosenId;
            }
        }

        return null;
    }

    public function generateGoalForecast(array $kpiData): string
    {
        $system = "You are a business analytics advisor for a " . self::modeLabel() . " company. Based on the KPI goal data provided, give a concise forecast and actionable advice. Format your response as:\n\n**Forecast:** (1-2 sentences on whether the goal will be met)\n**Key Insight:** (one data-driven observation)\n**Recommendation:** (one specific action to improve performance)\n\nBe practical and specific to " . self::modeLabel() . ". Output ONLY the analysis - no introductions.";

        $dataText = collect($kpiData)->map(fn($v, $k) => ucwords(str_replace('_', ' ', $k)) . ": {$v}")->implode("\n");
        $prompt = "Goal Data:\n{$dataText}\n\nProvide the forecast:";

        return $this->provider->chat($system, $prompt, ['temperature' => 0.4, 'max_tokens' => 400]);
    }

    // ── New Feature AI Methods ──────────────────────────────────

    /**
     * Analyze comparable sales and recommend offer price.
     */
    public function analyzeArv(Property $property, array $comps): string
    {
        $property->load('lead');
        $context = $this->buildPropertyContext($property);

        $compsText = '';
        foreach ($comps as $i => $comp) {
            $adj = is_array($comp['adjustments'] ?? null) ? array_sum($comp['adjustments']) : 0;
            $compsText .= ($i + 1) . ". {$comp['address']} — Sale: " . $this->fmt($comp['sale_price']) .
                ", Date: {$comp['sale_date']}, Adj: " . ($adj >= 0 ? '+' : '') . $this->fmt($adj) .
                ", Adj Price: " . $this->fmt($comp['adjusted_price'] ?? $comp['sale_price'] + $adj) . "\n";
            if (!empty($comp['sqft'])) $compsText .= "   Sqft: {$comp['sqft']}";
            if (!empty($comp['beds'])) $compsText .= ", Beds: {$comp['beds']}";
            if (!empty($comp['baths'])) $compsText .= ", Baths: {$comp['baths']}";
            if (!empty($comp['distance_miles'])) $compsText .= ", Distance: {$comp['distance_miles']}mi";
            if (!empty($comp['condition'])) $compsText .= ", Condition: {$comp['condition']}";
            $compsText .= "\n";
        }

        $isRE = \App\Services\BusinessModeService::isRealEstate();
        $system = $isRE
            ? "You are a " . self::modeLabel() . " market analyst. Analyze the subject property against the comparable sales provided. Format your response EXACTLY as:\n\n**Comp Quality Assessment:**\n- Rate the overall quality of these comps (excellent/good/fair/poor) and explain why\n- Flag any outliers or comps that should be weighted differently\n\n**Market Value Recommendation:**\n- Recommended market value with reasoning (weighted average, median, or specific comp preference)\n- Confidence level (high/medium/low)\n\n**Suggested List Price:**\n- Recommended list price range (low/target/high)\n- Price per square foot comparison\n\n**Pricing Strategy:**\n- Recommended pricing approach and positioning\n- Key factors to highlight in listing\n\n**Market Considerations:**\n- bullet points\n\nUse actual dollar figures. Output ONLY the analysis."
            : "You are a " . self::modeLabel() . " investment analyst. Analyze the subject property against the comparable sales provided. Format your response EXACTLY as:\n\n**Comp Quality Assessment:**\n- Rate the overall quality of these comps (excellent/good/fair/poor) and explain why\n- Flag any outliers or comps that should be weighted differently\n\n**ARV Recommendation:**\n- Recommended ARV with reasoning (weighted average, median, or specific comp preference)\n- Confidence level (high/medium/low)\n\n**MAO Calculations:**\n- 70% Rule: (ARV × 0.70) − Repairs = MAO\n- 75% Rule: (ARV × 0.75) − Repairs = MAO\n- Show the actual math with numbers\n\n**Offer Strategy:**\n- Recommended offer range (low/target/max)\n- Key negotiation points based on comp data\n\n**Risk Factors:**\n- bullet points\n\nUse actual dollar figures. Output ONLY the analysis.";

        return $this->provider->chat($system, "{$context}\n\nComparable Sales ({$i} comps):\n{$compsText}\nAnalyze:", ['temperature' => 0.4, 'max_tokens' => 1500]);
    }

    /**
     * Draft document content from a prompt/context.
     */
    public function draftDocumentContent(string $templateType, string $prompt, ?array $dealContext = null): string
    {
        $contextText = '';
        if ($dealContext) {
            foreach ($dealContext as $key => $value) {
                $contextText .= ucwords(str_replace('_', ' ', $key)) . ": {$value}\n";
            }
        }

        $system = "You are a real estate contract and document drafting specialist. Generate professional document content for a {$templateType}. The content should be formal, legally-oriented (but include a disclaimer that it is not legal advice), and ready to use in a " . self::modeLabel() . " context.\n\nFormat the output as clean HTML suitable for a document template. Use <h3> for section headers, <p> for paragraphs, <ul>/<li> for lists. Include merge field placeholders using {{field_name}} syntax where appropriate (e.g., {{buyer_name}}, {{seller_name}}, {{property_address}}, {{purchase_price}}, {{closing_date}}).\n\nOutput ONLY the HTML content — no explanations, no markdown code fences.";

        $userPrompt = "Document type: {$templateType}\n";
        if ($prompt) $userPrompt .= "Instructions: {$prompt}\n";
        if ($contextText) $userPrompt .= "\nDeal context:\n{$contextText}\n";
        $userPrompt .= "\nGenerate the document content:";

        return $this->provider->chat($system, $userPrompt, ['temperature' => 0.3, 'max_tokens' => 3000]);
    }

    /**
     * Analyze campaign performance and suggest optimizations.
     */
    public function analyzeCampaign(array $campaignData): string
    {
        $context = '';
        foreach ($campaignData as $key => $value) {
            $context .= ucwords(str_replace('_', ' ', $key)) . ": {$value}\n";
        }

        $system = "You are a real estate marketing analyst specializing in " . self::modeLabel() . " campaigns. Analyze the campaign performance data and provide actionable insights. Format your response EXACTLY as:\n\n**Performance Grade:** A/B/C/D/F (with one-sentence justification)\n\n**What's Working:**\n- bullet points of positive signals\n\n**Areas for Improvement:**\n- bullet points with specific, actionable suggestions\n\n**ROI Analysis:**\n- Cost efficiency assessment\n- Comparison to industry benchmarks\n\n**Recommendations:**\n1. [HIGH PRIORITY] specific action\n2. [MEDIUM PRIORITY] specific action\n3. [LOW PRIORITY] specific action\n\n**Budget Recommendation:**\n- Whether to increase, maintain, or decrease budget with reasoning\n\nBe specific to " . self::modeLabel() . ". Output ONLY the analysis.";

        return $this->provider->chat($system, "Campaign Data:\n{$context}\nAnalyze this campaign:", ['temperature' => 0.4, 'max_tokens' => 1200]);
    }

    /**
     * Assess buyer risk and reliability.
     */
    public function assessBuyerRisk(array $buyerData): string
    {
        $context = '';
        foreach ($buyerData as $key => $value) {
            if (is_array($value)) $value = implode(', ', $value);
            $context .= ucwords(str_replace('_', ' ', $key)) . ": {$value}\n";
        }

        $system = "You are a " . self::modeLabel() . " disposition analyst assessing buyer reliability. Analyze the buyer's profile, verification status, and transaction history. Format your response EXACTLY as:\n\n**Reliability Score:** X/10 (with one-sentence justification)\n\n**Strengths:**\n- bullet points of positive indicators\n\n**Risk Factors:**\n- bullet points of concerns\n\n**Verification Status:**\n- POF assessment\n- Transaction history assessment\n- Overall trust level (high/medium/low)\n\n**Recommendation:**\n- Whether to prioritize this buyer for deals\n- Suggested deal types/price ranges\n- Any conditions before assigning deals\n\nBe practical and specific to " . self::modeLabel() . ". Output ONLY the assessment.";

        return $this->provider->chat($system, "Buyer Profile:\n{$context}\nAssess this buyer:", ['temperature' => 0.4, 'max_tokens' => 1000]);
    }

    /**
     * Recommend goals based on current KPI data.
     */
    public function recommendGoals(array $currentMetrics): array
    {
        $context = '';
        foreach ($currentMetrics as $key => $value) {
            $context .= ucwords(str_replace('_', ' ', $key)) . ": {$value}\n";
        }

        $system = "You are a " . self::modeLabel() . " business coach. Based on the current business metrics, recommend specific, measurable KPI goals. Return ONLY valid JSON with this exact structure:\n\n{\"analysis\": \"2-3 sentences assessing current performance and growth strategy\", \"goals\": [{\"metric\": \"deals_closed\", \"target_value\": 5, \"period\": \"monthly\", \"reason\": \"brief reason\"}, ...]}\n\nRules:\n- metric MUST be one of: deals_closed, revenue_earned, leads_generated, activities_logged, calls_made, offers_sent\n- period MUST be one of: weekly, monthly, quarterly\n- target_value must be a realistic positive number\n- Recommend 3-5 goals total, mix of monthly and quarterly\n- For revenue_earned, target_value is in dollars (no formatting)\n- Use realistic targets for a wholesaling operation\n- Output ONLY the JSON object, nothing else";

        $response = $this->provider->chat($system, "Current Business Metrics:\n{$context}\nRecommend goals as JSON:", ['temperature' => 0.5, 'max_tokens' => 1000]);

        $validMetrics = ['deals_closed', 'revenue_earned', 'leads_generated', 'activities_logged', 'calls_made', 'offers_sent'];
        $validPeriods = ['weekly', 'monthly', 'quarterly'];

        $parsed = $this->extractJsonObject($response, 'goals');
        if (is_array($parsed) && isset($parsed['goals']) && is_array($parsed['goals'])) {
            $goals = array_filter(array_map(function ($g) use ($validMetrics, $validPeriods) {
                $metric = $g['metric'] ?? '';
                $period = $g['period'] ?? 'monthly';
                if (!in_array($metric, $validMetrics, true)) return null;
                if (!in_array($period, $validPeriods, true)) $period = 'monthly';
                return [
                    'metric' => $metric,
                    'target_value' => max(1, (float) ($g['target_value'] ?? 1)),
                    'period' => $period,
                    'reason' => $g['reason'] ?? '',
                ];
            }, $parsed['goals']));

            return [
                'analysis' => $parsed['analysis'] ?? '',
                'goals' => array_values($goals),
            ];
        }

        return [
            'analysis' => $response,
            'goals' => [],
        ];
    }

    /**
     * Generate a marketing description for a property (buyer portal).
     */
    public function generatePortalDescription(Property $property): string
    {
        $property->load('lead');

        $context = "Property: {$property->full_address}\n";
        $context .= "Type: " . str_replace('_', ' ', $property->property_type ?? 'residential') . "\n";
        $context .= "Bedrooms: " . ($property->bedrooms ?? 'N/A') . ", Bathrooms: " . ($property->bathrooms ?? 'N/A') . "\n";
        $context .= "Sq Ft: " . ($property->square_footage ? number_format($property->square_footage) : 'N/A') . "\n";
        $context .= "Year Built: " . ($property->year_built ?? 'N/A') . "\n";
        $context .= "Lot Size: " . ($property->lot_size ?? 'N/A') . "\n";
        $isRE = \App\Services\BusinessModeService::isRealEstate();
        if ($isRE) {
            if ($property->list_price) $context .= "List Price: " . $this->fmt($property->list_price) . "\n";
        } else {
            $context .= "ARV: " . $this->fmt($property->after_repair_value ?? 0) . "\n";
            $context .= "Repair Estimate: " . $this->fmt($property->repair_estimate ?? 0) . "\n";
        }
        $context .= "Asking Price: " . $this->fmt($property->asking_price ?? 0) . "\n";
        $context .= "Condition: " . ($property->condition ?? 'N/A') . "\n";

        if (!$isRE) {
            $markers = is_array($property->distress_markers) ? implode(', ', $property->distress_markers) : '';
            if ($markers) $context .= "Distress Markers: {$markers}\n";
        }

        $system = $isRE
            ? "You are a real estate marketing copywriter for a brokerage's client portal. Write a compelling 2-3 paragraph property description for a buyer audience. Highlight:\n- Key property features and amenities\n- Location and neighborhood appeal\n- Value proposition at the listed price\n- Lifestyle benefits\n\nTone: professional, appealing to homebuyers. Mention specific numbers from the data. Do NOT include seller information or internal notes. Output ONLY the description — no headers, no preamble."
            : "You are a real estate marketing copywriter for a wholesaling company's buyer portal. Write a compelling 2-3 paragraph investment opportunity description for a cash buyer audience. Highlight:\n- Investment potential (ARV, potential profit)\n- Property features\n- Renovation opportunity\n- Area/location appeal\n\nTone: professional, investor-focused (not consumer MLS listing style). Mention specific numbers from the data. Do NOT include seller information or internal notes. Output ONLY the description — no headers, no preamble.";

        return $this->provider->chat($system, $context . "\nWrite the buyer portal description:", ['temperature' => 0.6, 'max_tokens' => 500]);
    }

    /**
     * AI lead qualification for workflow automation.
     * Returns temperature and reasoning.
     */
    public function qualifyLeadForWorkflow(Lead $lead): array
    {
        return $this->qualifyLead($lead);
    }

    public function suggestTasks(Lead $lead): array
    {
        $lead->load(['property', 'activities', 'tasks', 'deals']);

        $recentActivities = $lead->activities->sortByDesc('logged_at')->take(10)->map(function ($activity) {
            $date = $activity->logged_at ? $activity->logged_at->format('M d') : ($activity->created_at ? $activity->created_at->format('M d') : '?');
            return "[{$date}] {$activity->type}: {$activity->subject} {$activity->body}";
        })->implode("\n");

        $existingTasks = $lead->tasks->map(function ($task) {
            $status = $task->is_completed ? 'DONE' : ($task->is_overdue ? 'OVERDUE' : 'pending');
            return "[{$status}] {$task->title} (due: " . ($task->due_date ? $task->due_date->format('M d') : '?') . ")";
        })->implode("\n");

        $system = "You are a " . self::modeLabel() . " task planner. Analyze the lead's current situation and suggest 3-5 specific, actionable next-step tasks. Each task should move the deal forward. Consider: lead status, temperature, recent activity (or lack thereof), existing tasks, property info, and deal stage.\n\nReturn ONLY a JSON array of objects: [{\"title\": \"task title\", \"days_from_now\": <integer 0-30>, \"priority\": \"high|medium|low\", \"reason\": \"one sentence why\"}]\n\nRules:\n- Tasks should be concrete actions (not vague like \"follow up\"), e.g. \"Call seller to discuss repair concerns\", \"Send comp analysis email\", \"Drive by property for condition check\"\n- Don't suggest tasks that duplicate existing pending tasks\n- Suggest urgent tasks (0-1 days) for hot leads or overdue situations\n- Consider what's MISSING: no property info? suggest gathering it. No recent contact? suggest outreach. Property but no deal? suggest making an offer.";
        $prompt = $this->buildLeadContext($lead) . "\n\nRecent activity:\n" . ($recentActivities ?: '(none)') . "\n\nExisting tasks:\n" . ($existingTasks ?: '(none)') . "\n\nHas property: " . ($lead->property ? 'Yes' : 'No') . "\nHas deal(s): " . ($lead->deals->count() > 0 ? 'Yes' : 'No') . "\nToday's date: " . now()->format('M d, Y') . "\n\nSuggest tasks:";
        $response = $this->provider->chat($system, $prompt, ['temperature' => 0.4]);

        $parsed = $this->extractJsonArray($response);
        if (is_array($parsed) && count($parsed) > 0) {
            return array_map(function ($task) {
                return [
                    'title' => $task['title'] ?? 'Follow up',
                    'days_from_now' => max(0, min(30, (int) ($task['days_from_now'] ?? 1))),
                    'priority' => in_array($task['priority'] ?? '', ['high', 'medium', 'low'], true) ? $task['priority'] : 'medium',
                    'reason' => $task['reason'] ?? '',
                ];
            }, array_slice($parsed, 0, 5));
        }

        return [];
    }

    // ── Auto-Briefings (cached, low-cost) ─────────────────────

    public function leadBriefing(Lead $lead): string
    {
        $lead->load(['property', 'activities', 'tasks', 'deals', 'agent', 'lists']);

        $lastActivity = $lead->activities->sortByDesc('logged_at')->first();
        $daysSinceContact = $lastActivity && $lastActivity->logged_at
            ? (int) now()->diffInDays($lastActivity->logged_at)
            : null;
        $overdueTasks = $lead->tasks->filter(fn($t) => !$t->is_completed && $t->due_date && $t->due_date->isPast())->count();
        $pendingTasks = $lead->tasks->filter(fn($t) => !$t->is_completed)->count();
        $activeDeals = $lead->deals->whereNotIn('stage', ['closed_won', 'closed_lost']);

        $data = "Lead: {$lead->full_name} | Status: {$lead->status} | Temp: " . ($lead->temperature ?? '?') . "\n";
        $data .= "Source: {$lead->lead_source} | Agent: " . ($lead->agent->name ?? 'Unassigned') . "\n";
        $data .= "Motivation: " . ($lead->motivation_score ?? 0) . "/100";
        if ($lead->ai_motivation_score !== null) $data .= " (AI: {$lead->ai_motivation_score})";
        $data .= "\n";
        $data .= "Last contact: " . ($daysSinceContact !== null ? "{$daysSinceContact} days ago ({$lastActivity->type})" : "Never contacted") . "\n";
        $data .= "Tasks: {$pendingTasks} pending, {$overdueTasks} overdue\n";
        $data .= "Deals: {$activeDeals->count()} active" . ($activeDeals->count() ? " — " . $activeDeals->map(fn($d) => $d->title . " ({$d->stage})")->implode(', ') : "") . "\n";
        $data .= "Lists: " . ($lead->lists->count() ? $lead->lists->pluck('name')->implode(', ') : "None") . "\n";
        if ($lead->do_not_contact) $data .= "WARNING: DNC flagged\n";
        if ($lead->property) {
            $p = $lead->property;
            $data .= "Property: {$p->address}" . ($p->city ? ", {$p->city}" : "") . " | Type: " . ($p->property_type ?? '?') . " | Condition: " . ($p->condition ?? '?') . "\n";
            if (!\App\Services\BusinessModeService::isRealEstate()) {
                if ($p->after_repair_value) $data .= "ARV: " . $this->fmt($p->after_repair_value);
                if ($p->repair_estimate) $data .= " | Repairs: " . $this->fmt($p->repair_estimate);
                if ($p->after_repair_value || $p->repair_estimate) $data .= "\n";
            } elseif ($p->list_price) {
                $data .= "List Price: " . $this->fmt($p->list_price) . "\n";
            }
        }

        $system = "You are a " . self::modeLabel() . " CRM assistant. Given the lead data below, write a 2-3 sentence briefing that helps the user decide what to do RIGHT NOW. Mention: contact recency, any overdue tasks, deal status, motivation level, and one specific next-step recommendation. Be direct and concise. No bullet points, no headers — just a short paragraph. Output ONLY the briefing.";

        return $this->provider->chat($system, $data, ['temperature' => 0.3, 'max_tokens' => 1024]);
    }

    public function dealBriefing(Deal $deal): string
    {
        $deal->load(['lead.property', 'lead.activities', 'agent', 'buyerMatches.buyer', 'activities']);

        $daysInStage = $deal->stage_changed_at ? (int) now()->diffInDays($deal->stage_changed_at, true) : null;
        $lastDealActivity = $deal->activities->sortByDesc('logged_at')->first();
        $daysSinceActivity = $lastDealActivity && $lastDealActivity->logged_at
            ? (int) now()->diffInDays($lastDealActivity->logged_at)
            : null;
        $lastLeadContact = $deal->lead?->activities?->sortByDesc('logged_at')->first();
        $daysSinceLeadContact = $lastLeadContact && $lastLeadContact->logged_at
            ? (int) now()->diffInDays($lastLeadContact->logged_at)
            : null;

        $feeLabel = \App\Services\BusinessModeService::isRealEstate() ? 'Commission' : 'Fee';
        $feeValue = \App\Services\BusinessModeService::isRealEstate() ? ($deal->total_commission ?? 0) : ($deal->assignment_fee ?? 0);

        $data = "Deal: {$deal->title} | Stage: {$deal->stage}";
        if ($daysInStage !== null) $data .= " ({$daysInStage}d in stage)";
        $data .= "\n";
        $data .= "Contract: " . $this->fmt($deal->contract_price ?? 0) . " | {$feeLabel}: " . $this->fmt($feeValue) . "\n";
        if ($deal->closing_date) {
            $daysToClose = (int) now()->diffInDays($deal->closing_date, false);
            $data .= "Closing: {$deal->closing_date->format('M d, Y')} (" . ($daysToClose >= 0 ? "{$daysToClose}d away" : abs($daysToClose) . "d OVERDUE") . ")\n";
        }
        if ($deal->due_diligence_end_date && !\App\Services\BusinessModeService::isRealEstate()) {
            $dd = (int) now()->diffInDays($deal->due_diligence_end_date, false);
            $data .= "DD deadline: " . ($dd >= 0 ? "{$dd}d remaining" : abs($dd) . "d PAST") . "\n";
        }
        $data .= "Lead: " . ($deal->lead ? $deal->lead->full_name . " (" . ($deal->lead->temperature ?? '?') . ")" : "None") . "\n";
        $data .= "Last lead contact: " . ($daysSinceLeadContact !== null ? "{$daysSinceLeadContact}d ago" : "Never") . "\n";
        $data .= "Last deal activity: " . ($daysSinceActivity !== null ? "{$daysSinceActivity}d ago" : "None") . "\n";
        $buyerTerm = \App\Services\BusinessModeService::isRealEstate() ? 'clients' : 'buyers';
        $data .= "Matched {$buyerTerm}: {$deal->buyerMatches->count()}";
        if ($deal->buyerMatches->count()) {
            $data .= " | Best: {$deal->buyerMatches->max('match_score')}%";
            $interested = $deal->buyerMatches->where('status', 'interested')->count();
            if ($interested) $data .= " | {$interested} interested";
        }
        $data .= "\n";
        if ($deal->lead?->property) {
            $p = $deal->lead->property;
            if (\App\Services\BusinessModeService::isRealEstate()) {
                $data .= "Property: {$p->address}" . ($p->list_price ? " | List Price: " . $this->fmt($p->list_price) : "") . "\n";
            } else {
                $data .= "Property: {$p->address} | ARV: " . $this->fmt($p->after_repair_value ?? 0) . " | Repairs: " . $this->fmt($p->repair_estimate ?? 0) . "\n";
            }
        }

        $buyerTermLower = \App\Services\BusinessModeService::isRealEstate() ? 'client' : 'buyer';
        $ddTerm = \App\Services\BusinessModeService::isRealEstate() ? 'contingency deadlines' : 'DD expiring';
        $system = "You are a " . self::modeLabel() . " CRM assistant. Given the deal data below, write a 2-3 sentence briefing that helps the user understand the deal status AT A GLANCE. Mention: stage progress, any urgency (closing deadline, {$ddTerm}, stale contact), {$buyerTermLower} pipeline status, and one specific next action. Be direct and concise. No bullet points, no headers — just a short paragraph. Output ONLY the briefing.";

        return $this->provider->chat($system, $data, ['temperature' => 0.3, 'max_tokens' => 1024]);
    }

    public function buyerBriefing(Buyer $buyer): string
    {
        $buyer->load(['dealMatches.deal', 'transactions']);

        $activeMatches = $buyer->dealMatches->filter(fn($m) => $m->deal && !in_array($m->deal->stage, ['closed_won', 'closed_lost']));
        $interestedCount = $buyer->dealMatches->where('status', 'interested')->count();

        $buyerTerm = \App\Services\BusinessModeService::isRealEstate() ? 'Client' : 'Buyer';
        $data = "{$buyerTerm}: {$buyer->full_name}" . ($buyer->company ? " ({$buyer->company})" : "") . "\n";
        $data .= "Max price: " . $this->fmt($buyer->max_purchase_price ?? 0) . " | Score: " . ($buyer->buyer_score ?? 0) . "/100\n";
        $data .= "Deals closed: " . ($buyer->total_deals_closed ?? 0) . " | Transactions: {$buyer->transactions->count()}\n";
        $data .= "POF: " . ($buyer->pof_verified ? "Verified" : "Not verified") . "\n";
        $data .= "Active matches: {$activeMatches->count()} | Interested in: {$interestedCount}\n";
        if ($activeMatches->count()) {
            $data .= "Deals: " . $activeMatches->take(3)->map(fn($m) => ($m->deal->title ?? 'Deal#' . $m->deal_id) . " ({$m->match_score}%)")->implode(', ') . "\n";
        }
        if ($buyer->preferred_property_types) $data .= "Prefers: " . implode(', ', (array) $buyer->preferred_property_types) . "\n";
        if ($buyer->preferred_states) $data .= "States: " . implode(', ', (array) $buyer->preferred_states) . "\n";
        $lastContact = $buyer->dealMatches->whereNotNull('notified_at')->sortByDesc('notified_at')->first();
        $data .= "Last notified: " . ($lastContact ? $lastContact->notified_at->diffForHumans() : "Never") . "\n";

        $buyerTermLower = \App\Services\BusinessModeService::isRealEstate() ? 'client' : 'buyer';
        $system = "You are a " . self::modeLabel() . " CRM assistant. Given the {$buyerTermLower} profile data below, write a 2-3 sentence briefing that helps the user assess this {$buyerTermLower} AT A GLANCE. Mention: reliability (score, POF, track record), current deal activity, and whether to prioritize them for new deals. Be direct and concise. No bullet points, no headers — just a short paragraph. Output ONLY the briefing.";

        return $this->provider->chat($system, $data, ['temperature' => 0.3, 'max_tokens' => 1024]);
    }

    /**
     * Parse AI response that contains narrative text + ---ACTIONS--- JSON block.
     * Falls back to extracting actions from narrative if AI didn't provide structured block.
     * Used for deal analysis (no guaranteed stage change).
     */
    public static function parseActionsFromResponse(string $raw): array
    {
        $parts = preg_split('/\n*---ACTIONS---\n*/i', $raw, 2);
        $text = trim($parts[0] ?? $raw);
        $actions = [];

        if (isset($parts[1])) {
            $jsonStr = trim($parts[1]);
            $jsonStr = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $jsonStr);
            $parsed = json_decode(trim($jsonStr), true);
            if (is_array($parsed)) {
                $actions = self::validateActions($parsed);
            }
        }

        // Fallback: extract actions from narrative text when AI didn't provide structured block
        if (empty($actions)) {
            $actions = self::extractActionsFromText($text);
        }

        // Always include a "Save analysis as note" action
        $actions[] = [
            'type' => 'add_note',
            'label' => 'Save full analysis as activity note',
            'text' => $text,
        ];

        return ['text' => $text, 'actions' => array_slice($actions, 0, 6)];
    }

    /**
     * Parse AI response for stage advice — guarantees a stage_change action is present.
     */
    public static function parseStageAdviceResponse(string $raw, string $currentStage, ?string $nextStage): array
    {
        $parts = preg_split('/\n*---ACTIONS---\n*/i', $raw, 2);
        $text = trim($parts[0] ?? $raw);
        $actions = [];

        if (isset($parts[1])) {
            $jsonStr = trim($parts[1]);
            $jsonStr = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $jsonStr);
            $parsed = json_decode(trim($jsonStr), true);
            if (is_array($parsed)) {
                $actions = self::validateActions($parsed);
            }
        }

        // Fallback: extract actions from narrative text
        if (empty($actions)) {
            $actions = self::extractActionsFromText($text);
        }

        // Guarantee a stage_change action exists — this is what makes Stage Advice different
        $hasStageChange = false;
        foreach ($actions as $action) {
            if ($action['type'] === 'stage_change') {
                $hasStageChange = true;
                break;
            }
        }

        if (!$hasStageChange) {
            // Try to detect stage recommendation from the text
            $detectedStage = self::detectStageFromText($text, $currentStage);
            if ($detectedStage && $detectedStage !== $currentStage) {
                $stageLabel = \App\Models\Deal::stageLabel($detectedStage);
                array_unshift($actions, [
                    'type' => 'stage_change',
                    'label' => "Move to {$stageLabel}",
                    'stage' => $detectedStage,
                ]);
            } elseif ($nextStage) {
                // Default: suggest advancing to the next stage
                $stageLabel = \App\Models\Deal::stageLabel($nextStage);
                array_unshift($actions, [
                    'type' => 'stage_change',
                    'label' => "Advance to {$stageLabel}",
                    'stage' => $nextStage,
                ]);
            }
        } else {
            // Ensure stage_change is FIRST in the list
            usort($actions, fn($a, $b) => ($a['type'] === 'stage_change' ? 0 : 1) - ($b['type'] === 'stage_change' ? 0 : 1));
        }

        // Always include a "Save advice as note" action
        $actions[] = [
            'type' => 'add_note',
            'label' => 'Save stage advice as activity note',
            'text' => $text,
        ];

        return ['text' => $text, 'actions' => array_slice($actions, 0, 6)];
    }

    /**
     * Try to detect which stage the AI text is recommending.
     */
    private static function detectStageFromText(string $text, string $currentStage): ?string
    {
        $stageNames = \App\Models\Deal::stages();
        $lowerText = strtolower($text);

        foreach ($stageNames as $slug => $label) {
            if ($slug === $currentStage) continue;

            $patterns = [
                'move (?:this deal )?to ' . preg_quote(strtolower($label), '/'),
                'advance (?:this deal )?to ' . preg_quote(strtolower($label), '/'),
                'transition (?:this deal )?to ' . preg_quote(strtolower($label), '/'),
                'should (?:be |move )(?:to |in )' . preg_quote(strtolower($label), '/'),
                'recommend(?:ed)? (?:moving|advancing) to ' . preg_quote(strtolower($label), '/'),
                'ready (?:for|to move to) ' . preg_quote(strtolower($label), '/'),
                'move (?:this deal )?to ' . preg_quote(str_replace('_', ' ', $slug), '/'),
                'advance (?:this deal )?to ' . preg_quote(str_replace('_', ' ', $slug), '/'),
            ];
            foreach ($patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $lowerText)) {
                    return $slug;
                }
            }
        }

        return null;
    }

    private static function validateActions(array $parsed): array
    {
        $actions = [];
        $validTypes = ['stage_change', 'create_task', 'add_note'];
        $validStages = array_keys(\App\Models\Deal::stages());

        foreach (array_slice($parsed, 0, 5) as $action) {
            if (!is_array($action) || !isset($action['type'], $action['label'])) continue;
            if (!in_array($action['type'], $validTypes, true)) continue;

            $clean = ['type' => $action['type'], 'label' => substr($action['label'], 0, 120)];
            if ($action['type'] === 'stage_change') {
                if (!isset($action['stage']) || !in_array($action['stage'], $validStages, true)) continue;
                $clean['stage'] = $action['stage'];
            } elseif ($action['type'] === 'create_task') {
                $clean['title'] = substr($action['title'] ?? $action['label'], 0, 255);
                $clean['due_days'] = max(1, min(90, (int) ($action['due_days'] ?? 3)));
                $clean['priority'] = in_array($action['priority'] ?? '', ['low', 'medium', 'high'], true)
                    ? $action['priority'] : 'medium';
            } elseif ($action['type'] === 'add_note') {
                $clean['text'] = substr($action['text'] ?? $action['label'], 0, 2000);
            }
            $actions[] = $clean;
        }

        return $actions;
    }

    /**
     * Extract actionable items from AI narrative when structured actions weren't provided.
     */
    private static function extractActionsFromText(string $text): array
    {
        $actions = [];
        $validStages = array_keys(\App\Models\Deal::stages());
        $stageNames = \App\Models\Deal::stages();
        $lowerText = strtolower($text);

        // Detect stage change recommendations
        foreach ($stageNames as $slug => $label) {
            $patterns = [
                'move to ' . strtolower($label),
                'move .* to ' . strtolower($label),
                'advance to ' . strtolower($label),
                'transition to ' . strtolower($label),
                'should be in ' . strtolower($label),
                'move to ' . str_replace('_', ' ', $slug),
            ];
            foreach ($patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $lowerText)) {
                    $actions[] = [
                        'type' => 'stage_change',
                        'label' => 'Move to ' . $label,
                        'stage' => $slug,
                    ];
                    break 2;
                }
            }
        }

        // Extract critical/important action items as tasks
        $lines = preg_split('/\n/', $text);
        $taskCount = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($taskCount >= 3) break;

            // Match lines starting with Critical:/Important: or bold critical/important
            if (preg_match('/(?:\*\*)?(?:Critical|Important)(?:\*\*)?[:\s]+(.+)/i', $line, $m)) {
                $taskTitle = trim(preg_replace('/[\*\#]+/', '', $m[1]));
                $taskTitle = preg_replace('/\s+/', ' ', $taskTitle);
                if (strlen($taskTitle) < 10 || strlen($taskTitle) > 200) continue;

                $priority = stripos($line, 'Critical') !== false ? 'high' : 'medium';
                $dueDays = $priority === 'high' ? 1 : 3;

                // Try to extract timeline from nearby text
                if (preg_match('/within (\d+)\s*(hour|day)/i', $taskTitle, $tm)) {
                    $dueDays = strtolower($tm[2]) === 'hour' ? 1 : (int) $tm[1];
                }

                $actions[] = [
                    'type' => 'create_task',
                    'label' => substr($taskTitle, 0, 80),
                    'title' => substr($taskTitle, 0, 255),
                    'due_days' => max(1, min(14, $dueDays)),
                    'priority' => $priority,
                ];
                $taskCount++;
            }
        }

        return $actions;
    }
}
