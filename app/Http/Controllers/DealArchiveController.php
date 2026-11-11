<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use Illuminate\Http\Request;

class DealArchiveController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Deal::class);

        $query = Deal::with([
            'lead.property',
            'committedBuyer',
            'signatureRequests' => fn ($q) => $q->where('status', 'completed')->latest('completed_at'),
        ])->whereHas('signatureRequests', fn ($q) => $q->where('status', 'completed'));

        if (auth()->user()->isAgent()) {
            $query->where('agent_id', auth()->id());
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('lead.property', function ($pq) use ($search) {
                        $pq->where('address', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('state', 'like', "%{$search}%")
                            ->orWhere('zip_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('committedBuyer', function ($bq) use ($search) {
                        $bq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('structure')) {
            $query->where('transaction_structure', $request->string('structure')->toString());
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->string('stage')->toString());
        }

        $deals = $query->latest('updated_at')->paginate(20)->withQueryString();

        return view('deals.archive', [
            'deals' => $deals,
            'structures' => [
                'assignment' => 'Assignment',
                'double_close' => 'Double Close',
                'novation' => 'Novation',
            ],
            'stages' => Deal::stageLabels(),
        ]);
    }
}
