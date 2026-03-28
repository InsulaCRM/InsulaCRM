<?php

namespace App\Services;

use App\Models\Tenant;

class CustomFieldService
{
    /**
     * Get system default options for a field type, aware of tenant business mode.
     */
    public static function getSystemDefaults(string $fieldType, ?Tenant $tenant = null): array
    {
        return BusinessModeService::getCustomFieldDefaults($fieldType, $tenant);
    }

    /**
     * Activity types that are considered outreach (blocked by DNC).
     */
    public static array $outreachActivityTypes = [
        'call', 'sms', 'email', 'voicemail', 'direct_mail',
    ];

    /**
     * Get all options for a field type (defaults + tenant custom).
     */
    public static function getOptions(string $fieldType, ?Tenant $tenant = null): array
    {
        if (!$tenant) {
            $tenant = auth()->check() ? auth()->user()->tenant : null;
        }

        $defaults = self::getSystemDefaults($fieldType, $tenant);

        if (!$tenant) {
            return array_map(fn($label) => __($label), $defaults);
        }

        $customOptions = $tenant->custom_options[$fieldType] ?? [];

        // Also merge legacy custom_lead_sources for backward compat
        if ($fieldType === 'lead_source' && !empty($tenant->custom_lead_sources)) {
            foreach ($tenant->custom_lead_sources as $source) {
                $customOptions[$source['slug']] = $source['name'];
            }
        }

        return array_map(fn($label) => __($label), array_merge($defaults, $customOptions));
    }

    /**
     * Get only the system defaults for a field type.
     */
    public static function getDefaults(string $fieldType, ?Tenant $tenant = null): array
    {
        return array_map(fn($label) => __($label), self::getSystemDefaults($fieldType, $tenant));
    }

    /**
     * Get only the tenant's custom options for a field type.
     */
    public static function getCustomOptions(string $fieldType, ?Tenant $tenant = null): array
    {
        if (!$tenant) {
            $tenant = auth()->check() ? auth()->user()->tenant : null;
        }

        if (!$tenant) {
            return [];
        }

        $custom = $tenant->custom_options[$fieldType] ?? [];

        // Legacy compat for lead sources
        if ($fieldType === 'lead_source' && !empty($tenant->custom_lead_sources)) {
            foreach ($tenant->custom_lead_sources as $source) {
                $custom[$source['slug']] = $source['name'];
            }
        }

        return $custom;
    }

    /**
     * Add a custom option for a field type.
     */
    public static function addOption(string $fieldType, string $name, Tenant $tenant): array
    {
        $slug = str_replace(' ', '_', strtolower(trim($name)));
        $defaults = self::getSystemDefaults($fieldType, $tenant);
        $customOptions = $tenant->custom_options ?? [];
        $existing = $customOptions[$fieldType] ?? [];

        if (isset($defaults[$slug]) || isset($existing[$slug])) {
            return ['success' => false, 'message' => 'This option already exists.'];
        }

        $existing[$slug] = trim($name);
        $customOptions[$fieldType] = $existing;
        $tenant->update(['custom_options' => $customOptions]);

        return ['success' => true, 'slug' => $slug, 'name' => trim($name)];
    }

    /**
     * Remove a custom option for a field type.
     */
    public static function removeOption(string $fieldType, string $slug, Tenant $tenant): bool
    {
        $defaults = self::getSystemDefaults($fieldType, $tenant);

        // Cannot remove system defaults
        if (isset($defaults[$slug])) {
            return false;
        }

        $customOptions = $tenant->custom_options ?? [];
        $existing = $customOptions[$fieldType] ?? [];

        unset($existing[$slug]);
        $customOptions[$fieldType] = $existing;
        $tenant->update(['custom_options' => $customOptions]);

        return true;
    }

    /**
     * Get all valid slugs for a field type (for validation rules).
     */
    public static function getValidSlugs(string $fieldType, ?Tenant $tenant = null): array
    {
        return array_keys(self::getOptions($fieldType, $tenant));
    }

    /**
     * Get the supported field types and their labels.
     */
    public static function getFieldTypes(?Tenant $tenant = null): array
    {
        return BusinessModeService::getFieldTypes($tenant);
    }
}
