<?php

namespace App\Services;

class AddressNormalizationService
{
    /**
     * Map of common street type abbreviations to their full forms.
     * Keys are lowercase canonical abbreviations; values are the full word.
     */
    protected static array $streetTypes = [
        'st'    => 'Street',
        'str'   => 'Street',
        'ave'   => 'Avenue',
        'av'    => 'Avenue',
        'blvd'  => 'Boulevard',
        'boul'  => 'Boulevard',
        'dr'    => 'Drive',
        'drv'   => 'Drive',
        'ln'    => 'Lane',
        'ct'    => 'Court',
        'crt'   => 'Court',
        'rd'    => 'Road',
        'pl'    => 'Place',
        'cir'   => 'Circle',
        'hwy'   => 'Highway',
        'pkwy'  => 'Parkway',
        'pky'   => 'Parkway',
        'ter'   => 'Terrace',
        'terr'  => 'Terrace',
        'trl'   => 'Trail',
        'way'   => 'Way',
    ];

    /**
     * Map of directional abbreviations to their full forms.
     */
    protected static array $directionals = [
        'n'  => 'N',
        'n.' => 'N',
        's'  => 'S',
        's.' => 'S',
        'e'  => 'E',
        'e.' => 'E',
        'w'  => 'W',
        'w.' => 'W',
        'ne' => 'NE',
        'nw' => 'NW',
        'se' => 'SE',
        'sw' => 'SW',
    ];

    /**
     * Unit type abbreviations to normalize.
     */
    protected static array $unitTypes = [
        'apt'   => 'Apt',
        'apt.'  => 'Apt',
        'apartment' => 'Apt',
        'ste'   => 'Ste',
        'ste.'  => 'Ste',
        'suite' => 'Ste',
        'unit'  => 'Unit',
        'fl'    => 'Fl',
        'fl.'   => 'Fl',
        'floor' => 'Fl',
        '#'     => '#',
    ];

    /**
     * Normalize an address string.
     */
    public static function normalize(string $address): string
    {
        // Remove periods after abbreviations (e.g., "St." -> "St", "Ave." -> "Ave")
        $address = preg_replace('/\.(?=\s|$)/', '', $address);

        // Remove extra whitespace
        $address = preg_replace('/\s{2,}/', ' ', trim($address));

        // Remove commas that have no content around them or trailing commas
        $address = preg_replace('/,\s*,/', ',', $address);
        $address = rtrim($address, ', ');

        // Split address into words for processing
        $words = preg_split('/\s+/', $address);
        $normalized = [];

        foreach ($words as $i => $word) {
            $lower = strtolower($word);

            // Check if this is a street type abbreviation or full name
            if (isset(self::$streetTypes[$lower])) {
                $normalized[] = self::$streetTypes[$lower];
                continue;
            }

            // Also match full street type names and ensure proper casing
            $fullTypeLower = array_map('strtolower', self::$streetTypes);
            if (in_array($lower, $fullTypeLower)) {
                // It's already a full street type, just fix casing
                $normalized[] = ucfirst($lower);
                continue;
            }

            // Check directionals (only at start or end of address typically)
            if (isset(self::$directionals[$lower])) {
                $normalized[] = self::$directionals[$lower];
                continue;
            }

            // Check unit types
            if (isset(self::$unitTypes[$lower])) {
                $normalized[] = self::$unitTypes[$lower];
                continue;
            }

            // Default: keep word as-is
            $normalized[] = $word;
        }

        $result = implode(' ', $normalized);

        // Final cleanup: remove any remaining double spaces
        $result = preg_replace('/\s{2,}/', ' ', trim($result));

        return $result;
    }

    /**
     * Normalize a city name (title case, trim, remove extra spaces).
     */
    public static function normalizeCity(string $city): string
    {
        $city = preg_replace('/\.(?=\s|$)/', '', $city);
        $city = preg_replace('/\s{2,}/', ' ', trim($city));
        $city = mb_convert_case($city, MB_CASE_TITLE, 'UTF-8');

        return $city;
    }

    /**
     * Normalize a state abbreviation (uppercase, trim).
     */
    public static function normalizeState(string $state): string
    {
        return strtoupper(trim($state));
    }

    /**
     * Normalize a zip code (trim, handle zip+4 format).
     */
    public static function normalizeZipCode(string $zipCode): string
    {
        $zipCode = trim($zipCode);

        // Remove spaces around dash in zip+4
        $zipCode = preg_replace('/\s*-\s*/', '-', $zipCode);

        return $zipCode;
    }

    /**
     * Normalize all address-related fields in the given data array.
     * Returns the modified array.
     */
    public static function normalizeAll(array $data): array
    {
        if (! empty($data['address'])) {
            $data['address'] = self::normalize($data['address']);
        }

        if (! empty($data['city'])) {
            $data['city'] = self::normalizeCity($data['city']);
        }

        if (! empty($data['state'])) {
            $data['state'] = self::normalizeState($data['state']);
        }

        if (! empty($data['zip_code'])) {
            $data['zip_code'] = self::normalizeZipCode($data['zip_code']);
        }

        return $data;
    }
}
