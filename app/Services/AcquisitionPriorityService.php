<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Property;

class AcquisitionPriorityService
{
    public function score(Lead $lead): array
    {
        $lead->loadMissing(['property', 'lists']);

        $score = 0;
        $reasons = [];
        $property = $lead->property;
        $seenSignals = [];

        foreach ($lead->lists as $list) {
            $signal = $this->normalizeSignal($list->type);
            if ($signal === '' || isset($seenSignals[$signal])) {
                continue;
            }

            $seenSignals[$signal] = true;
            $score += $this->signalPoints($signal);
            $reasons[] = $this->label($signal);
        }

        if ($property) {
            foreach ($this->normalizedMarkers($property) as $marker) {
                if (isset($seenSignals[$marker])) {
                    continue;
                }

                $seenSignals[$marker] = true;
                $score += $this->signalPoints($marker);
                $reasons[] = $this->label($marker);
            }

            $score += match (strtolower((string) $property->condition)) {
                'gut_rehab', 'heavy_rehab', 'poor' => 12,
                'moderate_rehab', 'fair' => 7,
                'light_rehab' => 4,
                default => 0,
            };

            if ($property->repair_estimate !== null && (float) $property->repair_estimate > 0) {
                $score += 4;
                $reasons[] = 'Repairs identified';
            }

            $value = (float) ($property->estimated_value ?? 0);
            $asking = (float) ($property->asking_price ?? 0);
            if ($value > 0 && $asking > 0 && $asking < $value) {
                $discount = ($value - $asking) / $value;
                if ($discount >= 0.30) {
                    $score += 10;
                    $reasons[] = '30%+ value-to-asking spread';
                } elseif ($discount >= 0.15) {
                    $score += 5;
                    $reasons[] = '15%+ value-to-asking spread';
                }
            }

            if ($property->after_repair_value !== null && $property->maximum_allowable_offer !== null
                && (float) $property->maximum_allowable_offer > 0) {
                $score += 5;
                $reasons[] = 'Underwriting completed';
            }
        }

        $score += match ($lead->temperature) {
            'hot' => 8,
            'warm' => 4,
            default => 0,
        };

        $score = min(100, $score);

        $tier = match (true) {
            $score >= 75 => 'priority',
            $score >= 50 => 'high',
            $score >= 25 => 'medium',
            default => 'low',
        };

        $action = match ($tier) {
            'priority' => 'Contact immediately and underwrite before making an offer.',
            'high' => 'Prioritize seller outreach and complete missing underwriting.',
            'medium' => 'Nurture and verify distress/equity before prioritizing.',
            default => 'Keep in follow-up until stronger acquisition signals appear.',
        };

        return [
            'score' => $score,
            'tier' => $tier,
            'action' => $action,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function signalPoints(string $signal): int
    {
        return match ($signal) {
            'pre_foreclosure' => 18,
            'tax_delinquent' => 16,
            'probate' => 14,
            'code_violation' => 12,
            'vacant' => 12,
            'absentee_owner' => 10,
            'high_equity' => 10,
            'tired_landlord' => 10,
            'vacant_land' => 8,
            default => 3,
        };
    }

    private function normalizeSignal(?string $value): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim((string) $value)));
    }

    private function normalizedMarkers(Property $property): array
    {
        $markers = $property->distress_markers;
        if (!is_array($markers)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($marker) => $this->normalizeSignal($marker),
            $markers
        ))));
    }

    private function label(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
