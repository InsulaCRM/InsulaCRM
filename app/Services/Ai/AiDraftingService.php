<?php

namespace App\Services\Ai;

use App\Models\Buyer;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;

class AiDraftingService extends BaseAiFeatureService
{
    private static function modeLabel(): string
    {
        return \App\Services\BusinessModeService::isRealEstate() ? 'real estate agent' : 'real estate wholesaling';
    }

    public function draftFollowUp(Lead $lead, string $messageType = 'sms'): string
    {
        $lead->load(['property', 'activities', 'agent']);

        $context = $this->buildLeadContext($lead);
        $callerContext = $this->buildCallerContext();
        $recentActivities = $lead->activities->sortByDesc('logged_at')->take(5)->map(function ($activity) {
            return "{$activity->type} on " . ($activity->logged_at ? $activity->logged_at->format('M d') : 'unknown') . ": {$activity->subject} {$activity->body}";
        })->implode("\n");

        $typeInstructions = match ($messageType) {
            'sms' => 'Write an SMS message. Must be under 160 characters. Short, direct, conversational.',
            'email' => 'Write an email body (no subject line). Under 200 words. Professional but warm.',
            'voicemail' => 'Write a voicemail script to read aloud. 30-45 seconds when spoken. Natural and friendly.',
            'call' => 'Write a concise call script. Include: a brief opener (1-2 sentences), 3-4 key questions to ask, and 2-3 short objection responses. Keep it scannable - this is a reference during a live call, not a novel.',
            'direct_mail' => 'Write a direct mail letter. 150-200 words. Personal, handwritten feel.',
            'note' => 'Write internal CRM notes summarizing a recommended follow-up strategy for this lead. Brief bullet points.',
            'meeting' => 'Write a meeting prep brief: key talking points, seller situation summary, and negotiation strategy. Use bullet points.',
            default => 'Write a follow-up message.',
        };

        $system = "You are a " . self::modeLabel() . " outreach specialist. Your job is to write seller follow-up content. IMPORTANT RULES:\n- Output ONLY the message content. No introductions, no explanations, no \"Here's a...\", no commentary.\n- NEVER use placeholder brackets like [Your Name], [Company], etc. - the caller's real name and company are provided below. Use them.\n- Be empathetic and solution-oriented.\n- Start directly with the message text.";
        $prompt = "{$callerContext}\nLead data:\n{$context}\n\nRecent activity:\n" . ($recentActivities ?: 'No prior contact.') . "\n\nTask: {$typeInstructions}";

        return $this->provider->chat($system, $prompt);
    }

    public function draftBuyerMessage(Deal $deal, Buyer $buyer): string
    {
        $deal->load(['lead.property']);
        $property = $deal->lead?->property;

        $dealInfo = "Deal: {$deal->title}\n";
        $dealInfo .= "Contract Price: " . $this->fmt($deal->contract_price ?? 0) . "\n";
        $dealInfo .= "Assignment Fee: " . $this->fmt($deal->assignment_fee ?? 0) . "\n";

        $propertyInfo = '';
        if ($property) {
            $propertyInfo = "Property: {$property->address}, {$property->city}, {$property->state} {$property->zip_code}\n";
            $propertyInfo .= "Type: " . str_replace('_', ' ', $property->property_type ?? 'unknown') . "\n";
            $propertyInfo .= "Bedrooms: " . ($property->bedrooms ?? 'N/A') . ", Bathrooms: " . ($property->bathrooms ?? 'N/A') . "\n";
            $propertyInfo .= "Sq Ft: " . ($property->square_footage ?? 'N/A') . "\n";
            $propertyInfo .= "ARV: " . $this->fmt($property->after_repair_value ?? 0) . "\n";
            $propertyInfo .= "Repair Estimate: " . $this->fmt($property->repair_estimate ?? 0) . "\n";
            $propertyInfo .= "Condition: " . ($property->condition ?? 'N/A') . "\n";
        }

        $buyerInfo = "Buyer: {$buyer->first_name} {$buyer->last_name}\n";
        $buyerInfo .= "Company: " . ($buyer->company ?? 'N/A') . "\n";
        $buyerInfo .= "Max Price: " . $this->fmt($buyer->max_purchase_price ?? 0) . "\n";

        $system = "You are a " . self::modeLabel() . " disposition agent. Draft a professional buyer outreach email about a deal opportunity. Highlight property details that match the buyer's criteria. Be direct, include key numbers, and create urgency without being pushy. Keep it under 200 words. NEVER use placeholder brackets like [Your Name] or [Company] - the sender's real name and company are provided. Output ONLY the email body - no introductions, no \"Here's...\", no commentary.";
        $prompt = $this->buildCallerContext() . "\n{$dealInfo}\n{$propertyInfo}\n{$buyerInfo}\n\nDraft the buyer outreach email:";

        return $this->provider->chat($system, $prompt);
    }

    public function draftSequenceStep(string $sequenceName, int $stepNumber, int $totalSteps, string $actionType, int $delayDays, ?string $previousStepSummary = null): string
    {
        $typeLabels = ['sms' => 'SMS', 'email' => 'Email', 'voicemail' => 'Voicemail Drop', 'task' => 'Task Reminder', 'direct_mail' => 'Direct Mail Piece'];
        $typeLabel = $typeLabels[$actionType] ?? ucfirst($actionType);

        $system = "You are a " . self::modeLabel() . " copywriter. Write drip sequence message templates for motivated seller outreach. Use {first_name}, {last_name}, {address}, and {company_name} as merge tags. Be empathetic, professional, and solution-oriented. SMS must be under 160 characters. Emails should be 100-150 words. Voicemail scripts should be 30-45 seconds when read aloud. Direct mail should be letter-format, 150-200 words. Tasks should be brief action items. Output ONLY the template - no introductions, no preamble, no commentary.";

        $prompt = "Write a {$typeLabel} template for step {$stepNumber} of {$totalSteps} in the \"{$sequenceName}\" drip sequence.\n";
        $prompt .= "This step fires {$delayDays} day(s) after " . ($stepNumber === 1 ? 'enrollment' : 'the previous step') . ".\n";
        if ($previousStepSummary) {
            $prompt .= "The previous step was: {$previousStepSummary}\n";
        }

        $prompt .= "\nGuidelines:\n";
        if ($stepNumber === 1) {
            $prompt .= "- This is the first contact. Introduce yourself and express interest in their property.\n";
        } elseif ($stepNumber === $totalSteps) {
            $prompt .= "- This is the final step. Create a sense of closing/last chance without being aggressive.\n";
        } else {
            $prompt .= "- This is a follow-up. Reference that you've reached out before. Add value or a new angle.\n";
        }
        $prompt .= "- Use merge tags: {first_name}, {last_name}, {address}, {company_name}\n";
        $prompt .= "\nWrite ONLY the message template, no subject line or labels:";

        return $this->provider->chat($system, $prompt);
    }

    public function generatePropertyDescription(Property $property): string
    {
        $isRE = \App\Services\BusinessModeService::isRealEstate();
        $system = $isRE
            ? "You are a real estate listing copywriter. Write a compelling property description for a residential listing. Highlight lifestyle appeal, neighborhood, condition, and key features that attract homebuyers. 150-200 words. Output ONLY the description - no headers, no labels, no preamble."
            : "You are a real estate investment copywriter. Write a compelling investor-focused property description. Highlight the investment opportunity: potential ROI, rehab scope, and key property features. 150-200 words. Output ONLY the description - no headers, no labels, no preamble.";

        $cta = $isRE ? "\n\nWrite the listing description:" : "\n\nWrite the investor property description:";
        return $this->provider->chat($system, $this->buildPropertyContext($property) . $cta);
    }

    public function generateAllSequenceSteps(string $sequenceName, array $steps): array
    {
        $stepList = '';
        foreach ($steps as $index => $step) {
            $stepList .= "Step " . ($index + 1) . ": {$step['action_type']} after {$step['delay_days']} day(s)\n";
        }

        $system = "You are a " . self::modeLabel() . " copywriter. Generate message templates for ALL steps of a drip sequence. Use merge tags: {first_name}, {last_name}, {address}, {company_name}. SMS must be under 160 chars. Emails 100-150 words. Voicemails 30-45 seconds. Direct mail 150-200 words. Tasks should be brief action items. Return ONLY a JSON array of strings where each element is the template for that step, in order. No preamble.";
        $response = $this->provider->chat($system, "Sequence: \"{$sequenceName}\"\n{$stepList}\nGenerate templates as a JSON array:", ['max_tokens' => 3000]);

        $parsed = $this->extractJsonArray($response);
        if (is_array($parsed) && count($parsed) === count($steps)) {
            return $parsed;
        }

        return [];
    }

    public function generateEmailSubjectLines(Lead $lead, string $emailBody): array
    {
        $lead->load('property');

        $context = "Lead: {$lead->first_name} {$lead->last_name}\n";
        if ($lead->property) {
            $context .= "Property: {$lead->property->address}, {$lead->property->city}, {$lead->property->state}\n";
        }
        $context .= "\nEmail body preview:\n" . mb_substr($emailBody, 0, 500) . "\n";

        $system = "You are an email marketing expert for " . self::modeLabel() . ". Generate exactly 3 compelling email subject lines for the given email body. Each subject line should use a different style. Return ONLY a JSON array of objects: [{\"subject\": \"subject line text\", \"style\": \"direct\"}, {\"subject\": \"subject line text\", \"style\": \"curiosity\"}, {\"subject\": \"subject line text\", \"style\": \"urgency\"}]. The styles MUST be: direct, curiosity, urgency (one of each). Keep subject lines under 60 characters. No preamble, no explanation - ONLY the JSON.";

        $response = $this->provider->chat($system, $context . "\nGenerate 3 subject lines:", ['temperature' => 0.8, 'max_tokens' => 300]);

        $parsed = $this->extractJsonArray($response);
        if (is_array($parsed) && count($parsed) >= 1) {
            $validStyles = ['direct', 'curiosity', 'urgency'];
            return array_map(function ($item) use ($validStyles) {
                return [
                    'subject' => $item['subject'] ?? 'Follow up on your property',
                    'style' => in_array($item['style'] ?? '', $validStyles, true) ? $item['style'] : 'direct',
                ];
            }, array_slice($parsed, 0, 3));
        }

        return [
            ['subject' => 'Could not generate subject lines', 'style' => 'direct'],
        ];
    }

    public function generateObjectionResponses(Lead $lead, ?string $specificObjection = null): string
    {
        $lead->load(['property', 'activities']);
        $prompt = $this->buildLeadContext($lead) . "\n\n";
        $prompt .= $specificObjection
            ? "The seller said: \"{$specificObjection}\"\n\nProvide 3 response options for this specific objection:"
            : "Generate the top 5 most likely objections this seller would raise, with 2-3 response options each:";

        $system = "You are a " . self::modeLabel() . " sales trainer. Based on the lead's profile and history, provide objection handling scripts. For each objection: state the objection, then provide 2-3 response options (empathetic, direct, value-based). Tailor responses to the seller's specific situation. Output ONLY the responses - no preamble.";

        return $this->provider->chat($system, $prompt, ['max_tokens' => 2000]);
    }

    public function generateBuyerCriteria(array $data): string
    {
        $context = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $context .= ucwords(str_replace('_', ' ', $key)) . ": " . ($value ?: 'N/A') . "\n";
        }

        $system = "You are a " . self::modeLabel() . " CRM assistant. Based on the buyer's information, write a concise buyer profile summary for the Notes field. Include: investment strategy, ideal deal profile, any concerns or requirements, and recommended approach for deal matching. 100-150 words. Output ONLY the notes - no preamble.";

        return $this->provider->chat($system, $context . "\nWrite buyer profile notes:", ['temperature' => 0.5, 'max_tokens' => 500]);
    }

    public function generateCampaignPlan(string $name, string $type, ?float $budget = null): string
    {
        $context = "Campaign: {$name}\nType: " . str_replace('_', ' ', $type) . "\n";
        if ($budget) {
            $context .= "Budget: " . $this->fmt($budget) . "\n";
        }

        $system = "You are a " . self::modeLabel() . " marketing strategist. Write a campaign plan/description for the Notes field. Include: campaign objectives, target audience, key messaging themes, success metrics to track, and timeline recommendations. Tailor advice to the specific campaign type (direct mail, PPC, cold calling, etc.). 150-200 words. Output ONLY the plan - no preamble.";

        return $this->provider->chat($system, $context . "\nWrite the campaign plan:", ['temperature' => 0.5, 'max_tokens' => 600]);
    }

    public function generateMarketingKit(Deal $deal): array
    {
        $deal->load(['lead.property']);
        $property = $deal->lead?->property;

        $context = "Deal: {$deal->title}\n";
        $context .= "Contract Price: " . $this->fmt($deal->contract_price ?? 0) . "\n";
        if ($property) {
            $context .= "Address: {$property->address}, {$property->city}, {$property->state} {$property->zip_code}\n";
            $context .= "Type: " . ucwords(str_replace('_', ' ', $property->property_type ?? 'residential')) . "\n";
            $context .= "Beds: " . ($property->bedrooms ?? 'N/A') . ", Baths: " . ($property->bathrooms ?? 'N/A') . "\n";
            $context .= "Sq Ft: " . ($property->square_footage ?? 'N/A') . "\n";
            $context .= "Year Built: " . ($property->year_built ?? 'N/A') . "\n";
            $context .= "Lot Size: " . ($property->lot_size ?? 'N/A') . "\n";
        }

        $system = "You are a real estate marketing copywriter. Generate professional marketing content based on the property data provided. Output ONLY the requested content — no introductions, headers, or commentary.";

        $sections = [
            'property_description' => "Write a compelling property listing description (150-200 words). Highlight key features and lifestyle benefits.\n\nProperty:\n{$context}",
            'social_caption' => "Write a social media caption for Instagram/Facebook (under 200 characters). Include relevant hashtags.\n\nProperty:\n{$context}",
            'flyer_copy' => "Write flyer body copy (100-150 words). Punchy, scannable, highlight top 5 features.\n\nProperty:\n{$context}",
            'open_house_blurb' => "Write an open house invitation blurb (80-100 words). Include excitement and urgency.\n\nProperty:\n{$context}",
            'email_blast' => "Write an email body for a property blast (150-200 words). Professional, warm, with clear call to action.\n\nProperty:\n{$context}",
        ];

        $results = [];
        foreach ($sections as $key => $prompt) {
            try {
                $results[$key] = $this->provider->chat($system, $prompt);
            } catch (\Exception $e) {
                $results[$key] = 'Error generating content: ' . $e->getMessage();
            }
        }

        return $results;
    }
}
