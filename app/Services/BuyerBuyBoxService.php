<?php

namespace App\Services;

use App\Models\Buyer;

/**
 * Applies a buyer's actual buy-box to a property/deal.
 *
 * This is an underwriting aid, not a promise that a buyer will close.
 * max_purchase_price remains a hard ceiling when supplied.
 */
class BuyerBuyBoxService
{
    public function underwrite(Buyer $buyer, array $deal): array
    {
        $arv = $this->money($deal['arv'] ?? null);
        $repairs = $this->money($deal['repairs'] ?? 0);
        $contractPrice = $this->money($deal['contract_price'] ?? null);
        $assignmentFee = $this->money($deal['assignment_fee'] ?? 0);

        if ($arv === null || $arv <= 0) {
            return [
                'status' => 'insufficient_data',
                'reason' => 'ARV is required for buyer-specific underwriting.',
            ];
        }

        $condition = (string) ($deal['condition'] ?? 'unknown');
        $strategy = (string) ($deal['strategy'] ?? 'flip');

        $transactionPct = $this->ratio($buyer->transaction_cost_percent) ?? 0.05;
        $holdingPct = $this->ratio($buyer->holding_cost_percent) ?? $this->defaultHolding($condition);
        $financingPct = $this->ratio($buyer->financing_cost_percent) ?? 0.03;
        $riskPct = $this->ratio($buyer->risk_buffer_percent) ?? $this->defaultRisk($condition);
        $profitTarget = $this->money($buyer->target_profit);

        if ($profitTarget === null) {
            $profitTarget = $arv * $this->defaultProfit($strategy, $condition);
        }

        $allInCeiling = $buyer->max_all_in_percent !== null
            ? $arv * (float) $buyer->max_all_in_percent
            : $arv - ($arv * $transactionPct)
                - ($arv * $holdingPct)
                - ($arv * $financingPct)
                - ($arv * $riskPct)
                - $profitTarget;

        $purchaseCeiling = max(0, $allInCeiling - $repairs);
        $wholesaleContractCeiling = max(0, $purchaseCeiling - $assignmentFee);

        if ($buyer->max_purchase_price !== null) {
            $purchaseCeiling = min($purchaseCeiling, (float) $buyer->max_purchase_price);
            $wholesaleContractCeiling = max(0, $purchaseCeiling - $assignmentFee);
        }

        $hardBudgetPass = $contractPrice === null || $contractPrice <= (float) ($buyer->max_purchase_price ?? PHP_FLOAT_MAX);
        $underwritingPass = $contractPrice === null || $contractPrice <= $wholesaleContractCeiling;

        return [
            'status' => 'calculated',
            'buyer_id' => $buyer->id,
            'buyer_name' => trim(($buyer->company ?: trim($buyer->first_name.' '.$buyer->last_name))),
            'strategy' => $strategy,
            'condition' => $condition,
            'arv' => round($arv, 2),
            'repairs' => round($repairs, 2),
            'assignment_fee' => round($assignmentFee, 2),
            'buyer_max_all_in_percent' => $buyer->max_all_in_percent !== null
                ? round((float) $buyer->max_all_in_percent, 4)
                : null,
            'target_profit' => round($profitTarget, 2),
            'transaction_cost_percent' => round($transactionPct, 4),
            'holding_cost_percent' => round($holdingPct, 4),
            'financing_cost_percent' => round($financingPct, 4),
            'risk_buffer_percent' => round($riskPct, 4),
            'buyer_purchase_ceiling' => round($purchaseCeiling, 2),
            'wholesale_contract_ceiling' => round($wholesaleContractCeiling, 2),
            'contract_price' => $contractPrice !== null ? round($contractPrice, 2) : null,
            'hard_budget_pass' => $hardBudgetPass,
            'underwriting_pass' => $underwritingPass,
            'estimated_spread' => $contractPrice !== null
                ? round($wholesaleContractCeiling - $contractPrice, 2)
                : null,
            'method' => $buyer->max_all_in_percent !== null ? 'buyer_buy_box_percent' : 'buyer_economics',
        ];
    }

    private function defaultProfit(string $strategy, string $condition): float
    {
        if ($strategy === 'rental') {
            return 0.08;
        }

        return match ($condition) {
            'turnkey' => 0.08,
            'light_rehab' => 0.09,
            'moderate_rehab' => 0.10,
            'heavy_rehab' => 0.12,
            'gut_rehab' => 0.15,
            default => 0.10,
        };
    }

    private function defaultHolding(string $condition): float
    {
        return match ($condition) {
            'turnkey' => 0.02,
            'light_rehab' => 0.03,
            'moderate_rehab' => 0.04,
            'heavy_rehab' => 0.05,
            'gut_rehab' => 0.06,
            default => 0.04,
        };
    }

    private function defaultRisk(string $condition): float
    {
        return match ($condition) {
            'turnkey' => 0.015,
            'light_rehab' => 0.02,
            'moderate_rehab' => 0.025,
            'heavy_rehab' => 0.035,
            'gut_rehab' => 0.05,
            default => 0.03,
        };
    }

    private function money(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? max(0, (float) $value) : null;
    }

    private function ratio(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (float) $value;
        if ($value > 1) {
            $value /= 100;
        }

        return $value >= 0 && $value <= 1 ? $value : null;
    }
}
