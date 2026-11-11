<?php

namespace Tests\Unit\Services;

use App\Models\Activity;
use App\Models\Buyer;
use App\Services\WholesaleCompletionService;
use Tests\TestCase;

class WholesaleCompletionServiceTest extends TestCase
{
    private function readyDeal(array $overrides = [])
    {
        $buyer = Buyer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Ready',
            'last_name' => 'Buyer',
        ]);

        return $this->createDeal(array_merge([
            'stage' => 'buyer_committed',
            'contract_gate_status' => 'approved',
            'executed_contract_status' => 'executed',
            'committed_buyer_id' => $buyer->id,
            'transaction_structure' => 'assignment',
            'assignment_rights_confirmed' => true,
            'confirmed_closing_provider' => 'Test Closing Attorney',
            'closing_date' => now()->addDays(10)->toDateString(),
            'buyer_covers_emd' => false,
            'earnest_money' => 0,
        ], $overrides));
    }

    public function test_assignment_completion_is_idempotent_under_repeated_retries(): void
    {
        $this->actingAsAdmin();
        $deal = $this->readyDeal();
        $service = app(WholesaleCompletionService::class);

        $first = $service->markAssigned($deal, ['final_assignment_fee' => 15000], $this->adminUser->id);
        $second = $service->markAssigned($deal->fresh(), ['final_assignment_fee' => 99999], $this->adminUser->id);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('15000.00', (string) $second->final_assignment_fee);
        $this->assertSame(1, Activity::where('deal_id', $deal->id)
            ->where('subject', 'Wholesale assignment completed')
            ->count());
    }

    public function test_assignment_completion_is_idempotent(): void
    {
        $this->actingAsAdmin();
        $deal = $this->readyDeal();
        $service = app(WholesaleCompletionService::class);

        $service->markAssigned($deal, ['final_assignment_fee' => 15000], $this->adminUser->id);
        $service->markAssigned($deal->fresh(), ['final_assignment_fee' => 99999], $this->adminUser->id);

        $fresh = $deal->fresh();

        $this->assertSame('assignment_completed', $fresh->completion_status);
        $this->assertSame('assigned', $fresh->stage);
        $this->assertSame('15000.00', (string) $fresh->final_assignment_fee);
        $this->assertSame(1, Activity::where('deal_id', $deal->id)
            ->where('subject', 'Wholesale assignment completed')
            ->count());
    }

    public function test_closed_won_completion_is_idempotent(): void
    {
        $this->actingAsAdmin();
        $deal = $this->readyDeal();

        $service = app(WholesaleCompletionService::class);
        $service->markAssigned($deal, ['final_assignment_fee' => 15000], $this->adminUser->id);
        $service->markClosedWon($deal->fresh(), ['final_closing_amount' => 225000], $this->adminUser->id);
        $service->markClosedWon($deal->fresh(), ['final_closing_amount' => 999999], $this->adminUser->id);

        $fresh = $deal->fresh();

        $this->assertSame('closed_won', $fresh->completion_status);
        $this->assertSame('closed_won', $fresh->stage);
        $this->assertSame('225000.00', (string) $fresh->final_closing_amount);
        $this->assertSame(1, Activity::where('deal_id', $deal->id)
            ->where('subject', 'Wholesale closing completed')
            ->count());
    }
}
