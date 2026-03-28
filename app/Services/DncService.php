<?php

namespace App\Services;

use App\Models\DoNotContact;
use App\Models\Lead;
use Carbon\Carbon;

class DncService
{
    /**
     * Check if a lead is on the Do Not Contact list.
     */
    public function check(Lead $lead): bool
    {
        if ($lead->do_not_contact) {
            return true;
        }

        return DoNotContact::where('tenant_id', $lead->tenant_id)
            ->where(function ($query) use ($lead) {
                if ($lead->phone) {
                    $query->orWhere('phone', $lead->phone);
                }
                if ($lead->email) {
                    $query->orWhere('email', $lead->email);
                }
            })
            ->exists();
    }

    /**
     * Determine if a lead can be contacted, with reasoning.
     */
    public function canContact(Lead $lead): array
    {
        if ($this->check($lead)) {
            return [
                'allowed' => false,
                'reason' => 'Lead is on the Do Not Contact list.',
            ];
        }

        $timezoneCheck = $this->checkTimezoneRestriction($lead);

        if (! $timezoneCheck['allowed']) {
            return [
                'allowed' => false,
                'reason' => "Outside allowed contact hours. Lead local time is {$timezoneCheck['local_time']}.",
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'Lead is eligible for contact.',
        ];
    }

    /**
     * Check if the current time in the lead's timezone is within the allowed
     * contact window of 8 AM to 9 PM.
     */
    public function checkTimezoneRestriction(Lead $lead): array
    {
        $timezone = $lead->timezone ?? 'America/New_York';

        try {
            $localTime = Carbon::now($timezone);
        } catch (\Throwable $e) {
            // Try prefixing common continent if timezone is a city name (e.g. "Dublin" → "Europe/Dublin")
            $resolved = $this->resolveTimezone($timezone);
            if ($resolved) {
                $localTime = Carbon::now($resolved);
            } else {
                // Unknown timezone — allow contact rather than blocking
                return ['allowed' => true, 'local_time' => 'unknown'];
            }
        }

        $allowed = $localTime->hour >= 8 && $localTime->hour < 21;

        return [
            'allowed' => $allowed,
            'local_time' => $localTime->format('h:i A T'),
        ];
    }

    /**
     * Attempt to resolve a short timezone name to a full IANA identifier.
     */
    private function resolveTimezone(string $timezone): ?string
    {
        $prefixes = ['America', 'Europe', 'Asia', 'Africa', 'Australia', 'Pacific', 'Atlantic', 'Indian'];

        foreach ($prefixes as $prefix) {
            $candidate = "{$prefix}/{$timezone}";
            if (in_array($candidate, timezone_identifiers_list())) {
                return $candidate;
            }
        }

        // Try with underscores for multi-word cities (e.g. "New York" → "America/New_York")
        $underscored = str_replace(' ', '_', $timezone);
        if ($underscored !== $timezone) {
            foreach ($prefixes as $prefix) {
                $candidate = "{$prefix}/{$underscored}";
                if (in_array($candidate, timezone_identifiers_list())) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
