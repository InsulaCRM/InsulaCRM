<?php

namespace App\Helpers;

use App\Models\Tenant;

class TenantFormatHelper
{
    protected static ?Tenant $cachedTenant = null;

    /**
     * Currency symbol map.
     */
    protected static array $currencySymbols = [
        'USD' => '$',
        'CAD' => 'C$',
        'EUR' => '€',
        'GBP' => '£',
        'AUD' => 'A$',
        'NZD' => 'NZ$',
        'ZAR' => 'R',
        'INR' => '₹',
        'BRL' => 'R$',
        'MXN' => 'MX$',
        'JPY' => '¥',
        'CNY' => '¥',
        'CHF' => 'CHF',
        'SEK' => 'kr',
        'NOK' => 'kr',
        'DKK' => 'kr',
        'PLN' => 'zł',
        'CZK' => 'Kč',
        'HUF' => 'Ft',
        'RON' => 'lei',
        'AED' => 'د.إ',
        'SAR' => '﷼',
        'PHP' => '₱',
        'THB' => '฿',
        'IDR' => 'Rp',
        'MYR' => 'RM',
        'SGD' => 'S$',
        'HKD' => 'HK$',
        'KRW' => '₩',
        'ILS' => '₪',
        'TRY' => '₺',
        'NGN' => '₦',
        'KES' => 'KSh',
        'EGP' => 'E£',
        'COP' => 'COL$',
        'CLP' => 'CLP$',
        'ARS' => 'AR$',
        'PEN' => 'S/',
    ];

    /**
     * Currencies that typically use 0 decimal places.
     */
    protected static array $zeroDecimalCurrencies = ['JPY', 'KRW', 'HUF', 'CLP'];

    /**
     * JS locale map for toLocaleString().
     */
    protected static array $countryLocaleMap = [
        'US' => 'en-US', 'GB' => 'en-GB', 'CA' => 'en-CA', 'AU' => 'en-AU',
        'NZ' => 'en-NZ', 'IE' => 'en-IE', 'DE' => 'de-DE', 'FR' => 'fr-FR',
        'ES' => 'es-ES', 'IT' => 'it-IT', 'PT' => 'pt-PT', 'NL' => 'nl-NL',
        'BE' => 'nl-BE', 'AT' => 'de-AT', 'CH' => 'de-CH', 'SE' => 'sv-SE',
        'NO' => 'nb-NO', 'DK' => 'da-DK', 'FI' => 'fi-FI', 'PL' => 'pl-PL',
        'CZ' => 'cs-CZ', 'HU' => 'hu-HU', 'RO' => 'ro-RO', 'GR' => 'el-GR',
        'BR' => 'pt-BR', 'MX' => 'es-MX', 'AR' => 'es-AR', 'CO' => 'es-CO',
        'CL' => 'es-CL', 'PE' => 'es-PE', 'ZA' => 'en-ZA', 'IN' => 'en-IN',
        'JP' => 'ja-JP', 'CN' => 'zh-CN', 'KR' => 'ko-KR', 'TH' => 'th-TH',
        'PH' => 'en-PH', 'MY' => 'ms-MY', 'SG' => 'en-SG', 'HK' => 'zh-HK',
        'AE' => 'ar-AE', 'SA' => 'ar-SA', 'IL' => 'he-IL', 'TR' => 'tr-TR',
        'EG' => 'ar-EG', 'NG' => 'en-NG', 'KE' => 'en-KE', 'ID' => 'id-ID',
    ];

    protected static function tenant(): ?Tenant
    {
        if (static::$cachedTenant) {
            return static::$cachedTenant;
        }

        if (auth()->check() && auth()->user()->tenant) {
            static::$cachedTenant = auth()->user()->tenant;
            return static::$cachedTenant;
        }

        return null;
    }

    /**
     * Set tenant explicitly (for jobs/commands without auth).
     */
    public static function setTenant(Tenant $tenant): void
    {
        static::$cachedTenant = $tenant;
    }

    /**
     * Format a monetary value with the tenant's currency symbol.
     */
    public static function currency(float|int|null $amount, int $decimals = 2): string
    {
        $tenant = static::tenant();
        $code = $tenant->currency ?? 'USD';
        $symbol = static::$currencySymbols[$code] ?? $code;

        if (in_array($code, static::$zeroDecimalCurrencies)) {
            $decimals = 0;
        }

        return $symbol . number_format($amount ?? 0, $decimals);
    }

    /**
     * Get just the currency symbol.
     */
    public static function currencySymbol(): string
    {
        $tenant = static::tenant();
        $code = $tenant->currency ?? 'USD';
        return static::$currencySymbols[$code] ?? $code;
    }

    /**
     * Get the currency code.
     */
    public static function currencyCode(): string
    {
        $tenant = static::tenant();
        return $tenant->currency ?? 'USD';
    }

    /**
     * Format area (square footage / square meters).
     */
    public static function area(float|int|null $value): string
    {
        if (!$value) return '-';
        $tenant = static::tenant();
        $system = $tenant->measurement_system ?? 'imperial';

        if ($system === 'metric') {
            return number_format($value) . ' m²';
        }
        return number_format($value) . ' sq ft';
    }

    /**
     * Get the area unit label.
     */
    public static function areaUnit(): string
    {
        $tenant = static::tenant();
        $system = $tenant->measurement_system ?? 'imperial';
        return $system === 'metric' ? 'm²' : 'sq ft';
    }

    /**
     * Get the area field label.
     */
    public static function areaLabel(): string
    {
        $tenant = static::tenant();
        $system = $tenant->measurement_system ?? 'imperial';
        return $system === 'metric' ? 'Area (m²)' : 'Square Footage';
    }

    /**
     * Format lot size (acres / hectares).
     */
    public static function lotSize(float|int|null $value): string
    {
        if (!$value) return '-';
        $tenant = static::tenant();
        $system = $tenant->measurement_system ?? 'imperial';

        if ($system === 'metric') {
            return number_format($value, 2) . ' ha';
        }
        return number_format($value, 2) . ' acres';
    }

    /**
     * Get the lot size unit label.
     */
    public static function lotSizeLabel(): string
    {
        $tenant = static::tenant();
        $system = $tenant->measurement_system ?? 'imperial';
        return $system === 'metric' ? 'Lot Size (hectares)' : 'Lot Size (acres)';
    }

    /**
     * Format a date using tenant's date format.
     */
    public static function date($date): string
    {
        if (!$date) return '-';
        $tenant = static::tenant();
        $format = $tenant->date_format ?? 'm/d/Y';

        if ($date instanceof \Carbon\Carbon || $date instanceof \DateTimeInterface) {
            return $date->format($format);
        }

        return \Carbon\Carbon::parse($date)->format($format);
    }

    /**
     * Get the "State/Province/County" label based on country.
     */
    public static function stateLabel(): string
    {
        $tenant = static::tenant();
        $country = $tenant->country ?? 'US';

        return match($country) {
            'US' => 'State',
            'CA' => 'Province',
            'GB', 'IE' => 'County',
            'AU' => 'State',
            'NZ' => 'Region',
            'DE', 'AT' => 'Bundesland',
            'FR' => 'Région',
            'IT' => 'Provincia',
            'ES' => 'Provincia',
            'NL' => 'Provincie',
            'BR' => 'Estado',
            'MX' => 'Estado',
            'IN' => 'State',
            'JP' => 'Prefecture',
            'ZA' => 'Province',
            default => 'State / Province',
        };
    }

    /**
     * Get the "Zip/Postal Code" label based on country.
     */
    public static function postalCodeLabel(): string
    {
        $tenant = static::tenant();
        $country = $tenant->country ?? 'US';

        return match($country) {
            'US' => 'Zip Code',
            'CA' => 'Postal Code',
            'GB', 'IE' => 'Postcode',
            'AU', 'NZ' => 'Postcode',
            'DE', 'AT', 'CH' => 'PLZ',
            'FR', 'BE' => 'Code Postal',
            'IT' => 'CAP',
            'ES' => 'Código Postal',
            'NL' => 'Postcode',
            'BR' => 'CEP',
            'IN' => 'PIN Code',
            'JP' => 'Postal Code',
            default => 'Postal Code',
        };
    }

    /**
     * Get max length for the state/province field.
     */
    public static function stateMaxLength(): int
    {
        $tenant = static::tenant();
        $country = $tenant->country ?? 'US';

        return match($country) {
            'US' => 2,
            'CA' => 2,
            'AU' => 3,
            default => 50,
        };
    }

    /**
     * Get max length for postal code field.
     */
    public static function postalCodeMaxLength(): int
    {
        $tenant = static::tenant();
        $country = $tenant->country ?? 'US';

        return match($country) {
            'US' => 10,
            'CA' => 7,
            'GB' => 8,
            default => 20,
        };
    }

    /**
     * Get the JS locale string for toLocaleString() calls.
     */
    public static function jsLocale(): string
    {
        $tenant = static::tenant();
        $country = $tenant->country ?? 'US';
        return static::$countryLocaleMap[$country] ?? 'en-US';
    }

    /**
     * Whether this tenant is US-based (for TCPA-specific features).
     */
    public static function isUS(): bool
    {
        $tenant = static::tenant();
        return ($tenant->country ?? 'US') === 'US';
    }

    /**
     * Get the compliance law name based on country.
     */
    public static function complianceLawName(): string
    {
        $tenant = static::tenant();
        $country = $tenant->country ?? 'US';

        return match($country) {
            'US' => 'TCPA',
            'CA' => 'CASL',
            'GB', 'IE' => 'PECR/GDPR',
            'AU' => 'Spam Act',
            'NZ' => 'Unsolicited Electronic Messages Act',
            default => 'GDPR',
        };
    }

    /**
     * Get all supported currencies for the settings dropdown.
     */
    public static function currencies(): array
    {
        return [
            'USD' => 'USD ($)',
            'CAD' => 'CAD (C$)',
            'EUR' => 'EUR (€)',
            'GBP' => 'GBP (£)',
            'AUD' => 'AUD (A$)',
            'NZD' => 'NZD (NZ$)',
            'ZAR' => 'ZAR (R)',
            'INR' => 'INR (₹)',
            'BRL' => 'BRL (R$)',
            'MXN' => 'MXN (MX$)',
            'JPY' => 'JPY (¥)',
            'CNY' => 'CNY (¥)',
            'CHF' => 'CHF',
            'SEK' => 'SEK (kr)',
            'NOK' => 'NOK (kr)',
            'DKK' => 'DKK (kr)',
            'PLN' => 'PLN (zł)',
            'CZK' => 'CZK (Kč)',
            'HUF' => 'HUF (Ft)',
            'RON' => 'RON (lei)',
            'AED' => 'AED (د.إ)',
            'SAR' => 'SAR (﷼)',
            'PHP' => 'PHP (₱)',
            'THB' => 'THB (฿)',
            'IDR' => 'IDR (Rp)',
            'MYR' => 'MYR (RM)',
            'SGD' => 'SGD (S$)',
            'HKD' => 'HKD (HK$)',
            'KRW' => 'KRW (₩)',
            'ILS' => 'ILS (₪)',
            'TRY' => 'TRY (₺)',
            'NGN' => 'NGN (₦)',
            'KES' => 'KES (KSh)',
            'EGP' => 'EGP (E£)',
            'COP' => 'COP (COL$)',
            'CLP' => 'CLP',
            'ARS' => 'ARS (AR$)',
            'PEN' => 'PEN (S/)',
        ];
    }

    /**
     * Get all supported countries for the settings dropdown.
     */
    public static function countries(): array
    {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'IE' => 'Ireland',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'PT' => 'Portugal',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'AT' => 'Austria',
            'CH' => 'Switzerland',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'PL' => 'Poland',
            'CZ' => 'Czech Republic',
            'HU' => 'Hungary',
            'RO' => 'Romania',
            'GR' => 'Greece',
            'TR' => 'Turkey',
            'IL' => 'Israel',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'EG' => 'Egypt',
            'ZA' => 'South Africa',
            'NG' => 'Nigeria',
            'KE' => 'Kenya',
            'IN' => 'India',
            'JP' => 'Japan',
            'CN' => 'China',
            'KR' => 'South Korea',
            'TH' => 'Thailand',
            'PH' => 'Philippines',
            'MY' => 'Malaysia',
            'SG' => 'Singapore',
            'ID' => 'Indonesia',
            'HK' => 'Hong Kong',
            'BR' => 'Brazil',
            'MX' => 'Mexico',
            'AR' => 'Argentina',
            'CO' => 'Colombia',
            'CL' => 'Chile',
            'PE' => 'Peru',
        ];
    }

    /**
     * Get common timezones grouped by region.
     */
    public static function timezones(): array
    {
        return [
            'Americas' => [
                'America/New_York' => 'Eastern (New York)',
                'America/Chicago' => 'Central (Chicago)',
                'America/Denver' => 'Mountain (Denver)',
                'America/Los_Angeles' => 'Pacific (Los Angeles)',
                'America/Anchorage' => 'Alaska',
                'Pacific/Honolulu' => 'Hawaii',
                'America/Toronto' => 'Toronto',
                'America/Vancouver' => 'Vancouver',
                'America/Halifax' => 'Atlantic (Halifax)',
                'America/St_Johns' => 'Newfoundland',
                'America/Mexico_City' => 'Mexico City',
                'America/Bogota' => 'Bogotá',
                'America/Lima' => 'Lima',
                'America/Santiago' => 'Santiago',
                'America/Buenos_Aires' => 'Buenos Aires',
                'America/Sao_Paulo' => 'São Paulo',
            ],
            'Europe' => [
                'Europe/London' => 'London (GMT/BST)',
                'Europe/Dublin' => 'Dublin',
                'Europe/Paris' => 'Paris (CET)',
                'Europe/Berlin' => 'Berlin (CET)',
                'Europe/Amsterdam' => 'Amsterdam',
                'Europe/Brussels' => 'Brussels',
                'Europe/Zurich' => 'Zurich',
                'Europe/Vienna' => 'Vienna',
                'Europe/Madrid' => 'Madrid',
                'Europe/Rome' => 'Rome',
                'Europe/Lisbon' => 'Lisbon',
                'Europe/Stockholm' => 'Stockholm',
                'Europe/Oslo' => 'Oslo',
                'Europe/Copenhagen' => 'Copenhagen',
                'Europe/Helsinki' => 'Helsinki',
                'Europe/Warsaw' => 'Warsaw',
                'Europe/Prague' => 'Prague',
                'Europe/Budapest' => 'Budapest',
                'Europe/Bucharest' => 'Bucharest',
                'Europe/Athens' => 'Athens',
                'Europe/Istanbul' => 'Istanbul',
                'Europe/Moscow' => 'Moscow',
            ],
            'Africa & Middle East' => [
                'Africa/Johannesburg' => 'Johannesburg',
                'Africa/Lagos' => 'Lagos',
                'Africa/Nairobi' => 'Nairobi',
                'Africa/Cairo' => 'Cairo',
                'Asia/Dubai' => 'Dubai',
                'Asia/Riyadh' => 'Riyadh',
                'Asia/Jerusalem' => 'Jerusalem',
            ],
            'Asia & Pacific' => [
                'Asia/Kolkata' => 'India (Kolkata)',
                'Asia/Tokyo' => 'Tokyo',
                'Asia/Shanghai' => 'Shanghai',
                'Asia/Hong_Kong' => 'Hong Kong',
                'Asia/Seoul' => 'Seoul',
                'Asia/Singapore' => 'Singapore',
                'Asia/Kuala_Lumpur' => 'Kuala Lumpur',
                'Asia/Bangkok' => 'Bangkok',
                'Asia/Jakarta' => 'Jakarta',
                'Asia/Manila' => 'Manila',
                'Australia/Sydney' => 'Sydney (AEST)',
                'Australia/Melbourne' => 'Melbourne',
                'Australia/Brisbane' => 'Brisbane',
                'Australia/Perth' => 'Perth (AWST)',
                'Australia/Adelaide' => 'Adelaide',
                'Pacific/Auckland' => 'Auckland (NZST)',
            ],
        ];
    }
}
