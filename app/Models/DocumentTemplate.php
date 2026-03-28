<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    public const TYPES = [
        'loi' => 'Letter of Intent',
        'purchase_agreement' => 'Purchase Agreement',
        'assignment_contract' => 'Assignment Contract',
        'addendum' => 'Addendum',
        'investor_packet' => 'Investor Packet',
        'other' => 'Other',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'content',
        'merge_fields',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'merge_fields' => 'array',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function generatedDocuments()
    {
        return $this->hasMany(GeneratedDocument::class, 'template_id');
    }

    /**
     * Get translated type labels.
     */
    public static function typeLabels(): array
    {
        return array_map(fn($label) => __($label), self::TYPES);
    }

    /**
     * Get translated label for a single type.
     */
    public static function typeLabel(string $type): string
    {
        return __(self::TYPES[$type] ?? ucwords(str_replace('_', ' ', $type)));
    }

    /**
     * All supported merge fields grouped by category.
     */
    public static function getAvailableMergeFields(): array
    {
        $isRE = \App\Services\BusinessModeService::isRealEstate();
        $contactLabel = $isRE ? __('Client') : __('Seller');

        $dealFields = [
            'deal.title' => __('Deal Title'),
            'deal.stage' => __('Deal Stage'),
            'deal.contract_price' => __('Contract Price'),
            'deal.earnest_money' => __('Earnest Money'),
            'deal.contract_date' => __('Contract Date'),
            'deal.closing_date' => __('Closing Date'),
            'deal.notes' => __('Deal Notes'),
        ];

        if ($isRE) {
            $dealFields['deal.total_commission'] = __('Total Commission');
            $dealFields['deal.listing_commission_pct'] = __('Listing Commission %');
            $dealFields['deal.buyer_commission_pct'] = __('Buyer Commission %');
            $dealFields['deal.mls_number'] = __('MLS Number');
        } else {
            $dealFields['deal.assignment_fee'] = __('Assignment Fee');
            $dealFields['deal.estimated_close_date'] = __('Estimated Close Date');
        }

        return [
            __('Deal') => $dealFields,
            $isRE ? __('Lead / Client') : __('Lead / Seller') => [
                'lead.first_name' => __("{$contactLabel} First Name"),
                'lead.last_name' => __("{$contactLabel} Last Name"),
                'lead.full_name' => __("{$contactLabel} Full Name"),
                'lead.phone' => __("{$contactLabel} Phone"),
                'lead.email' => __("{$contactLabel} Email"),
            ],
            __('Property') => [
                'property.address' => __('Property Address'),
                'property.city' => __('City'),
                'property.state' => __('State'),
                'property.zip_code' => __('Zip Code'),
                'property.full_address' => __('Full Address'),
                'property.property_type' => __('Property Type'),
                'property.bedrooms' => __('Bedrooms'),
                'property.bathrooms' => __('Bathrooms'),
                'property.square_footage' => __('Square Footage'),
                'property.year_built' => __('Year Built'),
                'property.lot_size' => __('Lot Size'),
                'property.estimated_value' => __('Estimated Value'),
                ...($isRE ? [
                    'property.list_price' => __('List Price'),
                    'property.listing_status' => __('Listing Status'),
                    'property.listed_at' => __('Listed Date'),
                    'property.sold_at' => __('Sold Date'),
                    'property.sold_price' => __('Sold Price'),
                    'property.mls_number' => __('MLS Number'),
                ] : [
                    'property.after_repair_value' => __('After Repair Value (ARV)'),
                    'property.repair_estimate' => __('Repair Estimate'),
                    'property.our_offer' => __('Our Offer'),
                    'property.distress_markers' => __('Distress Markers'),
                ]),
            ],
            $isRE ? __('Client') : __('Buyer') => [
                'buyer.first_name' => __($isRE ? 'Client First Name' : 'Buyer First Name'),
                'buyer.last_name' => __($isRE ? 'Client Last Name' : 'Buyer Last Name'),
                'buyer.company' => __($isRE ? 'Client Company' : 'Buyer Company'),
                'buyer.phone' => __($isRE ? 'Client Phone' : 'Buyer Phone'),
                'buyer.email' => __($isRE ? 'Client Email' : 'Buyer Email'),
            ],
            __('Company') => [
                'company.name' => __('Company Name'),
                'company.email' => __('Company Email'),
                'company.phone' => __('Company Phone'),
            ],
            __('Dates') => [
                'today' => __('Today (short)'),
                'today_long' => __('Today (long format)'),
            ],
        ];
    }

    /**
     * Get starter templates with realistic content.
     */
    public static function getStarterTemplates(): array
    {
        return [
            'loi' => [
                'name' => __('Letter of Intent'),
                'type' => 'loi',
                'content' => '<div style="font-family: Georgia, serif; max-width: 800px; margin: 0 auto; padding: 40px;">
<div style="text-align: center; margin-bottom: 40px;">
<h1 style="font-size: 24px; margin-bottom: 5px;">{{company.name}}</h1>
<p style="color: #666; margin: 0;">{{company.email}} | {{company.phone}}</p>
</div>

<p style="text-align: right;">{{today_long}}</p>

<h2 style="text-align: center; margin: 30px 0;">LETTER OF INTENT</h2>

<p>Dear {{lead.full_name}},</p>

<p>This Letter of Intent ("LOI") outlines the proposed terms for the purchase of the property located at:</p>

<div style="background: #f8f9fa; padding: 15px; margin: 20px 0; border-left: 4px solid #2563eb;">
<strong>{{property.full_address}}</strong><br>
Property Type: {{property.property_type}} | Sq. Ft.: {{property.square_footage}} | Year Built: {{property.year_built}}
</div>

<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
<tr style="border-bottom: 1px solid #ddd;">
<td style="padding: 10px;"><strong>Buyer:</strong></td>
<td style="padding: 10px;">{{company.name}}</td>
</tr>
<tr style="border-bottom: 1px solid #ddd;">
<td style="padding: 10px;"><strong>Seller:</strong></td>
<td style="padding: 10px;">{{lead.full_name}}</td>
</tr>
<tr style="border-bottom: 1px solid #ddd;">
<td style="padding: 10px;"><strong>Purchase Price:</strong></td>
<td style="padding: 10px;">{{deal.contract_price}}</td>
</tr>
<tr style="border-bottom: 1px solid #ddd;">
<td style="padding: 10px;"><strong>Earnest Money Deposit:</strong></td>
<td style="padding: 10px;">{{deal.earnest_money}}</td>
</tr>
<tr style="border-bottom: 1px solid #ddd;">
<td style="padding: 10px;"><strong>Estimated Closing Date:</strong></td>
<td style="padding: 10px;">{{deal.closing_date}}</td>
</tr>
</table>

<p>This LOI is non-binding and is intended to outline the general terms of the proposed transaction. A formal Purchase Agreement will follow upon mutual agreement.</p>

<p>This offer is valid for a period of five (5) business days from the date above.</p>

<div style="margin-top: 50px;">
<p>Sincerely,</p>
<br><br>
<p>_______________________________<br>{{company.name}}<br>{{company.email}}</p>
</div>

<div style="margin-top: 40px;">
<p><strong>Acknowledged and Agreed:</strong></p>
<br><br>
<p>_______________________________<br>{{lead.full_name}} (Seller)<br>Date: _______________</p>
</div>
</div>',
            ],

            'purchase_agreement' => [
                'name' => __('Purchase Agreement'),
                'type' => 'purchase_agreement',
                'content' => '<div style="font-family: Georgia, serif; max-width: 800px; margin: 0 auto; padding: 40px;">
<div style="text-align: center; margin-bottom: 30px;">
<h1 style="font-size: 24px; margin-bottom: 5px;">REAL ESTATE PURCHASE AGREEMENT</h1>
<p style="color: #666;">{{today_long}}</p>
</div>

<p>This Real Estate Purchase Agreement ("Agreement") is entered into on <strong>{{today_long}}</strong>, by and between:</p>

<div style="display: flex; margin: 20px 0;">
<div style="flex: 1; padding: 15px; background: #f8f9fa; margin-right: 10px;">
<h3 style="margin-top: 0;">SELLER</h3>
<p>{{lead.full_name}}<br>
Phone: {{lead.phone}}<br>
Email: {{lead.email}}</p>
</div>
<div style="flex: 1; padding: 15px; background: #f8f9fa;">
<h3 style="margin-top: 0;">BUYER</h3>
<p>{{company.name}}<br>
Phone: {{company.phone}}<br>
Email: {{company.email}}</p>
</div>
</div>

<h3>1. PROPERTY</h3>
<p>The property that is the subject of this Agreement is located at:</p>
<p style="padding: 10px; background: #f8f9fa; border-left: 4px solid #2563eb;">
<strong>{{property.full_address}}</strong><br>
Type: {{property.property_type}} | Beds: {{property.bedrooms}} | Baths: {{property.bathrooms}} | Sq Ft: {{property.square_footage}} | Lot: {{property.lot_size}} | Year Built: {{property.year_built}}
</p>

<h3>2. PURCHASE PRICE</h3>
<p>The total purchase price shall be <strong>{{deal.contract_price}}</strong> (the "Purchase Price").</p>

<h3>3. EARNEST MONEY DEPOSIT</h3>
<p>Within three (3) business days of the execution of this Agreement, Buyer shall deposit <strong>{{deal.earnest_money}}</strong> as earnest money with the designated title company or escrow agent.</p>

<h3>4. CLOSING</h3>
<p>Closing shall take place on or before <strong>{{deal.closing_date}}</strong> at a mutually agreed-upon title company.</p>

<h3>5. INSPECTION PERIOD</h3>
<p>Buyer shall have a period of fifteen (15) days from the date of this Agreement to conduct inspections of the Property at Buyer\'s expense.</p>

<h3>6. TITLE AND SURVEY</h3>
<p>Seller shall provide Buyer with a commitment for title insurance within ten (10) days of the execution of this Agreement. Seller shall convey marketable title to the Property by general warranty deed.</p>

<h3>7. CONDITION OF PROPERTY</h3>
<p>The Property is being sold in its current "AS-IS" condition. Estimated property value: {{property.estimated_value}}.</p>

<h3>8. DEFAULT</h3>
<p>If Buyer defaults, Seller shall retain the earnest money deposit as liquidated damages. If Seller defaults, Buyer may pursue specific performance or seek a return of the earnest money deposit.</p>

<h3>9. ADDITIONAL TERMS</h3>
<p>{{deal.notes}}</p>

<h3>10. ENTIRE AGREEMENT</h3>
<p>This Agreement constitutes the entire agreement between the parties and supersedes all prior negotiations and agreements.</p>

<div style="margin-top: 50px; display: flex;">
<div style="flex: 1; margin-right: 20px;">
<p><strong>SELLER:</strong></p>
<br><br>
<p>_______________________________<br>{{lead.full_name}}<br>Date: _______________</p>
</div>
<div style="flex: 1;">
<p><strong>BUYER:</strong></p>
<br><br>
<p>_______________________________<br>{{company.name}}<br>Date: _______________</p>
</div>
</div>
</div>',
            ],

            'assignment_contract' => [
                'name' => __('Assignment of Contract'),
                'type' => 'assignment_contract',
                'content' => '<div style="font-family: Georgia, serif; max-width: 800px; margin: 0 auto; padding: 40px;">
<div style="text-align: center; margin-bottom: 30px;">
<h1 style="font-size: 24px; margin-bottom: 5px;">ASSIGNMENT OF REAL ESTATE PURCHASE CONTRACT</h1>
<p style="color: #666;">{{today_long}}</p>
</div>

<p>This Assignment of Contract ("Assignment") is entered into on <strong>{{today_long}}</strong>, by and between:</p>

<div style="display: flex; margin: 20px 0;">
<div style="flex: 1; padding: 15px; background: #f8f9fa; margin-right: 10px;">
<h3 style="margin-top: 0;">ASSIGNOR</h3>
<p>{{company.name}}<br>
Phone: {{company.phone}}<br>
Email: {{company.email}}</p>
</div>
<div style="flex: 1; padding: 15px; background: #f8f9fa;">
<h3 style="margin-top: 0;">ASSIGNEE (End Buyer)</h3>
<p>{{buyer.first_name}} {{buyer.last_name}}<br>
Company: {{buyer.company}}<br>
Phone: {{buyer.phone}}<br>
Email: {{buyer.email}}</p>
</div>
</div>

<h3>1. ORIGINAL CONTRACT</h3>
<p>Assignor holds a Purchase Agreement dated <strong>{{deal.contract_date}}</strong> ("Original Contract") for the property located at:</p>
<p style="padding: 10px; background: #f8f9fa; border-left: 4px solid #2563eb;">
<strong>{{property.full_address}}</strong><br>
Type: {{property.property_type}} | Sq Ft: {{property.square_footage}} | Year Built: {{property.year_built}}
</p>

<p>Between <strong>{{lead.full_name}}</strong> (Seller) and <strong>{{company.name}}</strong> (Buyer/Assignor) at a purchase price of <strong>{{deal.contract_price}}</strong>.</p>

<h3>2. ASSIGNMENT</h3>
<p>Assignor hereby assigns, transfers, and conveys to Assignee all of Assignor\'s right, title, and interest in and to the Original Contract, subject to the terms and conditions set forth herein.</p>

<h3>3. ASSIGNMENT FEE</h3>
<p>Assignee shall pay Assignor an assignment fee of <strong>{{deal.assignment_fee}}</strong> (the "Assignment Fee"), payable at or before closing.</p>

<h3>4. CLOSING DATE</h3>
<p>Closing shall occur on or before <strong>{{deal.closing_date}}</strong>, in accordance with the Original Contract.</p>

<h3>5. EARNEST MONEY</h3>
<p>The earnest money deposit of <strong>{{deal.earnest_money}}</strong> under the Original Contract shall remain on deposit and shall be credited toward the purchase price at closing.</p>

<h3>6. ASSIGNEE OBLIGATIONS</h3>
<p>Assignee agrees to assume all obligations and responsibilities of Assignor under the Original Contract, including but not limited to the obligation to close and to comply with all terms and conditions therein.</p>

<h3>7. INDEMNIFICATION</h3>
<p>Assignee shall indemnify and hold Assignor harmless from any and all claims, damages, or expenses arising from the Assignee\'s failure to perform under the Original Contract after the date of this Assignment.</p>

<h3>8. ADDITIONAL TERMS</h3>
<p>{{deal.notes}}</p>

<div style="margin-top: 50px; display: flex;">
<div style="flex: 1; margin-right: 20px;">
<p><strong>ASSIGNOR:</strong></p>
<br><br>
<p>_______________________________<br>{{company.name}}<br>Date: _______________</p>
</div>
<div style="flex: 1;">
<p><strong>ASSIGNEE:</strong></p>
<br><br>
<p>_______________________________<br>{{buyer.first_name}} {{buyer.last_name}}<br>Company: {{buyer.company}}<br>Date: _______________</p>
</div>
</div>
</div>',
            ],
        ];
    }
}
