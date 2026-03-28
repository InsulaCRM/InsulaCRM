<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCsvImport;
use App\Models\ImportLog;
use App\Models\Lead;
use App\Models\LeadList;
use App\Services\MotivationScoreService;
use App\Services\ZipTimezoneService;
use Illuminate\Http\Request;

class ListController extends Controller
{
    /**
     * Display all lists with name, type, record count, and import date.
     */
    public function index()
    {
        $lists = LeadList::withCount('leads')->latest('imported_at')->get();

        return view('lists.index', compact('lists'));
    }

    /**
     * Show a single list with its leads.
     */
    public function show(LeadList $leadList)
    {
        $leadList->load('leads.agent');

        return view('lists.show', compact('leadList'));
    }

    /**
     * Show the import form with file upload, list name, type select, and column mapping.
     */
    public function create()
    {
        return view('lists.create');
    }

    /**
     * Handle CSV import - dispatches to queue if available, otherwise processes synchronously.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'column_mapping' => 'nullable|array',
            'dedupe_strategy' => 'nullable|string|in:skip,update,create_new',
        ]);

        $file = $request->file('file');
        $tenantId = auth()->user()->tenant_id;

        // Create the list record
        $leadList = LeadList::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'type' => $request->type,
            'record_count' => 0,
            'imported_at' => now(),
        ]);

        // Create ImportLog entry with 'pending' status
        $importLog = ImportLog::create([
            'tenant_id' => $tenantId,
            'list_id' => $leadList->id,
            'user_id' => auth()->id(),
            'filename' => $file->getClientOriginalName(),
            'status' => 'pending',
            'total_rows' => 0,
            'imported_rows' => 0,
            'skipped_rows' => 0,
            'duplicate_rows' => 0,
            'dedupe_strategy' => $request->input('dedupe_strategy', 'skip'),
        ]);

        // Store file temporarily
        $storedPath = $file->store('imports', 'local');
        $fullPath = storage_path('app/' . $storedPath);

        $columnMapping = $request->column_mapping;

        $dedupeStrategy = $request->input('dedupe_strategy', 'skip');

        // Try to dispatch to queue; fall back to sync if queue driver is 'sync'
        if (config('queue.default') !== 'sync') {
            ProcessCsvImport::dispatch(
                $importLog->id,
                $fullPath,
                $tenantId,
                $leadList->id,
                $columnMapping,
                $dedupeStrategy
            );

            return redirect()->route('lists.index')->with(
                'success',
                "Import queued. You can track progress on this page."
            );
        }

        // Synchronous fallback
        $this->processImportSync($fullPath, $tenantId, $leadList, $importLog, $columnMapping, $dedupeStrategy);

        $importLog->refresh();

        return redirect()->route('lists.index')->with(
            'success',
            "Import complete: {$importLog->imported_rows} imported, {$importLog->skipped_rows} skipped, {$importLog->duplicate_rows} duplicates."
        );
    }

    /**
     * Get import status for AJAX polling.
     */
    public function importStatus(ImportLog $importLog)
    {
        return response()->json([
            'status' => $importLog->status,
            'total_rows' => $importLog->total_rows,
            'imported_rows' => $importLog->imported_rows,
            'skipped_rows' => $importLog->skipped_rows,
            'duplicate_rows' => $importLog->duplicate_rows,
            'error_message' => $importLog->error_message,
        ]);
    }

    /**
     * Delete a list and its pivot entries.
     */
    public function destroy(LeadList $leadList)
    {
        $leadList->leads()->detach();
        $leadList->delete();

        return redirect()->route('lists.index')->with('success', 'List deleted successfully.');
    }

    /**
     * Process import synchronously (fallback when queue driver is sync).
     */
    private function processImportSync(string $filePath, int $tenantId, LeadList $leadList, ImportLog $importLog, ?array $columnMapping, string $dedupeStrategy = 'skip'): void
    {
        $importLog->update(['status' => 'processing']);

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $importLog->update(['status' => 'failed', 'error_message' => 'Unable to read CSV file.']);
            return;
        }

        $header = fgetcsv($handle);
        $mapping = $columnMapping ?? $this->autoMapColumns($header);

        $imported = 0;
        $skipped = 0;
        $duplicates = 0;
        $updatedRows = 0;
        $affectedLeadIds = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) {
                $skipped++;
                continue;
            }

            $rowData = $this->mapRowToFields($row, $mapping, $header);

            $phone = $rowData['phone'] ?? null;
            $address = isset($rowData['address']) ? strtolower(trim($rowData['address'])) : null;

            $existingLead = null;

            if ($phone) {
                $existingLead = Lead::where('tenant_id', $tenantId)
                    ->where('phone', $phone)
                    ->first();
            }

            if (!$existingLead && $address) {
                $existingLead = Lead::where('tenant_id', $tenantId)
                    ->whereHas('property', function ($q) use ($address) {
                        $q->whereRaw('LOWER(TRIM(address)) = ?', [$address]);
                    })
                    ->first();
            }

            $zipCode = $rowData['zip_code'] ?? null;
            $detectedTimezone = ZipTimezoneService::detect($zipCode);

            if ($existingLead) {
                if ($dedupeStrategy === 'update') {
                    $updateData = array_filter($rowData, fn($v) => $v !== null && $v !== '');
                    unset($updateData['tenant_id']);
                    $existingLead->update($updateData);
                    $updatedRows++;
                }

                if (!$existingLead->lists()->where('list_id', $leadList->id)->exists()) {
                    $existingLead->lists()->attach($leadList->id);
                }

                if ($detectedTimezone && !$existingLead->timezone) {
                    $existingLead->update(['timezone' => $detectedTimezone]);
                }

                $duplicates++;
                $affectedLeadIds[] = $existingLead->id;

                if ($dedupeStrategy !== 'create_new') {
                    continue;
                }
            }

            // Create new lead (for non-duplicates, or create_new strategy)
            if (!$existingLead || $dedupeStrategy === 'create_new') {
                $newLead = Lead::create([
                    'tenant_id' => $tenantId,
                    'agent_id' => null,
                    'lead_source' => 'list_import',
                    'first_name' => $rowData['first_name'] ?? '',
                    'last_name' => $rowData['last_name'] ?? '',
                    'phone' => $phone,
                    'email' => $rowData['email'] ?? null,
                    'status' => 'new',
                    'timezone' => $detectedTimezone,
                ]);

                $newLead->lists()->attach($leadList->id);
                $imported++;
                $affectedLeadIds[] = $newLead->id;
            }
        }

        fclose($handle);

        $leadList->update(['record_count' => $imported + $duplicates]);

        $importLog->update([
            'status' => 'completed',
            'total_rows' => $imported + $skipped + $duplicates,
            'imported_rows' => $imported,
            'skipped_rows' => $skipped,
            'duplicate_rows' => $duplicates,
            'updated_rows' => $updatedRows,
        ]);

        // Recalculate motivation scores
        $motivationService = new MotivationScoreService();
        $affectedLeads = Lead::whereIn('id', array_unique($affectedLeadIds))->get();
        foreach ($affectedLeads as $lead) {
            $motivationService->recalculate($lead);
        }

        // Clean up temp file
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    /**
     * Auto-map CSV header columns to lead fields.
     */
    private function autoMapColumns(array $header): array
    {
        $mapping = [];
        $fieldMap = [
            'first_name' => ['first_name', 'first name', 'firstname'],
            'last_name' => ['last_name', 'last name', 'lastname'],
            'phone' => ['phone', 'phone_number', 'phone number', 'tel'],
            'email' => ['email', 'email_address', 'email address'],
            'address' => ['address', 'property_address', 'property address', 'street'],
            'zip_code' => ['zip_code', 'zip', 'zipcode', 'postal_code', 'postal code'],
        ];

        foreach ($header as $index => $col) {
            $normalized = strtolower(trim($col));
            foreach ($fieldMap as $field => $aliases) {
                if (in_array($normalized, $aliases)) {
                    $mapping[$index] = $field;
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Map a CSV row to field names using the column mapping.
     */
    private function mapRowToFields(array $row, array $mapping, array $header): array
    {
        $data = [];
        foreach ($mapping as $index => $field) {
            if (isset($row[$index])) {
                $data[$field] = trim($row[$index]);
            }
        }
        return $data;
    }

    /**
     * Preview CSV headers and first rows for column mapping.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        $headers = fgetcsv($handle);
        $rows = [];
        $count = 0;
        while (($row = fgetcsv($handle)) !== false && $count < 5) {
            $rows[] = $row;
            $count++;
        }
        fclose($handle);

        return response()->json([
            'headers' => $headers,
            'rows' => $rows,
        ]);
    }

    /**
     * Get saved column mappings for the current tenant.
     */
    public function savedMappings()
    {
        $mappings = \App\Models\SavedMapping::orderBy('name')->get();

        return response()->json($mappings);
    }

    /**
     * Save a column mapping for reuse.
     */
    public function saveMappingAction(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'column_mapping' => 'required|array',
            'column_mapping.*' => 'nullable|string|max:255',
        ]);

        $mapping = \App\Models\SavedMapping::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $request->name,
            'column_mapping' => $request->column_mapping,
        ]);

        return response()->json($mapping, 201);
    }

    /**
     * Delete a saved column mapping.
     */
    public function deleteMappingAction(\App\Models\SavedMapping $mapping)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $mapping->delete();
        return response()->json(['success' => true]);
    }
}
