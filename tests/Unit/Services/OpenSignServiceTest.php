<?php

namespace Tests\Unit\Services;

use App\Models\Activity;
use App\Models\DealSignatureRequest;
use App\Services\OpenSignService;
use Tests\TestCase;

class OpenSignServiceTest extends TestCase
{
    public function test_completed_webhook_is_idempotent_for_completion_activity(): void
    {
        $this->actingAsAdmin();

        $deal = $this->createDeal([
            'tenant_id' => $this->tenant->id,
        ]);

        $request = DealSignatureRequest::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'title' => 'Purchase Agreement',
            'document_type' => 'purchase_agreement',
            'status' => 'sent',
            'opensign_object_id' => 'opensign-test-123',
            'archive_directory' => 'deals/' . $deal->id . '/executed',
        ]);

        $payload = [
            'event' => 'completed',
            'objectId' => $request->opensign_object_id,
            'completedAt' => now()->toIso8601String(),
        ];

        $service = app(OpenSignService::class);

        $first = $service->handleWebhook($payload);
        $second = $service->handleWebhook($payload);

        $this->assertSame('completed', $first?->status);
        $this->assertSame('completed', $second?->status);

        $this->assertSame(
            1,
            Activity::withoutGlobalScopes()
                ->where('deal_id', $deal->id)
                ->where('subject', 'Contract fully executed')
                ->count()
        );
    }
}
