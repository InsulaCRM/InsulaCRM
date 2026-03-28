<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuyerRequest;
use App\Models\AuditLog;
use App\Models\Buyer;
use App\Models\User;
use Illuminate\Http\Request;

class BuyerController extends Controller
{
    /**
     * Display a paginated list of buyers/clients with search and filters.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Buyer::class);

        $query = Buyer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('preferred_property_types')) {
            $types = $request->preferred_property_types;
            $query->where(function ($q) use ($types) {
                foreach ((array) $types as $type) {
                    $q->orWhereJsonContains('preferred_property_types', $type);
                }
            });
        }

        $buyers = $query->latest()->paginate(25);

        return view('buyers.index', compact('buyers'));
    }

    /**
     * Bulk action on selected buyers.
     */
    public function bulkAction(Request $request)
    {
        $this->authorize('bulkDelete', Buyer::class);

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'action' => 'required|in:delete',
        ]);

        $buyers = Buyer::whereIn('id', $request->ids)->get();
        $count = $buyers->count();

        switch ($request->action) {
            case 'delete':
                foreach ($buyers as $buyer) {
                    AuditLog::log('buyer.deleted', $buyer);
                    $buyer->delete();
                }
                $entityLabel = \App\Services\BusinessModeService::isRealEstate() ? 'client(s)' : 'buyer(s)';
                $message = "{$count} {$entityLabel} deleted.";
                break;
        }

        return redirect()->route('buyers.index')->with('success', $message);
    }

    /**
     * Show the form for creating a new buyer.
     */
    public function create()
    {
        $this->authorize('create', Buyer::class);

        $agents = User::where('tenant_id', auth()->user()->tenant_id)->get();

        return view('buyers.create', compact('agents'));
    }

    /**
     * Store a newly created buyer.
     */
    public function store(BuyerRequest $request)
    {
        $this->authorize('create', Buyer::class);

        $data = $request->validated();

        $data['tenant_id'] = auth()->user()->tenant_id;

        $buyer = Buyer::create($data);

        AuditLog::log('buyer.created', $buyer);

        $label = \App\Services\BusinessModeService::isRealEstate() ? __('Client created successfully.') : __('Buyer created successfully.');

        return redirect()->route('buyers.show', $buyer)->with('success', $label);
    }

    /**
     * Display the specified buyer with deal matches.
     */
    public function show(Buyer $buyer)
    {
        $this->authorize('view', $buyer);

        $buyer->load(['dealMatches.deal.lead', 'transactions']);

        return view('buyers.show', compact('buyer'));
    }

    /**
     * Show the form for editing the specified buyer.
     */
    public function edit(Buyer $buyer)
    {
        $this->authorize('update', $buyer);

        return view('buyers.edit', compact('buyer'));
    }

    /**
     * Update the specified buyer.
     */
    public function update(BuyerRequest $request, Buyer $buyer)
    {
        $this->authorize('update', $buyer);

        $data = $request->validated();

        $buyer->update($data);

        AuditLog::log('buyer.updated', $buyer);

        $label = \App\Services\BusinessModeService::isRealEstate() ? __('Client updated successfully.') : __('Buyer updated successfully.');

        return redirect()->route('buyers.show', $buyer)->with('success', $label);
    }

    /**
     * Remove the specified buyer.
     */
    public function destroy(Buyer $buyer)
    {
        $this->authorize('delete', $buyer);

        AuditLog::log('buyer.deleted', $buyer);

        $buyer->delete();

        $label = \App\Services\BusinessModeService::isRealEstate() ? __('Client deleted successfully.') : __('Buyer deleted successfully.');

        return redirect()->route('buyers.index')->with('success', $label);
    }

    /**
     * Import buyers from a CSV file.
     */
    public function import(Request $request)
    {
        $this->authorize('import', Buyer::class);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return redirect()->route('buyers.index')->with('error', 'Unable to read the CSV file.');
        }

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return redirect()->route('buyers.index')->with('error', 'The CSV file is empty or invalid.');
        }

        // Normalize headers (lowercase, trim)
        $header = array_map(function ($col) {
            return strtolower(trim($col));
        }, $header);

        // Map expected columns
        $columnMap = [
            'first_name'         => null,
            'last_name'          => null,
            'company'            => null,
            'phone'              => null,
            'email'              => null,
            'max_purchase_price' => null,
        ];

        foreach ($header as $index => $col) {
            if (array_key_exists($col, $columnMap)) {
                $columnMap[$col] = $index;
            }
        }

        // first_name and last_name are required columns
        if ($columnMap['first_name'] === null || $columnMap['last_name'] === null) {
            fclose($handle);
            return redirect()->route('buyers.index')->with('error', 'CSV must contain at least first_name and last_name columns.');
        }

        $tenantId = auth()->user()->tenant_id;
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (count($row) === 0 || (count($row) === 1 && trim($row[0]) === '')) {
                continue;
            }

            $firstName = trim($row[$columnMap['first_name']] ?? '');
            $lastName  = trim($row[$columnMap['last_name']] ?? '');

            // Skip rows without required name fields
            if ($firstName === '' || $lastName === '') {
                continue;
            }

            Buyer::create([
                'tenant_id'          => $tenantId,
                'first_name'         => $firstName,
                'last_name'          => $lastName,
                'company'            => $columnMap['company'] !== null ? trim($row[$columnMap['company']] ?? '') : null,
                'phone'              => $columnMap['phone'] !== null ? trim($row[$columnMap['phone']] ?? '') : null,
                'email'              => $columnMap['email'] !== null ? trim($row[$columnMap['email']] ?? '') : null,
                'max_purchase_price' => $columnMap['max_purchase_price'] !== null && isset($row[$columnMap['max_purchase_price']]) && is_numeric(trim($row[$columnMap['max_purchase_price']])) ? (float) trim($row[$columnMap['max_purchase_price']]) : null,
            ]);

            $imported++;
        }

        fclose($handle);

        $term = \App\Services\BusinessModeService::isRealEstate() ? 'clients' : 'buyers';
        return redirect()->route('buyers.index')->with('success', "Successfully imported {$imported} {$term}.");
    }

    /**
     * Export buyers as CSV.
     */
    public function export(Request $request)
    {
        $this->authorize('export', Buyer::class);

        $query = Buyer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('preferred_property_types')) {
            $types = $request->preferred_property_types;
            $query->where(function ($q) use ($types) {
                foreach ((array) $types as $type) {
                    $q->orWhereJsonContains('preferred_property_types', $type);
                }
            });
        }

        $buyers = $query->latest()->get();

        return response()->streamDownload(function () use ($buyers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                __('First Name'), __('Last Name'), __('Company'), __('Phone'), __('Email'),
                __('Max Purchase Price'), __('Preferred Property Types'), __('Preferred States'), __('Preferred Zip Codes'),
            ]);
            foreach ($buyers as $buyer) {
                fputcsv($handle, [
                    $buyer->first_name,
                    $buyer->last_name,
                    $buyer->company,
                    $buyer->phone,
                    $buyer->email,
                    $buyer->max_purchase_price,
                    is_array($buyer->preferred_property_types) ? implode(', ', $buyer->preferred_property_types) : $buyer->preferred_property_types,
                    is_array($buyer->preferred_states) ? implode(', ', $buyer->preferred_states) : $buyer->preferred_states,
                    is_array($buyer->preferred_zip_codes) ? implode(', ', $buyer->preferred_zip_codes) : $buyer->preferred_zip_codes,
                ]);
            }
            fclose($handle);
        }, (\App\Services\BusinessModeService::isRealEstate() ? 'clients' : 'buyers') . '-export-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

}

