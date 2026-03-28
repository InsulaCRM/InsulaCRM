<?php

namespace App\Jobs;

use App\Models\ImportLog;
use App\Models\Lead;
use App\Models\LeadList;
use App\Models\Tenant;
use App\Services\AiService;
use App\Services\MotivationScoreService;
use App\Services\ZipTimezoneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        protected int $importLogId,
        protected string $filePath,
        protected int $tenantId,
        protected int $listId,
        protected ?array $columnMapping = null,
        protected string $dedupeStrategy = 'skip',
    ) {}

    public function handle(): void
    {
        $importLog = ImportLog::withoutGlobalScopes()->find($this->importLogId);
        if (!$importLog) {
            return;
        }

        $importLog->update(['status' => 'processing']);

        try {
            $handle = fopen($this->filePath, 'r');
            if (!$handle) {
                $importLog->update(['status' => 'failed', 'error_message' => 'Unable to read CSV file.']);
                return;
            }

            $header = fgetcsv($handle);
            $mapping = $this->columnMapping ?? $this->autoMapColumns($header);

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
                    $existingLead = Lead::withoutGlobalScopes()
                        ->where('tenant_id', $this->tenantId)
                        ->where('phone', $phone)
                        ->first();
                }

                if (!$existingLead && $address) {
                    $existingLead = Lead::withoutGlobalScopes()
                        ->where('tenant_id', $this->tenantId)
                        ->whereHas('property', function ($q) use ($address) {
                            $q->whereRaw('LOWER(TRIM(address)) = ?', [$address]);
                        })
                        ->first();
                }

                $zipCode = $rowData['zip_code'] ?? null;
                $detectedTimezone = ZipTimezoneService::detect($zipCode);

                if ($existingLead) {
                    if ($this->dedupeStrategy === 'update') {
                        // Update existing lead with new data
                        $updateData = array_filter($rowData, fn($v) => $v !== null && $v !== '');
                        unset($updateData['tenant_id']); // don't update tenant
                        $existingLead->update($updateData);
                        $updatedRows++;
                    }

                    // Always add to list regardless of strategy
                    if (!$existingLead->lists()->where('list_id', $this->listId)->exists()) {
                        $existingLead->lists()->attach($this->listId);
                    }

                    if ($detectedTimezone && !$existingLead->timezone) {
                        $existingLead->update(['timezone' => $detectedTimezone]);
                    }

                    $duplicates++;
                    $affectedLeadIds[] = $existingLead->id;

                    if ($this->dedupeStrategy !== 'create_new') {
                        continue; // skip creating new lead
                    }
                } else {
                    $newLead = Lead::create([
                        'tenant_id' => $this->tenantId,
                        'agent_id' => null,
                        'lead_source' => 'list_import',
                        'first_name' => $rowData['first_name'] ?? '',
                        'last_name' => $rowData['last_name'] ?? '',
                        'phone' => $phone,
                        'email' => $rowData['email'] ?? null,
                        'status' => 'new',
                        'timezone' => $detectedTimezone,
                    ]);

                    $newLead->lists()->attach($this->listId);
                    $imported++;
                    $affectedLeadIds[] = $newLead->id;
                }

                // Update progress every 50 rows
                if (($imported + $skipped + $duplicates) % 50 === 0) {
                    $importLog->update([
                        'imported_rows' => $imported,
                        'skipped_rows' => $skipped,
                        'duplicate_rows' => $duplicates,
                        'total_rows' => $imported + $skipped + $duplicates,
                    ]);
                }
            }

            fclose($handle);

            // Update list record count
            LeadList::withoutGlobalScopes()->where('id', $this->listId)
                ->update(['record_count' => $imported + $duplicates]);

            // Final update
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
            $affectedLeads = Lead::withoutGlobalScopes()->whereIn('id', array_unique($affectedLeadIds))->get();
            foreach ($affectedLeads as $lead) {
                $motivationService->recalculate($lead);
            }

            // AI Lead Qualification (limit to first 50 new leads to control API costs)
            try {
                $tenant = Tenant::find($this->tenantId);
                if ($tenant && $tenant->ai_enabled) {
                    $listType = LeadList::withoutGlobalScopes()->where('id', $this->listId)->value('type');
                    $aiService = new AiService($tenant);
                    $newLeads = $affectedLeads->where('status', 'new')->take(50);
                    foreach ($newLeads as $lead) {
                        try {
                            $qualification = $aiService->qualifyLead($lead, $listType);
                            if (!empty($qualification['temperature'])) {
                                $lead->update(['temperature' => $qualification['temperature']]);
                            }
                        } catch (\Exception $e) {
                            break; // Stop on first failure (likely rate limit or key issue)
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('AI qualification during CSV import failed: ' . $e->getMessage());
            }

            // Clean up temp file
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }

        } catch (\Exception $e) {
            $importLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('CSV Import failed: ' . $e->getMessage(), ['import_log_id' => $this->importLogId]);
        }
    }

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
}
