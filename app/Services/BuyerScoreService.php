<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\BuyerTransaction;

class BuyerScoreService
{
    /**
     * Calculate a 0-100 buyer reliability score.
     */
    public static function calculate(Buyer $buyer): int
    {
        $score = 0;

        // POF verified: +20 points
        if ($buyer->pof_verified) {
            $score += 20;
        }

        // Total purchases: 1-2 = +10, 3-5 = +20, 6+ = +30
        $totalPurchases = $buyer->total_purchases ?? 0;
        if ($totalPurchases >= 6) {
            $score += 30;
        } elseif ($totalPurchases >= 3) {
            $score += 20;
        } elseif ($totalPurchases >= 1) {
            $score += 10;
        }

        // Average close days: <14 = +20, <30 = +15, <60 = +10, 60+ = +5
        $avgCloseDays = $buyer->avg_close_days;
        if ($avgCloseDays !== null && $totalPurchases > 0) {
            if ($avgCloseDays < 14) {
                $score += 20;
            } elseif ($avgCloseDays < 30) {
                $score += 15;
            } elseif ($avgCloseDays < 60) {
                $score += 10;
            } else {
                $score += 5;
            }
        }

        // Recency: last purchase <30 days = +15, <90 days = +10, <180 days = +5
        if ($buyer->last_purchase_at) {
            $daysSinceLast = now()->diffInDays($buyer->last_purchase_at);
            if ($daysSinceLast < 30) {
                $score += 15;
            } elseif ($daysSinceLast < 90) {
                $score += 10;
            } elseif ($daysSinceLast < 180) {
                $score += 5;
            }
        }

        // Has complete profile (phone + email + company): +5
        if ($buyer->phone && $buyer->email && $buyer->company) {
            $score += 5;
        }

        // Has preferences set (property types, zip codes): +10
        $hasPropertyTypes = $buyer->preferred_property_types && count($buyer->preferred_property_types) > 0;
        $hasZipCodes = $buyer->preferred_zip_codes && count($buyer->preferred_zip_codes) > 0;
        if ($hasPropertyTypes && $hasZipCodes) {
            $score += 10;
        }

        return min($score, 100);
    }

    /**
     * Recalculate score and update buyer stats from transactions.
     */
    public static function recalculate(Buyer $buyer): void
    {
        $transactions = BuyerTransaction::where('buyer_id', $buyer->id)->get();

        $totalPurchases = $transactions->count();
        $avgCloseDays = $totalPurchases > 0
            ? $transactions->whereNotNull('days_to_close')->avg('days_to_close')
            : null;
        $lastPurchaseAt = $transactions->max('close_date');

        $buyer->total_purchases = $totalPurchases;
        $buyer->avg_close_days = $avgCloseDays ?? 0;
        $buyer->last_purchase_at = $lastPurchaseAt;
        $buyer->buyer_score = self::calculate($buyer);
        $buyer->save();
    }
}
