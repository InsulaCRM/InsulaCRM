<?php

namespace App\Services;

use App\Models\Lead;

class MotivationScoreService
{
    /**
     * Bonus points for specific list types (wholesale mode).
     */
    protected array $listBonuses = [
        'tax_delinquent' => 20,
        'probate' => 15,
        'pre_foreclosure' => 15,
        'code_violation' => 10,
        'absentee_owner' => 10,
    ];

    /**
     * Recalculate the motivation score for a lead based on all available data signals.
     * This is the automated/system score — purely data-driven, no AI involved.
     * Scoring logic adapts to the tenant's business mode.
     */
    public function recalculate(Lead $lead): int
    {
        if (BusinessModeService::isRealEstate()) {
            return $this->recalculateRealEstate($lead);
        }

        return $this->recalculateWholesale($lead);
    }

    /**
     * Wholesale scoring: list stacking (50), temperature (15), activity (15), property distress (20).
     */
    protected function recalculateWholesale(Lead $lead): int
    {
        $lead->loadMissing(['lists', 'activities', 'property']);

        $score = 0;

        // ── List stacking (max 50 pts) ──
        $lists = $lead->lists;
        $score += min($lists->count() * 10, 30); // 10 pts per list, cap 30

        foreach ($lists as $list) {
            if (isset($this->listBonuses[$list->type])) {
                $score += $this->listBonuses[$list->type];
            }
        }
        $score = min($score, 50); // list section capped at 50

        // ── Temperature (max 15 pts) ──
        $score += match ($lead->temperature) {
            'hot' => 15,
            'warm' => 8,
            default => 0,
        };

        // ── Engagement / Activity (max 15 pts) ──
        $score += $this->activityScore($lead, 15);

        // ── Property signals (max 20 pts) ──
        $property = $lead->property;
        if ($property) {
            $score += match ($property->condition) {
                'poor' => 10,
                'fair' => 5,
                'good' => 2,
                default => 0,
            };

            $markers = is_array($property->distress_markers) ? $property->distress_markers : [];
            $score += min(count($markers) * 3, 10);
        }

        $score = min($score, 100);
        $lead->motivation_score = $score;
        $lead->save();

        return $score;
    }

    /**
     * Real estate agent scoring: activity engagement (40), temperature (25),
     * response recency (20), source quality (15).
     */
    protected function recalculateRealEstate(Lead $lead): int
    {
        $lead->loadMissing(['activities']);

        $score = 0;

        // ── Activity engagement (max 40 pts) ──
        $activities = $lead->activities;
        $activityCount = $activities->count();

        // Volume: up to 20 pts
        if ($activityCount >= 10) {
            $score += 20;
        } elseif ($activityCount >= 5) {
            $score += 14;
        } elseif ($activityCount >= 3) {
            $score += 8;
        } elseif ($activityCount >= 1) {
            $score += 4;
        }

        // Recency: up to 20 pts — last activity within past N days
        $lastActivity = $activities->sortByDesc('logged_at')->first();
        if ($lastActivity && $lastActivity->logged_at) {
            $daysSince = (int) now()->diffInDays($lastActivity->logged_at, true);
            if ($daysSince <= 2) {
                $score += 20;
            } elseif ($daysSince <= 7) {
                $score += 14;
            } elseif ($daysSince <= 14) {
                $score += 8;
            } elseif ($daysSince <= 30) {
                $score += 4;
            }
        }

        // ── Temperature (max 25 pts) ──
        $score += match ($lead->temperature) {
            'hot' => 25,
            'warm' => 14,
            default => 0,
        };

        // ── Response recency (max 20 pts) — how quickly lead was contacted after creation ──
        $firstActivity = $activities->sortBy('logged_at')->first();
        if ($firstActivity && $firstActivity->logged_at && $lead->created_at) {
            $hoursToContact = (int) $lead->created_at->diffInHours($firstActivity->logged_at, true);
            if ($hoursToContact <= 1) {
                $score += 20;
            } elseif ($hoursToContact <= 4) {
                $score += 15;
            } elseif ($hoursToContact <= 24) {
                $score += 10;
            } elseif ($hoursToContact <= 72) {
                $score += 5;
            }
        }

        // ── Source quality (max 15 pts) ──
        $highValueSources = ['referral', 'past_client', 'sphere', 'sign_call', 'open_house'];
        $mediumValueSources = ['website', 'zillow', 'realtor_com', 'mls'];
        if (in_array($lead->source, $highValueSources, true)) {
            $score += 15;
        } elseif (in_array($lead->source, $mediumValueSources, true)) {
            $score += 8;
        } elseif ($lead->source) {
            $score += 3;
        }

        $score = min($score, 100);
        $lead->motivation_score = $score;
        $lead->save();

        return $score;
    }

    /**
     * Shared activity count scoring helper.
     */
    protected function activityScore(Lead $lead, int $max): int
    {
        $count = $lead->activities->count();
        if ($count >= 10) return $max;
        if ($count >= 5) return (int) ($max * 0.67);
        if ($count >= 2) return (int) ($max * 0.33);
        return 0;
    }
}
