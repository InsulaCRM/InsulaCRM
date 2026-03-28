<?php

namespace App\Http\Controllers;

use App\Services\BusinessModeService;

class ApiDocsController extends Controller
{
    public function index()
    {
        $endpoints = $this->getEndpoints();

        return view('api-docs.index', compact('endpoints'));
    }

    public function json()
    {
        return response()->json($this->getOpenApiSpec(), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    protected function getEndpoints(): array
    {
        $isRealEstate = BusinessModeService::isRealEstate();

        return [
            [
                'group' => 'Leads',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/api/v1/leads', 'description' => 'List leads for the current tenant', 'params' => 'page, per_page, status, source, since'],
                    ['method' => 'POST', 'path' => '/api/v1/leads', 'description' => 'Create a lead with optional property and tracking fields', 'params' => 'first_name, last_name, phone, email, source, notes, property_address, property_city, property_state, property_zip, property_type, utm_source, utm_medium, utm_campaign, external_id'],
                    ['method' => 'GET', 'path' => '/api/v1/leads/{id}', 'description' => 'Get a single lead with its property', 'params' => '-'],
                    ['method' => 'PUT', 'path' => '/api/v1/leads/{id}', 'description' => 'Update lead fields', 'params' => 'first_name, last_name, phone, email, lead_source, status, temperature, do_not_contact, notes'],
                ],
            ],
            [
                'group' => 'Deals',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/api/v1/deals', 'description' => 'List deals for the current tenant', 'params' => 'page, per_page, stage, agent_id, since'],
                    ['method' => 'POST', 'path' => '/api/v1/deals', 'description' => 'Create a deal linked to an existing lead', 'params' => $isRealEstate
                        ? 'lead_id, agent_id, title, stage, contract_price, earnest_money, contract_date, closing_date, notes, listing_commission_pct, buyer_commission_pct, total_commission, brokerage_split_pct, mls_number, listing_date, days_on_market'
                        : 'lead_id, agent_id, title, stage, contract_price, assignment_fee, earnest_money, inspection_period_days, contract_date, closing_date, notes'],
                    ['method' => 'GET', 'path' => '/api/v1/deals/stages', 'description' => 'List available deal stage slugs and labels', 'params' => '-'],
                    ['method' => 'GET', 'path' => '/api/v1/deals/{id}', 'description' => 'Get a single deal with related records', 'params' => '-'],
                    ['method' => 'PUT', 'path' => '/api/v1/deals/{id}', 'description' => 'Update deal fields and optionally change stage', 'params' => $isRealEstate
                        ? 'stage, contract_price, earnest_money, contract_date, closing_date, notes, listing_commission_pct, buyer_commission_pct, total_commission, brokerage_split_pct, mls_number, listing_date, days_on_market'
                        : 'stage, contract_price, assignment_fee, earnest_money, inspection_period_days, contract_date, closing_date, notes'],
                ],
            ],
            [
                'group' => $isRealEstate ? 'Clients' : 'Buyers',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/api/v1/buyers', 'description' => $isRealEstate ? 'List clients for the current tenant' : 'List buyers for the current tenant', 'params' => 'page, per_page, search, state'],
                    ['method' => 'POST', 'path' => '/api/v1/buyers', 'description' => $isRealEstate ? 'Create a client with preferences' : 'Create a buyer and optional buying preferences', 'params' => $isRealEstate ? 'first_name, last_name, company, phone, email, max_purchase_price, preferred_property_types[], preferred_states[], preferred_zip_codes[], notes' : 'first_name, last_name, company, phone, email, max_purchase_price, preferred_property_types[], preferred_states[], preferred_zip_codes[], asset_classes[], notes'],
                    ['method' => 'GET', 'path' => '/api/v1/buyers/{id}', 'description' => $isRealEstate ? 'Get a single client with deal matches' : 'Get a single buyer with deal matches', 'params' => '-'],
                    ['method' => 'PUT', 'path' => '/api/v1/buyers/{id}', 'description' => $isRealEstate ? 'Update client details and preferences' : 'Update buyer details and preferences', 'params' => $isRealEstate ? 'first_name, last_name, company, phone, email, max_purchase_price, preferred_property_types[], preferred_states[], preferred_zip_codes[], notes' : 'first_name, last_name, company, phone, email, max_purchase_price, preferred_property_types[], preferred_states[], preferred_zip_codes[], asset_classes[], notes'],
                ],
            ],
            [
                'group' => 'Properties',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/api/v1/properties', 'description' => 'List properties for the current tenant', 'params' => 'page, per_page, search, property_type, state, zip_code, since'],
                    ['method' => 'POST', 'path' => '/api/v1/properties', 'description' => $isRealEstate ? 'Create a property record' : 'Create a property with auto MAO calculation', 'params' => $isRealEstate
                        ? 'lead_id, address, city, state, zip_code, property_type, bedrooms, bathrooms, square_footage, year_built, lot_size, estimated_value, asking_price, list_price, condition, notes, listing_status, listed_at, sold_at, sold_price, mls_number'
                        : 'lead_id, address, city, state, zip_code, property_type, bedrooms, bathrooms, square_footage, year_built, lot_size, estimated_value, repair_estimate, after_repair_value, asking_price, our_offer, condition, distress_markers[], notes'],
                    ['method' => 'GET', 'path' => '/api/v1/properties/{id}', 'description' => 'Get a single property with its linked lead', 'params' => '-'],
                    ['method' => 'PUT', 'path' => '/api/v1/properties/{id}', 'description' => $isRealEstate ? 'Update property details' : 'Update property (recalculates MAO)', 'params' => $isRealEstate
                        ? 'address, city, state, zip_code, property_type, bedrooms, bathrooms, square_footage, year_built, lot_size, estimated_value, asking_price, list_price, condition, notes, listing_status, listed_at, sold_at, sold_price, mls_number'
                        : 'address, city, state, zip_code, property_type, bedrooms, bathrooms, square_footage, year_built, lot_size, estimated_value, repair_estimate, after_repair_value, asking_price, our_offer, condition, distress_markers[], notes'],
                ],
            ],
            [
                'group' => 'Activities',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/api/v1/activities', 'description' => 'List activities for the current tenant', 'params' => 'page, per_page, lead_id, type, agent_id, since'],
                    ['method' => 'POST', 'path' => '/api/v1/activities', 'description' => 'Create a lead activity entry', 'params' => 'lead_id, agent_id, type, subject, body, logged_at'],
                ],
            ],
            [
                'group' => 'Stats',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/api/v1/stats', 'description' => 'Get tenant KPI, totals, pipeline, sources, and monthly trend data', 'params' => '-'],
                ],
            ],
        ];
    }

    protected function getOpenApiSpec(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name') . ' API',
                'version' => '1.0.0',
                'description' => 'Tenant-scoped REST API for managing leads, deals, buyers, properties, activities, and dashboard stats. Authenticate with the X-API-Key header.',
            ],
            'servers' => [
                ['url' => url('/api/v1'), 'description' => 'Current server'],
            ],
            'security' => [
                ['ApiKeyHeader' => []],
            ],
            'components' => [
                'securitySchemes' => [
                    'ApiKeyHeader' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key',
                        'description' => 'Tenant API key. Header authentication only.',
                    ],
                ],
            ],
            'paths' => $this->buildPaths(),
        ];
    }

    protected function buildPaths(): array
    {
        $paths = [];
        foreach ($this->getEndpoints() as $group) {
            foreach ($group['endpoints'] as $ep) {
                $path = str_replace('/api/v1', '', $ep['path']);
                $method = strtolower($ep['method']);
                $paths[$path][$method] = [
                    'summary' => $ep['description'],
                    'tags' => [$group['group']],
                    'responses' => [
                        '200' => ['description' => 'Success'],
                        '401' => ['description' => 'Unauthorized'],
                        '422' => ['description' => 'Validation error'],
                    ],
                ];
            }
        }
        return $paths;
    }
}
