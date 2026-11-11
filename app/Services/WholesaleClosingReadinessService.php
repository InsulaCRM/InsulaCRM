<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\TransactionChecklist;

class WholesaleClosingReadinessService
{
    public function assess(Deal $deal): array
    {
        $blockers = [];

        if ($deal->stage !== 'buyer_committed' && $deal->stage !== 'assigned' && $deal->stage !== 'closing') {
            $blockers[] = 'Deal must have a committed buyer before assignment/closing readiness.';
        }

        if ($deal->contract_gate_status !== 'approved') {
            $blockers[] = 'Contract Protection Gate is not approved.';
        }

        if (!$deal->committed_buyer_id) {
            $blockers[] = 'No committed buyer is recorded.';
        }

        if (!$deal->transaction_structure) {
            $blockers[] = 'Transaction structure is not recorded.';
        }

        if (!$deal->confirmed_closing_provider) {
            $blockers[] = 'Confirmed closing provider is missing.';
        }

        if (!$deal->closing_date) {
            $blockers[] = 'Closing date is missing.';
        }

        if ($deal->transaction_structure === 'assignment' && !$deal->assignment_rights_confirmed) {
            $blockers[] = 'Assignment rights are not confirmed.';
        }

        if ($deal->buyer_covers_emd && !$deal->emd_funding_confirmed) {
            $blockers[] = 'Buyer-funded EMD is not confirmed.';
        }

        if ($deal->earnest_money !== null && (float) $deal->earnest_money > 50 && !$deal->emd_exception_reason) {
            $blockers[] = 'EMD above $50 requires an explicit exception reason.';
        }

        $checklist = TransactionChecklist::where('deal_id', $deal->id)->get();
        $requiredIncomplete = $checklist->filter(fn ($item) => !in_array($item->status, ['completed', 'waived'], true));
        if ($requiredIncomplete->isNotEmpty()) {
            $blockers[] = 'One or more transaction checklist items remain incomplete.';
        }

        return [
            'ready' => empty($blockers),
            'blockers' => array_values(array_unique($blockers)),
            'checklist' => $checklist->map(fn ($item) => [
                'id' => $item->id,
                'item_key' => $item->item_key,
                'label' => $item->label,
                'status' => $item->status,
                'deadline' => $item->deadline?->toDateString(),
                'overdue' => $item->is_overdue,
            ])->values()->all(),
        ];
    }
}
