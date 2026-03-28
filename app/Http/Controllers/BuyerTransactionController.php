<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Buyer;
use App\Models\BuyerTransaction;
use App\Services\BuyerScoreService;
use Illuminate\Http\Request;

class BuyerTransactionController extends Controller
{
    /**
     * Store a new transaction for a buyer.
     */
    public function store(Request $request, Buyer $buyer)
    {
        $this->authorize('update', $buyer);

        $data = $request->validate([
            'property_address' => 'required|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'close_date' => 'required|date',
            'days_to_close' => 'nullable|integer|min:0',
            'deal_id' => 'nullable|integer|exists:deals,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['buyer_id'] = $buyer->id;

        $transaction = BuyerTransaction::create($data);

        // Recalculate buyer score and stats
        BuyerScoreService::recalculate($buyer);

        AuditLog::log('buyer_transaction.created', $transaction);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Transaction added successfully.'),
                'transaction' => $transaction,
                'buyer_score' => $buyer->fresh()->buyer_score,
            ]);
        }

        return redirect()->route('buyers.show', $buyer)->with('success', __('Transaction added successfully.'));
    }

    /**
     * Delete a buyer transaction.
     */
    public function destroy(BuyerTransaction $transaction)
    {
        $buyer = $transaction->buyer;
        $this->authorize('update', $buyer);

        AuditLog::log('buyer_transaction.deleted', $transaction);

        $transaction->delete();

        // Recalculate buyer score and stats
        BuyerScoreService::recalculate($buyer);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Transaction removed.'),
                'buyer_score' => $buyer->fresh()->buyer_score,
            ]);
        }

        return redirect()->route('buyers.show', $buyer)->with('success', __('Transaction removed.'));
    }
}
