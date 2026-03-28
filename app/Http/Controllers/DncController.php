<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DoNotContact;
use Illuminate\Http\Request;

class DncController extends Controller
{
    /**
     * Display all DNC entries paginated with search.
     */
    public function index(Request $request)
    {
        $query = DoNotContact::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $entries = $query->latest()->paginate(25);

        return view('dnc.index', compact('entries'));
    }

    /**
     * Add a single DNC entry.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'phone' => 'nullable|string|max:255|required_without:email',
            'email' => 'nullable|email|max:255|required_without:phone',
            'reason' => 'nullable|string|max:255',
        ]);

        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['added_by'] = auth()->id();
        $data['added_at'] = now();

        $entry = DoNotContact::create($data);

        AuditLog::log('dnc.added', $entry);

        return redirect()->route('dnc.index')->with('success', 'DNC entry added successfully.');
    }

    /**
     * Bulk CSV import of DNC entries.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return redirect()->back()->with('error', 'Unable to read the CSV file.');
        }

        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();

        // Skip header row
        $header = fgetcsv($handle);

        // Determine phone and email column indices
        $phoneIndex = null;
        $emailIndex = null;
        foreach ($header as $i => $col) {
            $normalized = strtolower(trim($col));
            if (in_array($normalized, ['phone', 'phone_number', 'phone number', 'tel'])) {
                $phoneIndex = $i;
            }
            if (in_array($normalized, ['email', 'email_address', 'email address'])) {
                $emailIndex = $i;
            }
        }

        $imported = 0;
        $batch = [];

        while (($row = fgetcsv($handle)) !== false) {
            $phone = ($phoneIndex !== null && isset($row[$phoneIndex])) ? trim($row[$phoneIndex]) : null;
            $email = ($emailIndex !== null && isset($row[$emailIndex])) ? trim($row[$emailIndex]) : null;

            if (! $phone && ! $email) {
                continue;
            }

            $batch[] = [
                'tenant_id' => $tenantId,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
                'reason' => 'Bulk CSV import',
                'added_by' => $userId,
                'added_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $imported++;

            if (count($batch) >= 500) {
                DoNotContact::insert($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            DoNotContact::insert($batch);
        }

        fclose($handle);

        AuditLog::log('dnc.imported');

        return redirect()->route('dnc.index')->with('success', "{$imported} DNC entries imported successfully.");
    }

    /**
     * Remove a single DNC entry.
     */
    public function destroy(DoNotContact $doNotContact)
    {
        abort_unless($doNotContact->tenant_id === auth()->user()->tenant_id, 403);

        AuditLog::log('dnc.removed', $doNotContact);

        $doNotContact->delete();

        return redirect()->route('dnc.index')->with('success', 'DNC entry removed successfully.');
    }
}
