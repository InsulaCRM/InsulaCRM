<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\Deal;
use App\Models\DealBuyerMatch;
use App\Models\Lead;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\Ai\AiDraftingService;
use App\Services\Ai\AiInsightService;
use App\Services\AiProviders\AiProviderInterface;
use App\Services\AiProviders\AnthropicProvider;
use App\Services\AiProviders\CustomOpenAiProvider;
use App\Services\AiProviders\GeminiProvider;
use App\Services\AiProviders\OllamaProvider;
use App\Services\AiProviders\OpenAiProvider;

class AiService
{
    protected ?AiProviderInterface $provider = null;
    protected Tenant $tenant;
    protected ?AiDraftingService $draftingService = null;
    protected ?AiInsightService $insightService = null;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;

        if ($tenant->ai_enabled && $tenant->ai_provider) {
            $this->provider = $this->resolveProvider($tenant);
        }
    }

    public function isAvailable(): bool
    {
        return $this->provider !== null;
    }

    public function testConnection(): bool
    {
        return $this->provider?->testConnection() ?? false;
    }

    public function listModels(): array
    {
        return $this->provider?->listModels() ?? [];
    }

    public static function createProvider(string $provider, ?string $apiKey, ?string $model, ?string $ollamaUrl, ?string $customUrl): AiProviderInterface
    {
        return match ($provider) {
            'openai' => new OpenAiProvider($apiKey ?? '', $model ?: 'gpt-4o-mini'),
            'anthropic' => new AnthropicProvider($apiKey ?? '', $model ?: 'claude-sonnet-4-6'),
            'gemini' => new GeminiProvider($apiKey ?? '', $model ?: 'gemini-2.5-flash'),
            'ollama' => new OllamaProvider($ollamaUrl ?: 'http://localhost:11434', $model ?: 'llama3.1'),
            'custom' => new CustomOpenAiProvider($apiKey ?? '', $model ?: '', $customUrl ?: 'http://localhost:1234'),
            default => throw new \RuntimeException("Unknown AI provider: {$provider}"),
        };
    }

    public function draftFollowUp(Lead $lead, string $messageType = 'sms'): string
    {
        return $this->drafting()->draftFollowUp($lead, $messageType);
    }

    public function summarizeNotes(Lead $lead): string
    {
        return $this->insights()->summarizeNotes($lead);
    }

    public function analyzeDeal(Deal $deal): string
    {
        return $this->insights()->analyzeDeal($deal);
    }

    public function analyzeDealWithActions(Deal $deal): array
    {
        return $this->insights()->analyzeDealWithActions($deal);
    }

    public function draftBuyerMessage(Deal $deal, Buyer $buyer): string
    {
        return $this->drafting()->draftBuyerMessage($deal, $buyer);
    }

    public function scoreLeadMotivation(Lead $lead): array
    {
        return $this->insights()->scoreLeadMotivation($lead);
    }

    public function draftSequenceStep(string $sequenceName, int $stepNumber, int $totalSteps, string $actionType, int $delayDays, ?string $previousStepSummary = null): string
    {
        return $this->drafting()->draftSequenceStep($sequenceName, $stepNumber, $totalSteps, $actionType, $delayDays, $previousStepSummary);
    }

    public function suggestOfferStrategy(Property $property): string
    {
        return $this->insights()->suggestOfferStrategy($property);
    }

    public function qualifyLead(Lead $lead, ?string $listType = null): array
    {
        return $this->insights()->qualifyLead($lead, $listType);
    }

    public function generatePropertyDescription(Property $property): string
    {
        return $this->drafting()->generatePropertyDescription($property);
    }

    public function adviseDealStage(Deal $deal): string
    {
        return $this->insights()->adviseDealStage($deal);
    }

    public function adviseDealStageWithActions(Deal $deal): array
    {
        return $this->insights()->adviseDealStageWithActions($deal);
    }

    public function suggestCsvMapping(array $headers, array $sampleRows): array
    {
        return $this->insights()->suggestCsvMapping($headers, $sampleRows);
    }

    public function generateAllSequenceSteps(string $sequenceName, array $steps): array
    {
        return $this->drafting()->generateAllSequenceSteps($sequenceName, $steps);
    }

    public function explainBuyerMatch(Deal $deal, Buyer $buyer, ?DealBuyerMatch $match = null): string
    {
        return $this->insights()->explainBuyerMatch($deal, $buyer, $match);
    }

    public function generateWeeklyDigest(array $kpiData): string
    {
        return $this->insights()->generateWeeklyDigest($kpiData);
    }

    public function flagDncRisks(Lead $lead): array
    {
        return $this->insights()->flagDncRisks($lead);
    }

    public function generateObjectionResponses(Lead $lead, ?string $specificObjection = null): string
    {
        return $this->drafting()->generateObjectionResponses($lead, $specificObjection);
    }

    public function suggestTasks(Lead $lead): array
    {
        return $this->insights()->suggestTasks($lead);
    }

    public function leadSummary(Lead $lead): string
    {
        return $this->insights()->leadSummary($lead);
    }

    public function comparableSalesAnalysis(Property $property): string
    {
        return $this->insights()->comparableSalesAnalysis($property);
    }

    public function generateEmailSubjectLines(Lead $lead, string $emailBody): array
    {
        return $this->drafting()->generateEmailSubjectLines($lead, $emailBody);
    }

    public function routeLead(Lead $lead, array $agentProfiles): ?int
    {
        return $this->insights()->routeLead($lead, $agentProfiles);
    }

    public function pipelineHealth(array $metrics): string
    {
        return $this->insights()->pipelineHealth($metrics);
    }

    public function generateGoalForecast(array $kpiData): string
    {
        return $this->insights()->generateGoalForecast($kpiData);
    }

    public function analyzeArv(Property $property, array $comps): string
    {
        return $this->insights()->analyzeArv($property, $comps);
    }

    public function draftDocumentContent(string $templateType, string $prompt, ?array $dealContext = null): string
    {
        return $this->insights()->draftDocumentContent($templateType, $prompt, $dealContext);
    }

    public function analyzeCampaign(array $campaignData): string
    {
        return $this->insights()->analyzeCampaign($campaignData);
    }

    public function assessBuyerRisk(array $buyerData): string
    {
        return $this->insights()->assessBuyerRisk($buyerData);
    }

    public function recommendGoals(array $currentMetrics): array
    {
        return $this->insights()->recommendGoals($currentMetrics);
    }

    public function generatePortalDescription(Property $property): string
    {
        return $this->insights()->generatePortalDescription($property);
    }

    public function qualifyLeadForWorkflow(Lead $lead): array
    {
        return $this->insights()->qualifyLeadForWorkflow($lead);
    }

    public function generateBuyerCriteria(array $data): string
    {
        return $this->drafting()->generateBuyerCriteria($data);
    }

    public function generateCampaignPlan(string $name, string $type, ?float $budget = null): string
    {
        return $this->drafting()->generateCampaignPlan($name, $type, $budget);
    }

    public function leadBriefing(Lead $lead): string
    {
        return $this->insights()->leadBriefing($lead);
    }

    public function dealBriefing(Deal $deal): string
    {
        return $this->insights()->dealBriefing($deal);
    }

    public function buyerBriefing(Buyer $buyer): string
    {
        return $this->insights()->buyerBriefing($buyer);
    }

    protected function resolveProvider(Tenant $tenant): AiProviderInterface
    {
        return self::createProvider(
            $tenant->ai_provider,
            $tenant->ai_api_key,
            $tenant->ai_model,
            $tenant->ai_ollama_url,
            $tenant->ai_custom_url,
        );
    }

    private function drafting(): AiDraftingService
    {
        if (! $this->provider) {
            throw new \RuntimeException('AI provider is not configured.');
        }

        return $this->draftingService ??= new AiDraftingService($this->tenant, $this->provider);
    }

    private function insights(): AiInsightService
    {
        if (! $this->provider) {
            throw new \RuntimeException('AI provider is not configured.');
        }

        return $this->insightService ??= new AiInsightService($this->tenant, $this->provider);
    }
}
