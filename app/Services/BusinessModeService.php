<?php

namespace App\Services;

use App\Models\Tenant;

class BusinessModeService
{
    // ── Wholesale pipeline stages (current default) ──

    public const WHOLESALE_STAGES = [
        'prospecting'     => 'Prospecting',
        'contacting'      => 'Contacting',
        'engaging'        => 'Engaging',
        'offer_presented' => 'Offer Presented',
        'under_contract'  => 'Under Contract',
        'dispositions'    => 'Dispositions',
        'assigned'        => 'Assigned',
        'closing'         => 'Closing',
        'closed_won'      => 'Closed Won',
        'closed_lost'     => 'Closed Lost',
    ];

    // ── Real estate agent pipeline stages ──

    public const REALESTATE_STAGES = [
        'lead'              => 'Lead',
        'listing_agreement' => 'Listing Agreement',
        'active_listing'    => 'Active Listing',
        'showing'           => 'Showing',
        'offer_received'    => 'Offer Received',
        'under_contract'    => 'Under Contract',
        'inspection'        => 'Inspection',
        'appraisal'         => 'Appraisal',
        'closing'           => 'Closing',
        'closed_won'        => 'Closed Won',
        'closed_lost'       => 'Closed Lost',
    ];

    // ── Wholesale lead statuses ──

    public const WHOLESALE_LEAD_STATUSES = [
        'new'             => 'New',
        'prospecting'     => 'Prospecting',
        'contacting'      => 'Contacting',
        'contacted'       => 'Contacted',
        'engaging'        => 'Engaging',
        'negotiating'     => 'Negotiating',
        'offer_presented' => 'Offer Presented',
        'under_contract'  => 'Under Contract',
        'assigned'        => 'Assigned',
        'dispositions'    => 'Dispositions',
        'closing'         => 'Closing',
        'closed_won'      => 'Closed Won',
        'closed_lost'     => 'Closed Lost',
        'dead'            => 'Dead',
    ];

    // ── Real estate lead statuses ──

    public const REALESTATE_LEAD_STATUSES = [
        'new'           => 'New',
        'inquiry'       => 'Inquiry',
        'consultation'  => 'Consultation',
        'active_client' => 'Active Client',
        'nurture'       => 'Nurture',
        'closed_won'    => 'Closed Won',
        'closed_lost'   => 'Closed Lost',
        'dead'          => 'Dead',
    ];

    // ── Wholesale distress markers ──

    public const WHOLESALE_DISTRESS_MARKERS = [
        'tax_delinquent'   => 'Tax Delinquent',
        'code_violation'   => 'Code Violation',
        'absentee_owner'   => 'Absentee Owner',
        'probate'          => 'Probate',
        'pre_foreclosure'  => 'Pre-Foreclosure',
        'divorce'          => 'Divorce',
        'out_of_state_owner' => 'Out of State Owner',
        'utility_shutoff'  => 'Utility Shutoff',
        'fire_damage'      => 'Fire Damage',
        'vacant'           => 'Vacant',
    ];

    // ── Wholesale lead sources ──

    public const WHOLESALE_LEAD_SOURCES = [
        'cold_call'            => 'Cold Call',
        'direct_mail'          => 'Direct Mail',
        'website'              => 'Website',
        'referral'             => 'Referral',
        'driving_for_dollars'  => 'Driving for Dollars',
        'ppc'                  => 'PPC / Paid Ads',
        'seo'                  => 'SEO / Organic',
        'social_media'         => 'Social Media',
        'list_import'          => 'List Import',
        'api'                  => 'API / Integration',
        'other'                => 'Other',
    ];

    // ── Real estate lead sources ──

    public const REALESTATE_LEAD_SOURCES = [
        'website'      => 'Website',
        'referral'     => 'Referral',
        'open_house'   => 'Open House',
        'sign_call'    => 'Sign Call',
        'ppc'          => 'PPC / Paid Ads',
        'seo'          => 'SEO / Organic',
        'social_media' => 'Social Media',
        'zillow'       => 'Zillow',
        'realtor_com'  => 'Realtor.com',
        'mls'          => 'MLS',
        'sphere'       => 'Sphere of Influence',
        'past_client'  => 'Past Client',
        'api'          => 'API / Integration',
        'other'        => 'Other',
    ];

    // ── Wholesale roles ──

    public const WHOLESALE_ROLES = [
        'admin', 'acquisition_agent', 'disposition_agent', 'field_scout', 'agent',
    ];

    // ── Real estate roles ──

    public const REALESTATE_ROLES = [
        'admin', 'listing_agent', 'buyers_agent', 'agent',
    ];

    /**
     * Override the mode for CLI/testing contexts where no auth tenant exists.
     * Call with null to clear the override.
     */
    protected static ?string $modeOverride = null;

    public static function setModeOverride(?string $mode): void
    {
        static::$modeOverride = $mode;
    }

    /**
     * Resolve the tenant from the given argument or the auth context.
     */
    protected static function resolveTenant(?Tenant $tenant = null): ?Tenant
    {
        if ($tenant) {
            return $tenant;
        }

        return auth()->check() ? auth()->user()->tenant : null;
    }

    /**
     * Get the business mode string for the tenant.
     */
    public static function mode(?Tenant $tenant = null): string
    {
        if (static::$modeOverride !== null && $tenant === null) {
            return static::$modeOverride;
        }

        $tenant = self::resolveTenant($tenant);
        return $tenant?->business_mode ?? 'wholesale';
    }

    /**
     * Check if the resolved tenant is in wholesale mode.
     */
    public static function isWholesale(?Tenant $tenant = null): bool
    {
        return self::mode($tenant) === 'wholesale';
    }

    /**
     * Check if the resolved tenant is in real estate mode.
     */
    public static function isRealEstate(?Tenant $tenant = null): bool
    {
        return self::mode($tenant) === 'realestate';
    }

    // ─── Pipeline Stages ─────────────────────────────────────

    public static function getStages(?Tenant $tenant = null): array
    {
        return self::isRealEstate($tenant)
            ? self::REALESTATE_STAGES
            : self::WHOLESALE_STAGES;
    }

    public static function getDefaultStage(?Tenant $tenant = null): string
    {
        return self::isRealEstate($tenant) ? 'lead' : 'prospecting';
    }

    public static function getStageLabel(string $stage, ?Tenant $tenant = null): string
    {
        $stages = self::getStages($tenant);
        return __($stages[$stage] ?? ucwords(str_replace('_', ' ', $stage)));
    }

    public static function getStageLabels(?Tenant $tenant = null): array
    {
        return array_map(fn($label) => __($label), self::getStages($tenant));
    }

    // ─── Buyer Match Trigger Stage ───────────────────────────

    public static function getBuyerMatchTriggerStage(?Tenant $tenant = null): string
    {
        return self::isRealEstate($tenant) ? 'active_listing' : 'dispositions';
    }

    // ─── Terminology ─────────────────────────────────────────

    public static function getTerminology(?Tenant $tenant = null): array
    {
        if (self::isRealEstate($tenant)) {
            return [
                'money_label'    => __('Commission'),
                'money_field'    => 'total_commission',
                'buyer_label'    => __('Clients'),
                'buyer_singular' => __('Client'),
                'seller_label'   => __('Sellers'),
                'pipeline_label' => __('Transactions'),
                'deal_label'     => __('Transaction'),
                'fee_label'      => __('in commission'),
            ];
        }

        return [
            'money_label'    => __('Assignment Fee'),
            'money_field'    => 'assignment_fee',
            'buyer_label'    => __('Buyers'),
            'buyer_singular' => __('Buyer'),
            'seller_label'   => __('Sellers'),
            'pipeline_label' => __('Pipeline'),
            'deal_label'     => __('Deal'),
            'fee_label'      => __('in fees'),
        ];
    }

    // ─── Custom Field Defaults ───────────────────────────────

    public static function getCustomFieldDefaults(string $fieldType, ?Tenant $tenant = null): array
    {
        $isRealEstate = self::isRealEstate($tenant);

        return match ($fieldType) {
            'lead_status' => $isRealEstate
                ? self::REALESTATE_LEAD_STATUSES
                : self::WHOLESALE_LEAD_STATUSES,

            'distress_markers' => $isRealEstate
                ? [] // Real estate agents don't use distress markers
                : self::WHOLESALE_DISTRESS_MARKERS,

            'lead_source' => $isRealEstate
                ? self::REALESTATE_LEAD_SOURCES
                : self::WHOLESALE_LEAD_SOURCES,

            // These are shared between modes
            'property_type' => [
                'single_family' => 'Single Family',
                'multi_family'  => 'Multi Family',
                'condo'         => 'Condo',
                'townhouse'     => 'Townhouse',
                'commercial'    => 'Commercial',
                'land'          => 'Land',
                'other'         => 'Other',
            ],

            'property_condition' => [
                'excellent'  => 'Excellent',
                'good'       => 'Good',
                'fair'       => 'Fair',
                'poor'       => 'Poor',
                'distressed' => 'Distressed',
            ],

            'activity_type' => [
                'call'        => 'Call',
                'sms'         => 'SMS',
                'email'       => 'Email',
                'voicemail'   => 'Voicemail',
                'direct_mail' => 'Direct Mail',
                'note'        => 'Note',
                'meeting'     => 'Meeting',
            ],

            'contact_type' => self::isRealEstate($tenant)
                ? [
                    'seller_lead'   => __('Seller Lead'),
                    'buyer_lead'    => __('Buyer Lead'),
                    'active_client' => __('Active Client'),
                    'past_client'   => __('Past Client'),
                ]
                : [],

            default => [],
        };
    }

    // ─── Field Visibility ────────────────────────────────────

    public static function getDealFields(?Tenant $tenant = null): array
    {
        if (self::isRealEstate($tenant)) {
            return [
                'contract_price', 'listing_commission_pct', 'buyer_commission_pct',
                'total_commission', 'mls_number', 'listing_date', 'days_on_market',
                'contract_date', 'closing_date', 'notes',
            ];
        }

        return [
            'contract_price', 'assignment_fee', 'earnest_money',
            'inspection_period_days', 'contract_date', 'due_diligence_end_date',
            'closing_date', 'notes',
        ];
    }

    public static function getPropertyFields(?Tenant $tenant = null): array
    {
        if (self::isRealEstate($tenant)) {
            return [
                'list_price', 'listing_status', 'listed_at', 'sold_at', 'sold_price',
                'asking_price', 'bedrooms', 'bathrooms', 'square_footage', 'lot_size',
                'year_built', 'property_type',
            ];
        }

        return [
            'arv', 'asking_price', 'estimated_repair_cost', 'bedrooms', 'bathrooms',
            'square_footage', 'lot_size', 'year_built', 'property_type', 'condition',
            'distress_markers',
        ];
    }

    // ─── Roles ───────────────────────────────────────────────

    public static function getRoles(?Tenant $tenant = null): array
    {
        return self::isRealEstate($tenant)
            ? self::REALESTATE_ROLES
            : self::WHOLESALE_ROLES;
    }

    /**
     * Agent role names (excludes admin) for the current business mode.
     * Useful for building agent dropdowns, report filters, and team queries.
     */
    public static function getAgentRoleNames(?Tenant $tenant = null): array
    {
        return array_values(array_filter(self::getRoles($tenant), fn($r) => $r !== 'admin'));
    }

    public static function getRoleLabels(?Tenant $tenant = null): array
    {
        if (self::isRealEstate($tenant)) {
            return [
                'admin'        => __('Admin'),
                'listing_agent' => __('Listing Agent'),
                'buyers_agent' => __('Buyers Agent'),
                'agent'        => __('Agent'),
            ];
        }

        return [
            'admin'             => __('Admin'),
            'acquisition_agent' => __('Acquisition Agent'),
            'disposition_agent' => __('Disposition Agent'),
            'field_scout'       => __('Field Scout'),
            'agent'             => __('Agent'),
        ];
    }

    // ─── Dashboard KPI Config ────────────────────────────────

    public static function getDashboardKpiConfig(?Tenant $tenant = null): array
    {
        if (self::isRealEstate($tenant)) {
            return [
                'fee_column'   => 'total_commission',
                'fee_label'    => __('in commission'),
                'deal_label'   => __('Transactions'),
                'closed_label' => __('Closed'),
            ];
        }

        return [
            'fee_column'   => 'assignment_fee',
            'fee_label'    => __('in fees'),
            'deal_label'   => __('Deals'),
            'closed_label' => __('Closed Won'),
        ];
    }

    // ─── AI Context ──────────────────────────────────────────

    public static function getAiSystemContext(?Tenant $tenant = null): string
    {
        if (self::isRealEstate($tenant)) {
            return 'You are an AI assistant for a real estate agent CRM. The user is a real estate agent or broker who represents sellers listing properties and buyers purchasing homes. Focus on listings, showings, offers, commissions, and the traditional real estate sales cycle.';
        }

        return 'You are an AI assistant for a real estate wholesaling CRM. The user is a real estate wholesaler who acquires properties under contract from motivated sellers and assigns or sells those contracts to cash buyers. Focus on deal analysis, ARV, assignment fees, motivated seller signals, and disposition strategies.';
    }

    // ─── Motivation Score Weights ────────────────────────────

    public static function getScoreWeights(?Tenant $tenant = null): array
    {
        if (self::isRealEstate($tenant)) {
            return [
                'activity_engagement' => 40,
                'temperature'         => 25,
                'showing_history'     => 20,
                'source_quality'      => 15,
            ];
        }

        return [
            'list_stacking'    => 50,
            'temperature'      => 15,
            'activity'         => 15,
            'property_distress' => 20,
        ];
    }

    // ─── Custom Field Types Visibility ───────────────────────

    public static function getFieldTypes(?Tenant $tenant = null): array
    {
        $types = [
            'lead_status'       => __('Lead Statuses'),
            'property_type'     => __('Property Types'),
            'property_condition' => __('Property Conditions'),
            'activity_type'     => __('Activity Types'),
            'lead_source'       => __('Lead Sources'),
        ];

        // Only wholesale mode shows distress markers
        if (self::isWholesale($tenant)) {
            $types['distress_markers'] = __('Distress Markers');
        }

        return $types;
    }

    // ─── Showing & Open House Helpers ───────────────────────

    public static function getShowingOutcomes(): array
    {
        return [
            'interested'           => __('Interested'),
            'not_interested'       => __('Not Interested'),
            'made_offer'           => __('Made Offer'),
            'needs_second_showing' => __('Needs Second Showing'),
        ];
    }

    // ─── Offer Management Helpers ───────────────────────────

    public static function getFinancingTypes(): array
    {
        return [
            'cash'         => __('Cash'),
            'conventional' => __('Conventional'),
            'fha'          => __('FHA'),
            'va'           => __('VA'),
            'other'        => __('Other'),
        ];
    }

    // ─── Transaction Checklist Defaults ─────────────────────

    public static function getDefaultChecklistItems(): array
    {
        return [
            ['item_key' => 'earnest_money_deposit', 'label' => __('Earnest Money Deposit'), 'sort_order' => 1],
            ['item_key' => 'inspection',            'label' => __('Home Inspection'),        'sort_order' => 2],
            ['item_key' => 'appraisal',             'label' => __('Appraisal'),              'sort_order' => 3],
            ['item_key' => 'financing',             'label' => __('Financing Contingency'),  'sort_order' => 4],
            ['item_key' => 'title_search',          'label' => __('Title Search & Insurance'), 'sort_order' => 5],
            ['item_key' => 'survey',                'label' => __('Property Survey'),        'sort_order' => 6],
            ['item_key' => 'hoa_docs',              'label' => __('HOA Documents Review'),   'sort_order' => 7],
            ['item_key' => 'home_warranty',         'label' => __('Home Warranty'),          'sort_order' => 8],
            ['item_key' => 'final_walkthrough',     'label' => __('Final Walkthrough'),      'sort_order' => 9],
        ];
    }
}
