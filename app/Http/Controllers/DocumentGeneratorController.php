<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Deal;
use App\Models\DocumentTemplate;
use App\Models\GeneratedDocument;
use App\Services\DocumentMergeService;
use Illuminate\Http\Request;

class DocumentGeneratorController extends Controller
{
    protected DocumentMergeService $mergeService;

    public function __construct(DocumentMergeService $mergeService)
    {
        $this->mergeService = $mergeService;
    }

    /**
     * Show form to select template and preview with real deal data.
     */
    public function create(Deal $deal)
    {
        $deal->loadMissing(['lead.property', 'tenant', 'buyerMatches.buyer']);

        $templates = DocumentTemplate::orderBy('name')->get();

        $generatedDocuments = GeneratedDocument::where('deal_id', $deal->id)
            ->with(['template', 'user'])
            ->latest()
            ->get();

        return view('documents.generate', compact('deal', 'templates', 'generatedDocuments'));
    }

    /**
     * AJAX: Preview a template merged with real deal data.
     */
    public function previewWithDeal(Request $request, Deal $deal)
    {
        $request->validate([
            'template_id' => 'required|exists:document_templates,id',
        ]);

        $template = DocumentTemplate::findOrFail($request->template_id);
        $deal->loadMissing(['lead.property', 'tenant', 'buyerMatches.buyer']);

        $rendered = $this->mergeService->merge($template->content, $deal);

        return response()->json([
            'html' => $rendered,
        ]);
    }

    /**
     * Generate a document from template + deal data.
     */
    public function store(Request $request, Deal $deal)
    {
        $request->validate([
            'template_id' => 'required|exists:document_templates,id',
            'name' => 'nullable|string|max:255',
        ]);

        $template = DocumentTemplate::findOrFail($request->template_id);
        $deal->loadMissing(['lead.property', 'tenant', 'buyerMatches.buyer']);

        $rendered = $this->mergeService->merge($template->content, $deal);

        // Strip script tags to prevent stored XSS in rendered documents
        $rendered = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $rendered);
        $rendered = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $rendered);

        $documentName = $request->input('name')
            ?: $template->name . ' - ' . ($deal->lead->full_name ?? $deal->title);

        $document = GeneratedDocument::create([
            'tenant_id' => auth()->user()->tenant_id,
            'deal_id' => $deal->id,
            'template_id' => $template->id,
            'user_id' => auth()->id(),
            'name' => $documentName,
            'content' => $rendered,
        ]);

        AuditLog::log('document.generated', $document, null, [
            'template' => $template->name,
            'deal' => $deal->title,
        ]);

        return redirect()->route('documents.show', $document)
            ->with('success', __('Document generated successfully.'));
    }

    /**
     * Display the generated document.
     */
    public function show(GeneratedDocument $document)
    {
        $document->loadMissing(['deal', 'template', 'user']);

        return view('documents.show', compact('document'));
    }

    /**
     * Print-optimized view for browser print-to-PDF.
     */
    public function print(GeneratedDocument $document)
    {
        $document->loadMissing(['deal.tenant']);

        $companyName = $document->deal?->tenant?->name
            ?? auth()->user()->tenant->name
            ?? '';

        return view('documents.print', [
            'content' => $document->content,
            'documentName' => $document->name,
            'companyName' => $companyName,
        ]);
    }

    /**
     * Delete a generated document.
     */
    public function destroy(GeneratedDocument $document)
    {
        $dealId = $document->deal_id;
        $name = $document->name;

        $document->delete();

        AuditLog::log('document.deleted', null, ['name' => $name]);

        return redirect()->route('documents.generate', $dealId)
            ->with('success', __('Document deleted successfully.'));
    }

    /**
     * Generate an investor packet for a deal with property, ARV, comps, and economics.
     */
    public function investorPacket(Deal $deal)
    {
        $this->authorize('view', $deal);

        $deal->load(['lead.property']);
        $property = $deal->lead?->property;

        // Build investor packet HTML
        $html = '<div class="investor-packet">';
        $html .= '<h1 style="text-align:center; margin-bottom:20px;">Investor Packet</h1>';

        // Property Overview
        $html .= '<h2>Property Overview</h2>';
        $html .= '<table style="width:100%; border-collapse:collapse; margin-bottom:20px;">';
        $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Address</td><td style="padding:8px; border:1px solid #ddd;">' . e($property->address ?? 'N/A') . ', ' . e($property->city ?? '') . ', ' . e($property->state ?? '') . ' ' . e($property->zip_code ?? '') . '</td></tr>';
        $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Property Type</td><td style="padding:8px; border:1px solid #ddd;">' . e(ucwords(str_replace('_', ' ', $property->property_type ?? 'N/A'))) . '</td></tr>';
        $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Beds / Baths</td><td style="padding:8px; border:1px solid #ddd;">' . e($property->bedrooms ?? 'N/A') . ' / ' . e($property->bathrooms ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Square Footage</td><td style="padding:8px; border:1px solid #ddd;">' . ($property->square_footage ? number_format($property->square_footage) : 'N/A') . '</td></tr>';
        $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Year Built</td><td style="padding:8px; border:1px solid #ddd;">' . e($property->year_built ?? 'N/A') . '</td></tr>';
        $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Lot Size</td><td style="padding:8px; border:1px solid #ddd;">' . ($property->lot_size ? \Fmt::area($property->lot_size) : 'N/A') . '</td></tr>';
        $html .= '</table>';

        // ARV Summary
        if ($property) {
            $comps = \App\Models\ComparableSale::where('property_id', $property->id)->get();
            if ($comps->isNotEmpty()) {
                $arvAvg = $comps->avg('sold_price');
                $arvMedian = $comps->median('sold_price');

                $html .= '<h2>ARV Summary</h2>';
                $html .= '<table style="width:100%; border-collapse:collapse; margin-bottom:20px;">';
                $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Average ARV</td><td style="padding:8px; border:1px solid #ddd;">' . \Fmt::currency($arvAvg) . '</td></tr>';
                $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Median ARV</td><td style="padding:8px; border:1px solid #ddd;">' . \Fmt::currency($arvMedian) . '</td></tr>';
                if ($property->after_repair_value) {
                    $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Stated ARV</td><td style="padding:8px; border:1px solid #ddd;">' . \Fmt::currency($property->after_repair_value) . '</td></tr>';
                }
                $html .= '</table>';

                // Comp table
                $html .= '<h2>Comparable Sales</h2>';
                $html .= '<table style="width:100%; border-collapse:collapse; margin-bottom:20px;">';
                $html .= '<tr style="background:#f5f5f5;"><th style="padding:8px; border:1px solid #ddd;">Address</th><th style="padding:8px; border:1px solid #ddd;">Sold Price</th><th style="padding:8px; border:1px solid #ddd;">Sq Ft</th><th style="padding:8px; border:1px solid #ddd;">$/Sq Ft</th><th style="padding:8px; border:1px solid #ddd;">Sold Date</th></tr>';
                foreach ($comps as $comp) {
                    $ppsf = ($comp->sold_price && $comp->square_footage) ? round($comp->sold_price / $comp->square_footage, 2) : 'N/A';
                    $html .= '<tr>';
                    $html .= '<td style="padding:8px; border:1px solid #ddd;">' . e($comp->address ?? 'N/A') . '</td>';
                    $html .= '<td style="padding:8px; border:1px solid #ddd;">' . ($comp->sold_price ? \Fmt::currency($comp->sold_price) : 'N/A') . '</td>';
                    $html .= '<td style="padding:8px; border:1px solid #ddd;">' . ($comp->square_footage ? number_format($comp->square_footage) : 'N/A') . '</td>';
                    $html .= '<td style="padding:8px; border:1px solid #ddd;">' . (is_numeric($ppsf) ? \Fmt::currency($ppsf) : 'N/A') . '</td>';
                    $html .= '<td style="padding:8px; border:1px solid #ddd;">' . ($comp->sold_date ? \Fmt::date($comp->sold_date) : 'N/A') . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</table>';
            }
        }

        // Economics
        $html .= '<h2>Deal Economics</h2>';
        $html .= '<table style="width:100%; border-collapse:collapse; margin-bottom:20px;">';
        $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Contract Price</td><td style="padding:8px; border:1px solid #ddd;">' . ($deal->contract_price ? \Fmt::currency($deal->contract_price) : 'N/A') . '</td></tr>';
        if ($property && $property->repair_estimate) {
            $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Estimated Repairs</td><td style="padding:8px; border:1px solid #ddd;">' . \Fmt::currency($property->repair_estimate) . '</td></tr>';
        }
        if ($deal->assignment_fee) {
            $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Assignment Fee</td><td style="padding:8px; border:1px solid #ddd;">' . \Fmt::currency($deal->assignment_fee) . '</td></tr>';
        }
        if ($property && $property->after_repair_value && $deal->contract_price && $property->repair_estimate) {
            $spread = $property->after_repair_value - $deal->contract_price - $property->repair_estimate - ($deal->assignment_fee ?? 0);
            $html .= '<tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Investor Spread</td><td style="padding:8px; border:1px solid #ddd;">' . \Fmt::currency($spread) . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '</div>';

        $printHtml = app(\App\Services\DocumentMergeService::class)->generatePrintHtml($html, 'Investor Packet — ' . ($property->address ?? $deal->title), auth()->user()->tenant->name);

        return response($printHtml);
    }
}
