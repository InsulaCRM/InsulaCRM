<?php

namespace App\Services;

use App\Models\Deal;
use App\Services\BusinessModeService;
use Carbon\Carbon;
use Fmt;

class DocumentMergeService
{
    /**
     * Currency fields that should be formatted with Fmt::currency().
     */
    protected array $currencyFields = [
        'deal.contract_price',
        'deal.assignment_fee',
        'deal.earnest_money',
        'deal.total_commission',
        'property.estimated_value',
        'property.list_price',
        'property.sold_price',
        'property.after_repair_value',
        'property.repair_estimate',
        'property.our_offer',
    ];

    /**
     * Date fields that should be formatted for human readability.
     */
    protected array $dateFields = [
        'deal.estimated_close_date',
        'deal.contract_date',
        'deal.closing_date',
        'deal.listing_date',
        'property.listed_at',
        'property.sold_at',
    ];

    /**
     * Replace all {{merge.field}} placeholders with actual values from the deal.
     */
    public function merge(string $template, Deal $deal): string
    {
        // Eager load relationships
        $deal->loadMissing(['lead.property', 'tenant']);

        // Resolve the best buyer match for the deal
        $buyerMatch = $deal->buyerMatches()->with('buyer')->orderByDesc('match_score')->first();
        $buyer = $buyerMatch?->buyer;

        $tenant = $deal->tenant ?? auth()->user()->tenant;
        $lead = $deal->lead;
        $property = $lead?->property;

        // Build the merge data map
        $data = $this->buildMergeData($deal, $lead, $property, $buyer, $tenant);

        // Replace all {{field}} placeholders
        return preg_replace_callback('/\{\{([a-z_.]+)\}\}/', function ($matches) use ($data) {
            $field = $matches[1];
            return $data[$field] ?? '';
        }, $template);
    }

    /**
     * Build the complete merge data map.
     */
    protected function buildMergeData(Deal $deal, $lead, $property, $buyer, $tenant): array
    {
        $data = [];

        // Deal fields (shared)
        $data['deal.title'] = $deal->title ?? '';
        $data['deal.stage'] = Deal::stageLabel($deal->stage);
        $data['deal.contract_price'] = $this->formatCurrency($deal->contract_price);
        $data['deal.earnest_money'] = $this->formatCurrency($deal->earnest_money);
        $data['deal.estimated_close_date'] = $this->formatDate($deal->closing_date);
        $data['deal.contract_date'] = $this->formatDate($deal->contract_date);
        $data['deal.closing_date'] = $this->formatDate($deal->closing_date);
        $data['deal.notes'] = $deal->notes ?? '';

        // Deal fields (RE)
        $data['deal.total_commission'] = $this->formatCurrency($deal->total_commission);
        $data['deal.listing_commission_pct'] = $deal->listing_commission_pct !== null ? $deal->listing_commission_pct . '%' : '';
        $data['deal.buyer_commission_pct'] = $deal->buyer_commission_pct !== null ? $deal->buyer_commission_pct . '%' : '';
        $data['deal.mls_number'] = $deal->mls_number ?? '';
        $data['deal.listing_date'] = $this->formatDate($deal->listing_date);

        // Deal fields (wholesale)
        $data['deal.assignment_fee'] = $this->formatCurrency($deal->assignment_fee);

        // Lead / Seller fields
        $data['lead.first_name'] = $lead->first_name ?? '';
        $data['lead.last_name'] = $lead->last_name ?? '';
        $data['lead.full_name'] = $lead?->full_name ?? '';
        $data['lead.phone'] = $lead->phone ?? '';
        $data['lead.email'] = $lead->email ?? '';

        // Property fields
        $data['property.address'] = $property->address ?? '';
        $data['property.city'] = $property->city ?? '';
        $data['property.state'] = $property->state ?? '';
        $data['property.zip_code'] = $property->zip_code ?? '';
        $data['property.full_address'] = $property?->full_address ?? '';
        $data['property.property_type'] = $property ? __(ucwords(str_replace('_', ' ', $property->property_type ?? ''))) : '';
        $data['property.bedrooms'] = (string) ($property->bedrooms ?? '');
        $data['property.bathrooms'] = (string) ($property->bathrooms ?? '');
        $data['property.square_footage'] = $property->square_footage ? number_format($property->square_footage) : '';
        $data['property.year_built'] = (string) ($property->year_built ?? '');
        $data['property.lot_size'] = $property->lot_size ? Fmt::area($property->lot_size) : '';
        $data['property.estimated_value'] = $this->formatCurrency($property->estimated_value ?? null);

        // Property fields (RE)
        $data['property.list_price'] = $this->formatCurrency($property->list_price ?? null);
        $data['property.listing_status'] = $property ? __(ucwords(str_replace('_', ' ', $property->listing_status ?? ''))) : '';
        $data['property.listed_at'] = $this->formatDate($property->listed_at ?? null);
        $data['property.sold_at'] = $this->formatDate($property->sold_at ?? null);
        $data['property.sold_price'] = $this->formatCurrency($property->sold_price ?? null);
        $data['property.mls_number'] = $property->mls_number ?? '';

        // Property fields (wholesale)
        $data['property.after_repair_value'] = $this->formatCurrency($property->after_repair_value ?? null);
        $data['property.repair_estimate'] = $this->formatCurrency($property->repair_estimate ?? null);
        $data['property.our_offer'] = $this->formatCurrency($property->our_offer ?? null);
        $data['property.distress_markers'] = $property && $property->distress_markers
            ? implode(', ', array_map(fn($m) => __(ucwords(str_replace('_', ' ', $m))), $property->distress_markers))
            : '';

        // Buyer fields
        $data['buyer.first_name'] = $buyer->first_name ?? '';
        $data['buyer.last_name'] = $buyer->last_name ?? '';
        $data['buyer.company'] = $buyer->company ?? '';
        $data['buyer.phone'] = $buyer->phone ?? '';
        $data['buyer.email'] = $buyer->email ?? '';

        // Company / Tenant fields
        $data['company.name'] = $tenant->name ?? '';
        $data['company.email'] = $tenant->email ?? '';
        $data['company.phone'] = $tenant->phone ?? '';

        // Date fields
        $data['today'] = now()->format('m/d/Y');
        $data['today_long'] = now()->format('F j, Y');

        return $data;
    }

    /**
     * Format a monetary value using Fmt::currency().
     */
    protected function formatCurrency($value): string
    {
        if ($value === null || $value === '' || $value === 0) {
            return '';
        }

        return Fmt::currency($value);
    }

    /**
     * Format a date value to human-readable string.
     */
    protected function formatDate($date): string
    {
        if (!$date) {
            return '';
        }

        if ($date instanceof Carbon) {
            return Fmt::date($date);
        }

        try {
            return Fmt::date(Carbon::parse($date));
        } catch (\Exception $e) {
            return (string) $date;
        }
    }

    /**
     * Generate a print-ready HTML page from rendered content.
     * Users print to PDF via the browser's print dialog.
     */
    public function generatePrintHtml(string $content, string $documentName = '', string $companyName = ''): string
    {
        return view('documents.print', [
            'content' => $content,
            'documentName' => $documentName,
            'companyName' => $companyName,
        ])->render();
    }

    /**
     * Preview a template with sample data (no real deal needed).
     */
    public function previewWithSampleData(string $template): string
    {
        $isRE = BusinessModeService::isRealEstate();

        $sampleData = [
            // Deal (shared)
            'deal.title' => 'Sample Deal - 123 Main St',
            'deal.stage' => __('Under Contract'),
            'deal.contract_price' => Fmt::currency(185000),
            'deal.earnest_money' => Fmt::currency(2500),
            'deal.estimated_close_date' => now()->addDays(30)->format('F j, Y'),
            'deal.contract_date' => now()->format('F j, Y'),
            'deal.closing_date' => now()->addDays(30)->format('F j, Y'),
            'deal.notes' => 'Subject to clear title and satisfactory inspection.',

            // Deal (RE)
            'deal.total_commission' => Fmt::currency(11100),
            'deal.listing_commission_pct' => '3%',
            'deal.buyer_commission_pct' => '3%',
            'deal.mls_number' => 'MLS-2026-12345',
            'deal.listing_date' => now()->subDays(14)->format('F j, Y'),

            // Deal (wholesale)
            'deal.assignment_fee' => Fmt::currency(15000),

            // Lead
            'lead.first_name' => 'John',
            'lead.last_name' => 'Smith',
            'lead.full_name' => 'John Smith',
            'lead.phone' => '(555) 123-4567',
            'lead.email' => 'john.smith@example.com',

            // Property (shared)
            'property.address' => '123 Main Street',
            'property.city' => 'Orlando',
            'property.state' => 'FL',
            'property.zip_code' => '32801',
            'property.full_address' => '123 Main Street, Orlando, FL 32801',
            'property.property_type' => __('Single Family'),
            'property.bedrooms' => '3',
            'property.bathrooms' => '2',
            'property.square_footage' => '1,850',
            'property.year_built' => '1995',
            'property.lot_size' => '0.25 acres',
            'property.estimated_value' => Fmt::currency(225000),

            // Property (RE)
            'property.list_price' => Fmt::currency(235000),
            'property.listing_status' => __('Active'),
            'property.listed_at' => now()->subDays(14)->format('F j, Y'),
            'property.sold_at' => '',
            'property.sold_price' => '',
            'property.mls_number' => 'MLS-2026-12345',

            // Property (wholesale)
            'property.after_repair_value' => Fmt::currency(280000),
            'property.repair_estimate' => Fmt::currency(45000),
            'property.our_offer' => Fmt::currency(150000),
            'property.distress_markers' => __('Tax Delinquent') . ', ' . __('Absentee Owner'),

            // Buyer / Client
            'buyer.first_name' => 'Jane',
            'buyer.last_name' => 'Doe',
            'buyer.company' => $isRE ? 'Doe Family' : 'Doe Investments LLC',
            'buyer.phone' => '(555) 987-6543',
            'buyer.email' => 'jane@doeinvestments.com',

            // Company
            'company.name' => auth()->user()->tenant->name ?? 'Your Company',
            'company.email' => auth()->user()->tenant->email ?? 'info@company.com',
            'company.phone' => auth()->user()->tenant->phone ?? '(555) 000-0000',

            // Dates
            'today' => now()->format('m/d/Y'),
            'today_long' => now()->format('F j, Y'),
        ];

        return preg_replace_callback('/\{\{([a-z_.]+)\}\}/', function ($matches) use ($sampleData) {
            $field = $matches[1];
            return $sampleData[$field] ?? '{{' . $field . '}}';
        }, $template);
    }
}
