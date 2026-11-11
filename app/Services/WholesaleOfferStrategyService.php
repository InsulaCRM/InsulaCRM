<?php

namespace App\Services;

/**
 * Calculates wholesale acquisition ranges from buyer economics instead of
 * treating the 70% rule as a universal offer price.
 *
 * The 70% rule remains available as a benchmark. It is not the system's
 * default decision rule when enough deal inputs are available.
 */
class WholesaleOfferStrategyService
{
    public function calculate(array $inputs): array
    {
        $arv = $this->money($inputs['after_repair_value'] ?? null);
        $repairs = $this->money($inputs['repair_estimate'] ?? 0);
        $assignmentFee = $this->money($inputs['assignment_fee'] ?? 0);
        $transactionCosts = $this->money($inputs['transaction_costs'] ?? null);
        $holdingCosts = $this->money($inputs['holding_costs'] ?? null);
        $financingCosts = $this->money($inputs['financing_costs'] ?? null);
        $riskBuffer = $this->money($inputs['risk_buffer'] ?? null);
        $buyerProfit = $this->money($inputs['buyer_profit_target'] ?? null);
        $buyerAllInPercent = $this->ratio($inputs['buyer_max_all_in_percent'] ?? null);
        $strategy = $inputs['strategy'] ?? 'flip';
        $condition = $inputs['condition'] ?? 'unknown';

        if ($arv === null || $arv <= 0) {
            return [
                'status' => 'insufficient_data',
                'reason' => 'After-repair value is required.',
            ];
        }

        $defaults = $this->defaults($arv, $strategy, $condition);

        $transactionCosts ??= $defaults['transaction_costs'];
        $holdingCosts ??= $defaults['holding_costs'];
        $financingCosts ??= $defaults['financing_costs'];
        $riskBuffer ??= $defaults['risk_buffer'];
        $buyerProfit ??= $defaults['buyer_profit_target'];

        $benchmarkBuyerMax = ($arv * 0.70) - $repairs;
        $buyerMaxAllIn = $buyerAllInPercent !== null
            ? $arv * $buyerAllInPercent
            : $arv - $transactionCosts - $holdingCosts - $financingCosts - $riskBuffer - $buyerProfit;

        $buyerPurchaseCeiling = $buyerMaxAllIn - $repairs;
        $sellerContractCeiling = $buyerPurchaseCeiling - $assignmentFee;

        $conservativeBuyerCeiling = $buyerPurchaseCeiling - ($riskBuffer * 0.50);
        $aggressiveBuyerCeiling = $buyerPurchaseCeiling + ($riskBuffer * 0.50);

        return [
            'status' => 'calculated',
            'strategy' => $strategy,
            'condition' => $condition,
            'arv' => round($arv, 2),
            'repairs' => round($repairs, 2),
            'transaction_costs' => round($transactionCosts, 2),
            'holding_costs' => round($holdingCosts, 2),
            'financing_costs' => round($financingCosts, 2),
            'risk_buffer' => round($riskBuffer, 2),
            'buyer_profit_target' => round($buyerProfit, 2),
            'assignment_fee' => round($assignmentFee, 2),
            'buyer_max_all_in_percent' => $buyerAllInPercent,
            'buyer_purchase_ceiling' => round($buyerPurchaseCeiling, 2),
            'seller_contract_ceiling' => round($sellerContractCeiling, 2),
            'buyer_price_range' => [
                'conservative' => round(max(0, $conservativeBuyerCeiling), 2),
                'base' => round(max(0, $buyerPurchaseCeiling), 2),
                'aggressive' => round(max(0, $aggressiveBuyerCeiling), 2),
            ],
            'legacy_70_percent_benchmark' => round(max(0, $benchmarkBuyerMax), 2),
            'legacy_70_percent_seller_ceiling' => round(max(0, $benchmarkBuyerMax - $assignmentFee), 2),
            'method' => $buyerAllInPercent !== null
                ? 'buyer_buy_box'
                : 'buyer_economics',
        ];
    }

    private function defaults(float $arv, string $strategy, string $condition): array
    {
        $conditionAdjustments = [
            'turnkey' => ['transaction' => 0.05, 'holding' => 0.02, 'financing' => 0.02, 'risk' => 0.015, 'profit' => 0.08],
            'light_rehab' => ['transaction' => 0.05, 'holding' => 0.03, 'financing' => 0.025, 'risk' => 0.02, 'profit' => 0.09],
            'moderate_rehab' => ['transaction' => 0.05, 'holding' => 0.04, 'financing' => 0.03, 'risk' => 0.025, 'profit' => 0.10],
            'heavy_rehab' => ['transaction' => 0.05, 'holding' => 0.05, 'financing' => 0.04, 'risk' => 0.035, 'profit' => 0.12],
            'gut_rehab' => ['transaction' => 0.05, 'holding' => 0.06, 'financing' => 0.05, 'risk' => 0.05, 'profit' => 0.15],
            'unknown' => ['transaction' => 0.05, 'holding' => 0.04, 'financing' => 0.03, 'risk' => 0.03, 'profit' => 0.10],
        ];

        $adjustment = $conditionAdjustments[$condition] ?? $conditionAdjustments['unknown'];

        if ($strategy === 'rental') {
            $adjustment['profit'] = 0.08;
            $adjustment['holding'] = 0.025;
        }

        return [
            'transaction_costs' => $arv * $adjustment['transaction'],
            'holding_costs' => $arv * $adjustment['holding'],
            'financing_costs' => $arv * $adjustment['financing'],
            'risk_buffer' => $arv * $adjustment['risk'],
            'buyer_profit_target' => $arv * $adjustment['profit'],
        ];
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

        return $value > 0 && $value <= 1 ? $value : null;
    }
}
