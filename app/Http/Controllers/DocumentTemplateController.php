<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DocumentTemplate;
use App\Services\DocumentMergeService;
use Illuminate\Http\Request;

class DocumentTemplateController extends Controller
{
    public function index()
    {
        $templates = DocumentTemplate::withCount('generatedDocuments')
            ->orderBy('name')
            ->get();

        return view('documents.templates.index', compact('templates'));
    }

    public function create()
    {
        $types = DocumentTemplate::typeLabels();
        $mergeFields = DocumentTemplate::getAvailableMergeFields();
        $starterTemplates = DocumentTemplate::getStarterTemplates();

        return view('documents.templates.create', compact('types', 'mergeFields', 'starterTemplates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(DocumentTemplate::TYPES)),
            'content' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]);

        $tenant = auth()->user()->tenant;

        // If marking as default, unmark other defaults of the same type
        if (!empty($validated['is_default'])) {
            DocumentTemplate::where('tenant_id', $tenant->id)
                ->where('type', $validated['type'])
                ->update(['is_default' => false]);
        }

        // Extract used merge fields from the content
        preg_match_all('/\{\{([a-z_.]+)\}\}/', $validated['content'], $matches);
        $usedMergeFields = array_unique($matches[1] ?? []);

        $template = DocumentTemplate::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'content' => $validated['content'],
            'merge_fields' => array_values($usedMergeFields),
            'is_default' => !empty($validated['is_default']),
        ]);

        AuditLog::log('document_template.created', $template, null, ['name' => $template->name]);

        return redirect()->route('document-templates.index')
            ->with('success', __('Template created successfully.'));
    }

    public function edit(DocumentTemplate $documentTemplate)
    {
        $types = DocumentTemplate::typeLabels();
        $mergeFields = DocumentTemplate::getAvailableMergeFields();
        $starterTemplates = DocumentTemplate::getStarterTemplates();

        return view('documents.templates.edit', [
            'template' => $documentTemplate,
            'types' => $types,
            'mergeFields' => $mergeFields,
            'starterTemplates' => $starterTemplates,
        ]);
    }

    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(DocumentTemplate::TYPES)),
            'content' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]);

        $tenant = auth()->user()->tenant;

        // If marking as default, unmark other defaults of the same type
        if (!empty($validated['is_default'])) {
            DocumentTemplate::where('tenant_id', $tenant->id)
                ->where('type', $validated['type'])
                ->where('id', '!=', $documentTemplate->id)
                ->update(['is_default' => false]);
        }

        // Extract used merge fields from the content
        preg_match_all('/\{\{([a-z_.]+)\}\}/', $validated['content'], $matches);
        $usedMergeFields = array_unique($matches[1] ?? []);

        $documentTemplate->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'content' => $validated['content'],
            'merge_fields' => array_values($usedMergeFields),
            'is_default' => !empty($validated['is_default']),
        ]);

        AuditLog::log('document_template.updated', $documentTemplate, null, ['name' => $documentTemplate->name]);

        return redirect()->route('document-templates.index')
            ->with('success', __('Template updated successfully.'));
    }

    public function destroy(DocumentTemplate $documentTemplate)
    {
        // Nullify template references in generated documents
        $documentTemplate->generatedDocuments()->update(['template_id' => null]);

        $name = $documentTemplate->name;
        $documentTemplate->delete();

        AuditLog::log('document_template.deleted', null, ['name' => $name]);

        return redirect()->route('document-templates.index')
            ->with('success', __('Template deleted successfully.'));
    }

    /**
     * AJAX preview of a template with sample data (existing template).
     */
    public function preview(Request $request, DocumentTemplate $documentTemplate)
    {
        $mergeService = new DocumentMergeService();
        $content = $request->input('content', $documentTemplate->content);
        $rendered = $mergeService->previewWithSampleData($content);

        return response()->json([
            'html' => $rendered,
        ]);
    }

    /**
     * AJAX preview of raw content with sample data (no template ID needed, used on create form).
     */
    public function previewContent(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $mergeService = new DocumentMergeService();
        $rendered = $mergeService->previewWithSampleData($request->input('content'));

        return response()->json([
            'html' => $rendered,
        ]);
    }
}
