<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Buyer;
use App\Services\BuyerScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BuyerVerificationController extends Controller
{
    /**
     * Upload a Proof of Funds document for a buyer.
     */
    public function uploadPof(Request $request, Buyer $buyer)
    {
        $this->authorize('update', $buyer);

        $request->validate([
            'pof_document' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'pof_amount' => 'nullable|numeric|min:0',
        ]);

        $file = $request->file('pof_document');

        // Validate actual file content matches claimed MIME type
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        if (!in_array($ext, $allowedExtensions)) {
            return redirect()->back()->withErrors(['pof_document' => __('Invalid file type.')]);
        }

        // Remove old file if exists
        if ($buyer->pof_document_path) {
            Storage::disk('local')->delete('buyer-pof/' . $buyer->id . '/' . basename($buyer->pof_document_path));
        }

        $filename = 'pof_' . time() . '.' . $ext;
        $file->storeAs("buyer-pof/{$buyer->id}", $filename, 'local');

        $buyer->update([
            'pof_document_path' => "buyer-pof/{$buyer->id}/{$filename}",
            'pof_verified' => true,
            'pof_verified_at' => now(),
            'pof_amount' => $request->pof_amount,
        ]);

        // Recalculate buyer score
        BuyerScoreService::recalculate($buyer);

        AuditLog::log('buyer.pof_uploaded', $buyer);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Proof of Funds uploaded successfully.'),
                'buyer_score' => $buyer->fresh()->buyer_score,
            ]);
        }

        return redirect()->route('buyers.show', $buyer)->with('success', __('Proof of Funds uploaded successfully.'));
    }

    /**
     * Remove Proof of Funds from a buyer.
     */
    public function removePof(Buyer $buyer)
    {
        $this->authorize('update', $buyer);

        // Delete the file
        if ($buyer->pof_document_path) {
            Storage::disk('local')->delete('buyer-pof/' . $buyer->id . '/' . basename($buyer->pof_document_path));
        }

        $buyer->update([
            'pof_document_path' => null,
            'pof_verified' => false,
            'pof_verified_at' => null,
            'pof_amount' => null,
        ]);

        // Recalculate buyer score
        BuyerScoreService::recalculate($buyer);

        AuditLog::log('buyer.pof_removed', $buyer);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Proof of Funds removed.'),
                'buyer_score' => $buyer->fresh()->buyer_score,
            ]);
        }

        return redirect()->route('buyers.show', $buyer)->with('success', __('Proof of Funds removed.'));
    }

    /**
     * Download Proof of Funds document (private storage).
     */
    public function downloadPof(Buyer $buyer)
    {
        $this->authorize('view', $buyer);

        if (!$buyer->pof_document_path || !Storage::disk('local')->exists($buyer->pof_document_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($buyer->pof_document_path);
    }

    /**
     * Recalculate buyer score (AJAX).
     */
    public function recalculateScore(Buyer $buyer)
    {
        $this->authorize('update', $buyer);

        BuyerScoreService::recalculate($buyer);

        return response()->json([
            'success' => true,
            'buyer_score' => $buyer->fresh()->buyer_score,
            'message' => __('Score recalculated successfully.'),
        ]);
    }
}
