<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Deal;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WholesaleCompletionService
{
    public function markAssigned(Deal $deal, array $data, int $userId): Deal
    {
        $this->assertReadyForCompletion($deal);

        if ($deal->stage !== 'closing' && $deal->stage !== 'buyer_committed' && $deal->stage !== 'assigned') {
            throw new RuntimeException('Deal must be buyer committed, assigned, or closing before assignment completion.');
        }

        return DB::transaction(function () use ($deal, $data, $userId) {
            $oldStage = $deal->stage;
            $deal->update([
                'stage' => 'assigned',
                'stage_changed_at' => now(),
                'completion_status' => 'assignment_completed',
                'assignment_completed_at' => now(),
                'final_assignment_fee' => array_key_exists('final_assignment_fee', $data)
                    ? $data['final_assignment_fee']
                    : $deal->assignment_fee,
                'closing_result' => $data['closing_result'] ?? 'assignment_completed',
                'closing_result_notes' => $data['closing_result_notes'] ?? null,
                'completion_recorded_by' => $userId,
            ]);

            Activity::withoutGlobalScopes()->create([
                'tenant_id' => $deal->tenant_id,
                'lead_id' => $deal->lead_id,
                'deal_id' => $deal->id,
                'agent_id' => $deal->agent_id ?: $userId,
                'type' => 'stage_change',
                'subject' => 'Wholesale assignment completed',
                'body' => 'Assignment completion was explicitly recorded for deal #' . $deal->id . '.',
                'logged_at' => now(),
            ]);

            WebhookService::dispatch('deal.assigned.completed', [
                'deal_id' => $deal->id,
                'lead_id' => $deal->lead_id,
                'buyer_id' => $deal->committed_buyer_id,
                'old_stage' => $oldStage,
                'stage' => 'assigned',
                'final_assignment_fee' => $deal->final_assignment_fee,
                'closing_result' => $deal->closing_result,
                'closing_result_notes' => $deal->closing_result_notes,
                'completed_at' => $deal->assignment_completed_at,
            ], $deal->tenant_id);

            return $deal->fresh(['committedBuyer']);
        });
    }

    public function markClosedWon(Deal $deal, array $data, int $userId): Deal
    {
        $this->assertReadyForCompletion($deal);

        if (!in_array($deal->stage, ['assigned', 'closing'], true)) {
            throw new RuntimeException('Deal must be assigned or closing before it can be marked closed won.');
        }

        if (empty($deal->assignment_completed_at) && $deal->transaction_structure === 'assignment') {
            throw new RuntimeException('Assignment completion must be recorded before an assignment deal can be closed won.');
        }

        return DB::transaction(function () use ($deal, $data, $userId) {
            $oldStage = $deal->stage;

            $deal->update([
                'stage' => 'closed_won',
                'stage_changed_at' => now(),
                'completion_status' => 'closed_won',
                'closed_won_at' => now(),
                'final_assignment_fee' => array_key_exists('final_assignment_fee', $data)
                    ? $data['final_assignment_fee']
                    : $deal->final_assignment_fee ?: $deal->assignment_fee,
                'final_closing_amount' => $data['final_closing_amount'] ?? $deal->final_closing_amount,
                'closing_result' => $data['closing_result'] ?? 'closed_won',
                'closing_result_notes' => $data['closing_result_notes'] ?? null,
                'completion_recorded_by' => $userId,
            ]);

            Activity::withoutGlobalScopes()->create([
                'tenant_id' => $deal->tenant_id,
                'lead_id' => $deal->lead_id,
                'deal_id' => $deal->id,
                'agent_id' => $deal->agent_id ?: $userId,
                'type' => 'stage_change',
                'subject' => 'Wholesale closing completed',
                'body' => 'Closing completion was explicitly recorded for deal #' . $deal->id . '.',
                'logged_at' => now(),
            ]);

            WebhookService::dispatch('deal.closed', [
                'deal_id' => $deal->id,
                'lead_id' => $deal->lead_id,
                'buyer_id' => $deal->committed_buyer_id,
                'old_stage' => $oldStage,
                'stage' => 'closed_won',
                'transaction_structure' => $deal->transaction_structure,
                'final_assignment_fee' => $deal->final_assignment_fee,
                'final_closing_amount' => $deal->final_closing_amount,
                'closing_result' => $deal->closing_result,
                'closing_result_notes' => $deal->closing_result_notes,
                'closed_won_at' => $deal->closed_won_at,
            ], $deal->tenant_id);

            return $deal->fresh(['committedBuyer']);
        });
    }

    protected function assertReadyForCompletion(Deal $deal): void
    {
        if ($deal->contract_gate_status !== 'approved') {
            throw new RuntimeException('Contract Protection Gate approval is required.');
        }

        if ($deal->executed_contract_status !== 'executed') {
            throw new RuntimeException('The executed contract must be recorded before completion.');
        }

        if (!$deal->committed_buyer_id) {
            throw new RuntimeException('A committed buyer is required.');
        }

        $readiness = app(WholesaleClosingReadinessService::class)->assess($deal);
        if (!$readiness['ready']) {
            throw new RuntimeException('Closing readiness gate is not satisfied: ' . implode(' ', $readiness['blockers']));
        }
    }
}
