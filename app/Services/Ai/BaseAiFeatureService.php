<?php

namespace App\Services\Ai;

use App\Models\Lead;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\AiProviders\AiProviderInterface;

abstract class BaseAiFeatureService
{
    public function __construct(
        protected Tenant $tenant,
        protected AiProviderInterface $provider,
    ) {
    }

    protected function buildPropertyContext(Property $property): string
    {
        $context = "Property: {$property->full_address}\n";
        $context .= "Type: " . str_replace('_', ' ', $property->property_type ?? 'unknown') . "\n";
        $context .= "Condition: " . ($property->condition ?? 'N/A') . "\n";
        $context .= "Bedrooms: " . ($property->bedrooms ?? 'N/A') . ", Bathrooms: " . ($property->bathrooms ?? 'N/A') . "\n";
        $context .= "Sq Ft: " . ($property->square_footage ? number_format($property->square_footage) : 'N/A') . "\n";
        $context .= "Year Built: " . ($property->year_built ?? 'N/A') . "\n";
        $context .= "Lot Size: " . ($property->lot_size ?? 'N/A') . " acres\n";
        $isRE = \App\Services\BusinessModeService::isRealEstate();
        if ($isRE) {
            if ($property->list_price) {
                $context .= "List Price: " . $this->fmt($property->list_price) . "\n";
            }
            if ($property->estimated_value) {
                $context .= "Estimated Value: " . $this->fmt($property->estimated_value) . "\n";
            }
            $context .= "Asking Price: " . $this->fmt($property->asking_price ?? 0) . "\n";
        } else {
            $context .= "Estimated Value: " . $this->fmt($property->estimated_value ?? 0) . "\n";
            $context .= "ARV: " . $this->fmt($property->after_repair_value ?? 0) . "\n";
            $context .= "Repair Estimate: " . $this->fmt($property->repair_estimate ?? 0) . "\n";
            $context .= "MAO (70% rule): " . $this->fmt($property->mao ?? 0) . "\n";
            $context .= "Asking Price: " . $this->fmt($property->asking_price ?? 0) . "\n";
            $context .= "Our Offer: " . $this->fmt($property->our_offer ?? 0) . "\n";

            $markers = is_array($property->distress_markers) ? implode(', ', $property->distress_markers) : '';
            if ($markers) {
                $context .= "Distress Markers: {$markers}\n";
            }
        }
        if ($property->notes) {
            $context .= "Property Notes: {$property->notes}\n";
        }
        if ($property->lead) {
            $contactLabel = \App\Services\BusinessModeService::isRealEstate() ? 'Contact' : 'Seller';
            $context .= "\n{$contactLabel}: {$property->lead->first_name} {$property->lead->last_name}\n";
            $context .= "Temperature: " . ($property->lead->temperature ?? 'N/A') . "\n";
        }

        return $context;
    }

    protected function buildLeadContext(Lead $lead): string
    {
        $property = $lead->property;

        $context = "Name: {$lead->first_name} {$lead->last_name}\n";
        $context .= "Phone: " . ($lead->phone ?? 'N/A') . "\n";
        $context .= "Status: {$lead->status}\n";
        $context .= "Temperature: {$lead->temperature}\n";
        $context .= "Source: {$lead->lead_source}\n";
        $context .= "DNC: " . ($lead->do_not_contact ? 'Yes' : 'No') . "\n";

        if ($lead->notes) {
            $context .= "Notes: {$lead->notes}\n";
        }

        if ($property) {
            $context .= "\nProperty: {$property->address}, {$property->city}, {$property->state} {$property->zip_code}\n";
            $context .= "Type: " . str_replace('_', ' ', $property->property_type ?? 'unknown') . "\n";
            $context .= "Condition: " . ($property->condition ?? 'N/A') . "\n";

            $isRE = \App\Services\BusinessModeService::isRealEstate();
            if ($isRE) {
                if ($property->list_price) {
                    $context .= "List Price: " . $this->fmt($property->list_price) . "\n";
                }
            } else {
                $context .= "ARV: " . $this->fmt($property->after_repair_value ?? 0) . "\n";

                $markers = is_array($property->distress_markers) ? implode(', ', $property->distress_markers) : '';
                if ($markers) {
                    $context .= "Distress Markers: {$markers}\n";
                }
            }
        }

        return $context;
    }

    protected function buildCallerContext(): string
    {
        $user = auth()->user();

        return "YOUR IDENTITY (the person sending/calling):\n"
            . "Your Name: " . ($user->name ?? 'Agent') . "\n"
            . "Company: " . ($this->tenant->name ?? 'Our Company') . "\n";
    }

    /**
     * Extract a JSON object from an AI response, handling markdown fences and extra text.
     */
    protected function extractJsonObject(string $response, string $requiredKey): ?array
    {
        // Strip markdown code fences
        $cleaned = preg_replace('/```(?:json|JSON)?\s*\n?/i', '', $response);
        $cleaned = trim($cleaned);

        // 1. Try direct json_decode on cleaned response
        $parsed = json_decode($cleaned, true);
        if (is_array($parsed) && isset($parsed[$requiredKey])) {
            return $parsed;
        }

        // 2. Extract outermost { ... } and try decoding
        $start = strpos($cleaned, '{');
        $end = strrpos($cleaned, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $parsed = json_decode(substr($cleaned, $start, $end - $start + 1), true);
            if (is_array($parsed) && isset($parsed[$requiredKey])) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * Extract a JSON array from an AI response.
     */
    protected function extractJsonArray(string $response): ?array
    {
        // Strip markdown code fences
        $cleaned = preg_replace('/```(?:json|JSON)?\s*\n?/i', '', $response);
        $cleaned = trim($cleaned);

        // 1. Try direct decode
        $parsed = json_decode($cleaned, true);
        if (is_array($parsed) && array_is_list($parsed)) {
            return $parsed;
        }

        // 2. Extract outermost [ ... ]
        $start = strpos($cleaned, '[');
        $end = strrpos($cleaned, ']');
        if ($start !== false && $end !== false && $end > $start) {
            $parsed = json_decode(substr($cleaned, $start, $end - $start + 1), true);
            if (is_array($parsed) && array_is_list($parsed)) {
                return $parsed;
            }
        }

        return null;
    }

    protected function fmt(float|int|null $amount): string
    {
        return \App\Helpers\TenantFormatHelper::currency($amount ?? 0);
    }
}
